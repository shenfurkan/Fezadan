<?php
class NotesController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    private function normalizeLangFilter(string $lang): string {
        $lang = strtoupper(trim($lang));
        return in_array($lang, ['TR', 'EN'], true) ? $lang : '';
    }

    private function notesBaseUrl(): string {
        if (defined('NOTES_SITE_URL')) {
            return rtrim(NOTES_SITE_URL, '/');
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'notlar.fezadan.org';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $host;
    }

    private function notesIndexQuery(string $search, int $catId, string $lang, int $page = 1): string {
        $query = [];
        if ($search !== '') {
            $query['search'] = $search;
        }
        if ($catId > 0) {
            $query['cat'] = $catId;
        }
        if ($lang !== '') {
            $query['lang'] = strtolower($lang);
        }
        if ($page > 1) {
            $query['page'] = $page;
        }
        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function notesIndexUrl(string $search, int $catId, string $lang, int $page = 1, bool $absolute = false): string {
        $query = $this->notesIndexQuery($search, $catId, $lang, $page);
        $path = '/' . ($query !== '' ? '?' . $query : '');
        return $absolute ? $this->notesBaseUrl() . $path : $path;
    }

    public function index() {
        try {
            $pdo = $this->getPDO();

            $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
            $cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
            $lang = $this->normalizeLangFilter((string)($_GET['lang'] ?? ''));

            $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 12;

            $whereSql = "1=1";
            $params = [];

            if ($search !== '') {
                $whereSql .= " AND MATCH(n.title, n.description) AGAINST(:search IN BOOLEAN MODE)";
                $params[':search'] = $search;
            }

            if ($cat_id > 0) {
                $whereSql .= " AND n.id IN (SELECT note_id FROM note_categories WHERE category_id = :cat)";
                $params[':cat'] = $cat_id;
            }

            if ($lang !== '') {
                $whereSql .= " AND n.lang = :lang";
                $params[':lang'] = $lang;
            }

            $countSql = "SELECT COUNT(DISTINCT n.id) FROM notes n WHERE $whereSql";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total_records = (int)$countStmt->fetchColumn();

            $total_pages = max(1, (int)ceil($total_records / $limit));
            if ($current_page > $total_pages) {
                $current_page = $total_pages;
            }

            $offset = ($current_page - 1) * $limit;

            $sql = "SELECT n.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
                    FROM notes n
                    LEFT JOIN note_categories nc ON n.id = nc.note_id
                    LEFT JOIN categories c ON nc.category_id = c.id
                    WHERE $whereSql
                    GROUP BY n.id
                    ORDER BY n.created_at DESC
                    LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $notes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $categories = $pdo->query("SELECT DISTINCT c.* FROM categories c
                                       JOIN note_categories nc ON c.id = nc.category_id
                                       ORDER BY c.name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('front/notes_home', [
                'notes' => $notes,
                'categories' => $categories,
                'current_search' => $search,
                'current_cat' => $cat_id,
                'current_lang' => $lang,
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'canonical_url' => $this->notesIndexUrl($search, $cat_id, $lang, $current_page, true),
                'previous_page_url' => $current_page > 1 ? $this->notesIndexUrl($search, $cat_id, $lang, $current_page - 1) : '',
                'next_page_url' => $current_page < $total_pages ? $this->notesIndexUrl($search, $cat_id, $lang, $current_page + 1) : '',
                'page_description' => 'Fezadan Notlar portalında bilimsel PDF dosyalarını, akademik makaleleri ve araştırma notlarını arayın, okuyun ve indirin.'
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function read($slug = '') {
        if ($slug === '') {
            header('Location: /');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT n.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
                                   FROM notes n
                                   LEFT JOIN note_categories nc ON n.id = nc.note_id
                                   LEFT JOIN categories c ON nc.category_id = c.id
                                   WHERE n.slug = ?
                                   GROUP BY n.id");
            $stmt->execute([$slug]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note) {
                http_response_code(404);
                $this->view('errors/404_note');
                exit;
            }

            $this->view('front/read-note', [
                'note' => $note,
                'pdfUrl' => '/not/view/' . rawurlencode($slug) . '?token=' . rawurlencode(self::viewToken($slug)),
                'downloadPath' => $this->noteDownloadPath($slug),
                'downloadUrl' => $this->noteDownloadUrl($slug),
                'page_description' => mb_substr((string)$note['description'], 0, 155) . '...',
                'is_note' => true
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public static function viewToken(string $slug, string $date = null): string {
        $date = $date ?? date('Y-m-d');
        return hash_hmac('sha256', $slug . '|' . $date, APP_SALT);
    }

    public function viewPdf($slug = '') {
        if ($slug === '') {
            header('Location: /');
            exit;
        }

        $token = $_GET['token'] ?? '';
        $todayToken = self::viewToken($slug);
        $yesterdayToken = self::viewToken($slug, date('Y-m-d', strtotime('-1 day')));

        if (!hash_equals($todayToken, $token) && !hash_equals($yesterdayToken, $token)) {
            http_response_code(403);
            echo 'Forbidden - Invalid Token';
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT title, r2_path, file_size FROM notes WHERE slug = ?");
            $stmt->execute([$slug]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note || empty($note['r2_path'])) {
                http_response_code(404);
                echo 'Belge bulunamadı.';
                exit;
            }

            if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
                $this->sendPdfHeadResponse($note);
            }

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2->streamView($note['r2_path'], $note['title'] ?? 'belge');
        } catch (\PDOException $e) {
            throw new \Exception("Belge Görüntüleme Hatası: " . $e->getMessage());
        }
    }

    private function sendPdfHeadResponse(array $note): void {
        $fileSize = max(0, (int)($note['file_size'] ?? 0));
        $statusCode = 200;
        $contentRange = null;
        $contentLength = $fileSize;
        $rangeHeader = trim((string)($_SERVER['HTTP_RANGE'] ?? ''));

        if ($rangeHeader !== '' && $fileSize > 0) {
            if (!preg_match('/^bytes=(\d*)-(\d*)$/i', $rangeHeader, $matches) || ($matches[1] === '' && $matches[2] === '')) {
                http_response_code(416);
                header('Content-Range: bytes */' . $fileSize);
                exit;
            }

            if ($matches[1] === '') {
                $suffixLength = (int)$matches[2];
                if ($suffixLength <= 0) {
                    http_response_code(416);
                    header('Content-Range: bytes */' . $fileSize);
                    exit;
                }
                $start = max(0, $fileSize - $suffixLength);
                $end = $fileSize - 1;
            } else {
                $start = (int)$matches[1];
                $end = $matches[2] === '' ? ($fileSize - 1) : min((int)$matches[2], $fileSize - 1);
                if ($start >= $fileSize || $end < $start) {
                    http_response_code(416);
                    header('Content-Range: bytes */' . $fileSize);
                    exit;
                }
            }

            $statusCode = 206;
            $contentRange = 'bytes ' . $start . '-' . $end . '/' . $fileSize;
            $contentLength = $end - $start + 1;
        }

        $safeName = str_replace([' ', '/', '\\', '"'], '_', (string)($note['title'] ?? 'belge')) . '.pdf';
        http_response_code($statusCode);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $safeName . '"');
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=300');
        if ($contentRange !== null) {
            header('Content-Range: ' . $contentRange);
        }
        if ($contentLength > 0) {
            header('Content-Length: ' . $contentLength);
        }
        exit;
    }

    public function download($slug = '') {
        if ($slug === '') {
            header('Location: /');
            exit;
        }

        // LiteCaptcha doğrulaması — her indirme geçerli bir token gerektirir
        $isLocal = function_exists('litecaptcha_is_local_request') && litecaptcha_is_local_request();

        if (defined('LITECAPTCHA_ENABLED') && LITECAPTCHA_ENABLED && !$isLocal) {
            $downloadPath = $this->noteDownloadPath($slug);
            $hasToken = isset($_GET['rt'], $_GET['sig'], $_GET['exp']) || isset($_GET['lc_rt'], $_GET['lc_sig'], $_GET['lc_exp']);
            if ($hasToken) {
                $error = null;
                if (!litecaptcha_verify($error, $downloadPath)) {
                    header('Location: /not/' . rawurlencode($slug) . '?error=captcha_fail');
                    exit;
                }
            } else {
                $litecaptchaUrl = rtrim(env_value('LITECAPTCHA_URL', 'https://litecaptcha.fezadan.org'), '/');
                $redirectTo = $this->noteDownloadUrl($slug);
                header('Location: ' . $litecaptchaUrl . '/?redirect=' . urlencode($redirectTo));
                exit;
            }
        }

        try {
            $pdo = $this->getPDO();
            require_once ROOT . '/app/Core/RateLimit.php';
            $ipHash = \App\Core\RateLimit::ipHash();

            $lockName = 'pdf_dl_' . $ipHash;
            $lockStmt = $pdo->prepare("SELECT GET_LOCK(?, 5)");
            $lockStmt->execute([$lockName]);
            $lockAcquired = $lockStmt->fetchColumn();

            if (!$lockAcquired) {
                header('HTTP/1.1 429 Too Many Requests');
                header('Location: /not/' . rawurlencode($slug) . '?error=rate_limit');
                exit;
            }

            try {
                $pdo->beginTransaction();

                \App\Core\RateLimit::cleanup($pdo, 'download_rate_limits', 'download_time', 60);
                $recentDownloads = \App\Core\RateLimit::countInWindow($pdo, 'download_rate_limits', $ipHash, 60);

                if ($recentDownloads >= 3) {
                    $pdo->commit();
                    header('HTTP/1.1 429 Too Many Requests');
                    header('Location: /not/' . rawurlencode($slug) . '?error=rate_limit');
                    exit;
                }

                $stmt = $pdo->prepare("SELECT id, title, r2_path FROM notes WHERE slug = ? FOR UPDATE");
                $stmt->execute([$slug]);
                $note = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$note) {
                    $pdo->commit();
                    header('Location: /?error=not-found');
                    exit;
                }

                \App\Core\RateLimit::record($pdo, 'download_rate_limits', $ipHash);
                $pdo->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?")
                    ->execute([$note['id']]);

                $pdo->commit();
            } finally {
                $pdo->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]);
            }

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2->streamDownload($note['r2_path'], $note['title']);
        } catch (\PDOException $e) {
            throw new \Exception("İndirme Hatası: " . $e->getMessage());
        }
    }

    public function rss() {
        try {
            $pdo = $this->getPDO();

            $sql = "SELECT n.id, n.slug, n.title, n.description, n.lang, n.file_size,
                           n.downloads, n.created_at, n.updated_at,
                           GROUP_CONCAT(c.name SEPARATOR ', ') AS category_names
                    FROM notes n
                    LEFT JOIN note_categories nc ON n.id = nc.note_id
                    LEFT JOIN categories c ON nc.category_id = c.id
                    GROUP BY n.id
                    ORDER BY n.created_at DESC
                    LIMIT 30";
            $notes = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            $notesBase = defined('NOTES_SITE_URL') ? rtrim(NOTES_SITE_URL, '/') : 'https://notlar.fezadan.org';
            $siteBase  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';

            // 304 Değiştirilmedi
            $lastModifiedTs = !empty($notes) ? strtotime($notes[0]['created_at']) : time();
            $lastModifiedHttp = gmdate('D, d M Y H:i:s', $lastModifiedTs) . ' GMT';
            $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
            if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModifiedTs) {
                header('HTTP/1.1 304 Not Modified');
                header('Last-Modified: ' . $lastModifiedHttp);
                header('Cache-Control: public, max-age=900');
                exit;
            }

            $lastBuild = !empty($notes) ? date(DATE_RSS, strtotime($notes[0]['created_at'])) : date(DATE_RSS);

            header('Content-Type: application/rss+xml; charset=UTF-8');
            header('Cache-Control: public, max-age=900');
            header('Last-Modified: ' . $lastModifiedHttp);

            $channelTitle = 'FEZADAN NOTLAR - Akademik Veri Havuzu';
            $channelDesc  = 'Fezadan Notlar: Astronomi, bilim ve teknoloji üzerine akademik PDF notlar, makaleler ve araştırmalar.';
            $feedSelf     = $notesBase . '/rss';

            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            ?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title><?= $this->xmlEscape($channelTitle) ?></title>
    <link><?= $this->xmlEscape($notesBase . '/') ?></link>
    <description><?= $this->xmlEscape($channelDesc) ?></description>
    <language>tr-TR</language>
    <lastBuildDate><?= $this->xmlEscape($lastBuild) ?></lastBuildDate>
    <atom:link href="<?= $this->xmlEscape($feedSelf) ?>" rel="self" type="application/rss+xml" />
    <image>
      <url><?= $this->xmlEscape($siteBase . '/cdn/notlar-social-preview.png') ?></url>
      <title><?= $this->xmlEscape($channelTitle) ?></title>
      <link><?= $this->xmlEscape($notesBase . '/') ?></link>
    </image>
<?php foreach ($notes as $n):
        $link    = $notesBase . '/not/' . rawurlencode($n['slug']);
        $pubDate = date(DATE_RSS, strtotime($n['created_at']));
        $desc    = (string)($n['description'] ?? '');
        $catNames = $n['category_names'] ?? '';
?>
    <item>
      <title><?= $this->xmlEscape($n['title']) ?> [<?= $this->xmlEscape(strtoupper($n['lang'] ?? 'tr')) ?>]</title>
      <link><?= $this->xmlEscape($link) ?></link>
      <guid isPermaLink="true"><?= $this->xmlEscape($link) ?></guid>
      <pubDate><?= $this->xmlEscape($pubDate) ?></pubDate>
      <description><?= $this->cdata($desc . ($catNames ? ' | Kategoriler: ' . $catNames : '')) ?></description>
      <content:encoded><?= $this->cdata(
        '<p>' . $desc . '</p>'
        . '<p>Dosya boyutu: ' . number_format((int)($n['file_size'] ?? 0) / 1024, 0) . ' KB | İndirme: ' . (int)($n['downloads'] ?? 0) . '</p>'
        . ($catNames ? '<p>Kategoriler: ' . $catNames . '</p>' : '')
        . '<p><a href="' . $link . '">Görüntüle / İndir</a></p>'
      ) ?></content:encoded>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
<?php
        } catch (\PDOException $e) {
            throw new \Exception("Notlar RSS Hatası: " . $e->getMessage());
        }
    }

    private function noteDownloadPath(string $slug): string
    {
        return '/not/download/' . rawurlencode($slug);
    }

    private function noteDownloadUrl(string $slug): string
    {
        $notesBaseUrl = defined('NOTES_SITE_URL')
            ? rtrim(NOTES_SITE_URL, '/')
            : 'https://notlar.fezadan.org';

        return $notesBaseUrl . $this->noteDownloadPath($slug);
    }
}
