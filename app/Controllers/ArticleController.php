<?php
class ArticleController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    /**
     * Okuma sayacı için HMAC-SHA256 token üretir.
     * APP_SALT zaten config.php'de tanımlı (SECRET_KEY env bağımlılığı kaldırıldı).
     */
    public static function readToken(int $articleId, string $date = null): string {
        $date = $date ?? date('Y-m-d');
        return hash_hmac('sha256', $articleId . '|' . $date, APP_SALT);
    }

    // slug ve yazar slug'ı
    public function index($slug = null, $authorSlug = null) {
        if (!$slug) { header('Location: /'); exit; }

        try {
            $pdo = $this->getPDO();

            // Eski /makale/{slug} URL'lerini kanonik yazar/makale yapısına yönlendir
            if (!$authorSlug) {
                $stmt = $pdo->prepare("
                    SELECT a.slug AS article_slug, a.lang, au.slug AS author_slug
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE a.slug = ? AND a.status = 'published'
                ");
                $stmt->execute([$slug]);
                $meta = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($meta) {
                    $targetUrl = articleUrl($meta['author_slug'] ?? 'yazar', $meta['article_slug'], strtolower($meta['lang'] ?? 'tr'));
                    http_response_code(301);
                    header('Location: ' . $targetUrl);
                    exit;
                } else {
                    http_response_code(404);
                    $this->view('errors/404_article');
                    exit;
                }
            }

            $requestedLang = App::getLang();
            $contentLang = $requestedLang;

            $sql = "SELECT articles.*,
                authors.name AS author_name,
                authors.bio AS author_bio,
                authors.image_url AS author_img,
                authors.slug AS author_slug
            FROM articles
            LEFT JOIN authors ON articles.author_id = authors.id
            WHERE articles.slug = :slug 
              AND authors.slug = :author_slug
              AND articles.lang = :lang
              AND articles.status = 'published'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':slug' => $slug, 
                ':author_slug' => $authorSlug,
                ':lang' => $contentLang
            ]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article && $requestedLang === 'EN') {
                $contentLang = 'TR';
                $stmt->execute([
                    ':slug' => $slug, 
                    ':author_slug' => $authorSlug,
                    ':lang' => $contentLang
                ]);
                $article = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Yedekten bulunduysa, /en/ arayüzü altında Türkçe içerik gösterilmesini önlemek için kanonik Türkçe URL'ye yönlendir.
                if ($article) {
                    $targetUrl = '/tr/' . $article['author_slug'] . '/' . $article['slug'];
                    http_response_code(301);
                    header('Location: ' . $targetUrl);
                    exit;
                }
            }

            if (!$article) {
                http_response_code(404);
                $this->view('errors/404_article');
                exit;
            }

            // Dil ve alternatif çeviri bağlantısını açığa çıkar
            $alternate = null;
            if (!empty($article['translation_of'])) {
                $altStmt = $pdo->prepare("
                    SELECT a.slug AS article_slug, a.lang, au.slug AS author_slug
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE a.id = ? AND a.status = 'published'
                ");
                $altStmt->execute([$article['translation_of']]);
                $alternate = $altStmt->fetch(\PDO::FETCH_ASSOC);
            } else {
                $altStmt = $pdo->prepare("
                    SELECT a.slug AS article_slug, a.lang, au.slug AS author_slug
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE a.translation_of = ? AND a.status = 'published'
                ");
                $altStmt->execute([$article['id']]);
                $alternate = $altStmt->fetch(\PDO::FETCH_ASSOC);
            }

            $page_alternates = [];
            $page_canonical = articleUrl($article['author_slug'] ?? 'yazar', $article['slug'], strtolower($article['lang'] ?? $requestedLang));
            $page_alternates[strtolower($requestedLang)] = $page_canonical;

            if ($alternate) {
                $page_alternates[strtolower($alternate['lang'])] = articleUrl($alternate['author_slug'] ?? 'yazar', $alternate['article_slug'], strtolower($alternate['lang'] ?? 'tr'));
            }

            $catStmt = $pdo->prepare("
                SELECT c.id, c.name, c.slug FROM categories c
                JOIN article_categories ac ON c.id = ac.category_id
                WHERE ac.article_id = ?
                ORDER BY c.name ASC
            ");
            $catStmt->execute([$article['id']]);
            $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Bu makalenin tüm yazarlarını getir
            $authorsStmt = $pdo->prepare("
                SELECT au.id, au.name, au.slug, au.bio, au.image_url
                FROM authors au
                JOIN article_authors aa ON au.id = aa.author_id
                WHERE aa.article_id = ?
                ORDER BY aa.display_order ASC
            ");
            $authorsStmt->execute([$article['id']]);
            $articleAuthors = $authorsStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($articleAuthors) && !empty($article['author_id'])) {
                $articleAuthors = [[
                    'id' => $article['author_id'],
                    'name' => $article['author_name'],
                    'slug' => $article['author_slug'],
                    'bio' => $article['author_bio'],
                    'image_url' => $article['author_img']
                ]];
            }

            // Bu makalenin düzeltmelerini getir
            $corrStmt = $pdo->prepare("
                SELECT correction_text, created_at
                FROM article_corrections
                WHERE article_id = ?
                ORDER BY created_at ASC
            ");
            $corrStmt->execute([$article['id']]);
            $corrections = $corrStmt->fetchAll(\PDO::FETCH_ASSOC);

            // İlgili makaleler — önce aynı kategoriden, yetmezse son yayınlananlardan (filtre: lang)
            $catIds = array_column($categories, 'id');
            $related = [];
            if (!empty($catIds)) {
                $in = implode(',', array_fill(0, count($catIds), '?'));
                $sqlRel = "SELECT DISTINCT a.id, a.slug, a.title, a.short_desc, a.image_url, a.created_at,
                                  au.name AS author_name, au.slug AS author_slug
                           FROM articles a
                           LEFT JOIN authors au ON a.author_id = au.id
                           JOIN article_categories ac ON ac.article_id = a.id
                           WHERE ac.category_id IN ($in)
                             AND a.id <> ?
                             AND a.lang = ?
                             AND a.status = 'published'
                           ORDER BY a.created_at DESC
                           LIMIT 3";
                $stmtRel = $pdo->prepare($sqlRel);
                $params = array_merge($catIds, [(int)$article['id'], $contentLang]);
                $stmtRel->execute($params);
                $related = $stmtRel->fetchAll(\PDO::FETCH_ASSOC);
            }
            if (count($related) < 3) {
                $exclude = array_merge([(int)$article['id']], array_column($related, 'id'));
                $in = implode(',', array_fill(0, count($exclude), '?'));
                $need = 3 - count($related);
                $sqlFill = "SELECT a.id, a.slug, a.title, a.short_desc, a.image_url, a.created_at,
                                   au.name AS author_name, au.slug AS author_slug
                            FROM articles a
                            LEFT JOIN authors au ON a.author_id = au.id
                            WHERE a.status = 'published' 
                              AND a.lang = ? 
                              AND a.id NOT IN ($in)
                            ORDER BY a.created_at DESC
                            LIMIT $need";
                $stmtFill = $pdo->prepare($sqlFill);
                $paramsFill = array_merge([$contentLang], $exclude);
                $stmtFill->execute($paramsFill);
                $related = array_merge($related, $stmtFill->fetchAll(\PDO::FETCH_ASSOC));
            }

            $currentCreatedAt = !empty($article['created_at']) ? $article['created_at'] : '1970-01-01 00:00:00';
            $navSelect = "SELECT a.id, a.slug, a.title, a.created_at, au.slug AS author_slug
                          FROM articles a
                          LEFT JOIN authors au ON a.author_id = au.id
                          WHERE a.status = 'published'
                            AND a.lang = ?
                            AND (
                                COALESCE(a.created_at, '1970-01-01 00:00:00') %s ?
                                OR (
                                    COALESCE(a.created_at, '1970-01-01 00:00:00') = ?
                                    AND a.id %s ?
                                )
                            )
                          ORDER BY COALESCE(a.created_at, '1970-01-01 00:00:00') %s, a.id %s
                          LIMIT 1";

            $prevStmt = $pdo->prepare(sprintf($navSelect, '<', '<', 'DESC', 'DESC'));
            $prevStmt->execute([$contentLang, $currentCreatedAt, $currentCreatedAt, (int)$article['id']]);
            $previousArticle = $prevStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            $nextStmt = $pdo->prepare(sprintf($navSelect, '>', '>', 'ASC', 'ASC'));
            $nextStmt->execute([$contentLang, $currentCreatedAt, $currentCreatedAt, (int)$article['id']]);
            $nextArticle = $nextStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            $this->view('front/read', [
                'article'         => $article,
                'categories'      => $categories,
                'related'         => $related,
                'previousArticle' => $previousArticle,
                'nextArticle'     => $nextArticle,
                'page_canonical'  => $page_canonical,
                'page_alternates' => $page_alternates,
                'page_keywords'   => $article['meta_keywords'] ?? '',
                'articleAuthors'  => $articleAuthors,
                'corrections'     => $corrections,
                'contentLang'     => $contentLang,
                'usingContentFallback' => $contentLang !== $requestedLang
            ]);

        } catch (\PDOException $e) {
            throw new \Exception("Sistem Hatası: " . $e->getMessage());
        }
    }

    // QR kod sayfası
    public function qr($slug = null) {
        if (!$slug) { header('Location: /'); exit; }

        try {
            $pdo  = $this->getPDO();
            $stmt = $pdo->prepare("
                SELECT a.title, a.slug, a.lang, au.slug AS author_slug
                FROM articles a
                LEFT JOIN authors au ON a.author_id = au.id
                WHERE a.slug = :slug AND a.status = 'published'
            ");
            $stmt->execute([':slug' => $slug]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article) {
                http_response_code(404);
                $this->view('errors/404_article');
                exit;
            }

            $this->view('front/qr', ['article' => $article]);

        } catch (\PDOException $e) {
            throw new \Exception("Sistem Hatası: " . $e->getMessage());
        }
    }

    // Makale okunma sayısı
    public function count() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $token = isset($data['token']) && is_string($data['token']) ? $data['token'] : '';

        if ($id <= 0 || $token === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }

        $tokenToday     = self::readToken($id, date('Y-m-d'));
        $tokenYesterday = self::readToken($id, date('Y-m-d', strtotime('-1 day')));

        if (!hash_equals($tokenToday, $token) && !hash_equals($tokenYesterday, $token)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
            exit;
        }

        try {
            $pdo = $this->getPDO();
            require_once ROOT . '/app/Core/RateLimit.php';
            $ipHash = \App\Core\RateLimit::ipHash();

            \App\Core\RateLimit::cleanupDaily($pdo, 'read_rate_limits');

            $counted = \App\Core\RateLimit::recordDailyUnique($pdo, 'read_rate_limits', $ipHash, $id);

            if (!$counted) {
                echo json_encode(['status' => 'ok', 'counted' => false]);
                exit;
            }

            $pdo->prepare("UPDATE articles SET `reads` = `reads` + 1 WHERE id = ?")
                ->execute([$id]);

            echo json_encode(['status' => 'success', 'counted' => true]);
        } catch (\PDOException $e) {
            error_log('reads count error: ' . $e->getMessage());
            echo json_encode(['status' => 'error']);
        }
    }
}
