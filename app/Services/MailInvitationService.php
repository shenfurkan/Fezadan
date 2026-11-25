<?php
require_once __DIR__ . '/CpanelMailService.php';

class MailInvitationService
{
    private PDO $pdo;
    private CpanelMailService $mail;

    public function __construct(PDO $pdo, ?CpanelMailService $mail = null)
    {
        $this->pdo = $pdo;
        $this->mail = $mail ?? new CpanelMailService();
    }

    public function configured(): bool
    {
        return $this->mail->configured();
    }

    public static function validateLocalPart(string $localPart): void
    {
        if (preg_match('/\A[a-z0-9](?:[a-z0-9._-]{0,62}[a-z0-9])?\z/D', $localPart) !== 1
            || str_contains($localPart, '..')
            || in_array($localPart, ['postmaster', 'abuse', 'root', 'mailer-daemon', 'cpanel'], true)) {
            throw new InvalidArgumentException('invalid_address');
        }
    }

    public static function validatePassword(string $password): void
    {
        if (preg_match('/\A.{12,}\z/usD', $password) !== 1 || strlen($password) > 128
            || preg_match('/[\x00-\x1f\x7f]/', $password) === 1) {
            throw new InvalidArgumentException('invalid_password');
        }
    }

    private function hash(string $value, string $purpose): string
    {
        return hash_hmac('sha256', $purpose . '|' . $value, APP_SALT);
    }

    public function create(string $localPart, int $quota, int $hours, int $adminId): array
    {
        self::validateLocalPart($localPart);
        if ($quota < 25 || $quota > 1024 || !in_array($hours, [1, 24, 48, 72, 168], true)) {
            throw new InvalidArgumentException('invalid_limits');
        }
        // Check existing mailboxes when connected, but never modify them.
        if ($this->configured() && $this->mail->accountExists($localPart)) {
            throw new InvalidArgumentException('address_exists');
        }
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, 31)];
        }
        $expires = gmdate('Y-m-d H:i:s', time() + $hours * 3600);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE mail_invitations SET status = 'expired', active_address = NULL
                WHERE status = 'pending' AND expires_at <= UTC_TIMESTAMP() AND local_part = ?")->execute([$localPart]);
            $this->pdo->prepare('INSERT INTO mail_invitations
                (code_hash, local_part, active_address, quota_mb, expires_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?)')->execute([$this->hash($code, 'mail_invite'), $localPart, $localPart, $quota, $expires, $adminId]);
            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof PDOException && $e->getCode() === '23000') {
                throw new InvalidArgumentException('invitation_exists');
            }
            throw $e;
        }
        return ['id' => $id, 'code' => $code, 'address' => $localPart . '@fezadan.org', 'expires_at' => $expires];
    }

    public function listing(): array
    {
        // Code hashes, lease identifiers and passwords are never sent to views.
        return $this->pdo->query("SELECT id, local_part, quota_mb, created_at, expires_at, used_at,
            CASE WHEN status = 'pending' AND expires_at <= UTC_TIMESTAMP() THEN 'expired' ELSE status END AS status
            FROM mail_invitations ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revoke(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE mail_invitations SET status = 'revoked', active_address = NULL
            WHERE id = ? AND status IN ('pending', 'expired')");
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }

    /** Atomic, shared rate limit; no raw IPs or submitted invite codes stored. */
    public function allowAttempt(string $ip): bool
    {
        $window = intdiv(time(), 900) * 900;
        $this->pdo->beginTransaction();
        try {
            $allowed = true;
            foreach (['global' => 200, 'ip:' . $ip => 8] as $scope => $limit) {
                $key = $this->hash($scope, 'mail_invite_rate');
                $this->pdo->prepare('INSERT INTO mail_invite_rate_limits (scope_hash, window_start, attempts)
                    VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE attempts = LEAST(attempts + 1, 10000)')->execute([$key, $window]);
                $stmt = $this->pdo->prepare('SELECT attempts FROM mail_invite_rate_limits
                    WHERE scope_hash = ? AND window_start = ? FOR UPDATE');
                $stmt->execute([$key, $window]);
                $allowed = (int)$stmt->fetchColumn() <= $limit && $allowed;
            }
            $this->pdo->prepare('DELETE FROM mail_invite_rate_limits WHERE window_start < ?')->execute([$window - 86400]);
            $this->pdo->commit();
            return $allowed;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function redeem(string $code, string $address, string $password): string
    {
        if (!$this->configured()) {
            return 'disabled';
        }
        $code = strtoupper(trim($code));
        if (preg_match('/\A[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{8}\z/D', $code) !== 1) {
            return 'invalid';
        }
        self::validatePassword($password);
        $attempt = bin2hex(random_bytes(16));
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, local_part, quota_mb, status, expires_at
                FROM mail_invitations WHERE code_hash = ? FOR UPDATE');
            $stmt->execute([$this->hash($code, 'mail_invite')]);
            $invite = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$invite || $invite['status'] !== 'pending'
                || $invite['expires_at'] <= gmdate('Y-m-d H:i:s')
                || strtolower(trim($address)) !== $invite['local_part'] . '@fezadan.org') {
                $this->pdo->commit();
                return 'invalid';
            }
            $this->pdo->prepare("UPDATE mail_invitations SET status = 'processing', attempt_id = ?,
                attempt_started_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$attempt, $invite['id']]);
            $this->pdo->commit(); // Never hold a database transaction across a network request.
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        try {
            $exists = $this->mail->accountExists($invite['local_part']);
        } catch (Throwable $e) {
            // No mutation was attempted. This code may safely be tried again.
            return $this->finish((int)$invite['id'], $attempt, 'pending') ? 'unavailable' : 'review';
        }
        if ($exists) {
            $this->finish((int)$invite['id'], $attempt, 'closed');
            return 'review'; // Never reset an existing account to the submitted password.
        }
        try {
            $this->mail->createAccount($invite['local_part'], $password, (int)$invite['quota_mb']);
        } catch (Throwable $e) {
            // Even an explicit API error might follow a partial write. No blind retry.
            $this->finish((int)$invite['id'], $attempt, 'review');
            return 'review';
        }
        return $this->finish((int)$invite['id'], $attempt, 'used') ? 'created' : 'review';
    }

    private function finish(int $id, string $attempt, string $status): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE mail_invitations SET status = ?,
                used_at = CASE WHEN ? = 'used' THEN UTC_TIMESTAMP() ELSE used_at END
                WHERE id = ? AND status = 'processing' AND attempt_id = ?");
            $stmt->execute([$status, $status, $id, $attempt]);
            return $stmt->rowCount() === 1;
        } catch (PDOException $e) {
            // A remote write may already have succeeded. Keep the lease blocked
            // and do not log traces containing the password passed to redeem().
            error_log('Mail invitation state update failed; administrator check required. id=' . $id);
            return false;
        }
    }

    /** Read-only cPanel reconciliation; never creates, resets or deletes a mailbox. */
    public function reconcile(int $id): string
    {
        if (!$this->configured()) {
            return 'disabled';
        }
        $stmt = $this->pdo->prepare("SELECT local_part, attempt_id, expires_at FROM mail_invitations
            WHERE id = ? AND status IN ('processing', 'review')
            AND attempt_started_at < UTC_TIMESTAMP() - INTERVAL 2 MINUTE");
        $stmt->execute([$id]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invite) {
            return 'wait';
        }
        $exists = $this->mail->accountExists($invite['local_part']);
        $status = $exists ? 'closed' : ($invite['expires_at'] <= gmdate('Y-m-d H:i:s') ? 'expired' : 'pending');
        $stmt = $this->pdo->prepare("UPDATE mail_invitations SET status = ?,
            active_address = CASE WHEN ? = 'expired' THEN NULL ELSE active_address END, attempt_id = NULL,
            attempt_started_at = NULL WHERE id = ? AND status IN ('processing', 'review') AND attempt_id = ?");
        $stmt->execute([$status, $status, $id, $invite['attempt_id']]);
        return $stmt->rowCount() === 1 ? ($exists ? 'exists' : ($status === 'expired' ? 'expired' : 'retry')) : 'wait';
    }
}
