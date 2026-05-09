<?php
if (!function_exists('env_value')) {
    function env_value(string $key, string $default = ''): string
    {
        $envPath = defined('ROOT') ? ROOT . '/.env' : dirname(__DIR__, 2) . '/.env';
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            if (is_file($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '' || $trimmed[0] === '#' || strpos($trimmed, '=') === false) {
                        continue;
                    }
                    [$name, $raw] = explode('=', $trimmed, 2);
                    $name = trim($name);
                    if ($name === '') {
                        continue;
                    }
                    $envCache[$name] = trim(trim($raw), "\"'");
                }
            }
        }

        if (array_key_exists($key, $envCache) && $envCache[$key] !== '') {
            return (string)$envCache[$key];
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return (string)$value;
        }

        return $envCache[$key] ?? $default;
    }
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$detectedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detectedSiteUrl = ($isHttps ? 'https://' : 'http://') . $detectedHost;

define('SITE_URL', env_value('SITE_URL', $detectedSiteUrl));
define('NOTES_SITE_URL', env_value('NOTES_SITE_URL', 'https://notlar.fezadan.org'));
define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_NAME', env_value('DB_NAME', ''));
define('DB_USER', env_value('DB_USER', ''));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));
define('APP_SALT', env_value('APP_SECURITY_SALT', 'change-me'));
define('CDN_URL', env_value('CDN_URL', env_value('R2_PUBLIC_URL', SITE_URL)));
