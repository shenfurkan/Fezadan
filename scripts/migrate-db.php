<?php
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Core/Db.php';

try {
    $pdo = Db::pdo();
    echo "Starting database migration...\n";

            // Kendi kendini iyileştirme: önce MyISAM tablolarını transaction ve FK desteği için InnoDB'ye çevir
            try {
                $dbName = DB_NAME;
                $myisamTables = $pdo->query(
                    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                     WHERE TABLE_SCHEMA = '{$dbName}' AND ENGINE = 'MyISAM'"
                )->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($myisamTables as $table) {
                    $pdo->exec("ALTER TABLE `{$table}` ENGINE=InnoDB");
                    error_log("Self-healing: Converted {$table} from MyISAM to InnoDB");
                }
            } catch (\Exception $e) {
                error_log("InnoDB self-healing migration failed: " . $e->getMessage());
            }

            // Kendi kendini iyileştiren sütun kontrolleri
            try {
                // 'lang' sütunu var mı kontrol et
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'lang'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `lang` VARCHAR(10) NOT NULL DEFAULT 'TR'");
                }
                // 'meta_keywords' sütunu var mı kontrol et
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'meta_keywords'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `meta_keywords` TEXT NULL");
                }
                // 'seo_title' sütunu var mı kontrol et
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'seo_title'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `seo_title` VARCHAR(255) NULL");
                }
                // 'seo_description' sütunu var mı kontrol et
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'seo_description'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `seo_description` TEXT NULL");
                }
                // 'translation_of' sütunu var mı kontrol et
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'translation_of'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `translation_of` INT NULL DEFAULT NULL");
                }
                // 'login_attempts' tablosu var mı kontrol et (brute-force koruması)
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'login_attempts'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `login_attempts` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `ip_hash` varchar(64) NOT NULL,
                      `username_hash` varchar(64) DEFAULT NULL,
                      `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_login_attempts_ip` (`ip_hash`),
                      KEY `idx_login_attempts_time` (`attempt_time`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }
                // 'download_rate_limits' tablosu var mı kontrol et (notlar indirme hız sınırı)
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'download_rate_limits'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `download_rate_limits` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `ip_hash` varchar(64) NOT NULL,
                      `download_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_download_rate_ip` (`ip_hash`),
                      KEY `idx_download_rate_time` (`download_time`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // 'read_rate_limits' tablosu var mı kontrol et (makale okuma tekilleme)
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'read_rate_limits'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `read_rate_limits` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `ip_hash` varchar(64) NOT NULL,
                      `article_id` int(11) NOT NULL,
                      `hit_date` date NOT NULL,
                      `hit_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `uniq_read_rate_daily` (`ip_hash`, `article_id`, `hit_date`),
                      KEY `idx_read_rate_ip` (`ip_hash`),
                      KEY `idx_read_rate_article` (`article_id`),
                      KEY `idx_read_rate_time` (`hit_time`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // Eski şemalarda da okuma tekillemenin atomik olduğundan emin ol.
                try {
                    $indexCheck = $pdo->query("SHOW INDEX FROM `read_rate_limits` WHERE Key_name = 'uniq_read_rate_daily'");
                    if ($indexCheck && $indexCheck->rowCount() === 0) {
                        $pdo->exec("DELETE rr1 FROM `read_rate_limits` rr1
                            INNER JOIN `read_rate_limits` rr2
                                ON rr1.ip_hash = rr2.ip_hash
                                AND rr1.article_id = rr2.article_id
                                AND rr1.hit_date = rr2.hit_date
                                AND rr1.id > rr2.id");
                        $pdo->exec("ALTER TABLE `read_rate_limits` ADD UNIQUE KEY `uniq_read_rate_daily` (`ip_hash`, `article_id`, `hit_date`)");
                    }
                } catch (\Exception $e) {
                    error_log("read_rate_limits unique index self-healing failed: " . $e->getMessage());
                }

                // login_attempts'te 'username_hash' sütunu var mı kontrol et (eski şemada eksik olabilir)
                $query = $pdo->query("SHOW COLUMNS FROM `login_attempts` LIKE 'username_hash'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `login_attempts` ADD COLUMN `username_hash` VARCHAR(64) NULL DEFAULT NULL");
                }
                // articles'da 'publish_at' sütunu var mı kontrol et (planlı yayın)
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'publish_at'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `publish_at` DATETIME NULL DEFAULT NULL");
                    // status'e 'scheduled' ekle — yalnızca hala ENUM kullanılıyor ve değer eksikse
                    $statusCol = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'status'")->fetch(\PDO::FETCH_ASSOC);
                    if ($statusCol && stripos($statusCol['Type'] ?? '', 'enum') !== false && stripos($statusCol['Type'] ?? '', 'scheduled') === false) {
                        $pdo->exec("ALTER TABLE `articles` MODIFY COLUMN `status` ENUM('published','draft','scheduled') DEFAULT 'published'");
                    }
                }
                // admins'te 'role' sütunu var mı kontrol et (çoklu admin)
                $query = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'role'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `role` ENUM('superadmin','editor','viewer') NOT NULL DEFAULT 'superadmin'");
                }
                // admins'te 'email' sütunu var mı kontrol et (şifre kurtarma)
                $query = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'email'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL");
                }
                // admins'te benzersiz email indeksi var mı kontrol et
                $query = $pdo->query("SHOW INDEX FROM `admins` WHERE Key_name = 'uniq_admins_email'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD UNIQUE KEY `uniq_admins_email` (`email`)");
                }
                // admins'te 'author_id' sütunu var mı kontrol et (admin-yazar bağlantısı)
                $query = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'author_id'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `author_id` INT NULL DEFAULT NULL");
                    $pdo->exec("ALTER TABLE `admins` ADD KEY `idx_admins_author_id` (`author_id`)");
                }
                // articles'da 'og_image' sütunu var mı kontrol et (OG görsel üretimi)
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'og_image'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `og_image` VARCHAR(255) NULL DEFAULT NULL");
                }

                // articles'da 'audio_url' sütunu var mı kontrol et (metin-ses)
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'audio_url'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `audio_url` VARCHAR(255) NULL DEFAULT NULL");
                }

                // 'admin_passkeys' tablosu var mı kontrol et
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'admin_passkeys'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `admin_passkeys` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `admin_id` int(11) NOT NULL,
                      `credential_id` text NOT NULL,
                      `public_key` text NOT NULL,
                      `sign_count` int(11) NOT NULL DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_admin_id` (`admin_id`),
                      CONSTRAINT `fk_passkeys_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // 'admin_password_resets' tablosu var mı kontrol et
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'admin_password_resets'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `admin_password_resets` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `admin_id` int(11) NOT NULL,
                      `token_hash` char(64) NOT NULL,
                      `expires_at` datetime NOT NULL,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_admin_password_resets_token_hash` (`token_hash`),
                      KEY `idx_admin_password_resets_admin_id` (`admin_id`),
                      KEY `idx_admin_password_resets_expires_at` (`expires_at`),
                      CONSTRAINT `fk_admin_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                $tableCheck = $pdo->query("SHOW TABLES LIKE 'admin_password_reset_rate_limits'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `admin_password_reset_rate_limits` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `ip_hash` varchar(64) NOT NULL,
                      `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_admin_password_reset_rate_ip` (`ip_hash`),
                      KEY `idx_admin_password_reset_rate_time` (`attempt_time`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // 'authors' tablosu var mı kontrol et
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'authors'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `authors` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL,
                      `slug` varchar(255) NOT NULL,
                      `bio` text DEFAULT NULL,
                      `image_url` varchar(255) DEFAULT NULL,
                      `twitter` varchar(255) DEFAULT NULL,
                      `instagram` varchar(255) DEFAULT NULL,
                      `website` varchar(255) DEFAULT NULL,
                      `email` varchar(255) DEFAULT NULL,
                      `featured_articles` varchar(255) DEFAULT NULL,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `idx_authors_slug` (`slug`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // 'article_authors' tablosu var mı kontrol et (çoklu yazar)
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'article_authors'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `article_authors` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `article_id` int(11) NOT NULL,
                      `author_id` int(11) NOT NULL,
                      `display_order` int(11) NOT NULL DEFAULT 0,
                      PRIMARY KEY (`id`),
                      KEY `idx_article_authors_article` (`article_id`),
                      KEY `idx_article_authors_author` (`author_id`),
                      CONSTRAINT `fk_article_authors_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
                      CONSTRAINT `fk_article_authors_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // Kendi kendini iyileştirme: eksik yazar sütunlarını ekle (S57)
                $authorCols = [
                    'twitter'           => 'VARCHAR(255) NULL DEFAULT NULL',
                    'instagram'         => 'VARCHAR(255) NULL DEFAULT NULL',
                    'website'           => 'VARCHAR(255) NULL DEFAULT NULL',
                    'email'             => 'VARCHAR(255) NULL DEFAULT NULL',
                    'featured_articles' => 'VARCHAR(255) NULL DEFAULT NULL',
                ];
                foreach ($authorCols as $col => $def) {
                    $q = $pdo->query("SHOW COLUMNS FROM `authors` LIKE '$col'");
                    if ($q->rowCount() === 0) {
                        $pdo->exec("ALTER TABLE `authors` ADD COLUMN `$col` $def");
                    }
                }

                // Kendi kendini iyileştirme: tüm içerik tablolarında updated_at olduğundan emin ol (S56)
                foreach (['articles', 'notes', 'authors', 'portfolio_images', 'patch_notes'] as $t) {
                    $q = $pdo->query("SHOW COLUMNS FROM `$t` LIKE 'updated_at'");
                    if ($q->rowCount() === 0) {
                        $q2 = $pdo->query("SHOW TABLES LIKE '$t'");
                        if ($q2->rowCount() > 0) {
                            $pdo->exec("ALTER TABLE `$t` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
                        }
                    }
                }

                // 'article_corrections' tablosu var mı kontrol et (düzeltme günlüğü)
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'article_corrections'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `article_corrections` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `article_id` int(11) NOT NULL,
                      `correction_text` text NOT NULL,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_article_corrections_article` (`article_id`),
                      CONSTRAINT `fk_article_corrections_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }



                // 'article_reading_analytics' tablosu var mı kontrol et
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'article_reading_analytics'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `article_reading_analytics` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `article_id` int(11) NOT NULL,
                      `ip_hash` varchar(64) NOT NULL,
                      `max_scroll_depth` int(11) NOT NULL DEFAULT 0,
                      `seconds_spent` int(11) NOT NULL DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_reading_analytics_article` (`article_id`),
                      CONSTRAINT `fk_reading_analytics_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // Kendi kendini iyileştirme: portfolio_images'ı eski 'category' sütunundan 'type'a taşı
                try {
                    $hasCategory = $pdo->query("SHOW COLUMNS FROM `portfolio_images` LIKE 'category'")->rowCount() > 0;
                    $hasType     = $pdo->query("SHOW COLUMNS FROM `portfolio_images` LIKE 'type'")->rowCount() > 0;
                    if ($hasCategory && !$hasType) {
                        $pdo->exec("ALTER TABLE `portfolio_images` ADD COLUMN `type` VARCHAR(20) NOT NULL DEFAULT 'photo'");
                        $pdo->exec("UPDATE `portfolio_images` SET `type` = CASE WHEN `category` = 'drawing' THEN 'drawing' ELSE 'photo' END");
                        $pdo->exec("ALTER TABLE `portfolio_images` DROP COLUMN `category`");
                        error_log("Self-healing: Migrated portfolio_images category → type");
                    }
                    if ($hasCategory && $hasType) {
                        $pdo->exec("ALTER TABLE `portfolio_images` DROP COLUMN `category`");
                        error_log("Self-healing: Removed legacy category column from portfolio_images");
                    }
                } catch (\Exception $e) {
                    // Tablo henüz yok, yoksaymak güvenli
                }

                // 'portfolio_images' tablosu var mı kontrol et
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'portfolio_images'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `portfolio_images` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `type` enum('photo', 'drawing') NOT NULL,
                      `image_url` varchar(255) NOT NULL,
                      `title_tr` varchar(255) NOT NULL,
                      `title_en` varchar(255) DEFAULT NULL,
                      `description_tr` text DEFAULT NULL,
                      `description_en` text DEFAULT NULL,
                      `display_order` int(11) NOT NULL DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_portfolio_type` (`type`),
                      KEY `idx_portfolio_display_order` (`display_order`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // 'patch_notes' tablosu var mı kontrol et
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'patch_notes'");
                if ($tableCheck->rowCount() === 0) {
                    $pdo->exec("CREATE TABLE `patch_notes` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `version` varchar(20) NOT NULL,
                      `title` varchar(255) NOT NULL,
                      `content` text NOT NULL,
                      `author` varchar(50) DEFAULT 'Admin',
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // Tablo boşsa gallery-data.js'den ilk verileri ek
                $count = (int)$pdo->query("SELECT COUNT(*) FROM `portfolio_images`")->fetchColumn();
                if ($count === 0) {
                    $rootPath = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
                    $jsPath = $rootPath . '/public_html/furkan/assets/js/gallery-data.js';
                    if (is_file($jsPath)) {
                        $content = file_get_contents($jsPath);
                        $photoImages = [];
                        $artImages = [];

                        if (preg_match('/var PHOTOGRAPHY_IMAGES = \[(.*?)\];/s', $content, $photoMatch)) {
                            if (preg_match_all('/"([^"]+)"/', $photoMatch[1], $imgMatches)) {
                                $photoImages = $imgMatches[1];
                            }
                        }

                        if (preg_match('/var VISUAL_ART_IMAGES = \[(.*?)\];/s', $content, $artMatch)) {
                            if (preg_match_all('/"([^"]+)"/', $artMatch[1], $imgMatches)) {
                                $artImages = $imgMatches[1];
                            }
                        }

                        $stmt = $pdo->prepare("INSERT INTO `portfolio_images` (type, image_url, title_tr, title_en, display_order) VALUES (?, ?, ?, ?, ?)");

                        $order = 10;
                        foreach ($photoImages as $idx => $img) {
                            $filename = pathinfo($img, PATHINFO_FILENAME);
                            $title = ucwords(str_replace(['-', '_'], ' ', $filename));
                            $finalUrl = '/furkan/' . ltrim($img, '/');
                            $stmt->execute(['photo', $finalUrl, $title, $title, $order]);
                            $order += 10;
                        }

                        $order = 10;
                        foreach ($artImages as $idx => $img) {
                            $filename = pathinfo($img, PATHINFO_FILENAME);
                            $title = ucwords(str_replace(['-', '_'], ' ', $filename));
                            $finalUrl = '/furkan/' . ltrim($img, '/');
                            $stmt->execute(['drawing', $finalUrl, $title, $title, $order]);
                            $order += 10;
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("Database self-healing migration failed: " . $e->getMessage());
            }

            // Kendi kendini iyileştirme: Boş SEO alanlarını makale içeriğinden doldur
            try {
                $seoUpdated = 0;

                // seo_title: boşsa başlıktan kopyala
                $missing = $pdo->query("SELECT COUNT(*) FROM articles WHERE (seo_title IS NULL OR seo_title = '') AND title != ''")->fetchColumn();
                if ($missing > 0) {
                    $pdo->exec("UPDATE articles SET seo_title = title WHERE (seo_title IS NULL OR seo_title = '') AND title != ''");
                    $seoUpdated += (int) $missing;
                }

                // seo_description: boş olanları doldur VEYA HTML içeren değerleri strip_tags ile düzelt
                // SQL strip_tags yapamaz; PHP döngüsü kullanılmalı.
                $rows = $pdo->query(
                    "SELECT id, content FROM articles
                     WHERE content IS NOT NULL AND content != ''
                       AND (
                           seo_description IS NULL
                           OR seo_description = ''
                           OR seo_description LIKE '%<%'
                       )"
                )->fetchAll(\PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $stmtSeo = $pdo->prepare("UPDATE articles SET seo_description = ? WHERE id = ?");
                    foreach ($rows as $row) {
                        $clean = mb_substr(trim(strip_tags((string)$row['content'])), 0, 160);
                        if ($clean !== '') {
                            $stmtSeo->execute([$clean, $row['id']]);
                            $seoUpdated++;
                        }
                    }
                }

                // meta_keywords: boşsa başlığı kopyala (basit)
                $missing = $pdo->query("SELECT COUNT(*) FROM articles WHERE (meta_keywords IS NULL OR meta_keywords = '') AND title != ''")->fetchColumn();
                if ($missing > 0) {
                    $pdo->exec("UPDATE articles SET meta_keywords = title WHERE (meta_keywords IS NULL OR meta_keywords = '') AND title != ''");
                    $seoUpdated += (int) $missing;
                }

                if ($seoUpdated > 0) {
                    error_log("Self-healing: Populated SEO fields for $seoUpdated articles");
                }
            } catch (\Exception $e) {
                error_log("SEO auto-fill failed: " . $e->getMessage());
            }

            // Kendi kendini iyileştirme: Arama için FULLTEXT indeksleri
            // Tablo seviyesi kilitleri önlemek için ALGORITHM=INPLACE LOCK=NONE kullanır
            // (MariaDB 10.3+ / MySQL 5.6+ destekliyorsa).
            try {
                $ftArticles = $pdo->query(
                    "SHOW INDEX FROM `articles` WHERE Index_type = 'FULLTEXT'"
                )->rowCount();
                if ($ftArticles === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD FULLTEXT idx_ft_articles (title, content), ALGORITHM=INPLACE, LOCK=NONE");
                }
            } catch (\Exception $e) {
                // Sunucu desteklemiyorsa ALGORITHM ipucu olmadan düş
                try {
                    $pdo->exec("ALTER TABLE `articles` ADD FULLTEXT idx_ft_articles (title, content)");
                } catch (\Exception $e2) {
                    error_log("FULLTEXT index creation failed (articles): " . $e2->getMessage());
                }
            }
            try {
                $ftNotes = $pdo->query(
                    "SHOW INDEX FROM `notes` WHERE Index_type = 'FULLTEXT'"
                )->rowCount();
                if ($ftNotes === 0) {
                    $pdo->exec("ALTER TABLE `notes` ADD FULLTEXT idx_ft_notes (title, description), ALGORITHM=INPLACE, LOCK=NONE");
                }
            } catch (\Exception $e) {
                try {
                    $pdo->exec("ALTER TABLE `notes` ADD FULLTEXT idx_ft_notes (title, description)");
                } catch (\Exception $e2) {
                    error_log("FULLTEXT index creation failed (notes): " . $e2->getMessage());
                }
            }

            // PDF benzeri R2 anahtarına işaret etmeyen yerel/içe aktarılmış notlar için görünürlük kontrolü.
            try {
                $invalidNotePaths = $pdo->query("SELECT id, slug, r2_path FROM `notes` WHERE r2_path IS NULL OR r2_path = '' OR r2_path NOT REGEXP '^notlar/.+\\.pdf$' LIMIT 20")
                    ->fetchAll(\PDO::FETCH_ASSOC);
                if (!empty($invalidNotePaths)) {
                    error_log('Notes with non-PDF r2_path need review: ' . json_encode($invalidNotePaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            } catch (\Exception $e) {
                error_log("notes r2_path validation check failed: " . $e->getMessage());
            }
    echo "Database migration completed successfully.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
