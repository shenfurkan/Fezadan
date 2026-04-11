<?php
class AdminController extends Controller
{

    // Database bağlantı fonksiyonu
    private function getPDO()
    {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new \PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function __construct()
    {
        $currentMethod = $_GET['url'] ?? ''; 
        
        if (!isset($_SESSION['admin_logged_in']) && !in_array($currentMethod, ['admin', 'admin/login'])) {
            header('Location: /admin');
            exit;
        }
    }

    // Admin giriş sayfası
    public function index()
    {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: /admin/dashboard');
            exit;
        }
        $this->view('admin/login');
    }

    // Admin girişi
    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            $pdo = $this->getPDO();
            // Admin kullanıcı adı arama
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Kullanıcı adı ve şifre kontrolü
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['username'];

                // Son giriş zamanı güncellemesi
                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                header('Location: /admin/dashboard');
            }
            else {
                // Geçersiz giriş
                header('Location: /admin?error=1');
            }
        }
        catch (\PDOException $e) {
            die("Giriş Hatası: " . $e->getMessage());
        }
    }

    // Admin arayüzü
    public function dashboard()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
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
                'category' => 'category_names'
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
            $totalRealCount = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
            $totalReads = (int)$pdo->query("SELECT SUM(`reads`) FROM articles")->fetchColumn();

            $stats = [
                'total_articles' => $totalRealCount,
                'total_reads' => $totalReads,
                'system_status' => 'Aktif',
                'last_login' => date('H:i')
            ];

            $this->view('admin/dashboard', [
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
            die("Dashboard Hatası: " . $e->getMessage());
        }
    }

    // --- Yama Notları ---

    // Yama notu oluşturma sayfası
    public function createPatch()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
            exit;
        }
        $this->view('admin/create-patch');
    }

    // Kaydetme
    public function storePatch()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $title = $_POST['title'] ?? 'Sistem Güncellemesi';
        $content = $_POST['content'] ?? '';
        $author = $_SESSION['admin_user'] ?? 'Admin';

        try {
            $pdo = $this->getPDO();

            // Yama id
            $stmt = $pdo->query("SELECT MAX(id) FROM patch_notes");
            $lastId = $stmt->fetchColumn();
            $nextId = $lastId ? $lastId + 1 : 1;
            $autoVersion = '1.' . $nextId;

            // Kaydetme
            $stmt = $pdo->prepare("INSERT INTO patch_notes (version, title, content, author) VALUES (?, ?, ?, ?)");
            $stmt->execute([$autoVersion, $title, $content, $author]);

            header('Location: /admin/dashboard?status=patch_added');
        }
        catch (\PDOException $e) {
            die("Yama Notu Kayıt Hatası: " . $e->getMessage());
        }
    }

    // Yama notu silme
    public function patchDelete()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("DELETE FROM patch_notes WHERE id = ?");
                $stmt->execute([$id]);
            }
            catch (\PDOException $e) {
                die("Yama Notu Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /admin/dashboard?status=patch_deleted');
    }


    // --- Makale yönetimi ---

    // Yeni makale oluşturma sayfası
    public function create()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('admin/create', [
                'authors' => $authors,
                'categories' => $categories
            ]);
        }
        catch (\PDOException $e) {
            die("Veri Çekilemedi: " . $e->getMessage());
        }
    }

    // Makale kaydetme
    public function store()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $title = $_POST['title'] ?? 'Adsız';
        $desc = $_POST['desc'] ?? '';
        $content = $_POST['content'] ?? '';
        $refs = $_POST['refs'] ?? '';
        $author_id = $_POST['author_id'] ?? null;
        $selectedCategories = $_POST['categories'] ?? [];
        $slug = $this->createSlug($title);
        $image_db_path = '';

        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $file = $_FILES['cover_image'];
            if (getimagesize($file['tmp_name']) !== false) {
                $upload_dir = ROOT . '/public_html/uploads/';
                if (!file_exists($upload_dir))
                    mkdir($upload_dir, 0777, true);
                $new_name = uniqid('cover_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    $image_db_path = '/uploads/' . $new_name;
                }
            }
        }

        try {
            $pdo = $this->getPDO();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO articles (title, slug, short_desc, content, refs, image_url, author_id) VALUES (:title, :slug, :desc, :content, :refs, :img, :author_id)");
            $stmt->execute([
                ':title' => $title, ':slug' => $slug, ':desc' => $desc,
                ':content' => $content,
                ':refs' => $refs,
                ':img' => $image_db_path, ':author_id' => $author_id
            ]);
            $articleId = $pdo->lastInsertId();

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$articleId, $catId]);
                }
            }

            $pdo->commit();
            $this->generateSitemap();
            header('Location: /admin/dashboard?status=success');
        }
        catch (\PDOException $e) {
            $pdo->rollBack();
            die("Makale Kayıt Hatası: " . $e->getMessage());
        }
    }

    // Makale silme
    public function delete()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $pdo = $this->getPDO();

                // Kapak fotoğrafı silme
                $stmt = $pdo->prepare("SELECT image_url FROM articles WHERE id = ?");
                $stmt->execute([$id]);
                $article = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($article && !empty($article['image_url'])) {
                    $file_path = ROOT . '/public_html' . $article['image_url'];
                    if (file_exists($file_path))
                        unlink($file_path);
                }

                $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
                $this->generateSitemap();

            }
            catch (\PDOException $e) {
                die("Makale Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /admin/dashboard?status=deleted');
    }

    // Çıkış yapma
    public function logout()
    {
        session_destroy();
        header('Location: /');
    }

    // Admin profil sayfası
    public function profile()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
            exit;
        }
        $this->view('admin/profile');
    }

    // Admin şifre değiştirme
    public function updatePassword()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $username = $_SESSION['admin_user'];

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Eski şifreyi kontrol et
            if ($user && password_verify($old_pass, $user['password'])) {

                if ($new_pass === $confirm_pass && !empty($new_pass)) {
                    // Yeni şifreyi hashle ve kaydet
                    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $update->execute([$hashed_pass, $user['id']]);

                    header('Location: /admin/profile?status=success');
                }
                else {
                    header('Location: /admin/profile?status=mismatch');
                }
            }
            else {
                header('Location: /admin/profile?status=wrong_pass');
            }
        }
        catch (\PDOException $e) {
            die("Güncelleme Hatası: " . $e->getMessage());
        }
    }

    // --- Kategori yönetimi ---

    // Kategori listesi
    public function categories()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $sql = "SELECT c.*, COUNT(a.id) as article_count 
                    FROM categories c 
                    LEFT JOIN article_categories ac ON c.id = ac.category_id 
                    LEFT JOIN articles a ON ac.article_id = a.id 
                    GROUP BY c.id 
                    ORDER BY c.name ASC";

            $categories = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('admin/categories', ['categories' => $categories]);
        }
        catch (\PDOException $e) {
            die("Kategori Listesi Hatası: " . $e->getMessage());
        }
    }

    // Yeni kategoriyi kaydet
    public function storeCategory()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $name = mb_strtoupper(trim($_POST['name'] ?? ''), 'UTF-8');

        if (empty($name)) {
            header('Location: /admin/categories?status=empty');
            exit;
        }

        // Kategori slug
        $slug = $this->createSlug($name);

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            header('Location: /admin/categories?status=success');
        }
        catch (\PDOException $e) {
            header('Location: /admin/categories?status=error');
        }
    }

    // Category silme
    public function deleteCategory()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
            }
            catch (\PDOException $e) {
                die("Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /admin/categories?status=deleted');
    }

    // --- Makale düzenleme ---

    // Düzenleme sayfası
    public function edit()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /admin/dashboard');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$id]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article) {
                die("Makale bulunamadı.");
            }

            $stmtCat = $pdo->prepare("SELECT category_id FROM article_categories WHERE article_id = ?");
            $stmtCat->execute([$id]);
            $selectedCategories = $stmtCat->fetchAll(\PDO::FETCH_COLUMN);

            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('admin/edit', [
                'article' => $article,
                'categories' => $categories,
                'authors' => $authors,
                'selectedCategories' => $selectedCategories
            ]);

        }
        catch (\PDOException $e) {
            die("Veri Hatası: " . $e->getMessage());
        }
    }

    // Makale güncelleme
    public function update()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? '';
        $desc = $_POST['desc'] ?? '';
        $content = $_POST['content'] ?? '';
        $refs = $_POST['refs'] ?? '';
        $author_id = $_POST['author_id'] ?? null;
        $selectedCategories = $_POST['categories'] ?? [];
        $current_image = $_POST['current_image'] ?? '';
        $image_db_path = $current_image;

        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $upload_dir = ROOT . '/public_html/uploads/';
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $new_name = uniqid('cover_') . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $new_name)) {
                $image_db_path = '/uploads/' . $new_name;
            }
        }

        try {
            $pdo = $this->getPDO();
            $pdo->beginTransaction();

            // Makale bilgisini güncelle
            $sql = "UPDATE articles SET title = ?, short_desc = ?, content = ?, refs = ?, author_id = ?, image_url = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $desc, $content, $refs, $author_id, $image_db_path, $id]);

            // Kategori sayısını güncelle
            $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$id]);

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            $pdo->commit();
            $this->generateSitemap();
            header('Location: /admin/dashboard?status=updated');

        }
        catch (\PDOException $e) {
            $pdo->rollBack();
            die("Güncelleme Hatası: " . $e->getMessage());
        }
    }

    // --- Sitemap ---
    private function generateSitemap()
    {
        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->query("SELECT slug, created_at FROM articles ORDER BY created_at DESC");
            $articles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // XML oluştur
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

            // Anasayfa
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>https://fezadan.org/</loc>' . PHP_EOL;
            $xml .= '    <changefreq>daily</changefreq>' . PHP_EOL;
            $xml .= '    <priority>1.0</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;

            // Makaleler sayfası
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>https://fezadan.org/makaleler</loc>' . PHP_EOL;
            $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.8</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;

            // Her makale için URL
            foreach ($articles as $article) {
                $lastMod = date('Y-m-d', strtotime($article['created_at']));
                $xml .= '  <url>' . PHP_EOL;
                $xml .= '    <loc>https://fezadan.org/makale/' . $article['slug'] . '</loc>' . PHP_EOL;
                $xml .= '    <lastmod>' . $lastMod . '</lastmod>' . PHP_EOL;
                $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
                $xml .= '    <priority>0.6</priority>' . PHP_EOL;
                $xml .= '  </url>' . PHP_EOL;
            }

            $xml .= '</urlset>';

            // Sitemap'i kaydet
            $filePath = ROOT . '/public_html/sitemap.xml';
            file_put_contents($filePath, $xml);

        }
        catch (\Exception $e) {
        }
    }

    // --- Yazar yönetimi---

    // Yazarları listele
    public function authors()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->query("SELECT * FROM authors ORDER BY name ASC");
            $authors = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $artStmt = $pdo->query("SELECT id, title, author_id FROM articles ORDER BY created_at DESC");
            $all_author_articles = $artStmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('admin/authors', [
                'authors' => $authors,
                'all_author_articles' => $all_author_articles
            ]);
        }
        catch (\PDOException $e) {
            die("Yazar Listesi Hatası: " . $e->getMessage());
        }
    }

    // Yazar kaydet/güncelle
    public function authorStore()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? 'İsimsiz');
            $bio = trim($_POST['bio'] ?? '');
            $image_path = $_POST['current_image'] ?? '';
            $slug = $this->createSlug($name);
            $twitter = trim($_POST['twitter'] ?? '');
            $instagram = trim($_POST['instagram'] ?? '');
            $website = trim($_POST['website'] ?? '');
            $featured = $_POST['featured'] ?? [];
            $featured_str = implode(',', $featured);

            // Yazar profil resmi
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = ROOT . '/public_html/uploads/authors/';
                if (!file_exists($upload_dir))
                    mkdir($upload_dir, 0777, true);

                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_name = uniqid('author_') . '.' . $ext;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    $image_path = '/uploads/authors/' . $new_name;
                }
            }

            try {
                $pdo = $this->getPDO();

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE authors SET name = ?, slug = ?, bio = ?, image_url = ?, twitter = ?, instagram = ?, website = ?, featured_articles = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $bio, $image_path, $twitter, $instagram, $website, $featured_str, $id]);
                }
                else {
                    $stmt = $pdo->prepare("INSERT INTO authors (name, slug, bio, image_url, twitter, instagram, website, featured_articles) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $bio, $image_path, $twitter, $instagram, $website, $featured_str]);
                }
                
                header('Location: /admin/authors?status=success');
                exit;
            }
            catch (\PDOException $e) {
                die("Yazar Kayıt Hatası: " . $e->getMessage());
            }
        }
    }

    // Yazar silme
    public function authorDelete()
    {
        if (!isset($_SESSION['admin_logged_in']))
            exit;

        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $pdo = $this->getPDO();
                $pdo->prepare("DELETE FROM authors WHERE id = ?")->execute([$id]);
            }
            catch (\PDOException $e) {
                die("Yazar Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /admin/authors?status=deleted');
        exit;
    }

    // Summernote resim yükleme
    public function uploadContentImage()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_FILES['file']['name']) {
            if (!$_FILES['file']['error']) {
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($_FILES['file']['size'] > $maxSize) {
                    echo json_encode(['error' => 'Dosya boyutu 5MB\'ı geçemez.']);
                    exit;
                }

                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $upload_dir = ROOT . '/public_html/uploads/content/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $new_name = uniqid('content_') . '.' . $ext;
                    $destination = $upload_dir . $new_name;
                    $location = $_FILES["file"]["tmp_name"];

                    if (move_uploaded_file($location, $destination)) {
                        $siteUrl = defined('SITE_URL') ? SITE_URL : '/';
                        $siteUrl = rtrim($siteUrl, '/');
                        echo $siteUrl . '/uploads/content/' . $new_name;
                        exit;
                    }
                    else {
                        echo json_encode(['error' => 'Dosya yüklenemedi.']);
                        exit;
                    }
                }
                else {
                    echo json_encode(['error' => 'Geçersiz dosya formatı.']);
                    exit;
                }
            }
            else {
                echo json_encode(['error' => 'Yükleme hatası: ' . $_FILES['file']['error']]);
                exit;
            }
        }
    }
}