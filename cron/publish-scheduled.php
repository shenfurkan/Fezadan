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

    $pdo->beginTransaction();
    $scheduledStmt = $pdo->query("
        SELECT a.slug, a.lang, au.slug AS author_slug
        FROM articles a
        LEFT JOIN authors au ON au.id = a.author_id
        WHERE a.status = 'scheduled' AND a.publish_at <= NOW()
        FOR UPDATE
    ");
    $scheduledArticles = $scheduledStmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    if (!empty($scheduledArticles)) {
        $idsStmt = $pdo->query("SELECT id FROM articles WHERE status = 'scheduled' AND publish_at <= NOW() FOR UPDATE");
        $ids = array_map('intval', $idsStmt->fetchAll(PDO::FETCH_COLUMN));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "UPDATE articles
                 SET status = 'published', publish_at = NULL, updated_at = NOW()
                 WHERE id IN ({$placeholders}) AND status = 'scheduled'"
            );
            $stmt->execute($ids);
            $count = $stmt->rowCount();
        }
    }
    $pdo->commit();

    if ($count > 0) {
        echo "[" . date('c') . "] {$count} planli makale yayinlandi.\n";

        require_once ROOT . '/app/Core/Csrf.php';
        require_once ROOT . '/app/Core/Flash.php';
        require_once ROOT . '/app/Core/Upload.php';
        require_once ROOT . '/app/Core/Controller.php';
        require_once ROOT . '/app/Controllers/AdminController.php';
        require_once ROOT . '/app/Services/IndexNowService.php';
        if (file_exists(ROOT . '/vendor/autoload.php')) {
            require_once ROOT . '/vendor/autoload.php';
        }
        $indexNowUrls = [];
        foreach ($scheduledArticles as $article) {
            if (empty($article['slug'])) continue;
            $lang = strtolower((string)($article['lang'] ?? 'tr')) === 'en' ? 'en' : 'tr';
            $indexNowUrls[] = articleUrl($article['slug'], $lang);
        }
        \App\Services\IndexNowService::submit($indexNowUrls);
        @touch(sys_get_temp_dir() . '/fezadan-sitemap.dirty');
    }
    @mkdir(ROOT . '/storage/cron_heartbeat', 0755, true);
    @file_put_contents(ROOT . '/storage/cron_heartbeat/publish-scheduled.heartbeat', time());
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, '[' . date('c') . '] publish-scheduled hatasi: ' . $e->getMessage() . "\n");
    exit(2);
}
