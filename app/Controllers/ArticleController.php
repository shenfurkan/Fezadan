<?php
class ArticleController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    public static function wordCount(string $content): int {
        $text = html_entity_decode(strip_tags(str_replace('><', '> <', $content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}-][\p{L}\p{N}]+)*/u', $text) ?: 0;
    }

    // The author slug is only supplied by old article URLs.
    public function index($slug = null, $authorSlug = null) {
        if (!$slug) { header('Location: /'); exit; }

        try {
            $pdo = $this->getPDO();
            $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
            $query = parse_url($requestUri, PHP_URL_QUERY);
            $query = is_string($query) && $query !== '' ? '?' . $query : '';

            $requestedLang = App::getLang();
            $contentLang = $requestedLang;

            $article = ArticleQueries::findPublished($pdo, $slug, $contentLang, $authorSlug);

            if (!$article && $requestedLang === 'EN') {
                $contentLang = 'TR';
                $article = ArticleQueries::findPublished($pdo, $slug, $contentLang, $authorSlug);
            }

            if (!$article && $authorSlug === null) {
                // Preserve old language-less /makale/{slug} links to English articles.
                $meta = ArticleQueries::publishedLink($pdo, 'slug', $slug);
                if ($meta) {
                    $targetUrl = articleUrl($meta['article_slug'], $meta['lang']);
                    http_response_code(301);
                    header('Location: ' . $targetUrl . $query);
                    exit;
                }
            }

            if (!$article) {
                http_response_code(404);
                $this->view('errors/404_article');
                exit;
            }

            $page_canonical = articleUrl($article['slug'], $article['lang']);
            if ($authorSlug !== null || parse_url($requestUri, PHP_URL_PATH) !== parse_url($page_canonical, PHP_URL_PATH)) {
                header('Location: ' . $page_canonical . $query, true, 301);
                exit;
            }

            // Expose language and alternate translation linking
            $alternate = !empty($article['translation_of'])
                ? ArticleQueries::publishedLink($pdo, 'id', $article['translation_of'])
                : ArticleQueries::publishedLink($pdo, 'translation_of', $article['id']);

            $page_alternates = [];
            $page_alternates[strtolower($requestedLang)] = $page_canonical;

            if ($alternate) {
                $page_alternates[strtolower($alternate['lang'])] = articleUrl($alternate['article_slug'], $alternate['lang']);
            }

            $catStmt = $pdo->prepare("
                SELECT c.id, c.name, c.slug FROM categories c
                JOIN article_categories ac ON c.id = ac.category_id
                WHERE ac.article_id = ?
                ORDER BY c.name ASC
            ");
            $catStmt->execute([$article['id']]);
            $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch all authors for this article
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

            // Fetch corrections for this article
            $corrStmt = $pdo->prepare("
                SELECT correction_text, created_at
                FROM article_corrections
                WHERE article_id = ?
                ORDER BY created_at ASC
            ");
            $corrStmt->execute([$article['id']]);
            $corrections = $corrStmt->fetchAll(\PDO::FETCH_ASSOC);

            // İlgili makaleler: önce aynı kategoriden, yetmezse son yayınlananlardan (filtre: lang)
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
                             AND " . ArticleQueries::PUBLISHED . "
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
                            WHERE " . ArticleQueries::PUBLISHED . "
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
                          WHERE " . ArticleQueries::PUBLISHED . "
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
            throw new \Exception("Sistem Hatası: " . $e->getMessage(), 0, $e);
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
                WHERE a.slug = :slug AND " . ArticleQueries::PUBLISHED . "
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
            throw new \Exception("Sistem Hatası: " . $e->getMessage(), 0, $e);
        }
    }

}
