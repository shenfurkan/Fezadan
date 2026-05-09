<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo "Method not allowed.\n";
    exit;
}

$target = __DIR__ . '/scripts/deploy-logic.php';
if (!is_file($target)) {
    error_log('Deploy endpoint missing deploy-logic.php');
    http_response_code(503);
    echo "Deploy unavailable.\n";
    exit;
}

require_once $target;
