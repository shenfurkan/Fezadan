<?php

namespace App\Services;

class AuthorService
{
    public function getAllAuthors(\PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM authors ORDER BY name ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllAuthorArticles(\PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT id, title, author_id FROM articles ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function storeAuthor(\PDO $pdo, array $data, array $files): void
    {
        $id           = (int)($data['id'] ?? 0);
        $name         = trim($data['name'] ?? 'isimsiz') ?: 'isimsiz';
        $bio          = trim($data['bio'] ?? '');
        $oldImagePath = trim($data['current_image'] ?? '');
        $image_path   = $oldImagePath;
        $twitter      = trim($data['twitter'] ?? '');
        $instagram    = trim($data['instagram'] ?? '');
        $website      = trim($data['website'] ?? '');
        $email        = trim($data['email'] ?? '');
        $featured_str = implode(',', array_map('intval', (array)($data['featured'] ?? [])));

        $newImageStored = null;

        require_once ROOT . '/app/Core/Controller.php'; // For uniqueSlug/createSlug if needed?
        // Controller'da uniqueSlug ve createSlug var ama AuthorService Controller'dan türemiyor. Slug üretimini burada uygula veya yardımcı çağır.
        $slug = $this->createSlug($name);
        $slug = $this->uniqueSlug($pdo, 'authors', $slug, $id ?: null);

        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $storedPath = \App\Core\Upload::saveImageToR2($files['image'], 'uploads/authors', 'author_', 5242880, $slug);
            if ($storedPath !== null) {
                $image_path     = $storedPath;
                $newImageStored = $storedPath;
            }
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE authors SET name = ?, slug = ?, bio = ?, image_url = ?, twitter = ?, instagram = ?, website = ?, email = ?, featured_articles = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $bio, $image_path, $twitter, $instagram, $website, $email, $featured_str, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO authors (name, slug, bio, image_url, twitter, instagram, website, email, featured_articles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $bio, $image_path, $twitter, $instagram, $website, $email, $featured_str]);
        }

        // Yeni resim yüklendiyse eskiyi temizle
        if ($newImageStored !== null && $oldImagePath !== '' && $oldImagePath !== $image_path) {
            $this->safeUnlinkUpload($oldImagePath);
        }
    }

    public function deleteAuthor(\PDO $pdo, int $id): void
    {
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT image_url FROM authors WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetchColumn();

            $pdo->prepare("DELETE FROM authors WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE admins SET author_id = NULL WHERE author_id = ?")->execute([$id]);

            if ($img) {
                $this->safeUnlinkUpload($img);
            }
        }
    }

    private function safeUnlinkUpload(string $path): void
    {
        if ($path === '') return;
        if (preg_match('#^https?://#i', $path)) {
            $path = (string)parse_url($path, PHP_URL_PATH);
        }
        $path = ltrim($path, '/');
        if (strpos($path, 'uploads/authors/') !== 0) {
            return;
        }
        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2->deleteObject($path);
        } catch (\Throwable $e) {
            // Silme hatalarını yoksay
        }
    }

    private function createSlug(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(
            ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'],
            ['c', 'g', 'i', 'o', 's', 'u'],
            $str
        );
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', '-', $str);
        return trim($str, '-');
    }

    private function uniqueSlug(\PDO $pdo, string $table, string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $count = 2;
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($stmt->fetchColumn()) {
            $slug = $base . '-' . $count;
            $params[0] = $slug;
            $stmt->execute($params);
            $count++;
        }
        return $slug;
    }
}
