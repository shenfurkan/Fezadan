<?php
class HomeController extends Controller {

    public function index() {
        try {
            $pdo = Db::pdo();
            $contentLang = App::getLang();
            $articles = $this->fetchPublishedArticles($pdo, $contentLang);

            $this->view('front/home', [
                'articles' => $articles,
                'contentLang' => $contentLang,
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Veritabani Hatasi: " . $e->getMessage());
        }
    }

    private function fetchPublishedArticles(\PDO $pdo, string $lang): array {
        $sql = "SELECT articles.*,
                       authors.slug AS author_slug,
                       authors.name AS author_name,
                       GROUP_CONCAT(DISTINCT CONCAT_WS('|', c.id, c.name, c.slug) SEPARATOR ';;') AS categories_raw
                FROM articles
                LEFT JOIN authors ON articles.author_id = authors.id
                LEFT JOIN article_categories ac ON ac.article_id = articles.id
                LEFT JOIN categories c ON c.id = ac.category_id
                WHERE articles.status = 'published' AND articles.lang = :lang
                GROUP BY articles.id
                ORDER BY articles.created_at DESC";
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
