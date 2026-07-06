<?php

class SeoController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    public function robots() {
        $host = $_SERVER['HTTP_HOST'] ?? 'fezadan.org';
        $isNotes = (strpos($host, 'notlar.') === 0);
        
        $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
        $notesBase = defined('NOTES_SITE_URL') ? rtrim(NOTES_SITE_URL, '/') : 'https://notlar.fezadan.org';
        
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        
        if ($isNotes) {
            echo "# =============================================================\n";
            echo "# notlar.fezadan.org — robots.txt\n";
            echo "# =============================================================\n\n";
            echo "User-agent: *\n";
            echo "Allow: /\n";
            echo "Disallow: /admin/\n";
            echo "Disallow: /app/\n\n";
        } else {
            echo "# =============================================================\n";
            echo "# fezadan.org — robots.txt\n";
            echo "# =============================================================\n\n";
            echo "User-agent: *\n";
            echo "Allow: /\n";
            echo "Disallow: /yonetim/\n";
            echo "Disallow: /yonetim\n";
            echo "Disallow: /git-deploy.php\n";
            echo "Disallow: /app/\n";
            echo "Disallow: /scripts/\n";
            echo "Disallow: /logs/\n";
            echo "Disallow: /backups/\n";
            echo "Disallow: /admin/\n\n";
        }
        $aiBots = [
            'GPTBot',
            'ChatGPT-User',
            'Google-Extended',
            'ClaudeBot',
            'anthropic-ai',
            'Omgilibot',
            'FacebookBot',
        ];

        foreach ($aiBots as $bot) {
            echo "User-agent: {$bot}\n";
            echo "Disallow: /\n\n";
        }

        echo 'Sitemap: ' . ($isNotes ? $notesBase . '/sitemap_notes.xml' : $base . '/sitemap.xml') . "\n";
        exit;
    }

    public function sitemapIndex() {
        $this->serveXml($this->generateSitemapIndexXml(), time());
    }

    public function sitemapMain() {
        $xml = $this->getCacheOrGenerate('sitemap_main.xml', fn() => $this->generateSitemapMainXml());
        $this->serveXml($xml, $this->getMaxDbModificationTime());
    }

    public function sitemapNotes() {
        $xml = $this->getCacheOrGenerate('sitemap_notes.xml', fn() => $this->generateSitemapNotesXml());
        $this->serveXml($xml, $this->getMaxDbModificationTime());
    }

    public function sitemapAnonymity() {
        $xml = $this->getCacheOrGenerate('sitemap_anonymity.xml', fn() => $this->generateSitemapAnonymityXml());
        $this->serveXml($xml, $this->getMaxDbModificationTime());
    }

    public function regenerateAllCache() {
        $xmlIndex = $this->generateSitemapIndexXml();
        $xmlMain = $this->generateSitemapMainXml();
        $xmlNotes = $this->generateSitemapNotesXml();
        $xmlAnonymity = $this->generateSitemapAnonymityXml();

        $this->writeAtomic(sys_get_temp_dir() . '/fezadan_sitemap.xml', $xmlIndex);
        $this->writeAtomic(sys_get_temp_dir() . '/fezadan_sitemap_main.xml', $xmlMain);
        $this->writeAtomic(sys_get_temp_dir() . '/fezadan_sitemap_notes.xml', $xmlNotes);
        $this->writeAtomic(sys_get_temp_dir() . '/fezadan_sitemap_anonymity.xml', $xmlAnonymity);

        $this->writeAtomic(ROOT . '/public_html/sitemap.xml', $xmlIndex);
        $this->writeAtomic(ROOT . '/public_html/sitemap_main.xml', $xmlMain);
        $this->writeAtomic(ROOT . '/public_html/sitemap_notes.xml', $xmlNotes);
        $this->writeAtomic(ROOT . '/public_html/sitemap_anonymity.xml', $xmlAnonymity);
    }

    private function generateSitemapIndexXml(): string {
        $base      = defined('SITE_URL')       ? rtrim(SITE_URL, '/')       : 'https://fezadan.org';
        $notesBase = defined('NOTES_SITE_URL') ? rtrim(NOTES_SITE_URL, '/') : 'https://notlar.fezadan.org';
        $anonymityBase = 'https://anonymitycheck.fezadan.org';
        $todayIso  = date('Y-m-d');

        $xmlIndex  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlIndex .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $xmlIndex .= "  <sitemap><loc>{$base}/sitemap_main.xml</loc><lastmod>{$todayIso}</lastmod></sitemap>" . PHP_EOL;
        $xmlIndex .= "  <sitemap><loc>{$notesBase}/sitemap_notes.xml</loc><lastmod>{$todayIso}</lastmod></sitemap>" . PHP_EOL;
        $xmlIndex .= "  <sitemap><loc>{$anonymityBase}/sitemap_anonymity.xml</loc><lastmod>{$todayIso}</lastmod></sitemap>" . PHP_EOL;
        $xmlIndex .= '</sitemapindex>';

        return $xmlIndex;
    }

    private function generateSitemapMainXml(): string {
        $pdo = $this->getPDO();
        $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
        $articles   = $pdo->query("
            SELECT a.id, a.slug, a.lang, a.created_at, COALESCE(a.updated_at, a.created_at) AS lastmod,
                   au.slug AS author_slug, a.translation_of
            FROM articles a
            LEFT JOIN authors au ON a.author_id = au.id
            WHERE a.status = 'published'
            ORDER BY a.created_at DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $articleMap = [];
        foreach ($articles as $a) {
            $articleMap[$a['id']] = $a;
        }

        $categories = $pdo->query("
            SELECT DISTINCT ac.category_id, a.lang
            FROM article_categories ac
            JOIN articles a ON ac.article_id = a.id
            WHERE a.status = 'published'
        ")->fetchAll(\PDO::FETCH_ASSOC);
        
        $authors = $pdo->query("
            SELECT DISTINCT au.slug, a.lang
            FROM authors au
            JOIN articles a ON a.author_id = au.id
            WHERE a.status = 'published'
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $todayIso  = date('Y-m-d');

        $xmlMain  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlMain .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;

        $langs = ['tr', 'en'];
        foreach ($langs as $lang) {
            $xmlMain .= "  <url><loc>" . htmlspecialchars(langUrl('', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>" . PHP_EOL;
            $xmlMain .= "  <url><loc>" . htmlspecialchars(langUrl($lang === 'en' ? 'articles' : 'makaleler', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>" . PHP_EOL;
            $xmlMain .= "  <url><loc>" . htmlspecialchars(pageUrl('about', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>" . PHP_EOL;
            $xmlMain .= "  <url><loc>" . htmlspecialchars(pageUrl('manifesto', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>" . PHP_EOL;
            $xmlMain .= "  <url><loc>" . htmlspecialchars(pageUrl('donate', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>" . PHP_EOL;
            $xmlMain .= "  <url><loc>" . htmlspecialchars(pageUrl('privacy', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>" . PHP_EOL;
            $xmlMain .= "  <url><loc>" . htmlspecialchars(pageUrl('verification', $lang), ENT_XML1) . "</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>" . PHP_EOL;
        }

        foreach ($articles as $article) {
            $lastMod = date('Y-m-d', strtotime($article['lastmod'] ?? $article['created_at']));
            $langCode = strtolower($article['lang'] ?? 'tr');
            $authorSlug = $article['author_slug'] ?: 'yazar';
            $loc = htmlspecialchars(articleUrl($authorSlug, $article['slug'], $langCode), ENT_XML1);

            $alternates = [];
            $alternates[$langCode] = $loc;
            if (!empty($article['translation_of']) && isset($articleMap[$article['translation_of']])) {
                $t = $articleMap[$article['translation_of']];
                $tLang = strtolower($t['lang'] ?? 'tr');
                $tAuthor = $t['author_slug'] ?: 'yazar';
                $alternates[$tLang] = htmlspecialchars(articleUrl($tAuthor, $t['slug'], $tLang), ENT_XML1);
            }
            foreach ($articleMap as $other) {
                if ($other['translation_of'] == $article['id']) {
                    $tLang = strtolower($other['lang'] ?? 'tr');
                    if (!isset($alternates[$tLang])) {
                        $tAuthor = $other['author_slug'] ?: 'yazar';
                        $alternates[$tLang] = htmlspecialchars(articleUrl($tAuthor, $other['slug'], $tLang), ENT_XML1);
                    }
                }
            }

            $xmlMain .= "  <url>" . PHP_EOL;
            $xmlMain .= "    <loc>{$loc}</loc>" . PHP_EOL;
            $xmlMain .= "    <lastmod>{$lastMod}</lastmod>" . PHP_EOL;
            $xmlMain .= "    <changefreq>weekly</changefreq>" . PHP_EOL;
            $xmlMain .= "    <priority>0.8</priority>" . PHP_EOL;
            foreach ($alternates as $hLang => $hUrl) {
                $xmlMain .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hLang}\" href=\"{$hUrl}\"/>" . PHP_EOL;
            }
            $xmlMain .= "  </url>" . PHP_EOL;
        }

        foreach ($categories as $cat) {
            $langCode = strtolower($cat['lang'] ?? 'tr');
            $path = ($langCode === 'en' ? 'articles' : 'makaleler') . '?cat=' . (int)$cat['category_id'];
            $loc = htmlspecialchars(langUrl($path, $langCode), ENT_XML1);
            $xmlMain .= "  <url><loc>{$loc}</loc><lastmod>{$todayIso}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>" . PHP_EOL;
        }

        foreach ($authors as $au) {
            if (empty($au['slug'])) continue;
            $langCode = strtolower($au['lang'] ?? 'tr');
            $loc = htmlspecialchars(authorUrl($au['slug'], $langCode), ENT_XML1);
            $xmlMain .= "  <url><loc>{$loc}</loc><lastmod>{$todayIso}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>" . PHP_EOL;
        }

        $xmlMain .= "  <url><loc>{$base}/furkan/</loc><lastmod>{$todayIso}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>" . PHP_EOL;

        $portfolioImages = $pdo->query("SELECT id FROM portfolio_images ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($portfolioImages as $pi) {
            $loc = htmlspecialchars("{$base}/furkan/image/{$pi['id']}", ENT_XML1);
            $xmlMain .= "  <url><loc>{$loc}</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>" . PHP_EOL;
        }

        $xmlMain .= '</urlset>';

        return $xmlMain;
    }

    private function generateSitemapNotesXml(): string {
        $pdo = $this->getPDO();
        $notesBase = defined('NOTES_SITE_URL') ? rtrim(NOTES_SITE_URL, '/') : 'https://notlar.fezadan.org';
        $todayIso  = date('Y-m-d');
        $notes = $pdo->query("SELECT slug, created_at, COALESCE(updated_at, created_at) AS lastmod FROM notes ORDER BY created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);

        $xmlNotes  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlNotes .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $xmlNotes .= "  <url><loc>{$notesBase}/</loc><lastmod>{$todayIso}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>" . PHP_EOL;

        foreach ($notes as $note) {
            $lastMod = date('Y-m-d', strtotime($note['lastmod'] ?? $note['created_at']));
            $loc     = htmlspecialchars($notesBase . '/not/' . $note['slug'], ENT_XML1);
            $xmlNotes .= "  <url><loc>{$loc}</loc><lastmod>{$lastMod}</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>" . PHP_EOL;
        }
        $xmlNotes .= '</urlset>';

        return $xmlNotes;
    }

    private function generateSitemapAnonymityXml(): string {
        $anonymityBase = 'https://anonymitycheck.fezadan.org';
        $todayIso = date('Y-m-d');

        $xmlAnonymity  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlAnonymity .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $xmlAnonymity .= "  <url><loc>{$anonymityBase}/</loc><lastmod>{$todayIso}</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>" . PHP_EOL;
        $xmlAnonymity .= '</urlset>';

        return $xmlAnonymity;
    }

    private function getCacheOrGenerate(string $cacheName, callable $generator) {
        $cacheFile = sys_get_temp_dir() . '/fezadan_' . $cacheName;
        $maxDbTime = $this->getMaxDbModificationTime();
        
        if (file_exists($cacheFile)) {
            $cacheTime = filemtime($cacheFile);
            if ($cacheTime >= $maxDbTime) {
                return file_get_contents($cacheFile);
            }
        }
        
        $xml = $generator();
        $this->writeAtomic($cacheFile, $xml);
        return $xml;
    }

    private function getMaxDbModificationTime(): int {
        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->query("
                SELECT MAX(lastmod) FROM (
                    SELECT MAX(COALESCE(updated_at, created_at)) AS lastmod FROM articles WHERE status = 'published'
                    UNION
                    SELECT MAX(COALESCE(updated_at, created_at)) AS lastmod FROM notes
                ) AS t
            ");
            $timeStr = $stmt->fetchColumn();
            return $timeStr ? strtotime($timeStr) : 0;
        } catch (\Throwable $e) {
            error_log('Error getting max DB modification time: ' . $e->getMessage());
            return time();
        }
    }

    private function serveXml(string $xml, int $lastModifiedTs) {
        $lastModifiedHttp = gmdate('D, d M Y H:i:s', $lastModifiedTs) . ' GMT';
        
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModifiedTs) {
            header('HTTP/1.1 304 Not Modified');
            header('Last-Modified: ' . $lastModifiedHttp);
            header('Cache-Control: public, max-age=600');
            exit;
        }
        
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=600');
        header('Last-Modified: ' . $lastModifiedHttp);
        echo $xml;
        exit;
    }

    private function writeAtomic(string $target, string $content): void {
        $dir  = dirname($target);
        $tmp  = tempnam($dir, 'sm_');
        if ($tmp === false) {
            file_put_contents($target, $content);
            return;
        }
        file_put_contents($tmp, $content);
        @chmod($tmp, 0644);
        if (file_exists($target)) {
            @unlink($target);
        }
        rename($tmp, $target);
    }
}
