<?php

class DailyArtwork
{
    private const TABLE = 'daily_artworks';
    private static $schemaReady = false;

    public static function today(): string
    {
        $timezone = function_exists('env_value') ? env_value('APP_TIMEZONE', 'Europe/Istanbul') : 'Europe/Istanbul';
        try {
            return (new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->format('Y-m-d');
        } catch (\Throwable $e) {
            return date('Y-m-d');
        }
    }

    public static function ensureSchema(\PDO $pdo): void
    {
        if (self::$schemaReady) {
            return;
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS daily_artworks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                slug VARCHAR(191) NOT NULL,
                title VARCHAR(255) NOT NULL,
                artist VARCHAR(255) NOT NULL DEFAULT 'Bilinmeyen Sanatci',
                artist_bio TEXT NULL,
                date_display VARCHAR(100) NULL,
                medium VARCHAR(255) NULL,
                dimensions VARCHAR(255) NULL,
                image_url VARCHAR(500) NOT NULL,
                thumbnail_url VARCHAR(500) NULL,
                provider VARCHAR(100) NOT NULL DEFAULT '',
                external_id VARCHAR(100) NOT NULL DEFAULT '',
                external_url VARCHAR(500) NULL,
                description_en TEXT NULL,
                description_tr TEXT NULL,
                description_source ENUM('wikipedia','museum','template','manual') DEFAULT 'template',
                wikipedia_url VARCHAR(500) NULL,
                is_public_domain TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_daily_artworks_date (date),
                KEY idx_daily_artworks_slug (slug),
                KEY idx_daily_artworks_date (date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $columns = self::columns($pdo);
        $add = [
            'slug' => "ALTER TABLE daily_artworks ADD COLUMN slug VARCHAR(191) NULL AFTER date",
            'artist_bio' => "ALTER TABLE daily_artworks ADD COLUMN artist_bio TEXT NULL AFTER artist",
            'date_display' => "ALTER TABLE daily_artworks ADD COLUMN date_display VARCHAR(100) NULL AFTER artist_bio",
            'medium' => "ALTER TABLE daily_artworks ADD COLUMN medium VARCHAR(255) NULL AFTER date_display",
            'dimensions' => "ALTER TABLE daily_artworks ADD COLUMN dimensions VARCHAR(255) NULL AFTER medium",
            'thumbnail_url' => "ALTER TABLE daily_artworks ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER image_url",
            'provider' => "ALTER TABLE daily_artworks ADD COLUMN provider VARCHAR(100) NOT NULL DEFAULT '' AFTER thumbnail_url",
            'external_id' => "ALTER TABLE daily_artworks ADD COLUMN external_id VARCHAR(100) NOT NULL DEFAULT '' AFTER provider",
            'external_url' => "ALTER TABLE daily_artworks ADD COLUMN external_url VARCHAR(500) NULL AFTER external_id",
            'description_en' => "ALTER TABLE daily_artworks ADD COLUMN description_en TEXT NULL AFTER external_url",
            'description_tr' => "ALTER TABLE daily_artworks ADD COLUMN description_tr TEXT NULL AFTER description_en",
            'description_source' => "ALTER TABLE daily_artworks ADD COLUMN description_source ENUM('wikipedia','museum','template','manual') DEFAULT 'template' AFTER description_tr",
            'wikipedia_url' => "ALTER TABLE daily_artworks ADD COLUMN wikipedia_url VARCHAR(500) NULL AFTER description_source",
            'is_public_domain' => "ALTER TABLE daily_artworks ADD COLUMN is_public_domain TINYINT(1) DEFAULT 1 AFTER wikipedia_url",
            'created_at' => "ALTER TABLE daily_artworks ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_public_domain",
        ];

        foreach ($add as $column => $sql) {
            if (!isset($columns[$column])) {
                $pdo->exec($sql);
                $columns[$column] = true;
            }
        }

        self::backfillMissingSlugs($pdo);
        self::ensureIndex($pdo, 'idx_daily_artworks_slug', 'CREATE INDEX idx_daily_artworks_slug ON daily_artworks (slug)');
        self::$schemaReady = true;
    }

    public static function createSlug(string $title, string $date): string
    {
        $months = [
            '01' => 'ocak', '02' => 'subat', '03' => 'mart', '04' => 'nisan',
            '05' => 'mayis', '06' => 'haziran', '07' => 'temmuz', '08' => 'agustos',
            '09' => 'eylul', '10' => 'ekim', '11' => 'kasim', '12' => 'aralik',
        ];

        $parts = explode('-', $date);
        $month = $months[$parts[1] ?? ''] ?? 'tarih';
        $dateStr = (int)($parts[2] ?? 0) . '-' . $month . '-' . ($parts[0] ?? date('Y'));

        $title = mb_strtolower(trim($title), 'UTF-8');
        $title = strtr($title, [
            'ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c',
            'İ' => 'i', 'I' => 'i', 'Ğ' => 'g', 'Ü' => 'u', 'Ş' => 's', 'Ö' => 'o', 'Ç' => 'c',
        ]);
        $title = preg_replace('/[^a-z0-9]+/u', '-', $title);
        $title = trim(preg_replace('/-+/', '-', (string)$title), '-');
        if ($title === '') {
            $title = 'eser';
        }

        return substr('fezadan-gunun-resmi-' . $dateStr . '-' . $title, 0, 191);
    }

    public static function saveForDate(\PDO $pdo, array $artwork, string $date): ?array
    {
        self::ensureSchema($pdo);
        $artwork = self::normalize($artwork);
        $existing = self::findByDate($pdo, $date);
        $slug = self::uniqueSlug($pdo, self::createSlug($artwork['title'], $date), $existing['id'] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO daily_artworks
            (date, slug, title, artist, artist_bio, date_display, medium, dimensions, image_url, thumbnail_url, provider, external_id, external_url, description_en, description_tr, description_source, wikipedia_url, is_public_domain)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                slug = VALUES(slug),
                title = VALUES(title),
                artist = VALUES(artist),
                artist_bio = VALUES(artist_bio),
                date_display = VALUES(date_display),
                medium = VALUES(medium),
                dimensions = VALUES(dimensions),
                image_url = VALUES(image_url),
                thumbnail_url = VALUES(thumbnail_url),
                provider = VALUES(provider),
                external_id = VALUES(external_id),
                external_url = VALUES(external_url),
                description_en = VALUES(description_en),
                description_tr = VALUES(description_tr),
                description_source = VALUES(description_source),
                wikipedia_url = VALUES(wikipedia_url),
                is_public_domain = VALUES(is_public_domain)
        ");

        $stmt->execute([
            $date,
            $slug,
            $artwork['title'],
            $artwork['artist'],
            $artwork['artist_bio'],
            $artwork['date_display'],
            $artwork['medium'],
            $artwork['dimensions'],
            $artwork['image_url'],
            $artwork['thumbnail_url'],
            $artwork['provider'],
            $artwork['external_id'],
            $artwork['external_url'],
            $artwork['description_en'],
            $artwork['description_tr'],
            $artwork['description_source'],
            $artwork['wikipedia_url'],
            $artwork['is_public_domain'],
        ]);

        return self::findByDate($pdo, $date);
    }

    public static function deleteByDate(\PDO $pdo, string $date): void
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->prepare('DELETE FROM daily_artworks WHERE date = ?');
        $stmt->execute([$date]);
    }

    public static function findByDate(\PDO $pdo, string $date): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM daily_artworks WHERE date = ? LIMIT 1');
        $stmt->execute([$date]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function latest(\PDO $pdo): ?array
    {
        $stmt = $pdo->query('SELECT * FROM daily_artworks ORDER BY date DESC LIMIT 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function recentBefore(\PDO $pdo, string $date, int $limit = 30): array
    {
        $stmt = $pdo->prepare('SELECT * FROM daily_artworks WHERE date < ? ORDER BY date DESC LIMIT ?');
        $stmt->bindValue(1, $date);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findBySlug(\PDO $pdo, string $slug): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM daily_artworks WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function neighborSlugs(\PDO $pdo, string $date): array
    {
        $prevStmt = $pdo->prepare('SELECT slug FROM daily_artworks WHERE date < ? ORDER BY date DESC LIMIT 1');
        $prevStmt->execute([$date]);

        $nextStmt = $pdo->prepare('SELECT slug FROM daily_artworks WHERE date > ? ORDER BY date ASC LIMIT 1');
        $nextStmt->execute([$date]);

        return [
            'prev' => $prevStmt->fetchColumn() ?: null,
            'next' => $nextStmt->fetchColumn() ?: null,
        ];
    }

    public static function countAll(\PDO $pdo): int
    {
        return (int)$pdo->query('SELECT COUNT(*) FROM daily_artworks')->fetchColumn();
    }

    public static function page(\PDO $pdo, int $limit, int $offset): array
    {
        $stmt = $pdo->prepare('SELECT * FROM daily_artworks ORDER BY date DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function all(\PDO $pdo, int $limit = 365): array
    {
        $stmt = $pdo->prepare('SELECT * FROM daily_artworks ORDER BY date DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private static function normalize(array $artwork): array
    {
        $source = (string)($artwork['description_source'] ?? 'template');
        if (!in_array($source, ['wikipedia', 'museum', 'template', 'manual'], true)) {
            $source = 'template';
        }

        $title = trim((string)($artwork['title'] ?? ''));
        $artist = trim((string)($artwork['artist'] ?? ''));
        $image = trim((string)($artwork['image_url'] ?? ''));

        return [
            'title' => $title !== '' ? $title : 'İsimsiz',
            'artist' => $artist !== '' ? $artist : 'Bilinmeyen Sanatçı',
            'artist_bio' => $artwork['artist_bio'] ?? null,
            'date_display' => $artwork['date_display'] ?? null,
            'medium' => $artwork['medium'] ?? null,
            'dimensions' => $artwork['dimensions'] ?? null,
            'image_url' => $image !== '' ? $image : '/cdn/notlar-social-preview.png',
            'thumbnail_url' => $artwork['thumbnail_url'] ?? $image,
            'provider' => $artwork['provider'] ?? 'FEZADAN Galeri',
            'external_id' => $artwork['external_id'] ?? substr(sha1(($title ?: 'artwork') . microtime(true)), 0, 16),
            'external_url' => $artwork['external_url'] ?? null,
            'description_en' => $artwork['description_en'] ?? null,
            'description_tr' => $artwork['description_tr'] ?? null,
            'description_source' => $source,
            'wikipedia_url' => $artwork['wikipedia_url'] ?? null,
            'is_public_domain' => isset($artwork['is_public_domain']) ? (int)$artwork['is_public_domain'] : 1,
        ];
    }

    private static function columns(\PDO $pdo): array
    {
        $columns = [];
        foreach ($pdo->query('SHOW COLUMNS FROM daily_artworks')->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $columns[$row['Field']] = true;
        }
        return $columns;
    }

    private static function ensureIndex(\PDO $pdo, string $name, string $sql): void
    {
        $indexes = $pdo->query('SHOW INDEX FROM daily_artworks')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($indexes as $index) {
            if (($index['Key_name'] ?? '') === $name) {
                return;
            }
        }

        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            error_log('DailyArtwork index error: ' . $e->getMessage());
        }
    }

    private static function backfillMissingSlugs(\PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT id, date, title FROM daily_artworks WHERE slug IS NULL OR slug = '' ORDER BY id ASC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!$rows) {
            return;
        }

        $update = $pdo->prepare('UPDATE daily_artworks SET slug = ? WHERE id = ?');
        foreach ($rows as $row) {
            $base = self::createSlug((string)($row['title'] ?? 'eser'), (string)($row['date'] ?? self::today()));
            $update->execute([self::uniqueSlug($pdo, $base, (int)$row['id']), (int)$row['id']]);
        }
    }

    private static function uniqueSlug(\PDO $pdo, string $base, ?int $ignoreId = null): string
    {
        $base = trim($base, '-') ?: 'fezadan-gunun-resmi';
        $base = substr($base, 0, 191);
        $slug = $base;
        $suffix = 2;

        while (self::slugExists($pdo, $slug, $ignoreId)) {
            $tail = '-' . $suffix++;
            $slug = substr($base, 0, 191 - strlen($tail)) . $tail;
        }

        return $slug;
    }

    private static function slugExists(\PDO $pdo, string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId !== null) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM daily_artworks WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM daily_artworks WHERE slug = ?');
            $stmt->execute([$slug]);
        }

        return (int)$stmt->fetchColumn() > 0;
    }
}
