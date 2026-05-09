#!/usr/bin/env php
<?php
/**
 * Fezadan — Mevcut Uploads Klasörünü R2'ye Taşıma Scripti
 *
 * Kullanım (SSH):
 *   php /home/fezadano5/scripts/migrate_uploads_to_r2.php
 *
 * Dry-run (yüklemeden sadece listelemek):
 *   php /home/fezadano5/scripts/migrate_uploads_to_r2.php --dry-run
 */

// ─── Bootstrap ──────────────────────────────────────────
define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/app/Core/R2Storage.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

// ─── Ayarlar ────────────────────────────────────────────
$uploadsDir = ROOT . '/public_html/uploads';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'pdf'];

// MIME map
$mimeMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'pdf'  => 'application/pdf',
];

// ─── Kontrol ────────────────────────────────────────────
if (!is_dir($uploadsDir)) {
    echo "HATA: uploads klasörü bulunamadı: {$uploadsDir}\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════╗\n";
echo "║   FEZADAN — Uploads → R2 Migrasyon Scripti  ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "⚠  DRY-RUN modu — dosyalar yüklenmeyecek, sadece listelenecek.\n\n";
}

// ─── R2 bağlantısı ──────────────────────────────────────
try {
    $r2 = \App\Core\R2Storage::instance();
    echo "✓ R2 bağlantısı kuruldu.\n\n";
} catch (\Throwable $e) {
    echo "HATA: R2 bağlantısı kurulamadı: " . $e->getMessage() . "\n";
    echo "  → .env dosyasındaki R2_* anahtarlarını kontrol edin.\n";
    exit(1);
}

// ─── Dosyaları tara ─────────────────────────────────────
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$files = [];
foreach ($iterator as $file) {
    if ($file->isDir()) continue;

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $allowedExtensions, true)) continue;

    $absolutePath = $file->getRealPath();
    // uploads/ altındaki göreceli yol → R2 object key olacak
    $relativePath = str_replace('\\', '/', substr($absolutePath, strlen(realpath($uploadsDir)) + 1));
    $objectKey = 'uploads/' . $relativePath;

    $files[] = [
        'absolute' => $absolutePath,
        'key'      => $objectKey,
        'ext'      => $ext,
        'size'     => $file->getSize(),
    ];
}

$totalFiles = count($files);
$totalSize  = array_sum(array_column($files, 'size'));

echo "Tarama tamamlandı:\n";
echo "  Dosya sayısı : {$totalFiles}\n";
echo "  Toplam boyut : " . round($totalSize / 1048576, 2) . " MB\n\n";

if ($totalFiles === 0) {
    echo "Yüklenecek dosya yok.\n";
    exit(0);
}

// ─── Yükle ──────────────────────────────────────────────
$uploaded = 0;
$skipped  = 0;
$failed   = 0;
$errors   = [];

foreach ($files as $i => $f) {
    $num = $i + 1;
    $sizeMB = round($f['size'] / 1048576, 2);
    $mime = $mimeMap[$f['ext']] ?? 'application/octet-stream';

    echo "[{$num}/{$totalFiles}] {$f['key']} ({$sizeMB} MB) ... ";

    if ($dryRun) {
        echo "ATLANIR (dry-run)\n";
        $skipped++;
        continue;
    }

    try {
        $result = $r2->uploadFile($f['absolute'], $f['key'], $mime);
        if ($result) {
            echo "✓ OK\n";
            $uploaded++;
        } else {
            echo "✗ BAŞARISIZ (uploadFile false döndü)\n";
            $failed++;
            $errors[] = $f['key'] . ': uploadFile false';
        }
    } catch (\Throwable $e) {
        echo "✗ HATA: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = $f['key'] . ': ' . $e->getMessage();
    }
}

// ─── Rapor ──────────────────────────────────────────────
echo "\n";
echo "══════════════════════════════════════════════\n";
echo "  SONUÇ\n";
echo "══════════════════════════════════════════════\n";
echo "  Yüklendi  : {$uploaded}\n";
echo "  Atlandı   : {$skipped}\n";
echo "  Başarısız : {$failed}\n";
echo "  Toplam    : {$totalFiles}\n";

if (!empty($errors)) {
    echo "\n  HATALAR:\n";
    foreach ($errors as $err) {
        echo "    - {$err}\n";
    }
}

echo "\n";
if ($uploaded > 0) {
    echo "✓ Migrasyon tamamlandı. Dosyalar artık CDN üzerinden erişilebilir.\n";
}
if ($dryRun) {
    echo "ℹ  Gerçek yükleme için --dry-run parametresini kaldırarak tekrar çalıştırın.\n";
}
echo "\n";
