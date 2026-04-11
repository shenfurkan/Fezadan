<?php

function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            putenv(sprintf('%s=%s', trim($name), trim($value)));
        }
    }

loadEnv(__DIR__ . '/.env');

class MakaleController extends Controller {
    
    // slug
    public function index($slug = null) {
        if (!$slug) { header('Location: /'); exit; }

        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $pdo = new \PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT articles.*, 
                authors.name AS author_name, 
                authors.bio AS author_bio, 
                authors.image_url AS author_img,
                authors.slug AS author_slug
            FROM articles 
            LEFT JOIN authors ON articles.author_id = authors.id 
            WHERE articles.slug = :slug";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article) {
                $this->view('errors/404_article');
                exit;
            }

            $catStmt = $pdo->prepare("
                SELECT c.id, c.name FROM categories c 
                JOIN article_categories ac ON c.id = ac.category_id
                WHERE ac.article_id = ?
            ");
            $catStmt->execute([$article['id']]);
            $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('front/read', [
                'article' => $article,
                'categories' => $categories
            ]);

        } catch (\PDOException $e) {
            die("Sistem Hatası: " . $e->getMessage());
        }
    }

    // Makale okunma sayısı
    public function count() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        // JSON dosyasını çek
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $token = $data['token'] ?? '';

        $secretKey = getenv('SECRET_KEY');
        
        // Bugünün tokeni
        $tokenToday = md5('okuma_' . $id . date('Y-m-d') . $secretKey);
        
        // Dünün tokeni
        $dateYesterday = date('Y-m-d', strtotime("-1 day"));
        $tokenYesterday = md5('okuma_' . $id . $dateYesterday . $secretKey);

        if ($id > 0 && ($token === $tokenToday || $token === $tokenYesterday)) {
            try {
                $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
                $pdo = new \PDO($dsn, DB_USER, DB_PASS);
                
                $stmt = $pdo->prepare("UPDATE articles SET `reads` = `reads` + 1 WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['status' => 'success']);
            } catch (\PDOException $e) {
                exit; 
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        }
    }
}