<?php

/**
 * Tek seferlik flash mesaj yardımcısı.
 *
 *   Flash::set('saved');                 // controller'da
 *   $msg = Flash::pull();                // view'da (okuyup siler)
 *
 * URL kirliliği yapan ?status=success gibi parametreler yerine session
 * içinde taşınan, ilk okumada otomatik silinen kısa ömürlü mesajlardır.
 */
class Flash
{
    private const KEY = '_flash';

    public static function set(string $message): void
    {
        $_SESSION[self::KEY] = $message;
    }

    /** Mesajı döndür ve session'dan kaldır. Yoksa null. */
    public static function pull(): ?string
    {
        if (!isset($_SESSION[self::KEY])) return null;
        $msg = $_SESSION[self::KEY];
        unset($_SESSION[self::KEY]);
        return is_string($msg) ? $msg : null;
    }

    /** Sadece kontrol — değeri silmez. */
    public static function peek(): ?string
    {
        $m = $_SESSION[self::KEY] ?? null;
        return is_string($m) ? $m : null;
    }
}
