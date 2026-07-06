<?php
// --- 1. Oturum ve Başlık Güvenliği ---
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// HTTPS tespiti — Cloudflare proxy veya direkt HTTPS
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (strpos(($_SERVER['HTTP_CF_VISITOR'] ?? ''), '"https"') !== false);

// cookie_secure: HTTPS'te her zaman açık, localhost dışı ortamlarda da zorla
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (strpos(($_SERVER['HTTP_CF_VISITOR'] ?? ''), '"https"') !== false);
$_host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$_isLocal = in_array(($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1', 'localhost'], true)
    || preg_match('/(^|\.)localhost(:\d+)?$/', $_host) === 1
    || preg_match('/^127\.0\.0\.1(:\d+)?$/', $_host) === 1;
ini_set('session.cookie_secure', ($_isHttps || !$_isLocal) ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');

// Oturum kayıt dizini: önce SESSION_SAVE_PATH env, sonra upload_tmp_dir, sonra sys temp dene
$customSessionPath = getenv('SESSION_SAVE_PATH') ?: '';
if ($customSessionPath !== '' && (!is_dir($customSessionPath) && !@mkdir($customSessionPath, 0755, true) || !is_writable($customSessionPath))) {
    $customSessionPath = '';
}
if ($customSessionPath === '') {
    $altBase = ini_get('upload_tmp_dir') ?: ini_get('sys_temp_dir') ?: sys_get_temp_dir();
    $customSessionPath = rtrim($altBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'fezadan_sessions';
    if (!is_dir($customSessionPath)) {
        @mkdir($customSessionPath, 0700, true);
    }
}
if (is_dir($customSessionPath) && is_writable($customSessionPath)) {
    session_save_path($customSessionPath);
}

session_start();

// Per-request CSP nonce (Faz 5)
$__csp_nonce = bin2hex(random_bytes(16));
define('CSP_NONCE', $__csp_nonce);

$_isAnonymityCheckHost = strpos($_host, 'anonymitycheck.') === 0;
header('X-Frame-Options: ' . ($_isAnonymityCheckHost ? 'DENY' : 'SAMEORIGIN'));
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=(), browsing-topics=()');
if ($_isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}
$__csp_upgrade = ($_isLocal || $_isAnonymityCheckHost) ? '' : ' upgrade-insecure-requests;';
$__csp_connect = $_isAnonymityCheckHost
    ? "'self' http://127.0.0.1:* http://localhost:*"
    : "'self'";
$__csp_frame_ancestors = $_isAnonymityCheckHost ? "'none'" : "'self'";
$_isAdminPath = strpos($_SERVER['REQUEST_URI'] ?? '', '/yonetim') !== false;
$__csp_script = $_isAnonymityCheckHost
    ? "'self' 'nonce-{$__csp_nonce}'"
    : "'self' 'nonce-{$__csp_nonce}'";
$__csp_style = $_isAnonymityCheckHost
    ? "'self' 'unsafe-inline'"
    : "'self' 'unsafe-inline'";
$__csp_img = $_isAnonymityCheckHost ? "'self' data: https://*.tile.openstreetmap.org https://flagcdn.com" : "'self' data: https:";
$__csp_font = $_isAnonymityCheckHost ? "'self'" : "'self' https://cdn.jsdelivr.net";
$__csp_frame = $_isAnonymityCheckHost
    ? "'none'"
    : "'self' http://localhost:8089 http://127.0.0.1:8089 https://litecaptcha.fezadan.org https://www.openstreetmap.org";
header("Content-Security-Policy: default-src 'self'; script-src {$__csp_script}; style-src {$__csp_style}; img-src {$__csp_img}; font-src {$__csp_font}; connect-src {$__csp_connect}; media-src 'self'; frame-src {$__csp_frame}; object-src 'none'; frame-ancestors {$__csp_frame_ancestors}; base-uri 'self'; form-action 'self';{$__csp_upgrade}");
header_remove('X-Powered-By');

// --- 2. Cloudflare IP Doğrulama Fonksiyonu ---
function get_secure_remote_ip() {
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Cloudflare IP Aralıkları (IPv4 + IPv6)
    // Kaynak: https://www.cloudflare.com/ips/
    $cf_ips = [
        // IPv4
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        // IPv6
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    $is_cf = false;
    foreach ($cf_ips as $range) {
        if (ip_in_range($remote_ip, $range)) {
            $is_cf = true;
            break;
        }
    }

    if ($is_cf && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $cfIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }
    }

    return $remote_ip;
}

// Yardımcı Fonksiyon: IP Aralık Kontrolü (IPv4 + IPv6)
function ip_in_range($ip, $range) {
    if (strpos($range, '/') === false) return false;
    list($subnet, $bits) = explode('/', $range, 2);
    $bits = (int)$bits;

    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);
    if ($ipBin === false || $subnetBin === false) return false;
    if (strlen($ipBin) !== strlen($subnetBin)) return false; // farklı aile (v4 vs v6)

    $bytes = (int) floor($bits / 8);
    $remainder = $bits % 8;

    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
        return false;
    }
    if ($remainder === 0) return true;

    $mask = chr(0xff << (8 - $remainder) & 0xff);
    return (ord($ipBin[$bytes]) & ord($mask)) === (ord($subnetBin[$bytes]) & ord($mask));
}

// Güvenli IP'yi ata
$_SERVER['REMOTE_ADDR'] = get_secure_remote_ip();

// --- 3. Hata Raporlama ---
// Yalnizca APP_DEBUG env aciksa localhost'ta hata goster. Production'da ve cPanel testlerinde susar.
if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) && getenv('APP_DEBUG') === '1') {
    ini_set('display_errors', 1); error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0); error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// --- 4. Dizin Yapılandırması ---
$current_dir = __DIR__;
if (file_exists(dirname($current_dir) . '/app/Config/config.php')) {
    define('ROOT', dirname($current_dir));
} else if (file_exists($current_dir . '/app/Config/config.php')) {
    define('ROOT', $current_dir);
} else {
    http_response_code(500);
    die("Sistem hatası: Yapılandırma dosyası eksik.");
}

// --- 5. Başlat ---
require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/app/Core/ErrorHandler.php';
require_once ROOT . '/app/Core/AdminLog.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Core/Csrf.php';
require_once ROOT . '/app/Core/Flash.php';
require_once ROOT . '/app/Core/Upload.php';
require_once ROOT . '/app/Core/App.php';
require_once ROOT . '/app/Core/Controller.php';
require_once ROOT . '/vendor/autoload.php';

ErrorHandler::register();

$app = new App();
