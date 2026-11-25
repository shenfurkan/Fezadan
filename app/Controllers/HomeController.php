<?php
class HomeController extends Controller {

    public function index() {
        try {
            $pdo = Db::pdo();
            $contentLang = App::getLang();
            $articles = $this->fetchPublishedArticles($pdo, $contentLang);
            $count = $pdo->prepare("SELECT COUNT(*) FROM articles a WHERE " . ArticleQueries::PUBLISHED . " AND a.lang = ?");
            $count->execute([$contentLang]);

            $this->view('front/home', [
                'articles' => $articles,
                'contentLang' => $contentLang,
                'totalArticles' => (int)$count->fetchColumn(),
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Veritabani Hatasi: " . $e->getMessage(), 0, $e);
        }
    }

    private function fetchPublishedArticles(\PDO $pdo, string $lang): array {
        $sql = "SELECT a.id, a.slug, a.title, a.short_desc, a.image_url, a.created_at,
                       au.slug AS author_slug,
                       au.name AS author_name,
                       GROUP_CONCAT(DISTINCT CONCAT_WS('|', c.id, c.name, c.slug) SEPARATOR ';;') AS categories_raw
                FROM articles a
                LEFT JOIN authors au ON a.author_id = au.id
                LEFT JOIN article_categories ac ON ac.article_id = a.id
                LEFT JOIN categories c ON c.id = ac.category_id
                WHERE " . ArticleQueries::PUBLISHED . " AND a.lang = :lang
                GROUP BY a.id
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT 8";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':lang' => $lang]);

        return $this->attachCategories($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function attachCategories(array $articles): array {
        foreach ($articles as &$article) {
            $cats = [];
            if (!empty($article['categories_raw'])) {
                foreach (explode(';;', $article['categories_raw']) as $row) {
                    if ($row === '') {
                        continue;
                    }
                    [$cid, $cname, $cslug] = array_pad(explode('|', $row, 3), 3, '');
                    if ($cid !== '' && $cname !== '') {
                        $cats[] = ['id' => (int)$cid, 'name' => $cname, 'slug' => $cslug];
                    }
                }
            }
            $article['categories'] = $cats;
            unset($article['categories_raw']);
        }
        unset($article);

        return $articles;
    }

    public function privacy() {
        $this->view('front/privacy');
    }
}
