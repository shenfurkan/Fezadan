<?php

class AnalyticsController extends Controller
{
    public function track()
    {
        $this->requirePost();
        header('Content-Type: application/json; charset=utf-8');

        $articleId    = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
        $scrollDepth  = isset($_POST['scroll_depth']) ? (int)$_POST['scroll_depth'] : 0;
        $secondsSpent = isset($_POST['seconds_spent']) ? (int)$_POST['seconds_spent'] : 0;

        if ($articleId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Geçersiz makale.']);
            exit;
        }

        $scrollDepth  = max(0, min(100, $scrollDepth));
        $secondsSpent = max(0, min(7200, $secondsSpent));

        try {
            $pdo = Db::pdo();
            
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE id = ? AND status = 'published' LIMIT 1");
            $stmt->execute([$articleId]);
            if (!$stmt->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Makale bulunamadı.']);
                exit;
            }

            require_once ROOT . '/app/Core/RateLimit.php';
            $ipHash = \App\Core\RateLimit::ipHash();

            $stmtCheck = $pdo->prepare(
                "SELECT id, max_scroll_depth, seconds_spent 
                 FROM article_reading_analytics 
                 WHERE article_id = ? AND ip_hash = ? AND created_at >= NOW() - INTERVAL 1 DAY 
                 LIMIT 1"
            );
            $stmtCheck->execute([$articleId, $ipHash]);
            $existing = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                $newDepth = max($existing['max_scroll_depth'], $scrollDepth);
                $newSeconds = max($existing['seconds_spent'], $secondsSpent);
                
                $stmtUpdate = $pdo->prepare(
                    "UPDATE article_reading_analytics 
                     SET max_scroll_depth = ?, seconds_spent = ? 
                     WHERE id = ?"
                );
                $stmtUpdate->execute([$newDepth, $newSeconds, $existing['id']]);
            } else {
                $stmtInsert = $pdo->prepare(
                    "INSERT INTO article_reading_analytics (article_id, ip_hash, max_scroll_depth, seconds_spent) 
                     VALUES (?, ?, ?, ?)"
                );
                $stmtInsert->execute([$articleId, $ipHash, $scrollDepth, $secondsSpent]);
            }

            echo json_encode(['success' => true]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Method Not Allowed';
            exit;
        }
    }
}
