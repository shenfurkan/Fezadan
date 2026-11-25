<?php
// Resolve the application root before loading shared request/proxy handling.
$current_dir = __DIR__;
if (file_exists(dirname($current_dir) . '/app/Config/config.php')) {
    define('ROOT', dirname($current_dir));
} else if (file_exists($current_dir . '/app/Config/config.php')) {
    define('ROOT', $current_dir);
} else {
    http_response_code(500);
    die("Sistem hatası: Yapılandırma dosyası eksik.");
}
require_once ROOT . '/app/Core/HttpRequest.php';
HttpRequest::normalizeProxy();
require_once ROOT . '/app/Core/WebAnalytics.php';
define('WEB_ANALYTICS_ENABLED', WebAnalytics::isEnabled($_SERVER));

// 1. Oturum ve Başlık Güvenliği
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// cookie_secure: always on for HTTPS, also force on for non-localhost environments
$_isHttps = HttpRequest::isHttps();
$_host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$_isLocal = HttpRequest::isLocalHost();
ini_set('session.cookie_secure', ($_isHttps || !$_isLocal) ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');

// Session kayıt dizini: SESSION_SAVE_PATH, .user.ini upload_tmp_dir veya sistem temp
$customSessionPath = (string)(getenv('SESSION_SAVE_PATH') ?: '');
if ($customSessionPath === '' || (!is_dir($customSessionPath) && !@mkdir($customSessionPath, 0755, true)) || !is_writable($customSessionPath)) {
    $altBase = ini_get('upload_tmp_dir') ?: ini_get('sys_temp_dir') ?: sys_get_temp_dir();
    $customSessionPath = rtrim($altBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'fezadan_sessions';
    if (!is_dir($customSessionPath)) {
        @mkdir($customSessionPath, 0700, true);
    }
}
if (is_dir($customSessionPath) && is_writable($customSessionPath)) {
    session_save_path($customSessionPath);
}

// Admin and mail-invitation forms use $_SESSION (auth, CSRF, flash). Other public pages skip it so cookieless
// bots don't create a session file per hit and pages aren't forced to no-store.
$__requestPath = rawurldecode(explode('?', (string)($_SERVER['REQUEST_URI'] ?? '/'), 2)[0]);
$__needsSession = preg_match('#^/(?:(?:tr|en)/)?(?:yonetim|posta-davet|mail-invitation)(?:/|$)#i', $__requestPath) === 1;
if ($__needsSession) {
    session_start();
    header('X-Robots-Tag: noindex, nofollow');
}
// Dynamic responses are private by default. Public feeds/assets override this explicitly.
header('Cache-Control: private, no-store, max-age=0');

// Per-request CSP nonce (Faz 5)
$__csp_nonce = bin2hex(random_bytes(16));
define('CSP_NONCE', $__csp_nonce);

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=(), browsing-topics=()');
if ($_isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}
$__csp_upgrade = $_isLocal ? '' : ' upgrade-insecure-requests;';
$__csp_connect = "'self'";
if (WEB_ANALYTICS_ENABLED) {
    // The manually embedded, nonced beacon reports only to this Cloudflare endpoint.
    $__csp_connect .= ' https://cloudflareinsights.com/cdn-cgi/rum';
}
$__csp_frame_ancestors = "'self'";
$__csp_script = "'self' 'nonce-{$__csp_nonce}'";
$__csp_style = "'self' 'unsafe-inline'";
$__csp_img = "'self' data: https:";
$__csp_font = "'self'";
$__csp_frame = "'self' http://localhost:8089 http://127.0.0.1:8089 https://litecaptcha.fezadan.org https://www.openstreetmap.org";
header("Content-Security-Policy: default-src 'self'; script-src {$__csp_script}; style-src {$__csp_style}; img-src {$__csp_img}; font-src {$__csp_font}; connect-src {$__csp_connect}; media-src 'self'; frame-src {$__csp_frame}; object-src 'none'; frame-ancestors {$__csp_frame_ancestors}; base-uri 'self'; form-action 'self';{$__csp_upgrade}");
header_remove('X-Powered-By');

// 3. Hata Raporlama
// Yalnizca APP_DEBUG env aciksa localhost'ta hata goster. Production'da ve cPanel testlerinde susar.
if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) && getenv('APP_DEBUG') === '1') {
    ini_set('display_errors', 1); error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0); error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// 5. Başlat
require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/app/Core/ErrorHandler.php';
require_once ROOT . '/app/Core/AdminLog.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Services/ArticleQueries.php';
require_once ROOT . '/app/Core/Csrf.php';
require_once ROOT . '/app/Core/Flash.php';
require_once ROOT . '/app/Core/Upload.php';
require_once ROOT . '/app/Core/App.php';
require_once ROOT . '/app/Core/Controller.php';
require_once ROOT . '/vendor/autoload.php';

ErrorHandler::register();

$app = new App();
