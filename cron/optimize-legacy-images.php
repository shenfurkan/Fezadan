<?php
/**
 * One-off migration: Download legacy cover images (.jpg, .jpeg, .png) from R2,
 * resize to max 1920px, convert to WebP format, upload back to R2 under
 * /uploads/covers/, and update the articles table.
 *
 * Usage:
 *   php cron/optimize-legacy-images.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI ortamında çalıştırılmalı.\n");
    exit(1);
}

define('ROOT', dirname(__DIR__));

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Core/Upload.php';
require_once ROOT . '/app/Core/R2Storage.php';

if (file_exists(ROOT . '/vendor/autoload.php')) {
    require_once ROOT . '/vendor/autoload.php';
}

use App\Core\R2Storage;

echo "--- FEZADAN LEGACY IMAGES MIGRATION START ---\n\n";

try {
    $pdo = Db::pdo();
    $r2 = R2Storage::instance();

    // Reflection to get private client and bucket properties from R2Storage
    $ref = new ReflectionClass($r2);
    $clientProp = $ref->getProperty('client');
    $clientProp->setAccessible(true);
    $client = $clientProp->getValue($r2);

    $bucketProp = $ref->getProperty('bucketName');
    $bucketProp->setAccessible(true);
    $bucket = $bucketProp->getValue($r2);

    // Get all articles with legacy cover images (.jpg, .jpeg, .png)
    $stmt = $pdo->query("
        SELECT id, title, slug, image_url
        FROM articles
        WHERE image_url IS NOT NULL
          AND image_url <> ''
          AND (image_url LIKE '%.jpg' OR image_url LIKE '%.jpeg' OR image_url LIKE '%.png')
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($articles)) {
        echo "Tebrikler! Optimize edilecek eski görsel bulunamadı.\n";
        exit(0);
    }

    echo "Bulunan eski görsel sayısı: " . count($articles) . "\n\n";

    $tempDir = sys_get_temp_dir() . '/fezadan_mig_' . bin2hex(random_bytes(4));
    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        throw new RuntimeException("Geçici klasör oluşturulamadı: $tempDir");
    }

    $successCount = 0;
    $failCount = 0;

    foreach ($articles as $art) {
        $id = $art['id'];
        $title = $art['title'];
        $slug = $art['slug'];
        $oldUrl = $art['image_url'];

        echo "İşleniyor: [ID: $id] \"$title\"\n";
        echo "  Mevcut URL: $oldUrl\n";

        // Extract clean object key for R2 (strip starting slash if present)
        $oldKey = ltrim(parse_url($oldUrl, PHP_URL_PATH), '/');

        // Target paths
        $localOriginal = $tempDir . '/' . basename($oldKey);
        $localWebp = $tempDir . '/' . $slug . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.webp';

        try {
            // 1. Download file from R2
            echo "  R2'den indiriliyor: $oldKey\n";
            $obj = $client->getObject([
                'Bucket' => $bucket,
                'Key'    => $oldKey,
            ]);
            file_put_contents($localOriginal, (string)$obj['Body']);

            if (!is_file($localOriginal) || filesize($localOriginal) === 0) {
                throw new RuntimeException("Görsel indirilemedi veya boş.");
            }

            // 2. Load and resize image using GD (fallback if Imagick is unavailable)
            echo "  Boyutlandırılıyor ve WebP formatına dönüştürülüyor...\n";
            $raw = file_get_contents($localOriginal);
            $im = imagecreatefromstring($raw);
            if (!$im) {
                throw new RuntimeException("Görsel GD kütüphanesi tarafından yüklenemedi.");
            }

            $width = imagesx($im);
            $height = imagesy($im);
            echo "    Orijinal boyutlar: {$width}x{$height} | Boyut: " . round(filesize($localOriginal) / 1024) . " KB\n";

            if ($width > 1920 || $height > 1920) {
                if ($width > $height) {
                    $newWidth = 1920;
                    $newHeight = (int)round(($height * 1920) / $width);
                } else {
                    $newHeight = 1920;
                    $newWidth = (int)round(($width * 1920) / $height);
                }
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                if ($resized !== false) {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($im);
                    $im = $resized;
                    echo "    Yeni boyutlar: {$newWidth}x{$newHeight}\n";
                }
            } else {
                echo "    Boyut 1920px'den küçük, yeniden boyutlandırma atlandı.\n";
            }

            // 3. Save as WebP
            imagepalettetotruecolor($im);
            imagealphablending($im, true);
            imagesavealpha($im, true);

            $converted = imagewebp($im, $localWebp, 82);
            imagedestroy($im);

            if (!$converted || !is_file($localWebp) || filesize($localWebp) === 0) {
                throw new RuntimeException("WebP dönüşümü başarısız oldu.");
            }
            echo "    Yeni WebP Boyutu: " . round(filesize($localWebp) / 1024) . " KB\n";

            // 4. Upload WebP back to R2
            $newKey = 'uploads/covers/' . basename($localWebp);
            echo "  R2'ye yükleniyor: $newKey\n";
            $uploaded = $r2->uploadFile($localWebp, $newKey, 'image/webp');
            if ($uploaded === null) {
                throw new RuntimeException("R2 yükleme başarısız.");
            }

            // 5. Update Database within transaction
            echo "  Veritabanı güncelleniyor...\n";
            $pdo->beginTransaction();
            $upStmt = $pdo->prepare("UPDATE articles SET image_url = ? WHERE id = ?");
            $upStmt->execute(['/' . $newKey, $id]);
            $pdo->commit();

            // 6. Delete old file from R2 to save space (keep database clean)
            echo "  Eski görsel R2'den siliniyor: $oldKey\n";
            $r2->deleteFile($oldKey);

            echo "  ✓ BAŞARILI: [ID: $id] güncellendi.\n\n";
            $successCount++;

        } catch (Throwable $artEx) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fwrite(STDERR, "  ✗ HATA (ID $id): " . $artEx->getMessage() . "\n\n");
            $failCount++;
        } finally {
            // Cleanup temp files
            if (is_file($localOriginal)) @unlink($localOriginal);
            if (is_file($localWebp)) @unlink($localWebp);
        }
    }

    // Delete temp folder
    @rmdir($tempDir);

    echo "--- ÖZET ---\n";
    echo "Başarılı: $successCount\n";
    echo "Hatalı  : $failCount\n";

} catch (Throwable $e) {
    fwrite(STDERR, "MIGRATION CRITICAL ERROR: " . $e->getMessage() . "\n");
    exit(2);
}
