<?php

trait AdminPasskeys
{
    private function expectedWebAuthnOrigin(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'fezadan.org';
        $scheme = 'http';
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $cfVisitor = $_SERVER['HTTP_CF_VISITOR'] ?? '';
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || $forwardedProto === 'https'
            || strpos($cfVisitor, '"https"') !== false) {
            $scheme = 'https';
        }

        return $scheme . '://' . $host;
    }

    public function registerPasskeyChallenge()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $userId = $_SESSION['admin_id'] ?? null;
        $username = $_SESSION['admin_user'] ?? '';
        $displayName = $_SESSION['admin_name'] ?? '';

        require_once ROOT . '/app/Core/WebAuthn.php';
        try {
            $challenge = \App\Core\WebAuthn::base64url_encode(random_bytes(32));
            $_SESSION['webauthn_challenge'] = $challenge;

            $host = $_SERVER['HTTP_HOST'] ?? 'fezadan.org';
            $host = explode(':', $host)[0];

            echo json_encode([
                'success' => true,
                'challenge' => $challenge,
                'rp' => [
                    'name' => 'Fezadan',
                    'id' => $host
                ],
                'user' => [
                    'id' => \App\Core\WebAuthn::base64url_encode((string)$userId),
                    'name' => $username,
                    'displayName' => $displayName
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7] // ES256
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function registerPasskeyVerify()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $clientDataJSON = $_POST['clientDataJSON'] ?? '';
        $attestationObject = $_POST['attestationObject'] ?? '';
        $challenge = $_SESSION['webauthn_challenge'] ?? '';
        
        if (!$clientDataJSON || !$attestationObject || !$challenge) {
            echo json_encode(['success' => false, 'error' => 'Geçersiz parametreler.']);
            exit;
        }

        $expectedOrigin = $this->expectedWebAuthnOrigin();

        require_once ROOT . '/app/Core/WebAuthn.php';
        try {
            $regData = \App\Core\WebAuthn::verifyRegistration(
                $clientDataJSON,
                $attestationObject,
                $challenge,
                $expectedOrigin
            );

            $pdo = Db::pdo();
            // Kimlik bilgisini veritabanına kaydet
            $stmt = $pdo->prepare("INSERT INTO admin_passkeys (admin_id, credential_id, public_key) VALUES (?, ?, ?)");
            $stmt->execute([
                $_SESSION['admin_id'],
                $regData['credentialId'],
                $regData['publicKey']
            ]);

            // Challenge'ı oturumdan temizle
            unset($_SESSION['webauthn_challenge']);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function loginPasskeyChallenge()
    {
        header('Content-Type: application/json; charset=utf-8');

        require_once ROOT . '/app/Core/WebAuthn.php';
        try {
            $challenge = \App\Core\WebAuthn::base64url_encode(random_bytes(32));
            $_SESSION['webauthn_challenge'] = $challenge;

            $host = $_SERVER['HTTP_HOST'] ?? 'fezadan.org';
            $host = explode(':', $host)[0];

            echo json_encode([
                'success' => true,
                'challenge' => $challenge,
                'rpId' => $host
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function loginPasskeyVerify()
    {
        header('Content-Type: application/json; charset=utf-8');

        $clientDataJSON = $_POST['clientDataJSON'] ?? '';
        $authenticatorData = $_POST['authenticatorData'] ?? '';
        $signature = $_POST['signature'] ?? '';
        $credentialId = $_POST['credentialId'] ?? '';
        $challenge = $_SESSION['webauthn_challenge'] ?? '';

        if (!$clientDataJSON || !$authenticatorData || !$signature || !$credentialId || !$challenge) {
            echo json_encode(['success' => false, 'error' => 'Geçersiz parametreler.']);
            exit;
        }

        $expectedOrigin = $this->expectedWebAuthnOrigin();

        try {
            $pdo = Db::pdo();
            // Veritabanında passkey bul
            $stmt = $pdo->prepare("SELECT * FROM admin_passkeys WHERE credential_id = ?");
            $stmt->execute([$credentialId]);
            $passkey = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$passkey) {
                echo json_encode(['success' => false, 'error' => 'Kayıtlı giriş anahtarı bulunamadı.']);
                exit;
            }

            require_once ROOT . '/app/Core/WebAuthn.php';
            // Veritabanındaki açık anahtarla imzayı doğrula
            \App\Core\WebAuthn::verifyAssertion(
                $clientDataJSON,
                $authenticatorData,
                $signature,
                $challenge,
                $expectedOrigin,
                $passkey['public_key']
            );
            $signCount = \App\Core\WebAuthn::assertionSignCount($authenticatorData);
            $storedSignCount = (int)($passkey['sign_count'] ?? 0);
            if ($signCount > 0 && $storedSignCount > 0 && $signCount <= $storedSignCount) {
                AdminLog::write('warning', 'Passkey sign count doğrulaması başarısız.', [
                    'endpoint' => 'loginPasskeyVerify',
                    'passkey_id' => (int)$passkey['id'],
                    'admin_id' => (int)$passkey['admin_id'],
                    'stored_sign_count' => $storedSignCount,
                    'received_sign_count' => $signCount,
                ]);
                echo json_encode(['success' => false, 'error' => 'Giriş anahtarı doğrulanamadı.']);
                exit;
            }

            // Admin'i getir
            $stmtAdmin = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
            $stmtAdmin->execute([$passkey['admin_id']]);
            $user = $stmtAdmin->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'Kullanıcı bulunamadı.']);
                exit;
            }

            // Başarılı giriş:
            // Bu IP için brute-force denemelerini temizle
            $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ipHash = $this->adminRateLimitHash($userIp, 'admin_login_ip');
            $usernameHash = $this->adminRateLimitHash($user['username'], 'admin_login_username');
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_hash = ? OR (username_hash IS NOT NULL AND username_hash = ?)")->execute([$ipHash, $usernameHash]);

            // Session fixation koruması: oturum kimliğini yenile
            session_regenerate_id(true);
            $_SESSION = [];
            Csrf::rotate();

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int)$user['id'];
            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_role'] = $user['role'] ?? 'viewer';
            $_SESSION['admin_login_at'] = time();
            $_SESSION['admin_last_seen_at'] = time();

            $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            if ($signCount > $storedSignCount) {
                $pdo->prepare("UPDATE admin_passkeys SET sign_count = ? WHERE id = ?")
                    ->execute([$signCount, (int)$passkey['id']]);
            }

            // Challenge'ı oturumdan temizle
            unset($_SESSION['webauthn_challenge']);

            echo json_encode(['success' => true, 'role' => $user['role'] ?? 'superadmin']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function deletePasskey()
    {
        $this->requirePost();

        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        $passkeyId = (int)($_POST['id'] ?? 0);
        if ($passkeyId > 0) {
            try {
                $pdo = Db::pdo();
                // Passkey'in giriş yapmış admin'e ait olduğundan emin ol
                $stmt = $pdo->prepare("DELETE FROM admin_passkeys WHERE id = ? AND admin_id = ?");
                $stmt->execute([$passkeyId, $_SESSION['admin_id']]);
                Flash::set('Giriş anahtarı başarıyla silindi.');
            } catch (\Exception $e) {
                Flash::set('Giriş anahtarı silinirken hata oluştu.');
            }
        }
        header('Location: /yonetim/profile');
        exit;
    }
}
