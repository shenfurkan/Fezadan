<?php

namespace App\Services;

class SystemHealthService
{
    public function runHealthChecks(\PDO $pdo, array $stats): array
    {
        $forceRefresh = isset($_GET['scan']) && $_GET['scan'] == '1';

        if (!$forceRefresh) {
            $stats['overall_status'] = 'TARAMA BEKLENİYOR';
            $stats['overall_status_type'] = 'info';
            return [[['label' => 'SİSTEM TARAMASI', 'status' => 'info', 'message' => 'Tarama henüz başlatılmadı', 'detail' => 'Taramayı başlatmak için SİSTEMİ TARA butonuna tıklayın.']], $stats];
        }

        $healthChecks = [];
        $warnCount = 0;
        $failCount = 0;
        $rootPath = defined('ROOT') ? ROOT : dirname(__DIR__, 2);

        $healthChecks[] = ['label' => 'VERITABANI', 'status' => 'ok', 'message' => 'SELECT 1 basarili', 'detail' => 'PDO baglantisi aktif'];

        try {
            $totalScheduled = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'scheduled'")->fetchColumn();
            $delayedScheduled = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'scheduled' AND publish_at <= NOW()")->fetchColumn();
            if ($delayedScheduled > 0) {
                $healthChecks[] = ['label' => 'GECIKMIS SCHEDULED', 'status' => 'warn', 'message' => $delayedScheduled . ' makale gecikmis', 'detail' => 'Cron calismamis olabilir'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'SCHEDULED', 'status' => 'ok', 'message' => $totalScheduled . ' zamanli makale', 'detail' => 'Gecikme yok'];
            }
            $stats['total_scheduled'] = $totalScheduled;
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'SCHEDULED', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        $sitemapPath = $rootPath . '/public_html/sitemap.xml';
        if (file_exists($sitemapPath)) {
            $sitemapSize = filesize($sitemapPath);
            $sitemapAge = time() - filemtime($sitemapPath);
            $ageText = $sitemapAge < 60 ? 'az once' : ($sitemapAge < 3600 ? floor($sitemapAge / 60) . ' dk once' : ($sitemapAge < 86400 ? floor($sitemapAge / 3600) . ' saat once' : floor($sitemapAge / 86400) . ' gun once'));
            $healthChecks[] = ['label' => 'SITEMAP DOSYASI', 'status' => 'ok', 'message' => 'Mevcut (' . round($sitemapSize / 1024, 1) . ' KB)', 'detail' => 'Son guncelleme: ' . $ageText];
        } else {
            $healthChecks[] = ['label' => 'SITEMAP DOSYASI', 'status' => 'fail', 'message' => 'Bulunamadi', 'detail' => 'Manuel rezenerasyon gerekli'];
            $failCount++;
        }

        $dirtyFlag = sys_get_temp_dir() . '/fezadan-sitemap.dirty';
        if (file_exists($dirtyFlag)) {
            $healthChecks[] = ['label' => 'SITEMAP DIRTY FLAG', 'status' => 'warn', 'message' => 'Rejenerasyon bekleniyor', 'detail' => 'Veri degisikligi yapildi'];
            $warnCount++;
        } else {
            $healthChecks[] = ['label' => 'SITEMAP DIRTY FLAG', 'status' => 'ok', 'message' => 'Temiz', 'detail' => 'Sitemap guncel'];
        }

        if (file_exists($sitemapPath) && filesize($sitemapPath) > 100) {
            $sitemapContent = file_get_contents($sitemapPath);
            $urlCount = preg_match_all('/<url\b/i', $sitemapContent);
            $sitemapIndexCount = preg_match_all('/<sitemap\b/i', $sitemapContent);
            $totalSitemapEntries = $urlCount + $sitemapIndexCount;
            if ($totalSitemapEntries > 0) {
                $healthChecks[] = ['label' => 'SITEMAP URL SAYISI', 'status' => 'ok', 'message' => $totalSitemapEntries . ' kayit indekslenmis', 'detail' => 'url: ' . $urlCount . ', sitemap: ' . $sitemapIndexCount];
            } else {
                $healthChecks[] = ['label' => 'SITEMAP URL SAYISI', 'status' => 'warn', 'message' => 'Sitemap kayitsiz', 'detail' => 'URL veya sitemapindex kaydi yok'];
                $warnCount++;
            }
        } else {
            $healthChecks[] = ['label' => 'SITEMAP URL SAYISI', 'status' => 'warn', 'message' => 'Sitemap bos veya cok kucuk', 'detail' => 'Rejenerasyon gerekli'];
            $warnCount++;
        }

        require_once ROOT . '/app/Controllers/SeoController.php';
        if (class_exists('SeoController') && method_exists('SeoController', 'robots')) {
            $healthChecks[] = ['label' => 'ROBOTS.TXT', 'status' => 'ok', 'message' => 'Mevcut ve calisiyor', 'detail' => 'Dinamik uretim aktif'];
        } else {
            $healthChecks[] = ['label' => 'ROBOTS.TXT', 'status' => 'fail', 'message' => 'SeoController eksik', 'detail' => 'robots.txt uretilemiyor'];
            $failCount++;
        }

        try {
            $seoMissingTitle = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (seo_title IS NULL OR seo_title = '')")->fetchColumn();
            $seoMissingDesc = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (seo_description IS NULL OR seo_description = '')")->fetchColumn();
            $seoMissingKeywords = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (meta_keywords IS NULL OR meta_keywords = '')")->fetchColumn();
            $seoMissingOg = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (og_image IS NULL OR og_image = '') AND (image_url IS NULL OR image_url = '')")->fetchColumn();

            $seoIssues = [];
            if ($seoMissingTitle > 0) $seoIssues[] = 'seo_title: ' . $seoMissingTitle;
            if ($seoMissingDesc > 0) $seoIssues[] = 'seo_description: ' . $seoMissingDesc;
            if ($seoMissingKeywords > 0) $seoIssues[] = 'meta_keywords: ' . $seoMissingKeywords;
            if ($seoMissingOg > 0) $seoIssues[] = 'og/social image: ' . $seoMissingOg;

            if (empty($seoIssues)) {
                $healthChecks[] = ['label' => 'SEO EKSİKLERİ', 'status' => 'ok', 'message' => 'Tum alanlar dolu', 'detail' => 'Indeksleme icin hazir'];
            } else {
                $healthChecks[] = ['label' => 'SEO EKSİKLERİ', 'status' => 'warn', 'message' => count($seoIssues) . ' alan eksik', 'detail' => implode(', ', $seoIssues)];
                $warnCount++;
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'SEO EKSİKLERİ', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $orphanArticles = $pdo->query("
                SELECT COUNT(DISTINCT a.id)
                FROM articles a
                LEFT JOIN article_categories ac ON a.id = ac.article_id
                WHERE ac.article_id IS NULL AND a.status = 'published'
            ")->fetchColumn();
            if ($orphanArticles > 0) {
                $healthChecks[] = ['label' => 'YETIM MAKALE', 'status' => 'warn', 'message' => $orphanArticles . ' makale kategorisiz', 'detail' => 'Kategori atanmamis'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'YETIM MAKALE', 'status' => 'ok', 'message' => 'Tum makaleler kategorili', 'detail' => 'Kategori atamasi tamam'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'YETIM MAKALE', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $lastModStmt = $pdo->query("
                SELECT MAX(lastmod) FROM (
                    SELECT MAX(COALESCE(updated_at, created_at)) AS lastmod FROM articles WHERE status = 'published'
                    UNION
                    SELECT MAX(COALESCE(updated_at, created_at)) AS lastmod FROM notes
                ) AS t
            ");
            $lastModTime = $lastModStmt->fetchColumn();
            if ($lastModTime) {
                $lastModAge = time() - strtotime($lastModTime);
                $lastModText = $lastModAge < 60 ? 'az once' : ($lastModAge < 3600 ? floor($lastModAge / 60) . ' dk once' : ($lastModAge < 86400 ? floor($lastModAge / 3600) . ' saat once' : floor($lastModAge / 86400) . ' gun once'));
                $healthChecks[] = ['label' => 'SON VERI DEGISIKLIGI', 'status' => 'info', 'message' => $lastModText, 'detail' => 'Son makale/not guncellemesi'];
            } else {
                $healthChecks[] = ['label' => 'SON VERI DEGISIKLIGI', 'status' => 'info', 'message' => 'Bilinmiyor', 'detail' => 'Kayit yok'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'SON VERI DEGISIKLIGI', 'status' => 'info', 'message' => 'Hata', 'detail' => $e->getMessage()];
        }

        try {
            $backupResult = $this->checkBackupStatus($rootPath);
            if ($backupResult['status'] === 'fail') {
                $failCount++;
            } elseif ($backupResult['status'] === 'warn') {
                $warnCount++;
            }
            $healthChecks[] = $backupResult;
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'BACKUP', 'status' => 'warn', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
            $warnCount++;
        }

        if (defined('CDN_URL') && defined('SITE_URL') && CDN_URL !== SITE_URL) {
            $healthChecks[] = ['label' => 'CDN YAPILANDIRMASI', 'status' => 'ok', 'message' => 'CDN aktif', 'detail' => 'Statik dosyalar CDN üzerinden'];
        } else {
            $healthChecks[] = ['label' => 'CDN YAPILANDIRMASI', 'status' => 'warn', 'message' => 'CDN yok', 'detail' => 'Statik dosyalar sunucudan gidiyor'];
            $warnCount++;
        }

        try {
            $errorCount = class_exists('AdminLog') ? \AdminLog::countByLevel('ERROR', 86400) : 0;
            if ($errorCount > 10) {
                $healthChecks[] = ['label' => 'HATA SAYISI (24h)', 'status' => 'warn', 'message' => $errorCount . ' hata', 'detail' => 'Son 24 saatte'];
                $warnCount++;
            } elseif ($errorCount > 0) {
                $healthChecks[] = ['label' => 'HATA SAYISI (24h)', 'status' => 'info', 'message' => $errorCount . ' hata', 'detail' => 'Son 24 saatte'];
            } else {
                $healthChecks[] = ['label' => 'HATA SAYISI (24h)', 'status' => 'ok', 'message' => 'Hata yok', 'detail' => 'Son 24 saat temiz'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'HATA SAYISI (24h)', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        try {
            $emptyContent = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (content IS NULL OR content = '')")->fetchColumn();
            if ($emptyContent > 0) {
                $healthChecks[] = ['label' => 'BOS ICERIK', 'status' => 'warn', 'message' => $emptyContent . ' makale', 'detail' => 'content kolonu bos'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'BOS ICERIK', 'status' => 'ok', 'message' => 'Tum makaleler dolu', 'detail' => 'content kolonu tam'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'BOS ICERIK', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $brokenTranslation = $pdo->query("
                SELECT COUNT(*) FROM articles
                WHERE translation_of IS NOT NULL
                AND translation_of NOT IN (SELECT id FROM articles)
            ")->fetchColumn();
            if ($brokenTranslation > 0) {
                $healthChecks[] = ['label' => 'KIRIK CEVIRI', 'status' => 'warn', 'message' => $brokenTranslation . ' gecersiz referans', 'detail' => 'translation_of ID bulunamadi'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'KIRIK CEVIRI', 'status' => 'ok', 'message' => 'Tum referanslar gecerli', 'detail' => 'Ceviri baglantilari tamam'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'KIRIK CEVIRI', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $slugConflicts = $pdo->query("
                SELECT slug, COUNT(*) as cnt FROM articles
                WHERE status = 'published'
                GROUP BY slug
                HAVING cnt > 1
            ")->fetchAll(\PDO::FETCH_COLUMN);
            if (count($slugConflicts) > 0) {
                $healthChecks[] = ['label' => 'SLUG CAKISMASI', 'status' => 'warn', 'message' => count($slugConflicts) . ' cift slug', 'detail' => 'Ayni slug birden fazla makalede'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'SLUG CAKISMASI', 'status' => 'ok', 'message' => 'Tum sluglar benzersiz', 'detail' => 'Cakisma yok'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'SLUG CAKISMASI', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $missingAuthor = $pdo->query("
                SELECT COUNT(*) FROM articles
                WHERE status = 'published'
                AND (author_id IS NULL OR author_id NOT IN (SELECT id FROM authors))
            ")->fetchColumn();
            if ($missingAuthor > 0) {
                $healthChecks[] = ['label' => 'EKSIK YAZAR', 'status' => 'warn', 'message' => $missingAuthor . ' makale', 'detail' => 'author_id gecersiz veya NULL'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'EKSIK YAZAR', 'status' => 'ok', 'message' => 'Tum yazarlar gecerli', 'detail' => 'Yazar referanslari tamam'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'EKSIK YAZAR', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $missingImage = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (image_url IS NULL OR image_url = '')")->fetchColumn();
            if ($missingImage > 0) {
                $healthChecks[] = ['label' => 'RESIMSIZ MAKALE', 'status' => 'warn', 'message' => $missingImage . ' makale', 'detail' => 'image_url bos (OG/sosyal paylasimda kotu gorunur)'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'RESIMSIZ MAKALE', 'status' => 'ok', 'message' => 'Tum makaleler resimli', 'detail' => 'image_url dolu'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'RESIMSIZ MAKALE', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $thinContent = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND CHAR_LENGTH(content) < 300")->fetchColumn();
            if ($thinContent > 0) {
                $healthChecks[] = ['label' => 'KISA ICERIK', 'status' => 'warn', 'message' => $thinContent . ' makale <300 karakter', 'detail' => 'Thin content SEO cezasi riski'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'KISA ICERIK', 'status' => 'ok', 'message' => 'Tum makaleler yeterli', 'detail' => '>=300 karakter'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'KISA ICERIK', 'status' => 'fail', 'message' => 'Sorgu hatasi', 'detail' => $e->getMessage()];
            $failCount++;
        }

        try {
            $failedLogins = $pdo->query("SELECT COUNT(*) FROM login_attempts WHERE attempt_time > NOW() - INTERVAL 3 HOUR")->fetchColumn();
            if ($failedLogins > 5) {
                $healthChecks[] = ['label' => 'FAILED LOGIN (3h)', 'status' => 'warn', 'message' => $failedLogins . ' basarisiz giris', 'detail' => 'Son 3 saatte'];
                $warnCount++;
            } elseif ($failedLogins > 0) {
                $healthChecks[] = ['label' => 'FAILED LOGIN (3h)', 'status' => 'info', 'message' => $failedLogins . ' basarisiz giris', 'detail' => 'Son 3 saatte'];
            } else {
                $healthChecks[] = ['label' => 'FAILED LOGIN (3h)', 'status' => 'ok', 'message' => 'Basarisiz giris yok', 'detail' => 'Son 3 saat temiz'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'FAILED LOGIN (3h)', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        $missingExtensions = [];
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            $missingExtensions[] = 'gd/imagick';
        }
        if (!extension_loaded('openssl')) {
            $missingExtensions[] = 'openssl';
        }
        if (!extension_loaded('mbstring')) {
            $missingExtensions[] = 'mbstring';
        }
        if (!extension_loaded('pdo_mysql')) {
            $missingExtensions[] = 'pdo_mysql';
        }
        if (empty($missingExtensions)) {
            $healthChecks[] = ['label' => 'PHP EKLENTILERI', 'status' => 'ok', 'message' => 'Tum eklentiler mevcut', 'detail' => 'gd/imagick, openssl, mbstring, pdo_mysql'];
        } else {
            $healthChecks[] = ['label' => 'PHP EKLENTILERI', 'status' => 'warn', 'message' => 'Eksik: ' . implode(', ', $missingExtensions), 'detail' => 'Bazi ozellikler calismayabilir'];
            $warnCount++;
        }

        try {
            $tempFree = @disk_free_space(sys_get_temp_dir());
            $logsFree = @disk_free_space($rootPath . '/logs');
            $tempFreeMB = $tempFree !== false ? round($tempFree / 1024 / 1024, 1) : 0;
            $logsFreeMB = $logsFree !== false ? round($logsFree / 1024 / 1024, 1) : 0;
            if ($tempFreeMB < 100 || $logsFreeMB < 100) {
                $healthChecks[] = ['label' => 'DISK ALANI', 'status' => 'warn', 'message' => 'Dusuk alan', 'detail' => 'temp: ' . $tempFreeMB . 'MB, logs: ' . $logsFreeMB . 'MB'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'DISK ALANI', 'status' => 'ok', 'message' => 'Yeterli alan', 'detail' => 'temp: ' . $tempFreeMB . 'MB, logs: ' . $logsFreeMB . 'MB'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'DISK ALANI', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        $logsDir = $rootPath . '/logs';
        $logsWritable = is_dir($logsDir) && is_writable($logsDir);
        if ($logsWritable) {
            $healthChecks[] = ['label' => 'LOGS YAZILABILIR', 'status' => 'ok', 'message' => 'logs/ yazilabilir', 'detail' => 'Hata kayitlari yazilabilir'];
        } else {
            $healthChecks[] = ['label' => 'LOGS YAZILABILIR', 'status' => 'warn', 'message' => 'logs/ yazilamaz', 'detail' => 'Hata kayitlari yazilamıyor'];
            $warnCount++;
        }

        try {
            $totalAdmins = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
            $adminsWithPasskey = $pdo->query("SELECT COUNT(DISTINCT admin_id) FROM admin_passkeys")->fetchColumn();
            $adminsWithoutPasskey = $totalAdmins - $adminsWithPasskey;
            if ($adminsWithoutPasskey > 0) {
                $healthChecks[] = ['label' => 'PASSKEY DURUMU', 'status' => 'info', 'message' => $adminsWithoutPasskey . ' admin passkeysiz', 'detail' => $totalAdmins . ' toplam admin'];
            } else {
                $healthChecks[] = ['label' => 'PASSKEY DURUMU', 'status' => 'ok', 'message' => 'Tum adminlerde passkey', 'detail' => $totalAdmins . ' toplam admin'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'PASSKEY DURUMU', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        try {
            $tableSize = $pdo->query("
                SELECT ROUND(SUM(data_length + index_length)/1024/1024, 1)
                FROM information_schema.tables
                WHERE table_schema = '" . DB_NAME . "' AND table_name = 'articles'
            ")->fetchColumn();
            $healthChecks[] = ['label' => 'ARTICLES TABLOSU', 'status' => 'info', 'message' => $tableSize . ' MB', 'detail' => 'Veri + index boyutu'];
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'ARTICLES TABLOSU', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        try {
            $mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
            $healthChecks[] = ['label' => 'MYSQL VERSIYONU', 'status' => 'info', 'message' => $mysqlVersion, 'detail' => 'Veritabani sunucusu'];
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'MYSQL VERSIYONU', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        try {
            $myisamCount = $pdo->query("
                SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = '" . DB_NAME . "' AND engine != 'InnoDB'
            ")->fetchColumn();
            if ($myisamCount > 0) {
                $healthChecks[] = ['label' => 'INNODB KONTROLU', 'status' => 'warn', 'message' => $myisamCount . ' MyISAM tablo', 'detail' => 'Self-healing basarisiz olabilir'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'INNODB KONTROLU', 'status' => 'ok', 'message' => 'Tum tablolar InnoDB', 'detail' => 'Transaction destegi aktif'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'INNODB KONTROLU', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        try {
            $ftArticles = $pdo->query("SHOW INDEX FROM articles WHERE Index_type = 'FULLTEXT'")->rowCount();
            $ftNotes = $pdo->query("SHOW INDEX FROM notes WHERE Index_type = 'FULLTEXT'")->rowCount();
            if ($ftArticles > 0 && $ftNotes > 0) {
                $healthChecks[] = ['label' => 'FULLTEXT INDEX', 'status' => 'ok', 'message' => 'Her iki tabloda da mevcut', 'detail' => 'articles + notes'];
            } else {
                $missing = [];
                if ($ftArticles === 0) $missing[] = 'articles';
                if ($ftNotes === 0) $missing[] = 'notes';
                $healthChecks[] = ['label' => 'FULLTEXT INDEX', 'status' => 'warn', 'message' => 'Eksik: ' . implode(', ', $missing), 'detail' => 'Arama performansi etkilenebilir'];
                $warnCount++;
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'FULLTEXT INDEX', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $healthChecks[] = ['label' => 'R2 BAGLANTISI', 'status' => 'ok', 'message' => 'R2 erisilebilir', 'detail' => 'Storage client aktif'];
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'R2 BAGLANTISI', 'status' => 'warn', 'message' => 'R2 baglantisi basarisiz', 'detail' => $e->getMessage()];
            $warnCount++;
        }

        try {
            $noteCount = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
            $articleWithImage = $pdo->query("SELECT COUNT(*) FROM articles WHERE image_url IS NOT NULL AND image_url != ''")->fetchColumn();
            $articleWithOg = $pdo->query("SELECT COUNT(*) FROM articles WHERE og_image IS NOT NULL AND og_image != ''")->fetchColumn();
            $authorImages = $pdo->query("SELECT COUNT(*) FROM authors WHERE image_url IS NOT NULL AND image_url != ''")->fetchColumn();
            $totalUploads = $noteCount + $articleWithImage + $articleWithOg + $authorImages;
            $healthChecks[] = ['label' => 'TOPLAM YUKLEME', 'status' => 'info', 'message' => $totalUploads . ' dosya', 'detail' => 'notes: ' . $noteCount . ', article images: ' . $articleWithImage . ', og: ' . $articleWithOg . ', author: ' . $authorImages];
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'TOPLAM YUKLEME', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        $healthChecks[] = ['label' => 'PHP / SUNUCU', 'status' => 'info', 'message' => 'PHP ' . PHP_VERSION, 'detail' => 'memory_limit: ' . ini_get('memory_limit') . ' | max_execution: ' . ini_get('max_execution_time') . 's'];

        // --- Extended health checks ---

        // Composer bağımlılıkları
        $vendorAutoload = $rootPath . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            $healthChecks[] = ['label' => 'COMPOSER', 'status' => 'ok', 'message' => 'vendor/autoload.php mevcut', 'detail' => 'Bagimliliklar yuklu'];
        } else {
            $healthChecks[] = ['label' => 'COMPOSER', 'status' => 'fail', 'message' => 'vendor/autoload.php eksik', 'detail' => 'composer install calistirilmali'];
            $failCount++;
        }

        // HTTPS kontrolü
        $siteIsHttps = (defined('SITE_URL') && strpos(SITE_URL, 'https://') === 0);
        if ($siteIsHttps) {
            $healthChecks[] = ['label' => 'HTTPS', 'status' => 'ok', 'message' => 'Site URL HTTPS', 'detail' => 'Guvenli baglanti'];
        } else {
            $healthChecks[] = ['label' => 'HTTPS', 'status' => 'warn', 'message' => 'Site URL HTTP', 'detail' => 'SITE_URL https olarak ayarlanmali'];
            $warnCount++;
        }

        // Güvenlik başlıkları kontrolü (yerel HTTP isteği ile)
        try {
            $healthUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36 FezadanHealthCheck/1.0';
            $ctx = stream_context_create(['http' => [
                'timeout' => 5,
                'ignore_errors' => true,
                'method' => 'HEAD',
                'header' => "User-Agent: {$healthUserAgent}\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
            ]]);
            $testUrl = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://localhost:8080') . '/tr';
            $headers = @get_headers($testUrl, 1, $ctx);
            $hstsPresent = false;
            $cspPresent = false;
            if ($headers) {
                $allHeaders = [];
                foreach ($headers as $k => $v) {
                    if (is_string($k)) {
                        $allHeaders[strtolower($k)] = is_array($v) ? implode(', ', $v) : $v;
                    } elseif (is_string($v) && strpos($v, ':') !== false) {
                        $parts = explode(':', $v, 2);
                        $allHeaders[strtolower(trim($parts[0]))] = trim($parts[1] ?? '');
                    }
                }
                $hstsPresent = isset($allHeaders['strict-transport-security']);
                $cspPresent = isset($allHeaders['content-security-policy']);
            }
            if ($hstsPresent && $cspPresent) {
                $healthChecks[] = ['label' => 'SECURITY HEADERS', 'status' => 'ok', 'message' => 'CSP ve HSTS aktif', 'detail' => 'Guvenlik basliklari mevcut'];
            } elseif ($cspPresent) {
                $healthChecks[] = ['label' => 'SECURITY HEADERS', 'status' => 'warn', 'message' => 'HSTS eksik', 'detail' => 'CSP var, Strict-Transport-Security yok'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'SECURITY HEADERS', 'status' => 'warn', 'message' => 'Guvenlik basliklari eksik', 'detail' => 'CSP ve HSTS kontrolu basarisiz'];
                $warnCount++;
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'SECURITY HEADERS', 'status' => 'info', 'message' => 'Kontrol yapilamadi', 'detail' => $e->getMessage()];
        }

        // APP_SALT / .security_salt kontrolü
        $saltFile = $rootPath . '/app/Config/.security_salt';
        if (file_exists($saltFile)) {
            $saltSize = filesize($saltFile);
            if ($saltSize >= 64) {
                $healthChecks[] = ['label' => 'APP SALT', 'status' => 'ok', 'message' => 'Guvenli salt mevcut', 'detail' => round($saltSize) . ' byte'];
            } else {
                $healthChecks[] = ['label' => 'APP SALT', 'status' => 'warn', 'message' => 'Salt cok kisa', 'detail' => $saltSize . ' byte, minimum 64 olmali'];
                $warnCount++;
            }
        } else {
            $saltFromEnv = env_value('APP_SECURITY_SALT', '');
            if (!empty($saltFromEnv) && $saltFromEnv !== 'change-me') {
                $healthChecks[] = ['label' => 'APP SALT', 'status' => 'ok', 'message' => 'Env tabanli salt', 'detail' => 'APP_SECURITY_SALT kullaniyor'];
            } else {
                $healthChecks[] = ['label' => 'APP SALT', 'status' => 'fail', 'message' => 'Salt uretilememis', 'detail' => '.security_salt eksik, APP_SECURITY_SALT tanimli degil'];
                $failCount++;
            }
        }

        // Cron kalp atışı — her cron işinin en son ne zaman başarıyla çalıştığını kontrol et
        try {
            $cronDir = $rootPath . '/storage/cron_heartbeat';
            $cronJobs = [
                'publish-scheduled'     => 'Zamanli makale yayinlama',
                'generate-sitemap'      => 'Sitemap uretimi',
                'backup-db'             => 'Veritabani yedegi',
            ];
            foreach ($cronJobs as $job => $label) {
                $hbFile = $cronDir . '/' . $job . '.heartbeat';
                if (is_file($hbFile)) {
                    $hbAge = time() - (int)@file_get_contents($hbFile);
                    $ageText = $hbAge < 60 ? 'az once' : ($hbAge < 3600 ? floor($hbAge / 60) . ' dk once' : ($hbAge < 86400 ? floor($hbAge / 3600) . ' saat once' : floor($hbAge / 86400) . ' gun once'));
                    $hbs = ($hbAge > 86400 * 2) ? 'warn' : 'ok';
                    $healthChecks[] = ['label' => 'CRON: ' . strtoupper($job), 'status' => $hbs, 'message' => $ageText, 'detail' => $label];
                    if ($hbs === 'warn') $warnCount++;
                } else {
                    $healthChecks[] = ['label' => 'CRON: ' . strtoupper($job), 'status' => 'warn', 'message' => 'Hic calismamis', 'detail' => $label];
                    $warnCount++;
                }
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'CRON HEARTBEAT', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        // LiteCaptcha erişilebilirliği
        if (defined('LITECAPTCHA_ENABLED') && LITECAPTCHA_ENABLED) {
            try {
                $litecaptchaUrl = rtrim(env_value('LITECAPTCHA_URL', 'https://litecaptcha.fezadan.org'), '/');
                $healthUserAgent = $healthUserAgent ?? 'Mozilla/5.0 FezadanHealthCheck/1.0';
                $ctx = stream_context_create(['http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                    'header' => "User-Agent: {$healthUserAgent}\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
                ]]);
                $lcHealthUrl = $litecaptchaUrl . '/?embed=1';
                $lcResponse = @file_get_contents($lcHealthUrl, false, $ctx);
                $lcLooksHealthy = $lcResponse !== false
                    && (stripos($lcResponse, 'LiteCaptcha') !== false
                        || stripos($lcResponse, 'window.LiteCaptchaConfig') !== false
                        || stripos($lcResponse, 'lc-widget') !== false);
                if ($lcLooksHealthy) {
                    $healthChecks[] = ['label' => 'LITECAPTCHA', 'status' => 'ok', 'message' => 'Erisilebilir', 'detail' => $lcHealthUrl];
                } else {
                    $healthChecks[] = ['label' => 'LITECAPTCHA', 'status' => 'warn', 'message' => 'Yanit alinamadi', 'detail' => $litecaptchaUrl];
                    $warnCount++;
                }
            } catch (\Throwable $e) {
                $healthChecks[] = ['label' => 'LITECAPTCHA', 'status' => 'warn', 'message' => 'Baglanti basarisiz', 'detail' => $e->getMessage()];
                $warnCount++;
            }
        } else {
            $healthChecks[] = ['label' => 'LITECAPTCHA', 'status' => 'info', 'message' => 'Devre disi', 'detail' => 'LITECAPTCHA_ENABLED=false'];
        }

        // .htaccess security files
        $htaccessFiles = [];
        if (file_exists($rootPath . '/app/.htaccess')) {
            $htaccessContent = (string) @file_get_contents($rootPath . '/app/.htaccess');
            if (stripos($htaccessContent, 'Deny') !== false || stripos($htaccessContent, 'Require all denied') !== false) {
                $htaccessFiles[] = 'app/ (korunuyor)';
            } else {
                $htaccessFiles[] = 'app/ (zayif)';
            }
        } else {
            $htaccessFiles[] = 'app/ (eksik!)';
            $failCount++;
        }
        if (file_exists($rootPath . '/public_html/.htaccess')) {
            $htaccessFiles[] = 'public_html/ (mevcut)';
        } else {
            $htaccessFiles[] = 'public_html/ (eksik!)';
            $failCount++;
        }
        $healthChecks[] = ['label' => '.HTACCESS', 'status' => $failCount > 0 ? 'fail' : 'ok', 'message' => implode(', ', $htaccessFiles), 'detail' => 'Guvenlik yapilandirmasi'];

        // Yinelenen makale başlıkları
        try {
            $dupTitles = $pdo->query("SELECT COUNT(*) FROM (SELECT title FROM articles WHERE status = 'published' GROUP BY title HAVING COUNT(*) > 1) AS dup")->fetchColumn();
            if ($dupTitles > 0) {
                $healthChecks[] = ['label' => 'DUPLICATE TITLES', 'status' => 'warn', 'message' => $dupTitles . ' yinelenen baslik', 'detail' => 'SEO icin benzersiz olmali'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'DUPLICATE TITLES', 'status' => 'ok', 'message' => 'Tum basliklar benzersiz', 'detail' => 'Yinelenen yok'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'DUPLICATE TITLES', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        // Boş kategoriler
        try {
            $emptyCats = $pdo->query("
                SELECT COUNT(*)
                FROM categories c
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM article_categories ac
                    JOIN articles a ON a.id = ac.article_id
                    WHERE ac.category_id = c.id
                      AND a.status = 'published'
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM note_categories nc
                    JOIN notes n ON n.id = nc.note_id
                    WHERE nc.category_id = c.id
                )
            ")->fetchColumn();
            if ($emptyCats > 0) {
                $healthChecks[] = ['label' => 'BOS KATEGORILER', 'status' => 'warn', 'message' => $emptyCats . ' bos kategori', 'detail' => 'Icerik atanmamis kategoriler'];
                $warnCount++;
            } else {
                $healthChecks[] = ['label' => 'BOS KATEGORILER', 'status' => 'ok', 'message' => 'Tum kategoriler dolu', 'detail' => 'Her kategoride makale var'];
            }
        } catch (\Throwable $e) {
            $healthChecks[] = ['label' => 'BOS KATEGORILER', 'status' => 'info', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }

        // Oturum güvenliği
        $sessionHttpOnly = ini_get('session.cookie_httponly');
        $sessionSecure = ini_get('session.cookie_secure');
        $sessionSameSite = ini_get('session.cookie_samesite');
        $sessionOk = ($sessionHttpOnly == '1' || $sessionHttpOnly === 'On' || $sessionHttpOnly === true);
        $secureOk = ($sessionSecure == '1' || $sessionSecure === 'On' || $sessionSecure === true);
        if ($sessionOk && $secureOk) {
            $healthChecks[] = ['label' => 'SESSION SECURITY', 'status' => 'ok', 'message' => 'HttpOnly + Secure aktif', 'detail' => 'SameSite: ' . ($sessionSameSite ?: 'default')];
        } elseif ($sessionOk) {
            $healthChecks[] = ['label' => 'SESSION SECURITY', 'status' => 'warn', 'message' => 'Secure cookie kapali', 'detail' => 'HttpOnly aktif, HTTPS gerekli'];
            $warnCount++;
        } else {
            $healthChecks[] = ['label' => 'SESSION SECURITY', 'status' => 'fail', 'message' => 'Oturum guvenligi zayif', 'detail' => 'HttpOnly=' . $sessionHttpOnly . ' Secure=' . $sessionSecure];
            $failCount++;
        }

        if ($failCount > 0) {
            $overallStatus = 'KRITIK HATA TESPIT EDILDI';
            $overallStatusType = 'fail';
        } elseif ($warnCount > 0) {
            $overallStatus = 'SISTEM STABIL (' . $warnCount . ' uyari)';
            $overallStatusType = 'warn';
        } else {
            $overallStatus = 'TUM SISTEMLER NORMAL';
            $overallStatusType = 'ok';
        }
        $healthChecks[] = ['label' => 'GENEL DURUM', 'status' => $overallStatusType, 'message' => $overallStatus, 'detail' => date('H:i:s') . ' - Tarama tamamlandi'];

        $stats['overall_status'] = $overallStatus;
        $stats['overall_status_type'] = $overallStatusType;

        return [$healthChecks, $stats];
    }

    private function checkBackupStatus(string $rootPath): array
    {
        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $ref = new \ReflectionClass($r2);
            $clientProp = $ref->getProperty('client');
            $clientProp->setAccessible(true);
            $client = $clientProp->getValue($r2);

            $bucketProp = $ref->getProperty('bucketName');
            $bucketProp->setAccessible(true);
            $bucket = $bucketProp->getValue($r2);

            $result = $client->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => 'backups/db-',
            ]);

            $backups = $result['Contents'] ?? [];
            if (empty($backups)) {
                return ['label' => 'BACKUP', 'status' => 'fail', 'message' => 'Hic yedek yok', 'detail' => 'R2 backups/ klasoru bos'];
            }

            usort($backups, function($a, $b) {
                $tsA = is_string($a['LastModified']) ? strtotime($a['LastModified']) : $a['LastModified']->getTimestamp();
                $tsB = is_string($b['LastModified']) ? strtotime($b['LastModified']) : $b['LastModified']->getTimestamp();
                return $tsB - $tsA;
            });

            $newest = $backups[0];
            $newestTime = is_string($newest['LastModified']) ? strtotime($newest['LastModified']) : $newest['LastModified']->getTimestamp();
            $ageHours = (time() - $newestTime) / 3600;

            if ($ageHours > 25) {
                return ['label' => 'BACKUP', 'status' => 'warn', 'message' => 'Son yedek ' . round($ageHours) . ' saat once', 'detail' => count($backups) . ' yedek mevcut, en yenisi gecikmis'];
            }

            return ['label' => 'BACKUP', 'status' => 'ok', 'message' => count($backups) . ' yedek mevcut', 'detail' => 'Son yedek: ' . round($ageHours) . ' saat once'];
        } catch (\Throwable $e) {
            return ['label' => 'BACKUP', 'status' => 'warn', 'message' => 'Kontrol basarisiz', 'detail' => $e->getMessage()];
        }
    }
}
