<?php
/**
 * Cron: dirty flag varsa sitemap'i yeniden üret.
 *
 * Kullanım (örn. her 5 dakikada bir):
 *   * /5 * * * * /usr/bin/php /var/www/html/cron/generate-sitemap.php >/dev/null 2>&1
 *
 * Admin write metodları (store/update/delete vb.) `/tmp/fezadan-sitemap.dirty`
 * dosyasına touch atar. Bu cron sadece flag varsa çalışır → büyük makale
 * arşivlerinde her admin işleminde sitemap üretmek yerine tamponlanır.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI ortamında çalıştırılmalı.\n");
    exit(1);
}

define('ROOT', dirname(__DIR__));

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Core/Csrf.php';
require_once ROOT . '/app/Core/Flash.php';
require_once ROOT . '/app/Core/Upload.php';
require_once ROOT . '/app/Core/Controller.php';
require_once ROOT . '/app/Controllers/AdminController.php';
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require_once ROOT . '/vendor/autoload.php';
}

$flag = sys_get_temp_dir() . '/fezadan-sitemap.dirty';
$force = in_array('--force', $argv ?? [], true);

if (!$force && !file_exists($flag)) {
    // Hiçbir şey değişmediyse erken çık
    exit(0);
}

try {
    $admin = new AdminController();
    $admin->generateSitemap();
    @unlink($flag);
    echo "[" . date('c') . "] Sitemap üretildi.\n";
} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('c') . '] Sitemap hatası: ' . $e->getMessage() . "\n");
    exit(2);
}
