<?php
/**
 * Merkezi hata/istisna yönetimi.
 * - Detayları PHP error_log'a yazar.
 * - Kullanıcıya jenerik 500 sayfası sunar.
 */
class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(\Throwable $e): void
    {
        error_log(sprintf(
            '[Fezadan][Exception] %s: %s in %s:%d%s%s',
            get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(),
            PHP_EOL, $e->getTraceAsString()
        ));
        self::renderFatal();
    }

    public static function handleError($severity, $message, $file, $line): bool
    {
        // E_NOTICE/E_DEPRECATED gibi durumlarda istisnaya çevirme; sadece logla.
        if (!(error_reporting() & $severity)) return false;
        error_log(sprintf('[Fezadan][Error %d] %s in %s:%d', $severity, $message, $file, $line));
        // Fatal düzeyse exception'a çevir
        if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }
        return true; // PHP'nin default handler'ını bastır (notice/warning)
    }

    public static function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log(sprintf('[Fezadan][Shutdown] %s in %s:%d', $err['message'], $err['file'], $err['line']));
            self::renderFatal();
        }
    }

    private static function renderFatal(): void
    {
        if (headers_sent()) return;
        if (ob_get_level()) {
            @ob_end_clean();
        }
        http_response_code(500);
        $view = ROOT . '/app/Views/errors/500.php';
        if (is_file($view)) {
            require $view;
        } else {
            echo "<h1>500</h1><p>Sistem hatası.</p>";
        }
        exit;
    }
}
