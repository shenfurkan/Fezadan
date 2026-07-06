<?php
/**
 * Production Smoke Test Script
 *
 * Sunucuya deploy yapıldıktan sonra sitenin ana fonksiyonlarının
 * ayakta olup olmadığını doğrulamak için kullanılır.
 * Kullanımı: php scripts/smoke-test.php
 */

define('ROOT', dirname(__DIR__));
require_once ROOT . '/app/Config/config.php';

$siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://localhost:8080';

// Test edilecek kritik yollar
$endpoints = [
    'Ana Sayfa (Yönlendirme)' => '/',
    'Türkçe Ana Sayfa' => '/tr',
    'Sitemap XML' => '/sitemap.xml',
    'Robots TXT' => '/robots.txt',
    'Yönetim Paneli Giriş' => '/yonetim'
];

echo "Fezadan Smoke Test Başlatılıyor...\n";
echo "Hedef URL: $siteUrl\n\n";

$passed = 0;
$failed = 0;

function checkUrl($name, $path, $siteUrl) {
    global $passed, $failed;

    $url = $siteUrl . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Redirectleri takip et
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // SSL hatasını göz ardı et (lokal test vs. durumları için)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // User-agent ekleyelim ki bot engellemesine takılmasın
    curl_setopt($ch, CURLOPT_USERAGENT, 'Fezadan-Smoke-Test/1.0');

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 400) {
        echo "✅ PASSED: $name ($url) - HTTP $httpCode\n";
        $passed++;
    } else {
        echo "❌ FAILED: $name ($url) - HTTP $httpCode\n";
        $failed++;
    }
}

foreach ($endpoints as $name => $path) {
    checkUrl($name, $path, $siteUrl);
}

echo "\n--- Smoke Test Özeti ---\n";
echo "Başarılı: $passed\n";
echo "Başarısız: $failed\n";

exit($failed > 0 ? 1 : 0);
