<?php
class HomeController extends Controller {
    
    // Anasayfa listeleme
    public function index() {
        try {
            $pdo = Db::pdo();

            // Makaleleri çek (sadece yayınlananlar) — tek sorgu (N+1 yok)
            $sql = "SELECT articles.*,
                           authors.slug AS author_slug,
                           GROUP_CONCAT(DISTINCT CONCAT_WS('|', c.id, c.name, c.slug) SEPARATOR ';;') AS categories_raw
                    FROM articles
                    LEFT JOIN authors ON articles.author_id = authors.id
                    LEFT JOIN article_categories ac ON ac.article_id = articles.id
                    LEFT JOIN categories c ON c.id = ac.category_id
                    WHERE articles.status = 'published'
                    GROUP BY articles.id
                    ORDER BY articles.created_at DESC";
            $articles = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($articles as &$article) {
                $cats = [];
                if (!empty($article['categories_raw'])) {
                    foreach (explode(';;', $article['categories_raw']) as $row) {
                        if ($row === '') continue;
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

            // Anasayfaya gönder
            $this->view('front/home', ['articles' => $articles]);

        } catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function privacy() {
        $this->view('front/privacy');
    }
}