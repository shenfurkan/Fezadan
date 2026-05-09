<?php
class MakaleController extends Controller {

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

    // slug
    public function index($slug = null) {
        if (!$slug) { header('Location: /'); exit; }

        try {
            $pdo = $this->getPDO();

            $sql = "SELECT articles.*,
                authors.name AS author_name,
                authors.bio AS author_bio,
                authors.image_url AS author_img,
                authors.slug AS author_slug
            FROM articles
            LEFT JOIN authors ON articles.author_id = authors.id
            WHERE articles.slug = :slug AND articles.status = 'published'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article) {
                http_response_code(404);
                $this->view('errors/404_article');
                exit;
            }

            $catStmt = $pdo->prepare("
                SELECT c.id, c.name, c.slug FROM categories c
                JOIN article_categories ac ON c.id = ac.category_id
                WHERE ac.article_id = ?
                ORDER BY c.name ASC
            ");
            $catStmt->execute([$article['id']]);
            $categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

            // İlgili makaleler — önce aynı kategoriden, yetmezse son yayınlananlardan
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
                             AND a.status = 'published'
                           ORDER BY a.created_at DESC
                           LIMIT 3";
                $stmtRel = $pdo->prepare($sqlRel);
                $params = array_merge($catIds, [(int)$article['id']]);
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
                            WHERE a.status = 'published' AND a.id NOT IN ($in)
                            ORDER BY a.created_at DESC
                            LIMIT $need";
                $stmtFill = $pdo->prepare($sqlFill);
                $stmtFill->execute($exclude);
                $related = array_merge($related, $stmtFill->fetchAll(\PDO::FETCH_ASSOC));
            }

            $this->view('front/read', [
                'article'    => $article,
                'categories' => $categories,
                'related'    => $related
            ]);

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

            // --- Mahremiyet odaklı rate limit (IP hash) ---
            $userIp    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $today     = date('Y-m-d');
            $dailySalt = $today . APP_SALT;
            $ipHash    = hash('sha256', $userIp . $dailySalt);

            // Eski kayıtları temizle (24 saat)
            $pdo->exec("DELETE FROM read_rate_limits WHERE hit_time < NOW() - INTERVAL 1 DAY");

            // Atomik insert: UNIQUE (ip_hash, article_id, hit_date) sayesinde
            // aynı gün aynı IP+makale için tek satır oluşur. INSERT IGNORE
            // çakışmada sessiz reddeder; affected_rows ile ilk kez mi anlarız.
            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO read_rate_limits (ip_hash, article_id, hit_date, hit_time) VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$ipHash, $id, $today]);

            if ($stmt->rowCount() === 0) {
                // Bu IP bugün bu makaleyi zaten saymış
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
