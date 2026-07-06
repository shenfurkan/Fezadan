<?php
/**
 * Cron: Planlanmış makaleleri yayınla.
 *
 * Kullanım (örn. her dakika):
 *   * * * * * /usr/bin/php /var/www/html/cron/publish-scheduled.php >/dev/null 2>&1
 *
 * `publish_at <= NOW()` olan 'scheduled' makaleleri 'published' yapar.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI ortamında çalıştırılmalı.\n");
    exit(1);
}

define('ROOT', dirname(__DIR__));

try {
    require_once ROOT . '/app/Config/config.php';
    require_once ROOT . '/app/Core/Db.php';

    $pdo = Db::pdo();

    $stmt = $pdo->prepare("
        UPDATE articles 
        SET status = 'published', publish_at = NULL, updated_at = NOW()
        WHERE status = 'scheduled' AND publish_at <= NOW()
    ");
    $stmt->execute();
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo "[" . date('c') . "] {$count} planli makale yayinlandi.\n";

        require_once ROOT . '/app/Core/Csrf.php';
        require_once ROOT . '/app/Core/Flash.php';
        require_once ROOT . '/app/Core/Upload.php';
        require_once ROOT . '/app/Controllers/AdminController.php';
        if (file_exists(ROOT . '/vendor/autoload.php')) {
            require_once ROOT . '/vendor/autoload.php';
        }
        @touch(sys_get_temp_dir() . '/fezadan-sitemap.dirty');
    }
    @mkdir(ROOT . '/storage/cron_heartbeat', 0755, true);
    @file_put_contents(ROOT . '/storage/cron_heartbeat/publish-scheduled.heartbeat', time());
} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('c') . '] publish-scheduled hatasi: ' . $e->getMessage() . "\n");
    exit(2);
}
