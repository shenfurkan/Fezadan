<?php

class AdminLog
{
    private const MAX_BYTES = 1048576;
    private const MAX_AGE_ROTATED = 2592000;

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
            self::cleanupRotated();
        }

        $entry = [
            'time' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'user' => $_SESSION['admin_user'] ?? null,
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? hash('sha256', $_SERVER['REMOTE_ADDR'] . date('Y-m-d') . (defined('APP_SALT') ? APP_SALT : '')) : null,
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

        $handle = @fopen($file, 'r');
        if (!$handle) {
            return [];
        }

        $rows = [];
        $max = max(1, $limit);
        $buffer = '';
        $pos = filesize($file);
        $chunkSize = 8192;

        while ($pos > 0 && count($rows) < $max) {
            $readLen = min($chunkSize, $pos);
            $pos -= $readLen;
            fseek($handle, $pos);
            $chunk = fread($handle, $readLen);
            if ($chunk === false) {
                break;
            }
            $buffer = $chunk . $buffer;

            $lines = explode("\n", $buffer);
            $buffer = array_shift($lines);

            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = trim($lines[$i]);
                if ($line === '') {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (is_array($decoded) && self::matchesFilters($decoded, $filters)) {
                    $rows[] = $decoded;
                    if (count($rows) >= $max) {
                        break 2;
                    }
                }
            }
        }

        fclose($handle);
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

    public static function countByLevel(string $level, int $secondsBack = 86400): int
    {
        $file = self::file();
        if (!is_file($file) || !is_readable($file)) {
            return 0;
        }

        $handle = @fopen($file, 'r');
        if (!$handle) {
            return 0;
        }

        $cutoff = time() - $secondsBack;
        $level = strtoupper($level);
        $count = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }
            if (strtoupper((string)($decoded['level'] ?? '')) !== $level) {
                continue;
            }
            $entryTime = isset($decoded['time']) ? strtotime($decoded['time']) : 0;
            if ($entryTime >= $cutoff) {
                $count++;
            }
        }

        fclose($handle);
        return $count;
    }

    private static function cleanupRotated(): void
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return;
        }

        $cutoff = time() - self::MAX_AGE_ROTATED;
        $pattern = $dir . '/admin-*.log';

        foreach (glob($pattern) as $rotatedFile) {
            if (is_file($rotatedFile) && filemtime($rotatedFile) < $cutoff) {
                @unlink($rotatedFile);
            }
        }
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
