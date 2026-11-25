<?php
class RssController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    public function index() {
        try {
            $pdo = $this->getPDO();

            $sql = "SELECT a.id, a.slug, a.title, a.short_desc, a.content, a.image_url,
                           a.created_at, a.updated_at, a.lang,
                           au.name AS author_name, au.slug AS author_slug
                    FROM articles a
                    LEFT JOIN authors au ON au.id = a.author_id
                    WHERE " . ArticleQueries::PUBLISHED . "
                    ORDER BY a.created_at DESC
                    LIMIT 30";
            $articles = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            $siteBase = rtrim(SITE_URL, '/');
            $lastModifiedTs = $this->feedCacheHeaders($articles, $siteBase . '/tr/rss');
            $lastBuild = date(DATE_RSS, $lastModifiedTs);

            $channelTitle = 'FEZADAN - Özgür Bilgi Platformu';
            $channelDesc  = 'Veri ve estetik arasındaki sessiz çatışma. FEZADAN - bilim, estetik ve fikir üzerine bağımsız bir yayın.';
            $feedSelf     = $siteBase . '/tr/rss';

            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            ?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title><?= $this->xmlEscape($channelTitle) ?></title>
    <link><?= $this->xmlEscape($siteBase . '/') ?></link>
    <description><?= $this->xmlEscape($channelDesc) ?></description>
    <language>tr</language>
    <lastBuildDate><?= $this->xmlEscape($lastBuild) ?></lastBuildDate>
    <atom:link href="<?= $this->xmlEscape($feedSelf) ?>" rel="self" type="application/rss+xml" />
    <image>
      <url><?= $this->xmlEscape($siteBase . '/cdn/logo-light.png') ?></url>
      <title><?= $this->xmlEscape($channelTitle) ?></title>
      <link><?= $this->xmlEscape($siteBase . '/') ?></link>
    </image>
<?php foreach ($articles as $a):
        $link    = articleUrl($a['slug'], $a['lang'] ?? 'tr');
        $pubDate = date(DATE_RSS, strtotime($a['created_at']));
        $img     = $this->absoluteUrl($a['image_url'] ?? '', $siteBase);
?>
    <item>
      <title><?= $this->xmlEscape($a['title']) ?></title>
      <link><?= $this->xmlEscape($link) ?></link>
      <guid isPermaLink="true"><?= $this->xmlEscape($link) ?></guid>
      <pubDate><?= $this->xmlEscape($pubDate) ?></pubDate>
<?php if (!empty($a['author_name'])): ?>
      <dc:creator><?= $this->cdata($a['author_name']) ?></dc:creator>
<?php endif; ?>
      <description><?= $this->cdata((string)($a['short_desc'] ?? '')) ?></description>
      <content:encoded><?= $this->cdata((string)($a['content'] ?? '')) ?></content:encoded>
<?php if ($img !== ''): ?>
      <enclosure url="<?= $this->xmlEscape($img) ?>" type="<?= $this->xmlEscape($this->guessMime($img)) ?>" />
<?php endif; ?>
    </item>
<?php endforeach; ?>
  </channel>
    </rss>
<?php
        } catch (\PDOException $e) {
            throw new \Exception("RSS Hatası: " . $e->getMessage(), 0, $e);
        }
    }

}
