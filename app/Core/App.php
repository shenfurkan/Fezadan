<?php

/**
 * Fezadan platformunun merkezi yönlendirici ve istek dağıtıcısı.
 *
 * Host tabanlı yönlendirme (ana site, notlar subdomain, anonimlik kontrolü),
 * dil öneki tespiti (tr/en), yol temizleme ve controller dağıtımını
 * ROUTABLE_ACTIONS izin listesiyle yönetir.
 *
 * ## Yönlendirme akışı
 *
 * 1. **Yol temizleme** — path traversal ve kodlanmış slash'leri engeller.
 * 2. **Engellenen rotalar** — /admin, /panel, /dashboard kasıtlı olarak 404.
 * 3. **Statik dosyalar** — robots.txt ve sitemap XML doğrudan sunulur.
 * 4. **Host dağıtımı** — anonymitycheck.* ve notlar.* subdomain'leri
 *    kendi controller'larına yönlendirilir.
 * 5. **Dil yönlendirmesi** — /tr veya /en öneki eksikse çerez tercihi
 *    veya Accept-Language başlığı ile yönlendirme yapılır.
 * 6. **Ana site yönlendirme** — URL segmenti bir controller sınıfına
 *    eşlenir, ROUTABLE_ACTIONS ile doğrulanır ve dağıtılır.
 * 7. **Yedek** — statik sayfalar ve yazar/makale kısayol aramaları.
 *
 * ## Yeni rota ekleme
 *
 * 1. `app/Controllers/` altında bir controller oluşturun veya mevcut kullanın.
 * 2. Her genel erişimli metod adını aşağıdaki `ROUTABLE_ACTIONS` sabitine
 *    ekleyin. Eksik girişler 404 ile sonuçlanır.
 *
 * @see App::ROUTABLE_ACTIONS Rota izin listesi
 * @see App::dispatchController() Ana dağıtım mantığı
 */
class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    public static $lang = 'TR';

    private const ROUTABLE_ACTIONS = [
        'AdminController' => [
            'index', 'login', 'dashboard', 'logout',
            'createPatch', 'storePatch', 'deletePatch', 'patchDelete',
            'create', 'store', 'edit', 'update', 'delete', 'publish', 'uploadContentImage',
            'categories', 'storeCategory', 'deleteCategory',
            'addNote', 'storeNote', 'deleteNote', 'editNote', 'updateNote',
            'registerPasskeyChallenge', 'registerPasskeyVerify',
            'loginPasskeyChallenge', 'loginPasskeyVerify', 'deletePasskey',
            'portfolio', 'storePortfolio', 'portfolioStore', 'portfolioEdit', 'updatePortfolio', 'portfolioUpdate', 'deletePortfolio', 'portfolioDelete', 'portfolioReorder',
            'generateSitemap',
            'forgotPassword', 'resetMailPreview', 'sendResetLink', 'resetPassword', 'updateResetPassword',
            'admins', 'storeAdmin', 'profile', 'logs', 'updatePassword', 'updateProfile',
            'generateTts', 'generateOgImage',
            'authors', 'storeAuthor', 'authorStore', 'deleteAuthor', 'authorDelete',
        ],
        'AnonymityCheckController' => ['index', 'torCheck', 'ispLookup', 'geoLookup', 'save'],
        'ArticleController' => ['index', 'qr', 'count'],
        'AnalyticsController' => ['track'],
        'ArticlesController' => ['index'],
        'AuthorController' => ['index'],
        'HomeController' => ['index', 'privacy'],
        'NotesController' => ['index', 'read', 'viewPdf', 'download', 'rss'],
        'PageController' => ['renderPage'],
        'RssController' => ['index'],
        'SearchController' => ['autocomplete'],
        'SeoController' => ['robots', 'sitemapIndex', 'sitemapMain', 'sitemapNotes', 'sitemapAnonymity'],
        'UploadsController' => ['index'],
    ];

    public static function getLang(): string {
        return self::$lang;
    }

    public function __construct() {
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ||
            isset($_SERVER['HTTP_X_HTTP_METHOD']) ||
            isset($_SERVER['HTTP_X_METHOD_OVERRIDE'])) {
            http_response_code(400);
            echo 'Bad Request: Method override headers are not allowed.';
            exit;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $parts = explode('?', $requestUri, 2);
        $rawPath = $parts[0];
        $queryString = isset($parts[1]) ? '?' . $parts[1] : '';

        $rawUriLower = strtolower($requestUri);
        if (strpos($rawUriLower, '%2e%2e') !== false ||
            strpos($rawUriLower, '%2e.') !== false ||
            strpos($rawUriLower, '.%2e') !== false ||
            strpos($rawUriLower, '..') !== false) {
            http_response_code(400);
            echo 'Bad Request: Path traversal detected.';
            exit;
        }

        if (strpos($rawUriLower, '%2f') !== false) {
            http_response_code(400);
            echo 'Bad Request: Encoded slashes are not allowed.';
            exit;
        }

        $canonicalPath = rawurldecode($rawPath);
        foreach (explode('/', $canonicalPath) as $segment) {
            if ($segment === '.' || $segment === '..') {
                http_response_code(400);
                echo 'Bad Request: Path traversal detected.';
                exit;
            }
        }

        $canonicalPath = preg_replace('#/{2,}#', '/', $canonicalPath);
        if ($canonicalPath !== '/' && substr($canonicalPath, -1) === '/') {
            $canonicalPath = rtrim($canonicalPath, '/');
        }

        if ($rawPath !== $canonicalPath) {
            http_response_code(301);
            header('Location: ' . $canonicalPath . $queryString);
            exit;
        }

        $pathLower = strtolower($canonicalPath);
        if (
            in_array($pathLower, ['/admin', '/panel', '/dashboard'], true) ||
            strpos($pathLower, '/admin/') === 0 ||
            strpos($pathLower, '/panel/') === 0 ||
            strpos($pathLower, '/dashboard/') === 0
        ) {
            $this->render404();
        }

        if ($pathLower === '/robots.txt') {
            require_once ROOT . '/app/Controllers/SeoController.php';
            $seo = new SeoController();
            $seo->robots();
            exit;
        }

        if (in_array($pathLower, ['/sitemap.xml', '/sitemap_main.xml', '/sitemap_notes.xml', '/sitemap_anonymity.xml'], true)) {
            $filePath = ROOT . '/public_html' . $pathLower;
            if (file_exists($filePath)) {
                header('Content-Type: application/xml; charset=utf-8');
                readfile($filePath);
                exit;
            }

            require_once ROOT . '/app/Controllers/SeoController.php';
            $seo = new SeoController();
            if ($pathLower === '/sitemap.xml') {
                strpos($host, 'notlar.') === 0 ? $seo->sitemapNotes() : $seo->sitemapIndex();
            } elseif ($pathLower === '/sitemap_main.xml') {
                $seo->sitemapMain();
            } elseif ($pathLower === '/sitemap_anonymity.xml') {
                $seo->sitemapAnonymity();
            } else {
                $seo->sitemapNotes();
            }
            exit;
        }

        $url = $this->parseUrl();
        $hasLangPrefix = $this->detectLanguagePrefix($url);

        if (strpos($host, 'anonymitycheck.') === 0) {
            $this->routeAnonymityCheck($url);
            return;
        }

        if (strpos($host, 'notlar.') === 0) {
            $this->routeNotes($url);
            return;
        }

        $this->redirectAnonymityPath($url, $queryString);
        $this->redirectMissingLanguagePrefix($hasLangPrefix, $canonicalPath, $queryString);
        $this->routeMainSite($url);
    }

    private function detectLanguagePrefix(array &$url): bool
    {
        $first = strtolower($url[0] ?? '');
        if ($first === 'en') {
            self::$lang = 'EN';
            array_shift($url);
            if (empty($url)) {
                $url = ['home'];
            }
            // 30 günlük dil tercihi çerezi
            if (!headers_sent()) {
                setcookie('fezadan_lang', 'en', [
                    'expires'  => time() + 2592000,
                    'path'     => '/',
                    'samesite' => 'Lax',
                    'httponly' => true,
                    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                ]);
            }
            return true;
        }

        if ($first === 'tr') {
            self::$lang = 'TR';
            array_shift($url);
            if (empty($url)) {
                $url = ['home'];
            }
            // 30 günlük dil tercihi çerezi
            if (!headers_sent()) {
                setcookie('fezadan_lang', 'tr', [
                    'expires'  => time() + 2592000,
                    'path'     => '/',
                    'samesite' => 'Lax',
                    'httponly' => true,
                    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                ]);
            }
            return true;
        }

        return false;
    }

    private function routeAnonymityCheck(array $url): void
    {
        require_once ROOT . '/app/Controllers/AnonymityCheckController.php';
        $this->controller = new AnonymityCheckController();
        $this->method = 'index';
        if (isset($url[0])) {
            $methodName = $this->resolveRoutableMethod($this->controller, $url[0]);
            if ($methodName !== null) {
                $this->method = $methodName;
                unset($url[0]);
            }
        }
        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    private function routeNotes(array $url): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod !== 'GET' && $requestMethod !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD');
            echo 'Method Not Allowed';
            exit;
        }

        require_once ROOT . '/app/Controllers/NotesController.php';
        $this->controller = new NotesController();

        if (strtolower($url[0] ?? '') === 'rss') {
            $this->method = 'rss';
            $this->params = [];
        } elseif (($url[0] ?? '') === 'not') {
            if (($url[1] ?? '') === 'download') {
                $this->method = 'download';
                $this->params = isset($url[2]) ? [$url[2]] : [];
            } elseif (($url[1] ?? '') === 'view') {
                $this->method = 'viewPdf';
                $this->params = isset($url[2]) ? [$url[2]] : [];
            } else {
                $this->method = 'read';
                $this->params = isset($url[1]) ? [$url[1]] : [];
            }
        } else {
            $this->method = 'index';
            $this->params = [];
        }

        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    private function redirectAnonymityPath(array $url, string $queryString): void
    {
        $firstSegmentLower = strtolower($url[0] ?? '');
        if ($firstSegmentLower !== 'anonymitycheck' && $firstSegmentLower !== 'anonymity-check') {
            return;
        }

        $redirectPath = '/anonymitycheck/';
        if (isset($url[1]) && $url[1] !== '') {
            $redirectPath .= implode('/', array_slice($url, 1));
        }
        if ($queryString !== '') {
            $redirectPath .= $queryString;
        }
        http_response_code(301);
        header('Location: ' . $redirectPath);
        exit;
    }

    private function redirectMissingLanguagePrefix(bool $hasLangPrefix, string $canonicalPath, string $queryString): void
    {
        if ($hasLangPrefix) {
            return;
        }

        $pathLower = strtolower($canonicalPath);
        $excludePrefixes = ['/yonetim', '/uploads', '/cdn', '/assets', '/scripts', '/admin', '/panel', '/dashboard'];
        foreach ($excludePrefixes as $prefix) {
            if ($pathLower === $prefix || strpos($pathLower, $prefix . '/') === 0) {
                return;
            }
        }

        if ($canonicalPath === '/') {
            $preferredLang = 'tr'; // site default

            // 1. Çerez önceliklidir — kullanıcının 30 günlük tercihi hatırlanır
            $cookieLang = strtolower(trim($_COOKIE['fezadan_lang'] ?? ''));
            if (in_array($cookieLang, ['tr', 'en'], true)) {
                $preferredLang = $cookieLang;
            } else {
                // 2. Accept-Language başlığına düş (yalnızca ilk ziyarette, çerez yoksa)
                $acceptLang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
                $enPos = strpos($acceptLang, 'en');
                $trPos = strpos($acceptLang, 'tr');
                if ($enPos !== false && ($trPos === false || $enPos < $trPos)) {
                    $preferredLang = 'en';
                }
            }

            http_response_code(307);
            header('Location: /' . $preferredLang . $queryString);
            exit;
        }

        http_response_code(301);
        header('Location: /tr' . $canonicalPath . $queryString);
        exit;
    }

    private function routeMainSite(array $url): void
    {
        $firstSegment = $url[0] ?? 'home';
        if ($firstSegment === '') {
            $firstSegment = 'home';
        }

        $controllerMapping = [
            'yonetim' => 'Admin',
            'makale' => 'Article',
            'article' => 'Article',
            'makaleler' => 'Articles',
            'articles' => 'Articles',
            'yazar' => 'Author',
            'author' => 'Author',
            'notlar' => 'Notes',
            'notes' => 'Notes',
            'sitemap.xml' => 'Seo',
            'sitemap_main.xml' => 'Seo',
            'sitemap_notes.xml' => 'Seo',
            'sitemap_anonymity.xml' => 'Seo',
            'robots.txt' => 'Seo',
        ];

        $mappedSegment = $controllerMapping[strtolower($firstSegment)] ?? $firstSegment;
        $controllerClass = ucfirst($mappedSegment) . 'Controller';

        if (isset(self::ROUTABLE_ACTIONS[$controllerClass])) {
            $controllerFile = ROOT . '/app/Controllers/' . $controllerClass . '.php';
            if (file_exists($controllerFile)) {
                array_shift($url);
                $this->dispatchController($controllerClass, array_values($url));
                return;
            }
        }

        $staticPage = $this->resolveStaticPage($firstSegment);
        if ($staticPage !== null) {
            require_once ROOT . '/app/Controllers/PageController.php';
            $this->controller = new PageController();
            $this->method = 'renderPage';
            $this->params = [$staticPage];
            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }

        $this->routeAuthorArticleShortcut($firstSegment, array_values($url));
    }

    private function dispatchController(string $controllerClass, array $segments): void
    {
        require_once ROOT . '/app/Controllers/' . $controllerClass . '.php';

        if (!isset(self::ROUTABLE_ACTIONS[$controllerClass])) {
            $this->render404();
        }

        $this->controller = new $controllerClass();
        $this->method = 'index';

        if ($controllerClass === 'AuthorController') {
            if (($segments[0] ?? '') === 'index') {
                array_shift($segments);
            }
            if (count($segments) > 1) {
                $this->render404();
            }
            $this->params = array_values($segments);
            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }

        if ($controllerClass === 'ArticleController') {
            $methodName = isset($segments[0]) ? $this->resolveRoutableMethod($this->controller, $segments[0]) : null;
            if ($methodName !== null) {
                $this->method = $methodName;
                array_shift($segments);
            }
            $this->params = array_values($segments);
            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }

        if ($controllerClass === 'UploadsController') {
            $this->params = array_values($segments);
            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }

        if (isset($segments[0])) {
            $methodName = $this->resolveRoutableMethod($this->controller, $segments[0]);
            if ($methodName === null) {
                $this->render404();
            }
            $this->method = $methodName;
            array_shift($segments);
        } elseif (!$this->isRoutableAction($controllerClass, $this->method)) {
            $this->render404();
        }

        $this->params = array_values($segments);
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    private function resolveStaticPage(string $segment): ?string
    {
        $staticPageMapping = [
            'about' => 'about',
            'hakkinda' => 'about',
            'manifesto' => 'manifesto',
            'privacy' => 'privacy',
            'gizlilik-politikasi' => 'privacy',
            'verification' => 'verification',
            'teyit' => 'verification',
        ];

        $page = $staticPageMapping[strtolower($segment)] ?? null;
        if ($page === null) {
            return null;
        }

        return file_exists(ROOT . '/app/Views/front/' . $page . '.php') ? $page : null;
    }

    private function routeAuthorArticleShortcut(string $firstSegment, array $url): void
    {
        require_once ROOT . '/app/Core/Db.php';
        try {
            $pdo = Db::pdo();
            $stmt = $pdo->prepare("SELECT id FROM authors WHERE slug = ? LIMIT 1");
            $stmt->execute([$firstSegment]);
            $authorExists = $stmt->fetchColumn();
        } catch (\Exception $e) {
            $authorExists = false;
        }

        if (!$authorExists) {
            $this->render404();
        }

        if (!isset($url[1]) || $url[1] === '') {
            http_response_code(301);
            header('Location: ' . authorUrl($firstSegment));
            exit;
        }

        require_once ROOT . '/app/Controllers/ArticleController.php';
        $this->controller = new ArticleController();
        $this->method = 'index';
        $this->params = [$url[1], $firstSegment];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }
    public function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return ['home'];
    }

    private function resolveRoutableMethod(object $controller, string $segment): ?string
    {
        $candidates = array_values(array_unique([
            $segment,
            lcfirst(str_replace('-', '', ucwords($segment, '-'))),
        ]));

        foreach ($candidates as $method) {
            if ($method === '' || !method_exists($controller, $method)) {
                continue;
            }
            if ($this->isRoutableAction(get_class($controller), $method)) {
                return $method;
            }
        }

        return null;
    }

    private function isRoutableAction(string $controllerClass, string $method): bool
    {
        return in_array($method, self::ROUTABLE_ACTIONS[$controllerClass] ?? [], true);
    }

    private function render404(): void {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $view = ROOT . '/app/Views/errors/404.php';
        if (is_file($view)) {
            require $view;
        } else {
            echo 'Sayfa bulunamadı.';
        }
        exit;
    }
}
