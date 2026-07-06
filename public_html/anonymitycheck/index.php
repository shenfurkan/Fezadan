<?php
// Local/public_html entrypoint for the anonymity scanner.
// Gerçek uygulamayı AnonymityCheckController'da tutarken şu erişime izin verir:
// http://localhost:8080/anonymitycheck/
$originalHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$originalUri = $_SERVER['REQUEST_URI'] ?? '/anonymitycheck/';
$port = '';
if (strpos($originalHost, ':') !== false) {
    $port = ':' . substr(strrchr($originalHost, ':'), 1);
}

$path = parse_url($originalUri, PHP_URL_PATH) ?: '/anonymitycheck/';
$query = parse_url($originalUri, PHP_URL_QUERY);
$subPath = '/';
if (strpos($path, '/anonymitycheck') === 0) {
    $subPath = substr($path, strlen('/anonymitycheck')) ?: '/';
    $subPath = '/' . ltrim($subPath, '/');
}

$_SERVER['HTTP_HOST'] = 'anonymitycheck.localhost' . $port;
$_SERVER['REQUEST_URI'] = $subPath . ($query ? '?' . $query : '');
$_SERVER['SCRIPT_NAME'] = '/index.php';
if ($subPath === '/') {
    unset($_GET['url']);
} else {
    $_GET['url'] = trim($subPath, '/');
}

require dirname(__DIR__) . '/index.php';
