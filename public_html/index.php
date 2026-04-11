<?php
// --- 1. Oturum ve Başlık Güvenliği ---
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header("Content-Security-Policy: frame-ancestors 'self'; upgrade-insecure-requests;");
header_remove('X-Powered-By');

// --- 2. Cloudflare IP Doğrulama Fonksiyonu ---
function get_secure_remote_ip() {
    $remote_ip = $_SERVER['REMOTE_ADDR'];
    
    // Cloudflare IP Aralıkları (IPv4)
    $cf_ips = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
    ];

    // Gelen isteğin gerçekten Cloudflare'den gelip gelmediğini kontrol et
    $is_cf = false;
    foreach ($cf_ips as $range) {
        if (ip_in_range($remote_ip, $range)) {
            $is_cf = true;
            break;
        }
    }

    if ($is_cf && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    return $remote_ip;
}

// Yardımcı Fonksiyon: IP Aralık Kontrolü
function ip_in_range($ip, $range) {
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask;
    return ($ip & $mask) == $subnet;
}

// Güvenli IP'yi ata
$_SERVER['REMOTE_ADDR'] = get_secure_remote_ip();

// --- 3. Hata Raporlama ---
if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    ini_set('display_errors', 1); error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0); error_reporting(0);
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
require_once ROOT . '/app/Core/App.php';
require_once ROOT . '/app/Core/Controller.php';
require_once ROOT . '/vendor/autoload.php';

$app = new App();