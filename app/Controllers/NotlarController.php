<?php
class NotlarController extends Controller {

    private function getPDO() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new \PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    // notlar.fezadan.org anasayfası (Listeleme, Filtreleme ve Sayfalandırma)
    // notlar.fezadan.org anasayfası (Listeleme, Filtreleme ve Sayfalandırma)
    public function index() {
        try {
            $pdo = $this->getPDO();
            
            // Kullanıcıdan gelen filtreleme parametreleri
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
            $lang = isset($_GET['lang']) ? trim($_GET['lang']) : ''; // YENİ: Dil parametresi
            
            // Sayfalandırma Ayarları
            $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 12; 
            
            $whereSql = "1=1";
            $params = [];

            if (!empty($search)) {
                $whereSql .= " AND n.title LIKE :search";
                $params[':search'] = "%$search%";
            }

            if ($cat_id > 0) {
                $whereSql .= " AND n.id IN (SELECT note_id FROM note_categories WHERE category_id = :cat)";
                $params[':cat'] = $cat_id;
            }

            // YENİ: Dil Filtreleme Mantığı
            if (!empty($lang)) {
                $whereSql .= " AND n.lang = :lang";
                $params[':lang'] = $lang;
            }

            // 1. TOPLAM KAYIT SAYISINI BUL
            $countSql = "SELECT COUNT(DISTINCT n.id) FROM notes n WHERE $whereSql";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total_records = $countStmt->fetchColumn();
            
            $total_pages = ceil($total_records / $limit);
            if ($total_pages == 0) $total_pages = 1; 
            if ($current_page > $total_pages) $current_page = $total_pages; 
            
            $offset = ($current_page - 1) * $limit;

            // 2. VERİLERİ ÇEK
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

            // GÜNCELLENDİ: Sadece içinde en az 1 not bulunan kategorileri çekiyoruz
            $categories = $pdo->query("SELECT DISTINCT c.* FROM categories c 
                                       JOIN note_categories nc ON c.id = nc.category_id 
                                       ORDER BY c.name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            // Verileri Görünüme Gönder
            $this->view('front/notlar_home', [
                'notes' => $notes,
                'categories' => $categories,
                'current_search' => $search,
                'current_cat' => $cat_id,
                'current_lang' => $lang, // YENİ: Seçili dilin dropdown'da kalması için
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'page_description' => 'Fezadan Notlar portalında bilimsel PDF dosyalarını, akademik makaleleri ve araştırma notlarını arayın, okuyun ve indirin.'
            ]);

        } catch (\PDOException $e) {
            die("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    // notlar.fezadan.org/not/abc için okuma sayfası
    public function read($slug = '') {
        if (empty($slug)) {
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
                http_response_code(404); // Arama motorları için 404 kodu gönder
                $this->view('errors/404_note'); // Özel hata sayfasını yükle
                exit;
            }

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = new \App\Core\R2Storage();
            $pdfUrl = $r2->getFileUrl($note['r2_path']);

            $this->view('front/read-note', [
                'note' => $note,
                'pdfUrl' => $pdfUrl,
                'page_description' => mb_substr($note['description'], 0, 155) . '...', // SEO için 155 karaktere kırpıyoruz
                'is_note' => true
            ]);

        } catch (\PDOException $e) {
            die("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function download($slug = '') {
        if (empty($slug)) {
            header('Location: /');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            // --- 1. MAHREMİYET ODAKLI RATE LIMITING ---
            // IP adresini "Günün Tarihi + Gizli Kelime" ile harmanlıyoruz.
            // Bu sayede gerçek IP asla saklanmaz ve her gün hash'ler geçersiz olur.
            $dailySalt = date('Y-m-d') . APP_SALT; 
            $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ipHash = hash('sha256', $userIp . $dailySalt);

            // 1 Dakikadan eski olan limit kayıtlarını temizle (Veritabanı şişmesini önler)
            $pdo->exec("DELETE FROM download_rate_limits WHERE download_time < NOW() - INTERVAL 1 MINUTE");

            // Son 1 dakika içindeki indirme sayısını kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM download_rate_limits WHERE ip_hash = ?");
            $stmt->execute([$ipHash]);
            $recentDownloads = $stmt->fetchColumn();

            // LİMİT: 1 dakikada maksimum 3 indirme
            if ($recentDownloads >= 3) {
                // Sınır aşıldıysa, 429 Too Many Requests kodu gönder ve hata parametresiyle geri yolla
                header('HTTP/1.1 429 Too Many Requests');
                header('Location: /not/' . $slug . '?error=rate_limit');
                exit;
            }

            // --- 2. ASIL İNDİRME İŞLEMİ ---
            $stmt = $pdo->prepare("SELECT id, title, r2_path FROM notes WHERE slug = ?");
            $stmt->execute([$slug]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($note) {
                // İndirme başlayacağı için rate limit tablosuna anı kaydet
                $pdo->prepare("INSERT INTO download_rate_limits (ip_hash, download_time) VALUES (?, NOW())")
                    ->execute([$ipHash]);

                // Toplam indirme sayacını güncelle
                $pdo->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?")
                    ->execute([$note['id']]);

                // R2 üzerinden akış ile indirme başlat
                require_once ROOT . '/app/Core/R2Storage.php';
                $r2 = new \App\Core\R2Storage();
                $r2->streamDownload($note['r2_path'], $note['title']);
            } else {
                header('Location: /?error=not-found');
                exit;
            }
        } catch (\PDOException $e) {
            die("İndirme Hatası: " . $e->getMessage());
        }
    }
}