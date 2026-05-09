<?php

class AdminLog
{
    private const MAX_BYTES = 1048576;

    public static function write(string $level, string $message, array $context = []): void
    {
        $dir = self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('[Fezadan][AdminLog] ' . $level . ' ' . $message);
            return;
        }

        $file = self::file();
        if (is_file($file) && filesize($file) > self::MAX_BYTES) {
            @rename($file, $dir . '/admin-' . date('Ymd-His') . '.log');
        }

        $entry = [
            'time' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'user' => $_SESSION['admin_user'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'context' => self::safeContext($context),
        ];

        @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function recent(int $limit = 100, array $filters = []): array
    {
        $file = self::file();
        if (!is_file($file) || !is_readable($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $lines = array_reverse($lines);
        $rows = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded) && self::matchesFilters($decoded, $filters)) {
                $rows[] = $decoded;
                if (count($rows) >= max(1, $limit)) {
                    break;
                }
            }
        }
        return $rows;
    }

    private static function matchesFilters(array $row, array $filters): bool
    {
        $level = strtoupper(trim((string)($filters['level'] ?? '')));
        if ($level !== '' && strtoupper((string)($row['level'] ?? '')) !== $level) {
            return false;
        }

        $context = is_array($row['context'] ?? null) ? $row['context'] : [];
        $endpoint = trim((string)($filters['endpoint'] ?? ''));
        if ($endpoint !== '' && stripos((string)($context['endpoint'] ?? ''), $endpoint) === false) {
            return false;
        }

        $query = trim((string)($filters['q'] ?? ''));
        if ($query !== '') {
            $haystack = implode(' ', [
                $row['message'] ?? '',
                $row['uri'] ?? '',
                $context['request_id'] ?? '',
                $context['import_id'] ?? '',
                $context['name'] ?? '',
                $context['client_name'] ?? '',
                $context['path'] ?? '',
                $context['visibility_warning'] ?? '',
            ]);
            if (stripos($haystack, $query) === false) {
                return false;
            }
        }

        return true;
    }

    private static function safeContext(array $context): array
    {
        $blocked = ['password', 'token', 'secret', 'key', '_csrf'];
        $clean = [];
        foreach ($context as $key => $value) {
            $keyText = strtolower((string)$key);
            foreach ($blocked as $needle) {
                if (strpos($keyText, $needle) !== false) {
                    $clean[$key] = '[redacted]';
                    continue 2;
                }
            }
            if (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            } else {
                $clean[$key] = json_decode(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
            }
        }
        return $clean;
    }

    private static function dir(): string
    {
        return ROOT . '/logs';
    }

    private static function file(): string
    {
        return self::dir() . '/admin.log';
    }
}
