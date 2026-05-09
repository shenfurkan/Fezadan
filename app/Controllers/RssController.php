<?php
class RssController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    public function index() {
        try {
            $pdo = $this->getPDO();

            $sql = "SELECT a.id, a.slug, a.title, a.short_desc, a.content, a.image_url,
                           a.created_at, a.updated_at, au.name AS author_name
                    FROM articles a
                    LEFT JOIN authors au ON au.id = a.author_id
                    WHERE a.status = 'published'
                    ORDER BY a.created_at DESC
                    LIMIT 30";
            $articles = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            // Site tabanı (header.php ile aynı yaklaşım)
            $isHttps  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
            $scheme   = $isHttps ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'] ?? 'fezadan.org';
            $siteBase = $scheme . '://' . $host;

            // lastBuildDate
            $lastBuild = !empty($articles)
                ? date(DATE_RSS, strtotime($articles[0]['created_at']))
                : date(DATE_RSS);

            // 304 Not Modified desteği (opsiyonel hafif cache)
            $lastModifiedTs = !empty($articles) ? strtotime($articles[0]['created_at']) : time();
            $lastModifiedHttp = gmdate('D, d M Y H:i:s', $lastModifiedTs) . ' GMT';
            $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
            if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModifiedTs) {
                header('HTTP/1.1 304 Not Modified');
                header('Last-Modified: ' . $lastModifiedHttp);
                header('Cache-Control: public, max-age=900');
                exit;
            }

            header('Content-Type: application/rss+xml; charset=UTF-8');
            header('Cache-Control: public, max-age=900');
            header('Last-Modified: ' . $lastModifiedHttp);

            $channelTitle = 'FEZADAN — Özgür Bilgi Platformu';
            $channelDesc  = 'Veri ve estetik arasındaki sessiz çatışma. FEZADAN — bilim, estetik ve fikir üzerine bağımsız bir yayın.';
            $feedSelf     = $siteBase . '/rss';

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
    <language>tr-TR</language>
    <lastBuildDate><?= $this->xmlEscape($lastBuild) ?></lastBuildDate>
    <atom:link href="<?= $this->xmlEscape($feedSelf) ?>" rel="self" type="application/rss+xml" />
    <image>
      <url><?= $this->xmlEscape($siteBase . '/cdn/notlar-social-preview.png') ?></url>
      <title><?= $this->xmlEscape($channelTitle) ?></title>
      <link><?= $this->xmlEscape($siteBase . '/') ?></link>
    </image>
<?php foreach ($articles as $a):
        $link    = $siteBase . '/makale/' . rawurlencode($a['slug']);
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
            throw new \Exception("RSS Hatası: " . $e->getMessage());
        }
    }

    private function xmlEscape(string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function cdata(string $s): string {
        // CDATA içinde "]]>" geçerse güvenli şekilde böl
        $safe = str_replace(']]>', ']]]]><![CDATA[>', $s);
        return '<![CDATA[' . $safe . ']]>';
    }

    private function absoluteUrl(string $url, string $siteBase): string {
        $url = trim($url);
        if ($url === '') return '';
        if (preg_match('#^https?://#i', $url)) return $url;
        if (strpos($url, '//') === 0) return 'https:' . $url;
        if ($url[0] !== '/') $url = '/' . $url;
        return $siteBase . $url;
    }

    private function guessMime(string $url): string {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'png':  return 'image/png';
            case 'gif':  return 'image/gif';
            case 'webp': return 'image/webp';
            case 'avif': return 'image/avif';
            case 'svg':  return 'image/svg+xml';
            default:     return 'image/jpeg';
        }
    }
}
