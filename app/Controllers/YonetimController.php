<?php
require_once ROOT . '/app/Core/DailyArtwork.php';

class YonetimController extends Controller
{

    // Database bağlantı fonksiyonu
    /** @deprecated Yeni kodda doğrudan Db::pdo() kullanın. */
    private function getPDO()
    {
        return Db::pdo();
    }

    /** CSRF verify gerektirmeyen istisnai metodlar (login: session henüz açılmamış olabilir) */
    private static $csrfExempt = ['login'];

    private static $jsonMethods = ['uploadContentImage'];

    /** GET ile çağrılması yasaklanan, yalnızca POST kabul edilen yazma uçları */
    private static $writeMethods = [
        'login','logout','store','update','delete','publish',
        'storeCategory','deleteCategory',
        'storePatch','patchDelete',
        'authorStore','authorDelete',
        'storeNote','updateNote','deleteNote',
        'updatePassword','uploadContentImage',
        'refreshDailyArt','updateArtDescription',
        'generateSitemap',
    ];

    public function __construct()
    {
        // CLI (cron / artisan benzeri scriptler) auth kontrolünü atlasın.
        if (PHP_SAPI === 'cli') {
            return;
        }

        $currentMethod = $_GET['url'] ?? '';
        $segments = explode('/', trim($currentMethod, '/'));
        $action   = isset($segments[1]) ? $segments[1] : '';
        $camel    = $action ? lcfirst(str_replace('-', '', ucwords($action, '-'))) : '';
        $isJsonMethod = in_array($action, self::$jsonMethods, true)
                     || in_array($camel, self::$jsonMethods, true);

        if (!isset($_SESSION['admin_logged_in']) && $isJsonMethod) {
            return;
        }

        if (!isset($_SESSION['admin_logged_in']) && !in_array($currentMethod, ['yonetim', 'yonetim/login'])) {
            header('Location: /yonetim');
            exit;
        }

        // POST tabanlı yazma uçlarında CSRF doğrulaması
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isWrite = in_array($action, self::$writeMethods, true)
                    || in_array($camel,  self::$writeMethods, true);

            if ($isWrite
                && !in_array($action, self::$csrfExempt, true)
                && !in_array($camel,  self::$csrfExempt, true)) {
                if ($isJsonMethod && !$this->csrfValid()) {
                    header('Content-Type: application/json; charset=utf-8');
                    $this->jsonError(403, 'Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.', [
                        'endpoint' => $camel ?: $action,
                    ], 'csrf_failed');
                }
                Csrf::verify();
            }

            // Login için: token üretilmiş olmalı, yoksa CSRF zorunlu
            if ((in_array($action, self::$csrfExempt, true) || in_array($camel, self::$csrfExempt, true))
                && !empty($_SESSION['csrf_token'])) {
                Csrf::verify();
            }
        }
    }

    /** Yazma uçlarında GET ile çağrılmayı engelle */
    private function requirePost(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }
    }

    private function normalizeNoteLang(string $lang): string
    {
        $lang = strtoupper(trim($lang));
        $allowed = ['TR', 'EN'];
        return in_array($lang, $allowed, true) ? $lang : 'TR';
    }

    private function pdfUploadError(array $file, int $maxBytes = 52428800): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'PDF sunucu limitinden büyük.',
                UPLOAD_ERR_FORM_SIZE => 'PDF form limitinden büyük.',
                UPLOAD_ERR_PARTIAL => 'PDF yüklemesi yarım kaldı.',
                UPLOAD_ERR_NO_FILE => 'PDF dosyası seçilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici yükleme klasörü yok.',
                UPLOAD_ERR_CANT_WRITE => 'PDF diske yazılamadı.',
                UPLOAD_ERR_EXTENSION => 'PDF yüklemesi PHP eklentisi tarafından durduruldu.',
            ];
            return $map[$error] ?? 'PDF yüklenemedi.';
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return 'PDF dosyası boş görünüyor.';
        }
        if ($size > $maxBytes) {
            return 'PDF 50 MB sınırını aşıyor.';
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'PDF geçici dosyası doğrulanamadı.';
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return 'Yalnızca PDF dosyası yüklenebilir.';
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }
        if ($mime !== '' && !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            return 'Dosya türü PDF olarak doğrulanamadı.';
        }

        $handle = @fopen($tmp, 'rb');
        if (!$handle) {
            return 'PDF dosyası okunamadı.';
        }
        $magic = fread($handle, 5);
        fclose($handle);
        if ($magic !== '%PDF-') {
            return 'PDF imzası geçersiz.';
        }

        return null;
    }

    private function csrfValid(): bool
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        return is_string($sent) && is_string($stored) && $stored !== '' && hash_equals($stored, $sent);
    }

    private function requestId(): string
    {
        try {
            return substr(bin2hex(random_bytes(8)), 0, 12);
        } catch (\Throwable $e) {
            return substr(sha1(uniqid('', true)), 0, 12);
        }
    }

    private function elapsedMs(float $started): int
    {
        return (int)round((microtime(true) - $started) * 1000);
    }

    private function uploadTelemetryContext(array $file = [], string $requestId = ''): array
    {
        return array_filter([
            'endpoint' => 'uploadContentImage',
            'request_id' => $requestId,
            'import_id' => trim((string)($_POST['import_id'] ?? '')),
            'image_index' => isset($_POST['image_index']) ? (int)$_POST['image_index'] : null,
            'upload_source' => trim((string)($_POST['upload_source'] ?? '')),
            'client_name' => trim((string)($_POST['client_name'] ?? '')),
            'client_size' => isset($_POST['client_size']) ? (int)$_POST['client_size'] : null,
            'client_type' => trim((string)($_POST['client_type'] ?? '')),
            'name' => $file['name'] ?? null,
            'size' => isset($file['size']) ? (int)$file['size'] : null,
            'php_error' => $file['error'] ?? null,
        ], static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function jsonError(int $status, string $message, array $context = [], string $code = 'admin_error'): void
    {
        $requestId = (string)($context['request_id'] ?? $this->requestId());
        $context['request_id'] = $requestId;
        $context['code'] = $code;

        http_response_code($status);
        AdminLog::write('error', $message, $context);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'request_id' => $requestId,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function failBackWithFlash(string $redirectUrl, string $message, array $context = [], string $code = 'form_error'): void
    {
        $requestId = (string)($context['request_id'] ?? $this->requestId());
        $context['request_id'] = $requestId;
        $context['code'] = $code;
        AdminLog::write('error', $message, $context);
        Flash::set($message . ' (Hata kodu: ' . $requestId . ')');
        header('Location: ' . $redirectUrl);
        exit;
    }

    private function uploadCoverOrFail(array $file, string $slug, string $redirectUrl): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $uploadError = Upload::imageUploadError($file, 5242880);
        if ($uploadError !== null) {
            $this->failBackWithFlash($redirectUrl, 'Kapak görseli yüklenemedi: ' . $uploadError, [
                'endpoint' => 'coverUpload',
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'php_error' => $file['error'] ?? null,
            ], 'cover_validation_failed');
        }

        $storedPath = Upload::saveImageToR2($file, 'uploads/covers', 'cover_', 5242880, $slug);
        if ($storedPath === null) {
            $detail = Upload::lastError();
            $message = $detail !== ''
                ? 'Kapak görseli yüklenemedi: ' . $detail
                : 'Kapak görseli doğrulandı ama R2/CDN yüklemesi tamamlanamadı.';
            $this->failBackWithFlash($redirectUrl, $message, [
                'endpoint' => 'coverUpload',
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'slug' => $slug,
                'detail' => $detail,
            ], 'cover_upload_failed');
        }

        AdminLog::write('info', 'Kapak görseli yüklendi.', [
            'endpoint' => 'coverUpload',
            'name' => $file['name'] ?? '',
            'size' => $file['size'] ?? 0,
            'path' => $storedPath,
            'meta' => Upload::lastMeta(),
        ]);

        return $storedPath;
    }

    // Admin giriş sayfası
    public function index()
    {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: /yonetim/dashboard');
            exit;
        }
        $this->view('yonetim/login');
    }

    // Admin girişi
    public function login()
    {
        $this->requirePost();

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // MAHREMİYET ODAKLI IP HASHLEME
        $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $dailySalt = date('Y-m-d') . APP_SALT; 
        $ipHash = hash('sha256', $userIp . $dailySalt);

        try {
            $pdo = $this->getPDO();

            // 1. ESKİ KAYITLARI TEMİZLE: 3 saatten eski denemeleri sil
            $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 3 HOUR");

            // 2. KONTROL: Bu hash ile son 3 saatte kaç hatalı giriş yapılmış?
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ?");
            $stmtCount->execute([$ipHash]);
            $attempts = $stmtCount->fetchColumn();

            // 3. ENGEL: 3 hata varsa engelle
            if ($attempts >= 3) {
                header('Location: /yonetim?error=locked');
                exit;
            }

            // 4. DOĞRULAMA
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                
                // Başarılı giriş: Bu hash'e ait hatalı denemeleri temizle
                $pdo->prepare("DELETE FROM login_attempts WHERE ip_hash = ?")->execute([$ipHash]);

                // Session fixation koruması: oturum kimliğini yenile, eski içerikleri sıfırla
                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_name'] = $user['name'];

                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                header('Location: /yonetim/dashboard');
                exit;
            }
            else {
                // Başarısız giriş: Hashlenmiş IP'yi kaydet
                $stmtFail = $pdo->prepare("INSERT INTO login_attempts (ip_hash) VALUES (?)");
                $stmtFail->execute([$ipHash]);

                header('Location: /yonetim?error=1');
                exit;
            }
        }
        catch (\PDOException $e) {
            throw new \Exception("Giriş Hatası: " . $e->getMessage());
        }
    }

    // Admin arayüzü
    public function dashboard()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            // Makale arama
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $whereSql = "1=1";
            $params = [];

            if (!empty($search)) {
                // id= ile arama
                if (stripos($search, 'id=') === 0) {
                    $searchId = (int)substr($search, 3);
                    $whereSql .= " AND a.id = :id";
                    $params[':id'] = $searchId;
                }
                else {
                    // Title search
                    $whereSql .= " AND a.title LIKE :search";
                    $params[':search'] = "%$search%";
                }
            }

            // Sıralama
            $sort = $_GET['sort'] ?? 'id';
            $order = $_GET['order'] ?? 'DESC';

            $allowedSorts = [
                'id' => 'a.id',
                'title' => 'a.title',
                'reads' => 'a.reads',
                'author' => 'author_name',
                'category' => 'MIN(c.name)'
            ];

            if (!array_key_exists($sort, $allowedSorts)) {
                $sort = 'id';
            }

            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

            $orderBySQL = $allowedSorts[$sort];

            // Sayfalandırma
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10; // Article per page
            $offset = ($page - 1) * $limit;

            // Toplam makale sayısı
            $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) FROM articles a WHERE $whereSql");
            $countStmt->execute($params);
            $totalArticles = $countStmt->fetchColumn();
            $totalPages = ceil($totalArticles / $limit);
            if ($page > $totalPages && $totalPages > 0)
                $page = $totalPages;

            // Makale bilgisini çek
            $sql = "SELECT a.*, au.name as author_name, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names 
                    FROM articles a
                    LEFT JOIN article_categories ac ON a.id = ac.article_id
                    LEFT JOIN categories c ON ac.category_id = c.id
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE $whereSql
                    GROUP BY a.id
                    ORDER BY $orderBySQL $order
                    LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Yama notları
            try {
                $patches = $pdo->query("SELECT * FROM patch_notes ORDER BY created_at DESC LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);
            }
            catch (\PDOException $e) {
                $patches = [];
            }

            // İstatistikler
            $totalRealCount = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
            $totalDrafts = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn();
            $totalReads = (int)$pdo->query("SELECT SUM(`reads`) FROM articles")->fetchColumn();

            $stats = [
                'total_articles' => $totalRealCount,
                'total_drafts'   => $totalDrafts, 
                'total_reads' => $totalReads,
                'system_status' => 'Aktif',
                'last_login' => date('H:i')
            ];

            $this->view('yonetim/dashboard', [
                'stats' => $stats,
                'articles' => $articles,
                'patches' => $patches,
                'pagination' => [
                    'current' => $page,
                    'total' => $totalPages,
                    'search' => $search
                ],
                'sort' => [
                    'column' => $sort,
                    'order' => $order
                ]
            ]);

        }
        catch (\PDOException $e) {
            throw new \Exception("Dashboard Hatası: " . $e->getMessage());
        }
    }

    // --- Yama Notları ---

    // Yama notu oluşturma sayfası
    public function createPatch()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }
        $this->view('yonetim/create-patch');
    }

    // Kaydetme
    public function storePatch()
    {
        $this->requirePost();

        $title = $_POST['title'] ?? 'Sistem Güncellemesi';
        $content = $_POST['content'] ?? '';
        $author = $_SESSION['admin_user'] ?? 'yonetim';

        try {
            $pdo = $this->getPDO();

            // Önce geçici değerle insert et, sonra LAST_INSERT_ID() ile güncelle (race-safe)
            $stmt = $pdo->prepare("INSERT INTO patch_notes (version, title, content, author) VALUES ('', ?, ?, ?)");
            $stmt->execute([$title, $content, $author]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE patch_notes SET version = ? WHERE id = ?")
                ->execute(['1.' . $newId, $newId]);

            header('Location: /yonetim/dashboard?status=patch_added');
        }
        catch (\PDOException $e) {
            throw new \Exception("Yama Notu Kayıt Hatası: " . $e->getMessage());
        }
    }

    // Yama notu silme
    public function patchDelete()
    {
        $this->requirePost();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("DELETE FROM patch_notes WHERE id = ?");
                $stmt->execute([$id]);
            }
            catch (\PDOException $e) {
                throw new \Exception("Yama Notu Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/dashboard?status=patch_deleted');
    }


    // --- Makale yönetimi ---

    // Yeni makale oluşturma sayfası
    public function create()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('yonetim/create', [
                'authors' => $authors,
                'categories' => $categories
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veri Çekilemedi: " . $e->getMessage());
        }
    }

    // Makale kaydetme
    public function store()
    {
        $this->requirePost();

        $title              = trim($_POST['title'] ?? '') ?: 'Adsiz';
        $desc               = $_POST['desc'] ?? '';
        $content            = $_POST['content'] ?? '';
        $refs               = $_POST['refs'] ?? '';
        $author_id          = isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0;
        $selectedCategories = $_POST['categories'] ?? [];
        $image_db_path      = '';
        $status             = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        try {
            $pdo = $this->getPDO();

            // Yazar varlık kontrolü
            $author_id = $this->validateAuthorId($pdo, $author_id);
            // Kategori whitelist
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            $slug = $this->uniqueSlug($pdo, 'articles', $this->createSlug($title));

            if (isset($_FILES['cover_image'])) {
                $storedPath = $this->uploadCoverOrFail($_FILES['cover_image'], $slug, '/yonetim/create');
                if ($storedPath !== null) {
                    $image_db_path = $storedPath;
                }
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO articles (title, slug, short_desc, content, refs, image_url, author_id, status) VALUES (:title, :slug, :desc, :content, :refs, :img, :author_id, :status)");
            $stmt->execute([
                ':title' => $title, ':slug' => $slug, ':desc' => $desc,
                ':content' => $content,
                ':refs' => $refs,
                ':img' => $image_db_path, ':author_id' => $author_id ?: null,
                ':status' => $status
            ]);
            $articleId = $pdo->lastInsertId();

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$articleId, $catId]);
                }
            }

            $pdo->commit();
            $this->markSitemapDirty();
            $msg = ($status === 'draft') ? 'draft_saved' : 'success';
            header('Location: /yonetim/dashboard?status=' . $msg);
        }
        catch (\PDOException $e) {
            if ($image_db_path) {
                $this->safeUnlinkUpload($image_db_path);
            }
            throw new \Exception("Makale Kayıt Hatası: " . $e->getMessage());
        }
    }

    /** FK validation: var olan author_id'yi geri döndürür; aksi halde 0. */
    private function validateAuthorId(\PDO $pdo, int $id): int
    {
        if ($id <= 0) return 0;
        $stmt = $pdo->prepare("SELECT id FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    /** FK validation: yalnızca DB'de mevcut olan kategori id'lerini döndürür. */
    private function validateCategoryIds(\PDO $pdo, $ids): array
    {
        if (!is_array($ids) || empty($ids)) return [];
        $clean = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        if (empty($clean)) return [];
        $place = implode(',', array_fill(0, count($clean), '?'));
        $stmt  = $pdo->prepare("SELECT id FROM categories WHERE id IN ($place)");
        $stmt->execute($clean);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    // Düzenleme sayfası
    public function edit()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /yonetim/dashboard');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$id]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article) {
                http_response_code(404);
                $this->view('errors/404_article');
                exit;
            }

            $stmtCat = $pdo->prepare("SELECT category_id FROM article_categories WHERE article_id = ?");
            $stmtCat->execute([$id]);
            $selectedCategories = $stmtCat->fetchAll(\PDO::FETCH_COLUMN);

            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors    = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('yonetim/edit', [
                'article'            => $article,
                'categories'         => $categories,
                'authors'            => $authors,
                'selectedCategories' => $selectedCategories
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veri Hatası: " . $e->getMessage());
        }
    }

    // Makale güncelleme
    public function update()
    {
        $this->requirePost();

        $id                 = (int)($_POST['id'] ?? 0);
        $title              = trim($_POST['title'] ?? '') ?: 'Adsiz';
        $desc               = $_POST['desc'] ?? '';
        $content            = $_POST['content'] ?? '';
        $refs               = $_POST['refs'] ?? '';
        $author_id          = isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0;
        $selectedCategories = $_POST['categories'] ?? [];
        $current_image      = $_POST['current_image'] ?? '';
        $image_db_path      = $current_image;
        $oldImagePath       = $current_image;
        $status             = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        if ($id <= 0) {
            header('Location: /yonetim/dashboard?status=invalid');
            exit;
        }

        $newImageStored = null;

        try {
            $pdo = $this->getPDO();
            $author_id          = $this->validateAuthorId($pdo, $author_id);
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            $slug = $this->uniqueSlug($pdo, 'articles', $this->createSlug($title), $id);

            if (isset($_FILES['cover_image'])) {
                $storedPath = $this->uploadCoverOrFail($_FILES['cover_image'], $slug, '/yonetim/edit?id=' . $id);
                if ($storedPath !== null) {
                    $image_db_path  = $storedPath;
                    $newImageStored = $storedPath;
                }
            }

            $pdo->beginTransaction();

            $sql = "UPDATE articles SET title = ?, slug = ?, short_desc = ?, content = ?, refs = ?, author_id = ?, image_url = ?, status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $slug, $desc, $content, $refs, $author_id ?: null, $image_db_path, $status, $id]);

            $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$id]);

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            $pdo->commit();

            // Yeni resim yüklendiyse eski dosyayı disk'ten kaldır
            if ($newImageStored !== null && $oldImagePath !== '' && $oldImagePath !== $image_db_path) {
                $this->safeUnlinkUpload($oldImagePath);
            }

            $this->markSitemapDirty();
            header('Location: /yonetim/dashboard?status=updated');
        }
        catch (\PDOException $e) {
            if ($newImageStored !== null) $this->safeUnlinkUpload($newImageStored);
            throw new \Exception("Güncelleme Hatası: " . $e->getMessage());
        }
    }

    /** Yalnızca /uploads/ altındaki dosyaları silmeye izin ver (path traversal koruması). */
    private function safeUnlinkUpload(string $relativePath): bool
    {
        if ($relativePath === '' || strpos($relativePath, '/uploads/') !== 0) return false;
        if (strpos($relativePath, '..') !== false) return false;

        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $objectKey = ltrim($relativePath, '/');
            $ok = $r2->deleteFile($objectKey);
            $webpKey = preg_replace('/\.(jpe?g|png)$/i', '.webp', $objectKey);
            if ($webpKey !== $objectKey) {
                $r2->deleteFile($webpKey);
            }
            return $ok;
        } catch (\Throwable $e) {
            $abs  = realpath(ROOT . '/public_html' . $relativePath);
            $base = realpath(ROOT . '/public_html/uploads');
            if ($abs === false || $base === false) return false;
            if (strpos($abs, $base) !== 0) return false;
            $ok = @unlink($abs);
            $webpAbs = preg_replace('/\.[^.]+$/', '.webp', $abs);
            if ($webpAbs !== $abs && is_file($webpAbs)) @unlink($webpAbs);
            return $ok;
        }
    }

    /**
     * Sitemap üretimi.
     *
     * - lastmod artık `updated_at` (yoksa created_at) kullanıyor → makale güncellendiğinde Google geri gelir.
     * - Makaleler dışında: yazar profilleri ve kategori sayfaları da indexlenir.
     * - Atomik yazım (tempnam → rename) ile yarım dosya görünmez.
     * - Cron için public bir entry point: AdminController'dan değil cron/generate-sitemap.php'den de çağrılabilir.
     */
    public function generateSitemap()
    {
        $this->requirePost();

        try {
            $pdo = $this->getPDO();

            $articles   = $pdo->query("SELECT slug, created_at, COALESCE(updated_at, created_at) AS lastmod FROM articles WHERE status = 'published' ORDER BY created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);
            $notes      = $pdo->query("SELECT slug, created_at, COALESCE(updated_at, created_at) AS lastmod FROM notes ORDER BY created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors    = $pdo->query("SELECT DISTINCT au.slug FROM authors au JOIN articles a ON a.author_id = au.id WHERE a.status = 'published'")->fetchAll(\PDO::FETCH_ASSOC);
            $categories = $pdo->query("SELECT DISTINCT c.id FROM categories c JOIN article_categories ac ON ac.category_id = c.id JOIN articles a ON ac.article_id = a.id WHERE a.status = 'published'")->fetchAll(\PDO::FETCH_ASSOC);

            $base      = defined('SITE_URL')       ? rtrim(SITE_URL, '/')       : 'https://fezadan.org';
            $notesBase = defined('NOTES_SITE_URL') ? rtrim(NOTES_SITE_URL, '/') : 'https://notlar.fezadan.org';
            $todayIso  = date('Y-m-d');

            // --- A. ANA SİTE SİTEMAP ---
            $xmlMain  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xmlMain .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

            $staticPages = [
                ['loc' => $base . '/',          'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => $base . '/makaleler', 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => $base . '/hakkinda',  'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => $base . '/manifesto', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ];
            foreach ($staticPages as $sp) {
                $xmlMain .= "  <url><loc>{$sp['loc']}</loc><lastmod>{$todayIso}</lastmod><changefreq>{$sp['changefreq']}</changefreq><priority>{$sp['priority']}</priority></url>" . PHP_EOL;
            }
            foreach ($articles as $article) {
                $lastMod = date('Y-m-d', strtotime($article['lastmod'] ?? $article['created_at']));
                $loc     = htmlspecialchars($base . '/makale/' . $article['slug'], ENT_XML1);
                $xmlMain .= "  <url><loc>{$loc}</loc><lastmod>{$lastMod}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>" . PHP_EOL;
            }
            foreach ($categories as $cat) {
                $loc = htmlspecialchars($base . '/makaleler?cat=' . (int)$cat['id'], ENT_XML1);
                $xmlMain .= "  <url><loc>{$loc}</loc><lastmod>{$todayIso}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>" . PHP_EOL;
            }
            foreach ($authors as $au) {
                if (empty($au['slug'])) continue;
                $loc = htmlspecialchars($base . '/yazar/' . $au['slug'], ENT_XML1);
                $xmlMain .= "  <url><loc>{$loc}</loc><lastmod>{$todayIso}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>" . PHP_EOL;
            }
            $xmlMain .= '</urlset>';
            $this->writeAtomic(ROOT . '/public_html/sitemap_main.xml', $xmlMain);

            // --- B. NOTLAR SİTESİ SİTEMAP ---
            $xmlNotes  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xmlNotes .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
            $xmlNotes .= "  <url><loc>{$notesBase}/</loc><lastmod>{$todayIso}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>" . PHP_EOL;
            foreach ($notes as $note) {
                $lastMod = date('Y-m-d', strtotime($note['lastmod'] ?? $note['created_at']));
                $loc     = htmlspecialchars($notesBase . '/not/' . $note['slug'], ENT_XML1);
                $xmlNotes .= "  <url><loc>{$loc}</loc><lastmod>{$lastMod}</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>" . PHP_EOL;
            }
            $xmlNotes .= '</urlset>';
            $this->writeAtomic(ROOT . '/public_html/sitemap_notes.xml', $xmlNotes);
        }
        catch (\Exception $e) {
            error_log('Sitemap üretim hatası: ' . $e->getMessage());
        }
    }

    /**
     * Sitemap'in yeniden üretilmesi gerektiğini işaretle.
     * cron/generate-sitemap.php her N dakikada bir bu flag'e bakıp üretimi gerçekleştirir.
     * Inline üretim yerine bu pattern admin yanıt sürelerini hızlandırır.
     */
    private function markSitemapDirty(): void
    {
        @touch(sys_get_temp_dir() . '/fezadan-sitemap.dirty');
    }

    /** Yarım dosya yazımını engellemek için atomik yazım. */
    private function writeAtomic(string $target, string $content): void
    {
        $dir  = dirname($target);
        $tmp  = tempnam($dir, 'sm_');
        if ($tmp === false) {
            file_put_contents($target, $content);
            return;
        }
        file_put_contents($tmp, $content);
        @chmod($tmp, 0644);
        rename($tmp, $target);
    }

    // Makale silme
    public function delete()
    {
        $this->requirePost();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();

                $stmt = $pdo->prepare("SELECT image_url FROM articles WHERE id = ?");
                $stmt->execute([$id]);
                $article = $stmt->fetch(\PDO::FETCH_ASSOC);

                $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);

                if ($article && !empty($article['image_url'])) {
                    $this->safeUnlinkUpload($article['image_url']);
                }

                $this->markSitemapDirty();
            }
            catch (\PDOException $e) {
                throw new \Exception("Makale Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/dashboard?status=deleted');
    }

    // Taslak yayınlama
    public function publish()
    {
        $this->requirePost();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $pdo->prepare("UPDATE articles SET status = 'published' WHERE id = ?")
                    ->execute([$id]);
                $this->markSitemapDirty();
            } catch (\PDOException $e) {
                throw new \Exception("Yayınlama Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/dashboard?status=published');
        exit;
    }

    // Çıkış yapma
    public function logout()
    {
        $this->requirePost();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: /');
        exit;
    }

    // Admin profil sayfası
    public function profile()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }
        $this->view('yonetim/profile');
    }

    public function logs()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }
        $filters = [
            'level' => $_GET['level'] ?? '',
            'endpoint' => $_GET['endpoint'] ?? '',
            'q' => $_GET['q'] ?? '',
        ];
        $this->view('yonetim/logs', [
            'logs' => AdminLog::recent(150, $filters),
            'filters' => $filters,
        ]);
    }

    // Admin şifre değiştirme
    public function updatePassword()
    {
        $this->requirePost();

        $old_pass     = $_POST['old_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $username     = $_SESSION['admin_user'] ?? '';

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user || !password_verify($old_pass, $user['password'])) {
                header('Location: /yonetim/profile?status=wrong_pass');
                exit;
            }

            if ($new_pass !== $confirm_pass) {
                header('Location: /yonetim/profile?status=mismatch');
                exit;
            }

            // Şifre politikası: en az 12 karakter, en az bir harf + bir rakam
            if (mb_strlen($new_pass, 'UTF-8') < 12
                || !preg_match('/[A-Za-z]/', $new_pass)
                || !preg_match('/\d/', $new_pass)) {
                header('Location: /yonetim/profile?status=weak');
                exit;
            }

            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")
                ->execute([$hashed_pass, $user['id']]);

            header('Location: /yonetim/profile?status=success');
            exit;
        }
        catch (\PDOException $e) {
            throw new \Exception("Güncelleme Hatası: " . $e->getMessage());
        }
    }

    // --- Kategori yönetimi---

    public function categories()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM article_categories ac
                     JOIN articles a ON ac.article_id = a.id
                     WHERE ac.category_id = c.id AND a.status = 'published') as article_count,
                    (SELECT COUNT(*) FROM note_categories nc
                     JOIN notes n ON nc.note_id = n.id
                     WHERE nc.category_id = c.id) as note_count
                    FROM categories c
                    ORDER BY c.name ASC";

            $categories = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $this->view('yonetim/categories', ['categories' => $categories]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Kategori Listesi Hatası: " . $e->getMessage());
        }
    }

    public function storeCategory()
    {
        $this->requirePost();

        $name = mb_strtoupper(trim($_POST['name'] ?? ''), 'UTF-8');
        if ($name === '') {
            header('Location: /yonetim/categories?status=empty');
            exit;
        }

        try {
            $pdo  = $this->getPDO();
            $slug = $this->uniqueSlug($pdo, 'categories', $this->createSlug($name));
            $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")
                ->execute([$name, $slug]);
            header('Location: /yonetim/categories?status=success');
            exit;
        }
        catch (\PDOException $e) {
            throw new \Exception("Kategori Kayıt Hatası: " . $e->getMessage());
        }
    }

    public function deleteCategory()
    {
        $this->requirePost();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            }
            catch (\PDOException $e) {
                throw new \Exception("Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/categories?status=deleted');
        exit;
    }

    // --- Yazar yönetimi---

    // Yazarları listele
    public function authors()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->query("SELECT * FROM authors ORDER BY name ASC");
            $authors = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $artStmt = $pdo->query("SELECT id, title, author_id FROM articles ORDER BY created_at DESC");
            $all_author_articles = $artStmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('yonetim/authors', [
                'authors' => $authors,
                'all_author_articles' => $all_author_articles
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Yazar Listesi Hatası: " . $e->getMessage());
        }
    }

    // Yazar kaydet/güncelle
    public function authorStore()
    {
        $this->requirePost();

        $id           = (int)($_POST['id'] ?? 0);
        $name         = trim($_POST['name'] ?? 'isimsiz') ?: 'isimsiz';
        $bio          = trim($_POST['bio'] ?? '');
        $oldImagePath = trim($_POST['current_image'] ?? '');
        $image_path   = $oldImagePath;
        $twitter      = trim($_POST['twitter'] ?? '');
        $instagram    = trim($_POST['instagram'] ?? '');
        $website      = trim($_POST['website'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $featured_str = implode(',', array_map('intval', (array)($_POST['featured'] ?? [])));

        $newImageStored = null;

        try {
            $pdo  = $this->getPDO();
            $slug = $this->uniqueSlug($pdo, 'authors', $this->createSlug($name), $id ?: null);

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $storedPath = Upload::saveImageToR2($_FILES['image'], 'uploads/authors', 'author_', 5242880, $slug);
                if ($storedPath !== null) {
                    $image_path     = $storedPath;
                    $newImageStored = $storedPath;
                }
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE authors SET name = ?, slug = ?, bio = ?, image_url = ?, twitter = ?, instagram = ?, website = ?, email = ?, featured_articles = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $bio, $image_path, $twitter, $instagram, $website, $email, $featured_str, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO authors (name, slug, bio, image_url, twitter, instagram, website, email, featured_articles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $bio, $image_path, $twitter, $instagram, $website, $email, $featured_str]);
            }

            // Yeni resim yüklendiyse eskiyi temizle
            if ($newImageStored !== null && $oldImagePath !== '' && $oldImagePath !== $image_path) {
                $this->safeUnlinkUpload($oldImagePath);
            }

            header('Location: /yonetim/authors?status=success');
            exit;
        }
        catch (\PDOException $e) {
            if ($newImageStored !== null) $this->safeUnlinkUpload($newImageStored);
            throw new \Exception("Yazar Kayıt Hatası: " . $e->getMessage());
        }
    }

    // Yazar silme
    public function authorDelete()
    {
        $this->requirePost();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("SELECT image_url FROM authors WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetchColumn();

                $pdo->prepare("DELETE FROM authors WHERE id = ?")->execute([$id]);

                if ($img) {
                    $this->safeUnlinkUpload($img);
                }
            }
            catch (\PDOException $e) {
                throw new \Exception("Yazar Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/authors?status=deleted');
        exit;
    }

    // Summernote resim yükleme — her durumda JSON döner.
    public function uploadContentImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        $requestStarted = microtime(true);
        $requestId = $this->requestId();
        $baseContext = $this->uploadTelemetryContext([], $requestId);

        if (!isset($_SESSION['admin_logged_in'])) {
            $this->jsonError(403, 'Oturum bulunamadı. Sayfayı yenileyip tekrar giriş yapın.', [
                ...$baseContext,
            ], 'unauthorized');
        }

        if (!isset($_FILES['file'])) {
            $this->jsonError(400, 'Yüklenecek dosya alınamadı.', [
                ...$baseContext,
            ], 'missing_file');
        }

        $file = $_FILES['file'];
        $context = $this->uploadTelemetryContext($file, $requestId);
        $validateStarted = microtime(true);
        $uploadError = Upload::imageUploadError($file, 5242880);
        $context['timings_ms']['validation'] = $this->elapsedMs($validateStarted);
        if ($uploadError !== null) {
            $context['total_ms'] = $this->elapsedMs($requestStarted);
            $this->jsonError(400, $uploadError, $context, 'image_validation_failed');
        }

        try {
            $slugSeed = trim((string)($_POST['slug'] ?? ''));
            $storedPath = Upload::saveImageToR2($file, 'uploads/content', 'content_', 5242880, $slugSeed);
            $meta = Upload::lastMeta();
            $context['slug'] = $slugSeed;
            $context['meta'] = $meta;
            if ($storedPath === null) {
                $detail = Upload::lastError();
                $message = $detail !== ''
                    ? 'Görsel yüklenemedi: ' . $detail
                    : 'Görsel doğrulandı ama R2/CDN yüklemesi tamamlanamadı. R2 anahtarları, bucket veya PHP GD/WebP desteği kontrol edilmeli.';
                $context['detail'] = $detail;
                $context['total_ms'] = $this->elapsedMs($requestStarted);
                $this->jsonError(500, $message, $context, 'image_upload_failed');
            }

            $url = Upload::assetUrl($storedPath);
            $visibilityWarning = Upload::publicUrlWarning($storedPath, $url);
            $context['path'] = $storedPath;
            $context['url'] = $url;
            $context['total_ms'] = $this->elapsedMs($requestStarted);
            if ($visibilityWarning !== '') {
                $context['visibility_warning'] = $visibilityWarning;
            }

            AdminLog::write($visibilityWarning !== '' ? 'warning' : 'info', 'Görsel yüklendi.', $context);

            $response = [
                'success' => true,
                'url' => $url,
                'request_id' => $requestId,
                'path' => $storedPath,
                'meta' => $meta,
            ];
            if ($visibilityWarning !== '') {
                $response['visibility_warning'] = $visibilityWarning;
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $this->jsonError(500, 'Görsel yüklenirken sistem hatası oluştu: ' . $e->getMessage(), [
                ...$context,
                'total_ms' => $this->elapsedMs($requestStarted),
            ], 'image_system_error');
        }
        exit;
    }

    // --- Notlar ---

    public function addNote()
    {
        if (!isset($_SESSION['admin_logged_in'])) { header('Location: /yonetim'); exit; }

        try {
            $pdo = $this->getPDO();
            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $notes = $pdo->query("SELECT n.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
                                  FROM notes n
                                  LEFT JOIN note_categories nc ON n.id = nc.note_id
                                  LEFT JOIN categories c ON nc.category_id = c.id
                                  GROUP BY n.id
                                  ORDER BY n.created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);

            $stats = $pdo->query("SELECT COUNT(*) as total_count, SUM(file_size) as total_size FROM notes")->fetch(\PDO::FETCH_ASSOC);

            $this->view('yonetim/add-note', [
                'categories' => $categories,
                'notes'      => $notes,
                'stats'      => $stats,
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Not Listesi Hatası: " . $e->getMessage());
        }
    }

    public function storeNote()
    {
        $this->requirePost();

        $title              = trim($_POST['title'] ?? '');
        $desc               = $_POST['description'] ?? '';
        $uploader_name      = $_SESSION['admin_name'] ?? 'yonetim';
        $selectedCategories = $_POST['categories'] ?? [];
        $lang               = $this->normalizeNoteLang((string)($_POST['lang'] ?? 'TR'));

        $pdfError = isset($_FILES['pdf_file']) ? $this->pdfUploadError($_FILES['pdf_file']) : 'PDF dosyası seçilmedi.';
        if ($pdfError !== null) {
            AdminLog::write('warning', 'Not PDF yüklemesi reddedildi.', [
                'endpoint' => 'storeNote',
                'detail' => $pdfError,
                'name' => $_FILES['pdf_file']['name'] ?? '',
                'size' => $_FILES['pdf_file']['size'] ?? 0,
                'php_error' => $_FILES['pdf_file']['error'] ?? null,
            ]);
            Flash::set($pdfError);
            header('Location: /yonetim/add-note?error=invalid_pdf');
            exit;
        }

        $r2Path = null;
        try {
            $pdo  = $this->getPDO();
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            // Türkçe karakter güvenli slug + benzersizlik
            $slug = $this->uniqueSlug($pdo, 'notes', $this->createSlug($title));

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2Path = $r2->uploadPDF($_FILES['pdf_file']['tmp_name'], $slug);

            if (!$r2Path) {
                throw new \Exception("Cloudflare R2 yükleme hatası oluştu.");
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO notes (title, slug, description, r2_path, uploader_name, file_size, lang) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $desc, $r2Path, $uploader_name, (int)$_FILES['pdf_file']['size'], $lang]);
            $noteId = (int)$pdo->lastInsertId();

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO note_categories (note_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$noteId, $catId]);
                }
            }

            $pdo->commit();
            $this->markSitemapDirty();

            header('Location: /yonetim/add-note?status=success');
            exit;
        }
        catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // R2'ye yüklenmiş ama DB'ye yazılamamış dosyayı temizle (yetim önleme)
            if ($r2Path) {
                try {
                    if (!isset($r2)) {
                        require_once ROOT . '/app/Core/R2Storage.php';
                        $r2 = \App\Core\R2Storage::instance();
                    }
                    $r2->deletePDF($r2Path);
                } catch (\Throwable $cleanupErr) {
                    error_log("R2 cleanup failed: " . $cleanupErr->getMessage());
                }
            }
            error_log("Note Upload Error: " . $e->getMessage());
            header('Location: /yonetim/add-note?error=system_failure');
            exit;
        }
    }

    public function deleteNote()
    {
        $this->requirePost();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("SELECT r2_path FROM notes WHERE id = ?");
                $stmt->execute([$id]);
                $note = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($note) {
                    // Önce R2'den dene; başarısız olursa DB kaydı kalır (kullanıcı tekrar deneyebilir)
                    require_once ROOT . '/app/Core/R2Storage.php';
                    $r2 = \App\Core\R2Storage::instance();
                    $r2->deletePDF($note['r2_path']);

                    $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([$id]);
                    $this->markSitemapDirty();
                }
            }
            catch (\Exception $e) {
                error_log("Note Delete Error: " . $e->getMessage());
                header('Location: /yonetim/add-note?error=delete_failed');
                exit;
            }
        }
        header('Location: /yonetim/add-note?status=deleted');
        exit;
    }

    public function editNote()
    {
        if (!isset($_SESSION['admin_logged_in'])) { header('Location: /yonetim'); exit; }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: /yonetim/add-note?error=note_not_found'); exit; }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note) {
                http_response_code(404);
                header('Location: /yonetim/add-note?error=note_not_found');
                exit;
            }

            $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $noteCatsStmt = $pdo->prepare("SELECT category_id FROM note_categories WHERE note_id = ?");
            $noteCatsStmt->execute([$id]);
            $noteCategoryIds = $noteCatsStmt->fetchAll(\PDO::FETCH_COLUMN);

            $this->view('yonetim/edit-note', [
                'note'            => $note,
                'categories'      => $cats,
                'noteCategoryIds' => $noteCategoryIds,
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function updateNote()
    {
        $this->requirePost();

        $id                 = (int)($_POST['id'] ?? 0);
        $title              = trim($_POST['title'] ?? '');
        $desc               = $_POST['description'] ?? '';
        $lang               = $this->normalizeNoteLang((string)($_POST['lang'] ?? 'TR'));
        $selectedCategories = $_POST['categories'] ?? [];

        if ($id <= 0 || $title === '') {
            header('Location: /yonetim/add-note?error=missing_data');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE notes SET title = ?, description = ?, lang = ? WHERE id = ?");
            $stmt->execute([$title, $desc, $lang, $id]);

            $pdo->prepare("DELETE FROM note_categories WHERE note_id = ?")->execute([$id]);

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO note_categories (note_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            $pdo->commit();
            $this->markSitemapDirty();

            header('Location: /yonetim/add-note?status=updated');
            exit;
        }
        catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            error_log("Note Update Error: " . $e->getMessage());
            throw new \Exception("Sistem Hatası: " . $e->getMessage());
        }
    }

    // --- Galeri ---

    public function galeri()
    {
        if (!isset($_SESSION['admin_logged_in'])) { header('Location: /yonetim'); exit; }

        try {
            $pdo = $this->getPDO();
            DailyArtwork::ensureSchema($pdo);

            $today = DailyArtwork::today();
            $todayArt = DailyArtwork::findByDate($pdo, $today);
            $allArtworks = DailyArtwork::all($pdo, 365);

            $this->view('yonetim/galeri', [
                'todayArt' => $todayArt,
                'allArtworks' => $allArtworks,
                'today' => $today,
            ]);
        } catch (\Throwable $e) {
            AdminLog::write('error', 'Admin galeri sayfasi yuklenemedi.', [
                'endpoint' => 'galeri',
                'detail'   => $e->getMessage(),
            ]);
            throw new \Exception("Galeri Hatası: " . $e->getMessage());
        }
    }

    public function refreshDailyArt()
    {
        $this->requirePost();
        
        try {
            $pdo = $this->getPDO();
            DailyArtwork::ensureSchema($pdo);
            $today = DailyArtwork::today();

            require_once ROOT . '/app/Core/ArtProvider.php';
            $artwork = \ArtProvider::getRandomArtwork(true);

            if (!$artwork) {
                throw new \RuntimeException('Sanat eseri sağlayıcılarından yanıt alınamadı.');
            }

            DailyArtwork::deleteByDate($pdo, $today);
            $saved = DailyArtwork::saveForDate($pdo, $artwork, $today);
            AdminLog::write('info', 'Galeri günün eseri yenilendi.', [
                'endpoint' => 'refreshDailyArt',
                'date' => $today,
                'title' => $saved['title'] ?? ($artwork['title'] ?? ''),
                'provider' => $saved['provider'] ?? ($artwork['provider'] ?? ''),
            ]);

            header('Location: /yonetim/galeri?status=refreshed');
            exit;

        } catch (\Exception $e) {
            error_log("Galeri Refresh Error: " . $e->getMessage());
            AdminLog::write('error', 'Galeri günün eseri yenilenemedi.', [
                'endpoint' => 'refreshDailyArt',
                'detail' => $e->getMessage(),
            ]);
            header('Location: /yonetim/galeri?error=refresh_failed');
            exit;
        }
    }

    public function updateArtDescription()
    {
        $this->requirePost();
        
        $id = (int)($_POST['id'] ?? 0);
        $descTr = $_POST['description_tr'] ?? '';
        
        if ($id <= 0) {
            header('Location: /yonetim/galeri?error=invalid_id');
            exit;
        }
        
        try {
            $pdo = $this->getPDO();
            DailyArtwork::ensureSchema($pdo);
            $stmt = $pdo->prepare("UPDATE daily_artworks SET description_tr = ?, description_source = 'manual' WHERE id = ?");
            $stmt->execute([$descTr, $id]);
            AdminLog::write('info', 'Galeri açıklaması güncellendi.', [
                'endpoint' => 'updateArtDescription',
                'artwork_id' => $id,
            ]);
            
            header('Location: /yonetim/galeri?status=updated');
            exit;
        } catch (\PDOException $e) {
            error_log("Galeri Update Error: " . $e->getMessage());
            AdminLog::write('error', 'Galeri açıklaması güncellenemedi.', [
                'endpoint' => 'updateArtDescription',
                'artwork_id' => $id,
                'detail' => $e->getMessage(),
            ]);
            header('Location: /yonetim/galeri?error=update_failed');
            exit;
        }
    }
}
