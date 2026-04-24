<?php
class YazarController extends Controller {

    public function index($slug = null) {
        if (!$slug) {
            header('Location: /');
            exit;
        }

        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $pdo = new \PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM authors WHERE slug = ?");
            $stmt->execute([$slug]);
            $author = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$author) {
                $this->view('errors/404_author');
                exit;
            }
            
            $stmtAll = $pdo->prepare("SELECT * FROM articles WHERE author_id = ? AND status = 'published' ORDER BY created_at DESC");
            $stmtAll->execute([$author['id']]);
            $all_articles = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);

            $featured_articles = [];
            if (!empty($author['featured_articles'])) {
                $featuredIds = explode(',', $author['featured_articles']);
                $inQuery = implode(',', array_fill(0, count($featuredIds), '?'));
                $stmtFeatured = $pdo->prepare("SELECT * FROM articles WHERE id IN ($inQuery) AND status = 'published'");
                $stmtFeatured->execute($featuredIds);
                $featured_articles = $stmtFeatured->fetchAll(\PDO::FETCH_ASSOC);
            }

            $this->view('front/author', [
                'author' => $author,
                'all_articles' => $all_articles,
                'featured_articles' => $featured_articles
            ]);

        } catch (\PDOException $e) {
            die("Sistem Hatası: " . $e->getMessage());
        }
    }
}