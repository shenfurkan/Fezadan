<?php
/**
 * Bir kerelik bulk WebP üretim script'i.
 *
 * Kullanım:
 *   php cron/convert-existing-to-webp.php           (sadece eksik olanları üretir)
 *   php cron/convert-existing-to-webp.php --force   (varolan WebP'leri de yeniden üretir)
 *   php cron/convert-existing-to-webp.php --quality=80
 *
 * Mevcut yüklemelerdeki .jpg/.jpeg/.png dosyaları için yan yana .webp varyantı üretir.
 * View'lardaki <picture> tag'i WebP'yi otomatik bulup servis eder; bulunamazsa
 * orijinal JPEG/PNG'ye düşer. Read sayfası kapak görseli ve makale grid kartları
 * için LCP'yi belirgin biçimde düşürür.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI ortamında çalıştırılmalı.\n");
    exit(1);
}

$hasGdWebp   = function_exists('imagewebp');
$hasImagick  = class_exists('Imagick');
$imagickWebp = false;
if ($hasImagick) {
    try {
        $imagickWebp = (bool)(new Imagick())->queryFormats('WEBP');
    } catch (Throwable $e) {
        $imagickWebp = false;
    }
}
if (!$hasGdWebp && !$imagickWebp) {
    fwrite(STDERR, "Ne GD WebP ne de Imagick WebP mevcut. cPanel → Select PHP Version → Extensions'tan 'imagick' aktifleştir.\n");
    exit(2);
}
$encoder = $hasGdWebp ? 'gd' : 'imagick';
echo "Encoder: $encoder\n\n";

$root = dirname(__DIR__);
$uploadsDir = $root . '/public_html/uploads';

$args    = $argv ?? [];
$force   = in_array('--force', $args, true);
$quality = 82;
foreach ($args as $a) {
    if (preg_match('/^--quality=(\d+)$/', $a, $m)) {
        $quality = max(40, min(95, (int)$m[1]));
    }
}

if (!is_dir($uploadsDir)) {
    fwrite(STDERR, "uploads klasörü yok: $uploadsDir\n");
    exit(3);
}

$stats = ['scanned' => 0, 'converted' => 0, 'skipped' => 0, 'failed' => 0, 'savedKb' => 0];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) continue;

    $stats['scanned']++;
    $src  = $file->getPathname();
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);

    if (!$force && file_exists($webp)) {
        $stats['skipped']++;
        continue;
    }

    try {
        $tmp = $webp . '.tmp';

        if ($encoder === 'gd') {
            $img = ($ext === 'png') ? @imagecreatefrompng($src) : @imagecreatefromjpeg($src);
            if (!$img) {
                $img = @imagecreatefromstring(@file_get_contents($src));
            }
            if (!$img) {
                $stats['failed']++;
                fwrite(STDERR, "  ✗ okunamadı: $src\n");
                continue;
            }
            if ($ext === 'png') {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
            $ok = imagewebp($img, $tmp, $quality);
            imagedestroy($img);
        } else {
            $im = new Imagick($src);
            $im->setImageFormat('webp');
            $im->setImageCompressionQuality($quality);
            $im->setOption('webp:method', '6');
            if ($ext !== 'png') {
                $im->setOption('webp:lossless', 'false');
            }
            $ok = $im->writeImage($tmp);
            $im->clear();
            $im->destroy();
        }

        if (!$ok || !file_exists($tmp) || filesize($tmp) === 0) {
            $stats['failed']++;
            @unlink($tmp);
            fwrite(STDERR, "  ✗ webp yazılamadı: $webp\n");
            continue;
        }
        rename($tmp, $webp);

        $saved = max(0, filesize($src) - filesize($webp));
        $stats['savedKb'] += (int)round($saved / 1024);
        $stats['converted']++;
        echo "  ✓ " . basename($webp) . "  (" . round($saved / 1024) . " KB tasarruf)\n";
    } catch (Throwable $e) {
        $stats['failed']++;
        fwrite(STDERR, "  ✗ " . $src . " — " . $e->getMessage() . "\n");
    }
}

echo "\n--- Özet ---\n";
echo "Taranan : {$stats['scanned']}\n";
echo "Üretilen: {$stats['converted']}\n";
echo "Atlanan : {$stats['skipped']}\n";
echo "Hatalı  : {$stats['failed']}\n";
echo "Tasarruf: ~{$stats['savedKb']} KB\n";

exit($stats['failed'] > 0 ? 4 : 0);
