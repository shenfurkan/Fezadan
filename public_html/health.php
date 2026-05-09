<?php
/**
 * Hafif sağlık kontrolü uçnoktası — UptimeRobot, Cloudflare Health vb. için.
 * Yalnızca minimal payload; hassas detay sızdırmaz.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$rootDir = dirname(__DIR__);
require_once $rootDir . '/app/Config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]
    );
    $pdo->query('SELECT 1');
    echo json_encode(['ok' => true, 'time' => time()]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false]);
}
