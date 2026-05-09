<?php
class NotlarController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    private function normalizeLangFilter(string $lang): string {
        $lang = strtoupper(trim($lang));
        return in_array($lang, ['TR', 'EN'], true) ? $lang : '';
    }

    public function index() {
        try {
            $pdo = $this->getPDO();

            $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
            $cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
            $lang = $this->normalizeLangFilter((string)($_GET['lang'] ?? ''));

            $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 12;

            $whereSql = "1=1";
            $params = [];

            if ($search !== '') {
                $whereSql .= " AND n.title LIKE :search";
                $params[':search'] = "%$search%";
            }

            if ($cat_id > 0) {
                $whereSql .= " AND n.id IN (SELECT note_id FROM note_categories WHERE category_id = :cat)";
                $params[':cat'] = $cat_id;
            }

            if ($lang !== '') {
                $whereSql .= " AND n.lang = :lang";
                $params[':lang'] = $lang;
            }

            $countSql = "SELECT COUNT(DISTINCT n.id) FROM notes n WHERE $whereSql";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total_records = (int)$countStmt->fetchColumn();

            $total_pages = max(1, (int)ceil($total_records / $limit));
            if ($current_page > $total_pages) {
                $current_page = $total_pages;
            }

            $offset = ($current_page - 1) * $limit;

            $sql = "SELECT n.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
                    FROM notes n
                    LEFT JOIN note_categories nc ON n.id = nc.note_id
                    LEFT JOIN categories c ON nc.category_id = c.id
                    WHERE $whereSql
                    GROUP BY n.id
                    ORDER BY n.created_at DESC
                    LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $notes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $categories = $pdo->query("SELECT DISTINCT c.* FROM categories c
                                       JOIN note_categories nc ON c.id = nc.category_id
                                       ORDER BY c.name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('front/notlar_home', [
                'notes' => $notes,
                'categories' => $categories,
                'current_search' => $search,
                'current_cat' => $cat_id,
                'current_lang' => $lang,
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'page_description' => 'Fezadan Notlar portalında bilimsel PDF dosyalarını, akademik makaleleri ve araştırma notlarını arayın, okuyun ve indirin.'
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function read($slug = '') {
        if ($slug === '') {
            header('Location: /');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT n.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
                                   FROM notes n
                                   LEFT JOIN note_categories nc ON n.id = nc.note_id
                                   LEFT JOIN categories c ON nc.category_id = c.id
                                   WHERE n.slug = ?
                                   GROUP BY n.id");
            $stmt->execute([$slug]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note) {
                http_response_code(404);
                $this->view('errors/404_note');
                exit;
            }

            $this->view('front/read-note', [
                'note' => $note,
                'pdfUrl' => '/not/view/' . rawurlencode($slug),
                'page_description' => mb_substr((string)$note['description'], 0, 155) . '...',
                'is_note' => true
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function viewPdf($slug = '') {
        if ($slug === '') {
            header('Location: /');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT title, r2_path FROM notes WHERE slug = ?");
            $stmt->execute([$slug]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note || empty($note['r2_path'])) {
                http_response_code(404);
                echo 'Belge bulunamadı.';
                exit;
            }

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2->streamView($note['r2_path'], $note['title'] ?? 'belge');
        } catch (\PDOException $e) {
            throw new \Exception("Belge Görüntüleme Hatası: " . $e->getMessage());
        }
    }

    public function download($slug = '') {
        if ($slug === '') {
            header('Location: /');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $dailySalt = date('Y-m-d') . APP_SALT;
            $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ipHash = hash('sha256', $userIp . $dailySalt);

            $pdo->exec("DELETE FROM download_rate_limits WHERE download_time < NOW() - INTERVAL 1 MINUTE");

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM download_rate_limits WHERE ip_hash = ?");
            $stmt->execute([$ipHash]);
            $recentDownloads = (int)$stmt->fetchColumn();

            if ($recentDownloads >= 3) {
                header('HTTP/1.1 429 Too Many Requests');
                header('Location: /not/' . rawurlencode($slug) . '?error=rate_limit');
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, title, r2_path FROM notes WHERE slug = ?");
            $stmt->execute([$slug]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note) {
                header('Location: /?error=not-found');
                exit;
            }

            $pdo->prepare("INSERT INTO download_rate_limits (ip_hash, download_time) VALUES (?, NOW())")
                ->execute([$ipHash]);
            $pdo->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?")
                ->execute([$note['id']]);

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2->streamDownload($note['r2_path'], $note['title']);
        } catch (\PDOException $e) {
            throw new \Exception("İndirme Hatası: " . $e->getMessage());
        }
    }
}
