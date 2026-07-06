<?php
/**
 * Cron: dirty flag varsa sitemap'i yeniden uret.
 *
 * Kullanim (orn. her 5 dakikada bir):
 *   * /5 * * * * /usr/bin/php /var/www/html/cron/generate-sitemap.php >/dev/null 2>&1
 *
 * Admin write metodlari (store/update/delete vb.) `/tmp/fezadan-sitemap.dirty`
 * dosyasina touch atar. Bu cron sadece flag varsa calisir -> buyuk makale
 * arsivlerinde her admin isleminde sitemap uretmek yerine tamponlanir.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI ortaminda calistirilmali.\n");
    exit(1);
}

$rootDir = dirname(__DIR__);
$flag = sys_get_temp_dir() . '/fezadan-sitemap.dirty';
$force = in_array('--force', $argv ?? [], true);

if (!$force && !file_exists($flag)) {
    exit(0);
}

try {
    define('ROOT', $rootDir);

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

    \App\Services\SitemapService::generateSitemapInternal();
    @unlink($flag);
    @mkdir(ROOT . '/storage/cron_heartbeat', 0755, true);
    @file_put_contents(ROOT . '/storage/cron_heartbeat/generate-sitemap.heartbeat', time());
    echo "[" . date('c') . "] Sitemap uretildi.\n";
} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('c') . '] Sitemap hatasi: ' . $e->getMessage() . "\n");
    exit(2);
}
