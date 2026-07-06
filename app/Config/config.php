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
$detectedHost = $_SERVER['HTTP_HOST'] ?? '';
if ($detectedHost === '') {
    $detectedHost = (PHP_SAPI === 'cli') ? 'fezadan.org' : 'localhost';
}
$detectedSiteUrl = ((PHP_SAPI === 'cli' && $detectedHost === 'fezadan.org') || $isHttps ? 'https://' : 'http://') . $detectedHost;

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
define('LITECAPTCHA_ENABLED', filter_var(env_value('LITECAPTCHA_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN));

/**
 * Generates a language-prefixed URL.
 */
function langUrl(string $path = '', ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    $path = ltrim($path, '/');
    return rtrim(SITE_URL, '/') . '/' . $lang . ($path !== '' ? '/' . $path : '');
}

function pageUrl(string $page, ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    $key = strtolower(trim($page, '/'));
    $paths = [
        'about' => ['tr' => 'hakkinda', 'en' => 'about'],
        'hakkinda' => ['tr' => 'hakkinda', 'en' => 'about'],
        'manifesto' => ['tr' => 'manifesto', 'en' => 'manifesto'],
        'privacy' => ['tr' => 'gizlilik-politikasi', 'en' => 'privacy'],
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
 * Bir makale için dil ve yazar önekli URL üretir.
 */
function articleUrl(string $author_slug, string $article_slug, ?string $lang = null): string {
    $lang = strtolower($lang ?? App::getLang());
    return rtrim(SITE_URL, '/') . '/' . $lang . '/' . rawurlencode($author_slug) . '/' . rawurlencode($article_slug);
}
/**
 * Çeviri yardımcısı. Doğru dosyayı yüklemek için App::getLang() kullanır.
 * Anahtar bulunamazsa Türkçe'ye düşer.
 *
 * Kullanım: <?= __('nav.home') ?>
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
            // Türkçe'ye düş
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
 * GET veya POST parametrelerinden bir LiteCaptcha HMAC token'ını doğrular.
 * /tmp dosya tabanlı tekilleme ile token tekrarını önler (60sn TTL).
 *
 * @param string|null $error Başarısızlık durumunda hata sebebi doldurulur.
 * @param string|null $path  HMAC doğrulama için kullanılacak yol.
 *                           Varsayılan mevcut istek yoludur.
 * @return bool Token geçerli ve kullanılmamışsa true.
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

    function litecaptcha_verify(?string &$error = null, ?string $path = null): bool
    {
        if (litecaptcha_is_local_request()) {
            return true; // Local ortamda token doğrulamayı atla
        }

        $rt       = $_GET['rt']  ?? $_GET['lc_rt']  ?? $_POST['lc_rt'] ?? '';
        $sigInput = $_GET['sig'] ?? $_GET['lc_sig'] ?? $_POST['lc_sig'] ?? '';
        $exp      = (int)($_GET['exp'] ?? $_GET['lc_exp'] ?? $_POST['lc_exp'] ?? 0);

        $logDir  = defined('ROOT') ? ROOT . '/logs' : dirname(__DIR__, 2) . '/logs';
        $logFile = $logDir . '/captcha_debug.log';

        $logEntry = function (string $reason, array $ctx = []) use ($logFile, $rt, $exp): void {
            $line = '[' . date('Y-m-d H:i:s') . '] LITECAPTCHA_VERIFY_FAIL reason=' . $reason;
            $ctx['rt_hash']   = $rt !== '' ? substr(hash('sha256', $rt), 0, 12) : 'empty';
            $ctx['exp']       = $exp;
            $ctx['now']       = time();
            $ctx['time_diff'] = $exp > 0 ? (time() - $exp) : 'n/a';
            $ctx['uri']       = $_SERVER['REQUEST_URI'] ?? '';
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
                'secret_len'   => strlen($secret),
                'secret_start' => substr($secret, 0, 4),
                'sig_input'    => substr($sigInput, 0, 12),
                'sig_expected' => substr($expectedSig, 0, 12),
            ]);
            return false;
        }

        // Tekrar önleme: 60sn TTL ile atomik token dosyası oluşturma.
        $tokenDir  = sys_get_temp_dir() . '/litecaptcha_tokens';
        if (!is_dir($tokenDir)) {
            @mkdir($tokenDir, 0700, true);
        }
        $tokenFile = $tokenDir . '/' . hash('sha256', $rt . '_' . $secret);
        $tokenHandle = @fopen($tokenFile, 'x');
        if ($tokenHandle === false && is_file($tokenFile)) {
            $fileAge = time() - (int)@filemtime($tokenFile);
            if ($fileAge >= 60) {
                @unlink($tokenFile);
                $tokenHandle = @fopen($tokenFile, 'x');
            }
        }

        if ($tokenHandle === false) {
            $error = 'token_replay';
            $logEntry($error, ['file_age_s' => is_file($tokenFile) ? (time() - (int)@filemtime($tokenFile)) : 'n/a']);
            return false;
        }

        @fclose($tokenHandle);
        @chmod($tokenFile, 0600);

        $error = null;
        return true;
    }
}
