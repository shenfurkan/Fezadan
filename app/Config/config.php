<?php
require_once dirname(__DIR__) . '/Core/HttpRequest.php';
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

$isHttps = HttpRequest::isHttps();
$detectedHost = $_SERVER['FEZADAN_ORIGINAL_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
if ($detectedHost === '') {
    $detectedHost = (PHP_SAPI === 'cli') ? 'fezadan.org' : 'localhost';
}
$mainHost = preg_replace('/^(?:www|notlar)\./i', '', $detectedHost);
// Without an explicit SITE_URL, public Host headers must not poison canonical/sitemap caches.
$detectedSiteUrl = HttpRequest::isLocalHost()
    ? ($isHttps ? 'https://' : 'http://') . $mainHost
    : 'https://fezadan.org';

define('SITE_URL', env_value('SITE_URL', $detectedSiteUrl));
define('NOTES_SITE_URL', env_value('NOTES_SITE_URL', 'https://notlar.fezadan.org'));
define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_NAME', env_value('DB_NAME', ''));
define('DB_USER', env_value('DB_USER', ''));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));
$appSalt = env_value('APP_SECURITY_SALT', '');
if ($appSalt === '' || $appSalt === 'change-me') {
    $saltFile = (defined('ROOT') ? ROOT : dirname(__DIR__, 2)) . '/app/Config/.security_salt';
    if (is_file($saltFile) && is_readable($saltFile)) {
        $appSalt = trim((string)file_get_contents($saltFile));
    }
    if ($appSalt === '' || $appSalt === 'change-me') {
        $appSalt = bin2hex(random_bytes(32));
        @file_put_contents($saltFile, $appSalt, LOCK_EX);
        @chmod($saltFile, 0600);
    }
}
define('APP_SALT', $appSalt);
define('APP_DEBUG', filter_var(env_value('APP_DEBUG', '0'), FILTER_VALIDATE_BOOLEAN));
define('CDN_URL', env_value('CDN_URL', env_value('R2_PUBLIC_URL', SITE_URL)));
define('INDEXNOW_KEY', 'a7b3f8d1e9c2465db4f0a6c2e8d7b1f9');
define('LITECAPTCHA_ENABLED', filter_var(env_value('LITECAPTCHA_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN));
define('MONERO_ADDRESS', env_value('MONERO_ADDRESS', ''));

/**
 * Generates a language-prefixed URL.
 */
function langUrl(string $path = '', ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    $path = ltrim($path, '/');
    return rtrim(SITE_URL, '/') . '/' . $lang . ($path !== '' ? '/' . $path : '');
}

// The public tool has its own origin and is maintained as a separate module repository.
define('ANONYMITY_SITE_URL', 'https://anonymitycheck.fezadan.org');
function anonymityUrl(): string {
    return ANONYMITY_SITE_URL . '/';
}

function pageUrl(string $page, ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    $key = strtolower(trim($page, '/'));
    $paths = [
        'about' => ['tr' => 'hakkinda', 'en' => 'about'],
        'hakkinda' => ['tr' => 'hakkinda', 'en' => 'about'],
        'manifesto' => ['tr' => 'manifesto', 'en' => 'manifesto'],
        'privacy' => ['tr' => 'gizlilik-politikasi', 'en' => 'privacy'],
        'donate' => ['tr' => 'bagis', 'en' => 'donate'],
        'bagis' => ['tr' => 'bagis', 'en' => 'donate'],
        'verification' => ['tr' => 'teyit', 'en' => 'verification'],
        'teyit' => ['tr' => 'teyit', 'en' => 'verification'],
    ];

    $localizedPath = $paths[$key][$lang] ?? $key;
    return langUrl($localizedPath, $lang);
}

function authorUrl(string $author_slug, ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    $prefix = ($lang === 'en') ? 'author' : 'yazar';
    return langUrl($prefix . '/' . rawurlencode($author_slug), $lang);
}

/**
 * Generates the canonical language-prefixed URL for an article.
 */
function articleUrl(string $article_slug, ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    // The old makale/article paths issued permanent redirects to author URLs.
    return langUrl(($lang === 'en' ? 'post/' : 'yazi/') . rawurlencode($article_slug), $lang);
}
/**
 * Translation helper. Uses App::getLang() to load the correct file.
 * Falls back to Turkish if key not found.
 *
 * Usage: <?= __('nav.home') ?>
 */
if (!function_exists('__')) {
    function __(string $key, string $default = ''): string
    {
        static $translations = null;
        static $lang = null;

        $currentLang = class_exists('App') ? strtolower(App::getLang()) : 'tr';
        if ($translations === null || $lang !== $currentLang) {
            $lang = $currentLang;
            $file = ROOT . '/app/Translations/' . $lang . '.php';
            if (is_file($file)) {
                $loaded = require $file;
                $translations = is_array($loaded) ? $loaded : [];
            } else {
                $translations = [];
            }
        }

        $value = $translations[$key] ?? $default;
        if ($value === '' && $lang !== 'tr') {
            // Fallback to Turkish
            $trFile = ROOT . '/app/Translations/tr.php';
            if (is_file($trFile)) {
                $tr = require $trFile;
                $value = is_array($tr) ? ($tr[$key] ?? $default) : $default;
            }
        }

        return $value !== '' ? $value : $key;
    }
}

/**
 * Verifies a LiteCaptcha HMAC token from GET or POST parameters.
 * Prevents token replay via file-based deduplication until the signed expiry.
 *
 * @param string|null $error Populated with failure reason on return false.
 * @param string|null $path  Override the path used for HMAC verification.
 *                           Defaults to current request path.
 * @return bool True if the token is valid and unused.
 */
if (!function_exists('litecaptcha_verify')) {
    function litecaptcha_secret(): string
    {
        $secret = env_value('LITECAPTCHA_SECRET', '');
        if ($secret === '' || $secret === 'change-me') {
            $secret = env_value('APP_SECURITY_SALT', '');
        }

        return ($secret !== '' && $secret !== 'change-me') ? $secret : APP_SALT;
    }

    function litecaptcha_is_local_request(): bool
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $hostIsLocal = in_array($host, ['localhost', 'localhost:8080', '127.0.0.1', '127.0.0.1:8080', '[::1]', '[::1]:8080'], true)
            || preg_match('/(^|\.)localhost(:\d+)?$/', $host) === 1;
        return $hostIsLocal && in_array($remote, ['127.0.0.1', '::1'], true);
    }

    function litecaptcha_verify(?string &$error = null, ?string $path = null, bool $allowLocalBypass = true): bool
    {
        if ($allowLocalBypass && litecaptcha_is_local_request()) {
            return true; // Local ortamda token doğrulamayı atla
        }

        $rt       = $_GET['rt']  ?? $_GET['lc_rt']  ?? $_POST['lc_rt'] ?? '';
        $sigInput = $_GET['sig'] ?? $_GET['lc_sig'] ?? $_POST['lc_sig'] ?? '';
        $expInput = $_GET['exp'] ?? $_GET['lc_exp'] ?? $_POST['lc_exp'] ?? '';
        $rt = is_string($rt) ? $rt : '';
        $sigInput = is_string($sigInput) ? $sigInput : '';
        $exp = is_scalar($expInput) ? (int)$expInput : 0;

        $logDir  = defined('ROOT') ? ROOT . '/logs' : dirname(__DIR__, 2) . '/logs';
        $logFile = $logDir . '/captcha_debug.log';

        $logEntry = function (string $reason, array $ctx = []) use ($logFile, $rt, $exp): void {
            $line = '[' . date('Y-m-d H:i:s') . '] LITECAPTCHA_VERIFY_FAIL reason=' . $reason;
            $ctx['rt_hash']   = $rt !== '' ? substr(hash('sha256', $rt), 0, 12) : 'empty';
            $ctx['exp']       = $exp;
            $ctx['now']       = time();
            $ctx['time_diff'] = $exp > 0 ? (time() - $exp) : 'n/a';
            $ctx['uri']       = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            foreach ($ctx as $k => $v) {
                $line .= ' ' . $k . '=' . $v;
            }
            @is_dir(dirname($logFile)) || @mkdir(dirname($logFile), 0755, true);
            @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        };

        if ($rt === '' || $sigInput === '' || $exp <= 0) {
            $error = 'missing_params';
            $logEntry($error, ['rt_empty' => (int)($rt === ''), 'sig_empty' => (int)($sigInput === ''), 'exp' => $exp]);
            return false;
        }

        if (time() > $exp) {
            $error = 'token_expired';
            $logEntry($error);
            return false;
        }

        $currentPath = $path ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $secret      = litecaptcha_secret();
        $payload     = $rt . '|' . $exp . '|' . $currentPath;
        $expectedSig = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSig, $sigInput)) {
            $error = 'bad_signature';
            $logEntry($error, [
                'path'         => $currentPath,
                'sig_input'    => substr($sigInput, 0, 12),
            ]);
            return false;
        }

        // Keep the replay marker for the entire signed lifetime, not just the first 60 seconds.
        $tokenDir  = sys_get_temp_dir() . '/litecaptcha_tokens';
        if (!is_dir($tokenDir)) {
            @mkdir($tokenDir, 0700, true);
        }
        $tokenFile = $tokenDir . '/' . hash('sha256', $rt . '_' . $secret);
        $tokenHandle = @fopen($tokenFile, 'x');
        if ($tokenHandle === false) {
            $error = 'token_replay';
            $logEntry($error, ['file_age_s' => is_file($tokenFile) ? (time() - (int)@filemtime($tokenFile)) : 'n/a']);
            return false;
        }

        if (fwrite($tokenHandle, (string)$exp) !== strlen((string)$exp)) {
            fclose($tokenHandle);
            @unlink($tokenFile);
            $error = 'token_storage_failed';
            $logEntry($error);
            return false;
        }
        fclose($tokenHandle);
        @chmod($tokenFile, 0600);

        // Occasional cleanup bounds disk use without scanning the directory on every download.
        if (random_int(1, 100) === 1) {
            foreach (glob($tokenDir . '/*') ?: [] as $oldToken) {
                $expiry = (int)@file_get_contents($oldToken);
                if (($expiry > 0 && $expiry < time())
                    || ($expiry === 0 && (int)@filemtime($oldToken) < time() - 86400)) {
                    @unlink($oldToken);
                }
            }
        }

        $error = null;
        return true;
    }
}
