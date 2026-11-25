<?php
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Core/Db.php';

try {
    $pdo = Db::pdo();
    echo "Starting database migration...\n";

    // Mail invitations must be available before the new public/admin routes deploy.
    // Keep failures outside the legacy best-effort block so deploy stops safely.
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'mail_invitations'");
    if ($tableCheck->rowCount() === 0) {
        $pdo->exec("CREATE TABLE `mail_invitations` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `code_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `local_part` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `active_address` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
            `quota_mb` int unsigned NOT NULL DEFAULT 250,
            `status` varchar(16) NOT NULL DEFAULT 'pending',
            `created_by` int NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` datetime NOT NULL,
            `used_at` datetime DEFAULT NULL,
            `attempt_id` char(32) DEFAULT NULL,
            `attempt_started_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_mail_invite_code` (`code_hash`),
            UNIQUE KEY `uniq_mail_invite_address` (`active_address`),
            KEY `idx_mail_invite_expiry` (`status`, `expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'mail_invite_rate_limits'");
    if ($tableCheck->rowCount() === 0) {
        $pdo->exec("CREATE TABLE `mail_invite_rate_limits` (
            `scope_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `window_start` bigint NOT NULL,
            `attempts` int unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`scope_hash`, `window_start`),
            KEY `idx_mail_invite_rate_window` (`window_start`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

            // Self-healing: convert MyISAM tables to InnoDB first for transaction & FK support
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

            // Self-healing column checks
            try {
                // Check if 'lang' column exists
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'lang'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `lang` VARCHAR(10) NOT NULL DEFAULT 'TR'");
                }
                // Check if 'meta_keywords' column exists
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'meta_keywords'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `meta_keywords` TEXT NULL");
                }
                // Check if 'seo_title' column exists
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'seo_title'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `seo_title` VARCHAR(255) NULL");
                }
                // Check if 'seo_description' column exists
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'seo_description'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `seo_description` TEXT NULL");
                }
                // Check if 'translation_of' column exists
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'translation_of'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `translation_of` INT NULL DEFAULT NULL");
                }
                // Check if 'login_attempts' table exists (brute-force protection)
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
                // Check if 'download_rate_limits' table exists (notlar download rate limiting)
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

                // Retired local readership tables/data are left intact, but no longer created or modified.

                // Check if 'username_hash' column exists on login_attempts (may be missing from older schema)
                $query = $pdo->query("SHOW COLUMNS FROM `login_attempts` LIKE 'username_hash'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `login_attempts` ADD COLUMN `username_hash` VARCHAR(64) NULL DEFAULT NULL");
                }
                // Check if 'publish_at' column exists on articles (scheduled publishing)
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'publish_at'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `publish_at` DATETIME NULL DEFAULT NULL");
                    // Add 'scheduled' to status: only if still using ENUM and missing the value
                    $statusCol = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'status'")->fetch(\PDO::FETCH_ASSOC);
                    if ($statusCol && stripos($statusCol['Type'] ?? '', 'enum') !== false && stripos($statusCol['Type'] ?? '', 'scheduled') === false) {
                        $pdo->exec("ALTER TABLE `articles` MODIFY COLUMN `status` ENUM('published','draft','scheduled') DEFAULT 'published'");
                    }
                }
                // Check if 'role' column exists on admins (multi-admin)
                $query = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'role'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `role` ENUM('superadmin','editor','viewer') NOT NULL DEFAULT 'superadmin'");
                }
                // Check if 'email' column exists on admins (password recovery)
                $query = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'email'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL");
                }
                // Check if a unique email index exists on admins
                $query = $pdo->query("SHOW INDEX FROM `admins` WHERE Key_name = 'uniq_admins_email'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD UNIQUE KEY `uniq_admins_email` (`email`)");
                }
                // Check if 'author_id' column exists on admins (admin-to-author linkage)
                $query = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'author_id'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `author_id` INT NULL DEFAULT NULL");
                    $pdo->exec("ALTER TABLE `admins` ADD KEY `idx_admins_author_id` (`author_id`)");
                }
                // Check if 'og_image' column exists on articles (OG image generation)
                $query = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'og_image'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD COLUMN `og_image` VARCHAR(255) NULL DEFAULT NULL");
                }

                // Check if 'admin_passkeys' table exists
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

                // Check if 'admin_password_resets' table exists
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

                // Check if 'authors' table exists
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

                // Check if 'article_authors' table exists (multi-author)
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

                // Self-healing: add missing author columns (S57)
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

                // Self-healing: ensure updated_at exists on all content tables (S56)
                foreach (['articles', 'notes', 'authors', 'portfolio_images', 'patch_notes'] as $t) {
                    $q = $pdo->query("SHOW COLUMNS FROM `$t` LIKE 'updated_at'");
                    if ($q->rowCount() === 0) {
                        $q2 = $pdo->query("SHOW TABLES LIKE '$t'");
                        if ($q2->rowCount() > 0) {
                            $pdo->exec("ALTER TABLE `$t` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
                        }
                    }
                }

                // Check if 'article_corrections' table exists (corrections log)
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



                // Self-healing: migrate portfolio_images from old 'category' column to 'type'
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
                    // Table might not exist yet, safe to ignore
                }

                // Check if 'portfolio_images' table exists
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
                      `deleted_at` timestamp NULL DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `idx_portfolio_type` (`type`),
                      KEY `idx_portfolio_display_order` (`display_order`),
                      KEY `idx_portfolio_deleted_at` (`deleted_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // Self-healing: add missing deleted_at column to portfolio_images
                $query = $pdo->query("SHOW COLUMNS FROM `portfolio_images` LIKE 'deleted_at'");
                if ($query->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `portfolio_images` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL");
                    $pdo->exec("ALTER TABLE `portfolio_images` ADD KEY `idx_portfolio_deleted_at` (`deleted_at`)");
                    error_log("Self-healing: Added deleted_at column to portfolio_images");
                }

                // Check if 'patch_notes' table exists
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

                // Self-healing: purge legacy placeholder images (zoomin.jpg, hkmodular.png)
                $pdo->exec("DELETE FROM `portfolio_images` WHERE image_url LIKE '%zoomin.jpg%' OR image_url LIKE '%hkmodular.png%'");
            } catch (\Exception $e) {
                error_log("Database self-healing migration failed: " . $e->getMessage());
            }

            // Self-healing: Fill empty SEO fields from article content
            try {
                $seoUpdated = 0;

                // seo_title: copy from title if empty
                $missing = $pdo->query("SELECT COUNT(*) FROM articles WHERE (seo_title IS NULL OR seo_title = '') AND title != ''")->fetchColumn();
                if ($missing > 0) {
                    $pdo->exec("UPDATE articles SET seo_title = title WHERE (seo_title IS NULL OR seo_title = '') AND title != ''");
                    $seoUpdated += (int) $missing;
                }

                // seo_description: fill empty OR fix HTML-containing values via PHP strip_tags
                // SQL cannot strip_tags; must use PHP loop.
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

                // meta_keywords: copy title if empty (simple)
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

            // Self-healing: FULLTEXT indexes for search
            // Uses ALGORITHM=INPLACE LOCK=NONE where supported (MariaDB 10.3+ / MySQL 5.6+)
            // to avoid table-level locks on large article/note tables.
            try {
                $ftArticles = $pdo->query(
                    "SHOW INDEX FROM `articles` WHERE Index_type = 'FULLTEXT'"
                )->rowCount();
                if ($ftArticles === 0) {
                    $pdo->exec("ALTER TABLE `articles` ADD FULLTEXT idx_ft_articles (title, content), ALGORITHM=INPLACE, LOCK=NONE");
                }
            } catch (\Exception $e) {
                // Fall back without ALGORITHM hint if the server doesn't support it
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

            // articles.slug carried two identical UNIQUE indexes (`slug`, `uniq_slug`) from an old schema.
            // Drop the duplicate only while both exist as single-column keys, so slugs always stay unique.
            try {
                $uniqueArticleKeys = [];
                foreach ($pdo->query("SHOW INDEX FROM `articles` WHERE Non_unique = 0")->fetchAll(\PDO::FETCH_ASSOC) as $index) {
                    $uniqueArticleKeys[$index['Key_name']][] = $index['Column_name'];
                }
                if (($uniqueArticleKeys['slug'] ?? null) === ['slug'] && ($uniqueArticleKeys['uniq_slug'] ?? null) === ['slug']) {
                    $pdo->exec("ALTER TABLE `articles` DROP INDEX `slug`");
                }
            } catch (\Exception $e) {
                error_log("Duplicate articles.slug index cleanup failed: " . $e->getMessage());
            }

            // Visibility check for local/imported notes that do not point at a PDF-like R2 key.
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
