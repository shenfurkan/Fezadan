<?php
/**
 * Minimal Integration Test Suite for Fezadan
 * Runs basic HTTP assertions against localhost:8080
 */

$baseUrl = 'http://127.0.0.1';
$passed = 0;
$failed = 0;

function assertTest($name, $condition, $message = '') {
    global $passed, $failed;
    if ($condition) {
        echo "\033[32m[PASS]\033[0m $name\n";
        $passed++;
    } else {
        echo "\033[31m[FAIL]\033[0m $name - $message\n";
        $failed++;
    }
}

function fetchUrl($path, $options = []) {
    global $baseUrl;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if (!empty($options['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
    }
    if (!empty($options['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['post']));
    }
    if (!empty($options['follow'])) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    } else {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    }
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    return ['code' => $code, 'header' => $header, 'body' => $body];
}

echo "Running Fezadan Integration Tests...\n\n";

// 1. Canonicalization
$res = fetchUrl('/');
assertTest("Root canonicalization redirects", $res['code'] === 307 || $res['code'] === 301, "Expected 307 or 301, got " . $res['code']);
if (preg_match('/Location: \/(tr|en)/', $res['header'])) {
    assertTest("Root canonicalization target", true);
} else {
    assertTest("Root canonicalization target", false, "Did not redirect to /tr or /en");
}

// 2. Admin CSRF Protection on Public Method
$res = fetchUrl('/yonetim/sendResetLink', ['post' => ['email' => 'test@test.com']]);
assertTest("POST to public write method without CSRF returns 403", $res['code'] === 403, "Expected 403 Forbidden, got " . $res['code']);

// 3. Admin Auth Protection
$res = fetchUrl('/yonetim/dashboard');
assertTest("Admin Dashboard without auth redirects", $res['code'] === 302, "Expected 302 Redirect, got " . $res['code']);

// 4. Robots.txt
$res = fetchUrl('/robots.txt');
assertTest("Robots.txt returns 200", $res['code'] === 200, "Got " . $res['code']);
assertTest("Robots.txt lets AI answer engines read the site", strpos($res['body'], 'Content-Signal: search=yes, ai-input=yes') !== false && strpos($res['body'], 'User-agent: GPTBot') === false, "AI crawlers are still blocked or the content signal is missing");

// 5. Sitemap Base URL
$res = fetchUrl('/sitemap.xml');
assertTest("Sitemap.xml returns 200", $res['code'] === 200, "Got " . $res['code']);
assertTest("Sitemap.xml is valid XML format", strpos($res['body'], '<sitemapindex') !== false || strpos($res['body'], '<urlset') !== false, "Invalid XML structure");

// 6. PDF Guard Verification
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}
require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Core/Controller.php';
require_once ROOT . '/app/Controllers/NotesController.php';

try {
    $pdo = Db::pdo();
    $slug = $pdo->query("SELECT slug FROM notes LIMIT 1")->fetchColumn();
    if ($slug) {
        $res = fetchUrl('/not/view/' . rawurlencode($slug), ['headers' => ['Host: notlar.localhost']]);
        assertTest("Accessing /not/view/slug without token returns 403", $res['code'] === 403, "Expected 403 Forbidden, got " . $res['code']);

        $res = fetchUrl('/not/view/' . rawurlencode($slug) . '?token=invalidtoken123', ['headers' => ['Host: notlar.localhost']]);
        assertTest("Accessing /not/view/slug with invalid token returns 403", $res['code'] === 403, "Expected 403 Forbidden, got " . $res['code']);

        $validToken = NotesController::viewToken($slug);
        $res = fetchUrl('/not/view/' . rawurlencode($slug) . '?token=' . rawurlencode($validToken), ['headers' => ['Host: notlar.localhost']]);
        assertTest("Accessing /not/view/slug with valid token returns 200 or 206", $res['code'] === 200 || $res['code'] === 206, "Expected 200/206, got " . $res['code']);
    } else {
        echo "No notes found in database to test PDF Guard.\n";
    }
} catch (Exception $e) {
    echo "Database error during integration tests: " . $e->getMessage() . "\n";
}

echo "\nSummary: $passed passed, $failed failed.\n";
// @phpstan-ignore greater.alwaysFalse (assertTest() increments $failed through `global`)
if ($failed > 0) exit(1);
exit(0);
