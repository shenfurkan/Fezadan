<?php

trait AdminAuthAndLogs
{
    public function forgotPassword()
    {
        $this->view('admin/forgot-password');
    }

    public function resetMailPreview()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        $mail = $this->buildAdminPasswordResetMail(str_repeat('a', 64));
        header('Content-Type: text/html; charset=utf-8');
        echo $mail['body'];
        exit;
    }

    public function sendResetLink()
    {
        $this->requirePost();

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /yonetim/forgot-password?status=invalid');
            exit;
        }
        $email = strtolower($email);
        if (!$this->adminRecoveryEmailIsAllowed($email)) {
            header('Location: /yonetim/forgot-password?status=domain');
            exit;
        }

        try {
            $requestId = $this->requestId();
            $pdo = $this->getPDO();
            $pdo->exec("DELETE FROM admin_password_resets WHERE created_at < NOW() - INTERVAL 1 DAY");
            $this->ensureAdminPasswordResetRateLimitTable($pdo);
            $resetIpHash = $this->adminRateLimitHash($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'admin_password_reset_ip');
            $pdo->prepare("DELETE FROM admin_password_reset_rate_limits WHERE attempt_time < NOW() - INTERVAL 1 DAY")->execute();
            $rateStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM admin_password_reset_rate_limits
                 WHERE ip_hash = ? AND attempt_time > NOW() - INTERVAL 15 MINUTE"
            );
            $rateStmt->execute([$resetIpHash]);
            if ((int)$rateStmt->fetchColumn() >= 5) {
                AdminLog::write('warning', 'Şifre sıfırlama IP rate limit tetiklendi.', [
                    'endpoint' => 'sendResetLink',
                    'request_id' => $requestId,
                    'ip_hash' => $resetIpHash,
                ]);
                header('Location: /yonetim/forgot-password?status=rate_limited');
                exit;
            }
            $pdo->prepare("INSERT INTO admin_password_reset_rate_limits (ip_hash, attempt_time) VALUES (?, NOW())")
                ->execute([$resetIpHash]);

            $stmt = $pdo->prepare("SELECT id, email FROM admins WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($admin) {
                $recentStmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM admin_password_resets
                     WHERE admin_id = ? AND created_at >= NOW() - INTERVAL 2 MINUTE"
                );
                $recentStmt->execute([(int)$admin['id']]);
                $recentCount = (int)$recentStmt->fetchColumn();

                if ($recentCount === 0) {
                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);
                    $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes validity

                    $pdo->prepare("DELETE FROM admin_password_resets WHERE admin_id = ?")
                        ->execute([(int)$admin['id']]);
                    $insert = $pdo->prepare(
                        "INSERT INTO admin_password_resets (admin_id, token_hash, expires_at)
                         VALUES (?, ?, ?)"
                    );
                    $insert->execute([(int)$admin['id'], $tokenHash, $expiresAt]);

                    if (!$this->sendAdminPasswordResetMail((string)$admin['email'], $rawToken)) {
                        AdminLog::write('error', 'Şifre sıfırlama e-postası gönderilemedi.', [
                            'endpoint' => 'sendResetLink',
                            'request_id' => $requestId,
                            'admin_id' => (int)$admin['id'],
                        ]);
                        error_log('Admin password reset mail could not be sent admin_id=' . (int)$admin['id'] . ' request_id=' . $requestId);
                        header('Location: /yonetim/forgot-password?status=mail_error');
                        exit;
                    }
                }
            }

            header('Location: /yonetim/forgot-password?status=sent');
            exit;
        } catch (\PDOException $e) {
            throw new \Exception("Şifre Sıfırlama Hatası: " . $e->getMessage());
        }
    }

    private function ensureAdminPasswordResetRateLimitTable(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_password_reset_rate_limits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_hash` varchar(64) NOT NULL,
            `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_admin_password_reset_rate_ip` (`ip_hash`),
            KEY `idx_admin_password_reset_rate_time` (`attempt_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function resetPassword()
    {
        $token = (string)($_GET['token'] ?? '');
        $valid = false;

        if (preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            try {
                $pdo = $this->getPDO();
                $pdo->exec("DELETE FROM admin_password_resets WHERE created_at < NOW() - INTERVAL 1 DAY");

                $stmt = $pdo->prepare(
                    "SELECT id FROM admin_password_resets
                     WHERE token_hash = ? AND expires_at > ?
                     LIMIT 1"
                );
                $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
                $valid = (bool)$stmt->fetchColumn();
            } catch (\PDOException $e) {
                throw new \Exception("Şifre Sıfırlama Hatası: " . $e->getMessage());
            }
        }

        $this->view('admin/reset-password', [
            'token' => $token,
            'valid' => $valid,
        ]);
    }

    public function updateResetPassword()
    {
        $this->requirePost();

        $token = (string)($_POST['token'] ?? '');
        $newPass = (string)($_POST['new_password'] ?? '');
        $confirmPass = (string)($_POST['confirm_password'] ?? '');

        if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            header('Location: /yonetim/reset-password?status=invalid');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $pdo->exec("DELETE FROM admin_password_resets WHERE created_at < NOW() - INTERVAL 1 DAY");

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "SELECT id, admin_id FROM admin_password_resets
                 WHERE token_hash = ? AND expires_at > ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
            $reset = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$reset) {
                $pdo->rollBack();
                header('Location: /yonetim/reset-password?status=invalid');
                exit;
            }

            if ($newPass !== $confirmPass) {
                $pdo->rollBack();
                header('Location: /yonetim/reset-password?token=' . rawurlencode($token) . '&status=mismatch');
                exit;
            }

            if (!$this->adminPasswordIsStrong($newPass)) {
                $pdo->rollBack();
                header('Location: /yonetim/reset-password?token=' . rawurlencode($token) . '&status=weak');
                exit;
            }

            $adminId = (int)$reset['admin_id'];
            $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);

            $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")
                ->execute([$hashedPass, $adminId]);
            $pdo->prepare("DELETE FROM admin_password_resets WHERE admin_id = ?")
                ->execute([$adminId]);
            $pdo->commit();

            header('Location: /yonetim?reset=success');
            exit;
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \Exception("Şifre Sıfırlama Hatası: " . $e->getMessage());
        }
    }

    public function admins()
    {
        $this->requireRole(['superadmin']);

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->query(
                "SELECT a.id, a.username, a.name, a.email, a.role, a.last_login,
                        COUNT(pk.id) AS passkey_count
                 FROM admins a
                 LEFT JOIN admin_passkeys pk ON pk.admin_id = a.id
                 GROUP BY a.id, a.username, a.name, a.email, a.role, a.last_login
                 ORDER BY
                    CASE a.role
                        WHEN 'superadmin' THEN 1
                        WHEN 'editor' THEN 2
                        WHEN 'viewer' THEN 3
                        ELSE 4
                    END,
                    a.username ASC"
            );

            $this->view('admin/admins', [
                'admins' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Kullanıcı Yönetimi Hatası: " . $e->getMessage());
        }
    }

    public function storeAdmin()
    {
        $this->requirePost();
        $this->requireRole(['superadmin']);

        $username = trim((string)($_POST['username'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $role = (string)($_POST['role'] ?? 'editor');

        $redirect = '/yonetim/admins';

        if (!preg_match('/\A[a-zA-Z0-9._-]{3,64}\z/', $username)) {
            Flash::set('Kullanıcı adı 3-64 karakter olmalı; yalnız harf, rakam, nokta, tire ve alt çizgi kullanılabilir.');
            header('Location: ' . $redirect . '?status=invalid');
            exit;
        }

        if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
            Flash::set('Ad alanı zorunludur ve 150 karakteri geçemez.');
            header('Location: ' . $redirect . '?status=invalid');
            exit;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('Geçerli bir e-posta adresi girin.');
            header('Location: ' . $redirect . '?status=invalid_email');
            exit;
        }

        if (!$this->adminRecoveryEmailIsAllowed($email)) {
            Flash::set('Yalnızca @fezadan.org e-posta adresine izin verilir.');
            header('Location: ' . $redirect . '?status=email_domain');
            exit;
        }

        if (!in_array($role, ['editor', 'viewer'], true)) {
            Flash::set('Bu ekrandan yalnızca editor veya viewer rolü oluşturulabilir.');
            header('Location: ' . $redirect . '?status=invalid_role');
            exit;
        }

        if ($password !== $confirmPassword) {
            Flash::set('Şifreler eşleşmiyor.');
            header('Location: ' . $redirect . '?status=mismatch');
            exit;
        }

        if (!$this->adminPasswordIsStrong($password)) {
            Flash::set('Şifre zayıf. En az 12 karakter olmalı, harf ve rakam içermeli.');
            header('Location: ' . $redirect . '?status=weak');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $usernameCheck = $pdo->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
            $usernameCheck->execute([$username]);
            if ($usernameCheck->fetchColumn()) {
                Flash::set('Bu kullanıcı adı zaten kullanılıyor.');
                header('Location: ' . $redirect . '?status=duplicate_username');
                exit;
            }

            $emailCheck = $pdo->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
            $emailCheck->execute([$email]);
            if ($emailCheck->fetchColumn()) {
                Flash::set('Bu e-posta adresi zaten kullanılıyor.');
                header('Location: ' . $redirect . '?status=duplicate_email');
                exit;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO admins (username, password, name, email, role)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $name,
                $email,
                $role,
            ]);

            Flash::set('Kullanıcı başarıyla oluşturuldu.');
            header('Location: ' . $redirect . '?status=success');
            exit;
        } catch (\PDOException $e) {
            throw new \Exception("Kullanıcı Oluşturma Hatası: " . $e->getMessage());
        }
    }

    public function profile()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT id, username, name, email, author_id FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([(int)($_SESSION['admin_id'] ?? 0)]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$admin) {
                header('Location: /yonetim');
                exit;
            }

            $authors = $pdo->query("SELECT id, name, slug FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $linkedAuthor = null;
            if ($admin['author_id']) {
                $aStmt = $pdo->prepare("SELECT id, name, bio, image_url FROM authors WHERE id = ? LIMIT 1");
                $aStmt->execute([(int)$admin['author_id']]);
                $linkedAuthor = $aStmt->fetch(\PDO::FETCH_ASSOC);
            }

            $this->view('admin/profile', [
                'admin' => $admin,
                'authors' => $authors,
                'linkedAuthor' => $linkedAuthor,
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Profil Hatası: " . $e->getMessage());
        }
    }

    public function updateProfile()
    {
        $this->requirePost();

        $username   = trim((string)($_POST['username'] ?? ''));
        $name       = trim((string)($_POST['name'] ?? ''));
        $emailInput = trim((string)($_POST['email'] ?? ''));
        $bio        = trim((string)($_POST['bio'] ?? ''));
        $imageUrl   = trim((string)($_POST['image_url'] ?? ''));
        $authorId   = isset($_POST['author_id']) ? (int)$_POST['author_id'] : null;
        $adminId    = (int)($_SESSION['admin_id'] ?? 0);

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                header('Location: /yonetim');
                exit;
            }

            // Kullanıcı adı doğrulaması
            if (!preg_match('/\A[a-zA-Z0-9._-]{3,64}\z/', $username)) {
                header('Location: /yonetim/profile?status=invalid_username');
                exit;
            }

            $usernameCheck = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1");
            $usernameCheck->execute([$username, $user['id']]);
            if ($usernameCheck->fetchColumn()) {
                header('Location: /yonetim/profile?status=duplicate_username');
                exit;
            }

            // İsim doğrulaması
            if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
                header('Location: /yonetim/profile?status=invalid_name');
                exit;
            }

            // E-posta doğrulaması
            $email = $emailInput !== '' ? strtolower($emailInput) : null;
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header('Location: /yonetim/profile?status=invalid_email');
                exit;
            }
            if ($email !== null && !$this->adminRecoveryEmailIsAllowed($email)) {
                header('Location: /yonetim/profile?status=email_domain');
                exit;
            }
            if ($email !== null) {
                $emailCheck = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1");
                $emailCheck->execute([$email, $user['id']]);
                if ($emailCheck->fetchColumn()) {
                    header('Location: /yonetim/profile?status=duplicate_email');
                    exit;
                }
            }

            // Yazar bağlantı doğrulaması
            if ($authorId && $authorId > 0) {
                $authorCheck = $pdo->prepare("SELECT id FROM authors WHERE id = ? LIMIT 1");
                $authorCheck->execute([$authorId]);
                if (!$authorCheck->fetchColumn()) {
                    $authorId = $user['author_id'] ?? null;
                }
            }

            // Biyografi doğrulaması
            if ($bio !== '' && mb_strlen($bio, 'UTF-8') > 2000) {
                header('Location: /yonetim/profile?status=invalid_bio');
                exit;
            }

            // Image URL validation
            if ($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                header('Location: /yonetim/profile?status=invalid_image');
                exit;
            }

            // admins tablosunu güncelle
            $pdo->prepare("UPDATE admins SET username = ?, name = ?, email = ?, author_id = ? WHERE id = ?")
                ->execute([$username, $name, $email, $authorId, $user['id']]);

            // Yazar bağlantılıysa bağlı yazar biyografi/görselini güncelle
            if ($authorId && $authorId > 0) {
                $fields = [];
                $params = [];
                if ($bio !== '') {
                    $fields[] = 'bio = ?';
                    $params[] = $bio;
                }
                if ($imageUrl !== '') {
                    $fields[] = 'image_url = ?';
                    $params[] = $imageUrl;
                }
                if (!empty($fields)) {
                    $params[] = $authorId;
                    $pdo->prepare("UPDATE authors SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
                }
            }

            // Oturumu yenile
            $_SESSION['admin_user'] = $username;
            $_SESSION['admin_name'] = $name;

            if ($email !== ($user['email'] ?? '')) {
                $pdo->prepare("DELETE FROM admin_password_resets WHERE admin_id = ?")
                    ->execute([$user['id']]);
            }

            header('Location: /yonetim/profile?status=profile_success');
            exit;
        } catch (\PDOException $e) {
            throw new \Exception("Profil Güncelleme Hatası: " . $e->getMessage());
        }
    }

    public function logs()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }
        $filters = [
            'level' => $_GET['level'] ?? '',
            'endpoint' => $_GET['endpoint'] ?? '',
            'q' => $_GET['q'] ?? '',
        ];
        $this->view('admin/logs', [
            'logs' => AdminLog::recent(150, $filters),
            'filters' => $filters,
        ]);
    }

    public function updatePassword()
    {
        $this->requirePost();

        $old_pass     = (string)($_POST['old_password'] ?? '');
        $new_pass     = (string)($_POST['new_password'] ?? '');
        $confirm_pass = (string)($_POST['confirm_password'] ?? '');
        $emailInput   = trim((string)($_POST['email'] ?? ''));
        $adminId      = (int)($_SESSION['admin_id'] ?? 0);

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user || !password_verify($old_pass, $user['password'])) {
                header('Location: /yonetim/profile?status=wrong_pass');
                exit;
            }

            $email = $emailInput !== '' ? strtolower($emailInput) : null;
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header('Location: /yonetim/profile?status=invalid_email');
                exit;
            }
            if ($email !== null && !$this->adminRecoveryEmailIsAllowed($email)) {
                header('Location: /yonetim/profile?status=email_domain');
                exit;
            }

            if ($email !== null) {
                $emailCheck = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1");
                $emailCheck->execute([$email, $user['id']]);
                if ($emailCheck->fetchColumn()) {
                    header('Location: /yonetim/profile?status=duplicate_email');
                    exit;
                }
            }

            $wantsPasswordChange = ($new_pass !== '' || $confirm_pass !== '');
            if ($wantsPasswordChange) {
                if ($new_pass !== $confirm_pass) {
                    header('Location: /yonetim/profile?status=mismatch');
                    exit;
                }

                if (!$this->adminPasswordIsStrong((string)$new_pass)) {
                    header('Location: /yonetim/profile?status=weak');
                    exit;
                }
            }

            $fields = [];
            $params = [];
            if ($email !== null) {
                $fields[] = 'email = ?';
                $params[] = $email;
            }

            if ($wantsPasswordChange) {
                $fields[] = 'password = ?';
                $params[] = password_hash((string)$new_pass, PASSWORD_DEFAULT);
            }

            if (empty($fields)) {
                header('Location: /yonetim/profile?status=success');
                exit;
            }

            $params[] = $user['id'];
            $sql = 'UPDATE admins SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $pdo->prepare($sql)->execute($params);

            if ($wantsPasswordChange || ($email ?? '') !== ($user['email'] ?? '')) {
                $pdo->prepare("DELETE FROM admin_password_resets WHERE admin_id = ?")
                    ->execute([$user['id']]);
            }

            header('Location: /yonetim/profile?status=success');
            exit;
        }
        catch (\PDOException $e) {
            throw new \Exception("Güncelleme Hatası: " . $e->getMessage());
        }
    }

    private function adminPasswordIsStrong(string $password): bool
    {
        return mb_strlen($password, 'UTF-8') >= 12
            && preg_match('/[A-Za-z]/', $password)
            && preg_match('/\d/', $password);
    }

    private function adminRecoveryEmailIsAllowed(string $email): bool
    {
        $domain = strtolower((string)substr(strrchr($email, '@') ?: '', 1));
        return $domain === 'fezadan.org';
    }

    private function buildAdminPasswordResetMail(string $token): array
    {
        $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
        $link = $base . '/yonetim/reset-password?token=' . rawurlencode($token);
        $subjectText = 'FEZADAN Yönetim Şifre Sıfırlama';
        $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN Şifre Sıfırlama</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1d1d1f;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f5f7; padding: 40px 16px;">
        <tr>
            <td align="center">
                <!-- Center Card -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e5ea; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden;">
                    <!-- Top Accent Line -->
                    <tr>
                        <td height="4" style="background-color: #A31D1D; line-height: 4px; font-size: 4px;">&nbsp;</td>
                    </tr>

                    <!-- Header / Logo -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 20px 40px;">
                            <img src="https://fezadan.org/cdn/logo-light.png" alt="FEZADAN" width="140" style="display: block; border: 0; outline: none; height: auto;" />
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px 40px 40px 40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            <h2 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 600; letter-spacing: -0.01em; color: #1d1d1f; text-align: center;">
                                Şifre Sıfırlama Talebi
                            </h2>
                            <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; color: #515154; text-align: center;">
                                FEZADAN yönetim paneli hesabınız için şifre sıfırlama talebinde bulundunuz. Yeni şifrenizi güvenle belirlemek için aşağıdaki butona tıklayın:
                            </p>

                            <!-- Centered Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$link}" target="_blank" style="display: inline-block; background-color: #A31D1D; color: #ffffff; text-decoration: none; padding: 14px 32px; font-size: 14px; font-weight: 500; border-radius: 8px; box-shadow: 0 2px 4px rgba(163, 29, 29, 0.2); transition: background-color 0.2s;">
                                            Şifremi Sıfırla
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fbfbfd; border: 1px solid #e5e5ea; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px; font-size: 13px; line-height: 1.5; color: #86868b; text-align: center;">
                                        Güvenliğiniz için bu bağlantı <strong>15 dakika</strong> boyunca geçerlidir. Bu sürenin ardından bağlantı kendiliğinden geçersiz olacaktır.
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px 0; font-size: 12px; line-height: 1.4; color: #86868b; text-align: center;">
                                Butona tıklayamıyorsanız aşağıdaki bağlantıyı tarayıcınızın adres çubuğuna yapıştırın:
                            </p>
                            <p style="margin: 0; font-size: 11px; line-height: 1.5; word-break: break-all; font-family: monospace; background-color: #f5f5f7; padding: 12px; border-radius: 6px; border: 1px solid #e5e5ea; color: #515154; text-align: center;">
                                <a href="{$link}" target="_blank" style="color: #A31D1D; text-decoration: none;">{$link}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Information -->
                    <tr>
                        <td align="center" style="background-color: #f5f5f7; border-top: 1px solid #e5e5ea; padding: 24px 40px; color: #86868b; font-size: 12px; line-height: 1.5;">
                            Bu e-posta şifre sıfırlama işlemi başlatıldığı için gönderilmiştir. Talebi siz oluşturmadıysanız, bu e-postayı güvenle yok sayabilirsiniz. Hesabınız güvendedir.
                        </td>
                    </tr>
                </table>

                <!-- Extra Footer info -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; margin-top: 24px;">
                    <tr>
                        <td align="center" style="font-size: 11px; color: #86868b; line-height: 1.5;">
                            FEZADAN © 2026 • Bilim, Estetik ve Bağımsız Düşünce
                            <br>
                            <a href="{$base}" style="color: #86868b; text-decoration: underline;">fezadan.org</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: FEZADAN <info@fezadan.org>',
            'Reply-To: info@fezadan.org',
            'X-Mailer: PHP/' . phpversion(),
        ];

        return [
            'subject_text' => $subjectText,
            'subject' => $subject,
            'body' => $body,
            'headers' => $headers,
            'link' => $link,
        ];
    }

    private function sendAdminPasswordResetMail(string $email, string $token): bool
    {
        $mail = $this->buildAdminPasswordResetMail($token);
        $sent = @mail($email, $mail['subject'], $mail['body'], implode("\r\n", $mail['headers']));
        if (!$sent) {
            error_log('Admin password reset mail() returned false for domain=' . (string)substr(strrchr($email, '@') ?: '', 1));
        }
        return $sent;
    }

}
