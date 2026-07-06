<?php
/**
 * CSRF Token yönetimi.
 * - token(): Session'da token yoksa üretir, hex stringi döndürür.
 * - field(): Formlara basılacak hidden input HTML'i.
 * - verify(): POST isteklerinde token doğrular, başarısızsa 403 + exit.
 */
class Csrf
{
    private const MAX_AGE_SECONDS = 14400;

    public static function token(): string
    {
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_created_at'])) {
            self::rotate();
        }
        return $_SESSION['csrf_token'];
    }

    public static function rotate(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created_at'] = time();
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }

    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        $createdAt = (int)($_SESSION['csrf_token_created_at'] ?? 0);
        $expired = $createdAt <= 0 || (time() - $createdAt) > self::MAX_AGE_SECONDS;
        if (!is_string($sent) || $stored === '' || $expired || !hash_equals($stored, $sent)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "403: Geçersiz veya eksik CSRF tokeni.";
            exit;
        }
    }
}
