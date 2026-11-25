<?php
/**
 * Production Smoke Test Script
 *
 * Sunucuya deploy yapıldıktan sonra sitenin ana fonksiyonlarının
 * ayakta olup olmadığını doğrulamak için kullanılır.
 * Kullanımı: php scripts/smoke-test.php [--url=https://fezadan.org]
 * Salt okunurdur; oturum açmaz, içerik değiştirmez veya analitik raporu göndermez.
 */

define('ROOT', dirname(__DIR__));
require_once ROOT . '/app/Config/config.php';

$siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://localhost:8080';
$options = getopt('', ['url:']);
if (isset($options['url']) && is_string($options['url'])) {
    $siteUrl = rtrim($options['url'], '/');
}
if (!filter_var($siteUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($siteUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
    fwrite(STDERR, "Geçerli bir http/https adresi gerekli.\n");
    exit(1);
}
$productionMain = in_array(strtolower((string)parse_url($siteUrl, PHP_URL_HOST)), ['fezadan.org', 'www.fezadan.org'], true);

// Test edilecek kritik yollar
$endpoints = [
    'Ana Sayfa (Yönlendirme)' => '/',
    'Sitemap XML' => '/sitemap.xml',
    'Robots TXT' => '/robots.txt',
    'Sağlık Kontrolü' => '/health.php',
];

echo "Fezadan Smoke Test Başlatılıyor...\n";
echo "Hedef URL: $siteUrl\n\n";

$passed = 0;
$failed = 0;

function checkUrl($name, $path, $siteUrl, ?int $beaconCount = null, array $headers = [], ?int $expectedStatus = null) {
    global $passed, $failed;

    $url = $siteUrl . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Redirectleri takip et
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // User-agent ekleyelim ki bot engellemesine takılmasın
    // Cloudflare may inject browser-only scripts that a generic bot UA does not receive.
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36');

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    curl_close($ch);
    $raw = is_string($raw) ? $raw : '';
    $responseHeaders = substr($raw, 0, $headerSize);
    $html = substr($raw, $headerSize);
    $issues = [];
    if ($error !== '') $issues[] = $error;
    if ($expectedStatus !== null ? $httpCode !== $expectedStatus : ($httpCode < 200 || $httpCode >= 400)) {
        $issues[] = 'beklenmeyen HTTP durumu';
    }
    if ($beaconCount !== null) {
        // Include versioned auto-injected URLs, not just the manual exact URL.
        $count = preg_match_all('~<script\b[^>]*\bsrc\s*=\s*["\']https://static\.cloudflareinsights\.com/[^"\']+["\']~i', $html);
        if ($count !== $beaconCount) $issues[] = "analitik betiği: $count (beklenen $beaconCount)";
    }
    if ($path === '/sw.js') {
        if (!preg_match('/^cache-control:.*\bno-cache\b/im', $responseHeaders)
            || !preg_match('/^cache-control:.*\bmax-age=0\b/im', $responseHeaders)
            || preg_match('/^cache-control:.*\bimmutable\b/im', $responseHeaders)
            || preg_match('/^expires:\s*(.+)$/im', $responseHeaders)) {
            $issues[] = 'service worker önbellek/Expires başlığı güncellemeyi geciktirebilir';
        }
    }

    if (!$issues) {
        echo "✅ PASSED: $name ($url) - HTTP $httpCode\n";
        $passed++;
    } else {
        echo "❌ FAILED: $name ($url) - HTTP $httpCode: " . implode('; ', $issues) . "\n";
        $failed++;
    }
}

foreach ($endpoints as $name => $path) {
    checkUrl($name, $path, $siteUrl);
}
$publicBeaconCount = $productionMain ? 1 : 0;
checkUrl('Türkçe Ana Sayfa / tek analitik betiği', '/tr', $siteUrl, $publicBeaconCount);
checkUrl('İngilizce Ana Sayfa / tek analitik betiği', '/en', $siteUrl, $publicBeaconCount);
checkUrl('Türkçe Gizlilik Politikası', '/tr/gizlilik-politikasi', $siteUrl, $publicBeaconCount);
checkUrl('İngilizce Gizlilik Politikası', '/en/privacy', $siteUrl, $publicBeaconCount);
checkUrl('DNT ölçüm dışı', '/tr', $siteUrl, 0, ['DNT: 1']);
checkUrl('GPC ölçüm dışı', '/tr', $siteUrl, 0, ['Sec-GPC: 1']);
checkUrl('Yönetim ölçüm dışı', '/yonetim', $siteUrl, 0);
checkUrl('Davet ölçüm dışı', '/tr/posta-davet', $siteUrl, 0);
checkUrl('Kaldırılmış okunma sayacı', '/tr/article/count', $siteUrl, 0, [], 404);
checkUrl('Kaldırılmış yerel analitik', '/tr/analytics/track', $siteUrl, 0, [], 404);
checkUrl('Service worker önbelleği', '/sw.js', $siteUrl, 0);
if ($productionMain) {
    checkUrl('Anonimlik ölçüm dışı', '/', 'https://anonymitycheck.fezadan.org', 0);
    checkUrl('Notlar ölçüm dışı', '/', 'https://notlar.fezadan.org', 0);
}

echo "\n--- Smoke Test Özeti ---\n";
echo "Başarılı: $passed\n";
echo "Başarısız: $failed\n";

// @phpstan-ignore greater.alwaysFalse (checkUrl() increments $failed through `global`)
exit($failed > 0 ? 1 : 0);
