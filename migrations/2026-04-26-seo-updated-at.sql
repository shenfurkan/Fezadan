-- ===================================================================
-- SEO: articles.updated_at — sitemap <lastmod> ve dateModified için
-- ===================================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles' AND COLUMN_NAME = 'updated_at');
SET @sql := IF(@col = 0,
  'ALTER TABLE articles ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE articles SET updated_at = created_at WHERE updated_at IS NULL;

-- Notes için de aynı (opsiyonel ama tutarlılık için)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = 'updated_at');
SET @sql := IF(@col = 0,
  'ALTER TABLE notes ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE notes SET updated_at = created_at WHERE updated_at IS NULL;
