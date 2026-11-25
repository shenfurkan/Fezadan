<?php

namespace App\Services;

class MediaService
{
    /**
     * Generates OG Image for an article and uploads to R2.
     * @param \PDO $pdo
     * @param int $articleId
     * @return array ['url' => ..., 'path' => ...]
     * @throws \Exception
     */
    public function generateAndUploadOgImage(\PDO $pdo, int $articleId): array
    {
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.slug, au.name AS author_name, au.slug AS author_slug,
                   GROUP_CONCAT(c.name SEPARATOR ', ') AS categories
            FROM articles a
            LEFT JOIN authors au ON a.author_id = au.id
            LEFT JOIN article_categories ac ON a.id = ac.article_id
            LEFT JOIN categories c ON ac.category_id = c.id
            WHERE a.id = ?
            GROUP BY a.id
        ");
        $stmt->execute([$articleId]);
        $article = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$article) {
            throw new \Exception('Makale bulunamadı.');
        }

        require_once ROOT . '/app/Core/OgImage.php';
        $tmpFile = \App\Core\OgImage::generate(
            $article['title'],
            $article['author_name'] ?? 'FEZADAN',
            $article['categories'] ?? ''
        );

        if (!$tmpFile || !file_exists($tmpFile)) {
            throw new \Exception('OG görsel üretilemedi. GD veya font kontrol edilmeli.');
        }

        require_once ROOT . '/app/Core/R2Storage.php';
        $r2 = \App\Core\R2Storage::instance();
        $extension = strtolower(pathinfo($tmpFile, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'svg'], true)) {
            @unlink($tmpFile);
            throw new \Exception('OG görsel formatı desteklenmiyor.');
        }
        $contentType = $extension === 'svg' ? 'image/svg+xml' : 'image/png';
        $version = date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $objectKey = 'uploads/og/og-' . $article['slug'] . '-' . $version . '.' . $extension;
        $result = $r2->uploadFile($tmpFile, $objectKey, $contentType);

        @unlink($tmpFile);

        if ($result === null) {
            throw new \Exception('R2 yükleme başarısız.');
        }

        $pdo->prepare("UPDATE articles SET og_image = ? WHERE id = ?")->execute([$objectKey, $articleId]);

        $url = \Upload::assetUrl($objectKey);

        return ['url' => $url, 'path' => $objectKey];
    }
}
