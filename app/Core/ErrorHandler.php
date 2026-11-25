<?php
/**
 * Merkezi hata/istisna yönetimi.
 * - Detayları PHP error_log'a ve superadmin'in gördüğü logs/admin.log'a yazar.
 * - Kullanıcıya hata kodlu jenerik 500 sayfası sunar.
 */
class ErrorHandler
{
    private const REPEAT_WINDOW_SECONDS = 300;

    /** One id per request: shown on the 500 page and stored with the log entry, so a report can be matched. */
    public static function requestId(): string
    {
        static $id = null;
        return $id ??= bin2hex(random_bytes(6));
    }

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
        self::record($e);
        self::renderFatal((defined('APP_DEBUG') && APP_DEBUG) ? $e : null);
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
            $fatalE = new \ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
            self::record($fatalE);
            self::renderFatal((defined('APP_DEBUG') && APP_DEBUG) ? $fatalE : null);
        }
    }

    /**
     * Crashes reach the admin log panel. Each crash site (deepest cause) is written at most once
     * per window, with the number of repeats skipped, so a crawler on a broken page can't fill the disk.
     */
    private static function record(\Throwable $e): void
    {
        try {
            if (!defined('ROOT') || !class_exists('AdminLog', false)) {
                return;
            }
            $root = $e;
            $causes = [];
            while (($previous = $root->getPrevious()) !== null && count($causes) < 5) {
                $root = $previous;
                $causes[] = get_class($root) . ': ' . $root->getMessage() . ' @ ' . self::relativePath($root->getFile()) . ':' . $root->getLine();
            }

            $marker = ROOT . '/logs/crash-' . substr(sha1(get_class($root) . '|' . $root->getFile() . '|' . $root->getLine()), 0, 16);
            $state = explode(':', (string)@file_get_contents($marker)) + [0, 0];
            $windowStart = (int)$state[0];
            $skipped = (int)$state[1];
            if (time() - $windowStart < self::REPEAT_WINDOW_SECONDS) {
                @file_put_contents($marker, $windowStart . ':' . ($skipped + 1), LOCK_EX);
                return;
            }
            @file_put_contents($marker, time() . ':0', LOCK_EX);

            AdminLog::write('error', 'Sistem hatası: ' . mb_substr($e->getMessage(), 0, 300), [
                'endpoint' => 'crash',
                'request_id' => self::requestId(),
                'exception' => get_class($e),
                'location' => self::relativePath($root->getFile()) . ':' . $root->getLine(),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'caused_by' => $causes,
                'repeats_skipped' => $skipped,
                'trace' => mb_substr($root->getTraceAsString(), 0, 3000),
            ]);
        } catch (\Throwable $ignored) {
            // Logging must never turn one failure into two.
        }
    }

    private static function relativePath(string $file): string
    {
        return defined('ROOT') && strpos($file, ROOT . '/') === 0 ? substr($file, strlen(ROOT) + 1) : $file;
    }

    private static function renderFatal(\Throwable $e = null): void
    {
        if (headers_sent()) return;
        if (ob_get_level()) {
            @ob_end_clean();
        }
        http_response_code(500);
        $errorCode = self::requestId();
        $view = ROOT . '/app/Views/errors/500.php';
        if (is_file($view)) {
            require $view;
        } else {
            echo "<h1>500</h1><p>Sistem hatası.</p>";
            if ($e) {
                echo "<p style='font-family:monospace;'>" . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        exit;
    }
}
