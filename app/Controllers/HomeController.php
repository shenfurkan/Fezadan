<?php
class HomeController extends Controller {
    
    // Anasayfa listeleme
    public function index() {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $pdo = new \PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Makaleleri çek (sadece yayınlananlar)
            $sql = "SELECT articles.*, authors.slug AS author_slug 
                    FROM articles 
                    LEFT JOIN authors ON articles.author_id = authors.id 
                    WHERE articles.status = 'published'
                    ORDER BY articles.created_at DESC";
            $articles = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($articles as &$article) {
                $cStmt = $pdo->prepare("
                    SELECT c.id, c.name FROM categories c
                    JOIN article_categories ac ON c.id = ac.category_id
                    WHERE ac.article_id = ?
                ");
                $cStmt->execute([$article['id']]);
                $article['categories'] = $cStmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Anasayfaya gönder
            $this->view('front/home', ['articles' => $articles]);

        } catch (\PDOException $e) {
            die("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    // okuma sayfası
    public function read($slug = null) {
        if (!$slug) {
            header('Location: /');
            exit;
        }
    
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $pdo = new \PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    
            $sql = "SELECT authors.slug AS author_slug, 
                articles.*, 
                authors.name AS author_name, 
                authors.bio AS author_bio, 
                authors.image_url AS author_img
            FROM articles 
            LEFT JOIN authors ON articles.author_id = authors.id 
            WHERE articles.slug = :slug AND articles.status = 'published'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);
    
            if (!$article) {
            $this->view('errors/404_article');
            exit;
        }
    
            $this->view('front/read', ['article' => $article]);
    
        } catch (\PDOException $e) {
            die("Sistem Hatası: " . $e->getMessage());
        }
    }

    public function privacy() {
        $this->view('front/privacy');
    }
}