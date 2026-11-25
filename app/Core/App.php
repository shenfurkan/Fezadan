<?php
class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    public static $lang = 'TR';

    private const ROUTABLE_ACTIONS = [
        'AdminController' => [
            'index', 'login', 'dashboard', 'logout',
            'createPatch', 'storePatch', 'deletePatch', 'patchDelete',
            'create', 'store', 'edit', 'update', 'delete', 'publish', 'uploadContentImage', 'prepareScientificImport',
            'unlockAdmin', 'adminResetLink', 'deleteAdmin', 'updateAdminRole',
            'categories', 'storeCategory', 'deleteCategory',
            'addNote', 'storeNote', 'deleteNote', 'editNote', 'updateNote',
            'registerPasskeyChallenge', 'registerPasskeyVerify',
            'loginPasskeyChallenge', 'loginPasskeyVerify', 'deletePasskey',
            'portfolio', 'storePortfolio', 'portfolioStore', 'portfolioEdit', 'updatePortfolio', 'portfolioUpdate', 'deletePortfolio', 'portfolioDelete', 'deleteAllPortfolio', 'portfolioDeleteAll', 'portfolioReorder',
            'generateSitemap',
            'forgotPassword', 'resetMailPreview', 'sendResetLink', 'resetPassword', 'updateResetPassword',
            'admins', 'storeAdmin', 'profile', 'logs', 'updatePassword', 'updateProfile',
            'generateOgImage',
            'authors', 'storeAuthor', 'authorStore', 'deleteAuthor', 'authorDelete',
            'mailInvitations', 'storeMailInvitation', 'revokeMailInvitation', 'reconcileMailInvitation', 'changeMailPassword',
        ],
        'ArticleController' => ['index', 'qr'],
        'ArticlesController' => ['index'],
        'AuthorController' => ['index'],
        'HomeController' => ['index', 'privacy'],
        'MailInviteController' => ['index', 'redeem'],
        'NotesController' => ['index', 'read', 'viewPdf', 'download', 'rss'],
        'PageController' => ['renderPage'],
        'RssController' => ['index'],
        'SearchController' => ['autocomplete'],
        'SeoController' => ['robots', 'sitemapIndex', 'sitemapMain', 'sitemapNotes'],
        'UploadsController' => ['index'],
        'ImageController' => ['cover'],
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

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $parts = explode('?', $requestUri, 2);
        $rawPath = $parts[0];
        $queryString = isset($parts[1]) ? '?' . $parts[1] : '';

        // Validate the path, not search terms or signed captcha URLs in the query string.
        if (stripos($rawPath, '%2f') !== false) {
            http_response_code(400);
            echo 'Bad Request: Encoded slashes are not allowed.';
            exit;
        }

        $canonicalPath = rawurldecode($rawPath);
        if (preg_match('//u', $canonicalPath) !== 1 || preg_match('/[\x00-\x1f\x7f\\\\]/', $canonicalPath)) {
            http_response_code(400);
            echo 'Bad Request: Invalid path.';
            exit;
        }
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

        // Re-encode decoded segments. Comparing an encoded URL with raw Unicode caused loops.
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $canonicalPath)));
        if ($rawPath !== $encodedPath) {
            http_response_code(in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) ? 301 : 308);
            header('Location: ' . ($_SERVER['FEZADAN_PATH_PREFIX'] ?? '') . $encodedPath . $queryString);
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

        if (in_array($pathLower, ['/sitemap.xml', '/sitemap_main.xml', '/sitemap_notes.xml'], true)) {
            require_once ROOT . '/app/Controllers/SeoController.php';
            $seo = new SeoController();
            if ($pathLower === '/sitemap.xml') {
                if (strpos($host, 'notlar.') === 0) $seo->sitemapNotes();
                else $seo->sitemapIndex();
            } elseif ($pathLower === '/sitemap_main.xml') {
                $seo->sitemapMain();
            } else {
                $seo->sitemapNotes();
            }
            exit;
        }

        $url = $this->parseUrl();
        $hasLangPrefix = $this->detectLanguagePrefix($url);
        // Admin auth, role and CSRF checks must see the same action the router dispatches.
        // Never let a client-supplied ?url= override Apache's rewritten route.
        $_GET['url'] = implode('/', $url);

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
            // Remember explicit language choice for 30 days
            if (!headers_sent() && ($_COOKIE['fezadan_lang'] ?? '') !== 'en') {
                setcookie('fezadan_lang', 'en', [
                    'expires'  => time() + 2592000,
                    'path'     => '/',
                    'samesite' => 'Lax',
                    'httponly' => true,
                    'secure'   => HttpRequest::isHttps(),
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
            // Remember explicit language choice for 30 days
            if (!headers_sent() && ($_COOKIE['fezadan_lang'] ?? '') !== 'tr') {
                setcookie('fezadan_lang', 'tr', [
                    'expires'  => time() + 2592000,
                    'path'     => '/',
                    'samesite' => 'Lax',
                    'httponly' => true,
                    'secure'   => HttpRequest::isHttps(),
                ]);
            }
            return true;
        }

        return false;
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
            if (count($url) !== 1) {
                $this->render404();
            }
            $this->method = 'rss';
            $this->params = [];
        } elseif (($url[0] ?? '') === 'not') {
            if (($url[1] ?? '') === 'download') {
                if (count($url) > 3) {
                    $this->render404();
                }
                $this->method = 'download';
                $this->params = isset($url[2]) ? [$url[2]] : [];
            } elseif (($url[1] ?? '') === 'view') {
                if (count($url) > 3) {
                    $this->render404();
                }
                $this->method = 'viewPdf';
                $this->params = isset($url[2]) ? [$url[2]] : [];
            } else {
                if (count($url) > 2) {
                    $this->render404();
                }
                $this->method = 'read';
                $this->params = isset($url[1]) ? [$url[1]] : [];
            }
        } else {
            if ($url !== ['home'] && $url !== ['index']) {
                $this->render404();
            }
            $this->method = 'index';
            $this->params = [];
        }

        $this->invokeAction();
    }

    private function redirectAnonymityPath(array $url, string $queryString): void
    {
        $firstSegmentLower = strtolower($url[0] ?? '');
        if ($firstSegmentLower !== 'anonymitycheck' && $firstSegmentLower !== 'anonymity-check') {
            return;
        }

        $redirectPath = ANONYMITY_SITE_URL . '/';
        if (isset($url[1]) && $url[1] !== '') {
            $redirectPath .= implode('/', array_map('rawurlencode', array_slice($url, 1)));
        }
        if ($queryString !== '') {
            $redirectPath .= $queryString;
        }
        http_response_code(in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) ? 301 : 308);
        header('Location: ' . $redirectPath);
        exit;
    }

    private function redirectMissingLanguagePrefix(bool $hasLangPrefix, string $canonicalPath, string $queryString): void
    {
        if ($hasLangPrefix) {
            return;
        }

        $pathLower = strtolower($canonicalPath);
        $excludePrefixes = ['/yonetim', '/uploads', '/portfolio', '/media', '/cdn', '/assets', '/scripts', '/admin', '/panel', '/dashboard'];
        foreach ($excludePrefixes as $prefix) {
            if ($pathLower === $prefix || strpos($pathLower, $prefix . '/') === 0) {
                return;
            }
        }

        if ($canonicalPath === '/') {
            $preferredLang = 'tr'; // site default

            // 1. Cookie takes priority: user's explicit choice remembered for 30 days
            $cookieLang = is_string($_COOKIE['fezadan_lang'] ?? null) ? strtolower(trim($_COOKIE['fezadan_lang'])) : '';
            if (in_array($cookieLang, ['tr', 'en'], true)) {
                $preferredLang = $cookieLang;
            } else {
                // 2. Fall back to Accept-Language header (only on first visit, no cookie)
                $bestQuality = 0.0;
                foreach (explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $preference) {
                    if (preg_match('/^\s*(tr|en)(?:-[a-z0-9-]+)?\s*(?:;\s*q=(0(?:\.\d{1,3})?|1(?:\.0{1,3})?))?\s*$/', $preference, $match)) {
                        $quality = isset($match[2]) ? (float)$match[2] : 1.0;
                        if ($quality > $bestQuality) {
                            $preferredLang = $match[1];
                            $bestQuality = $quality;
                        }
                    }
                }
            }

            http_response_code(307);
            header('Vary: Accept-Language, Cookie');
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
            'yazi' => 'Article',
            'post' => 'Article',
            'makaleler' => 'Articles',
            'articles' => 'Articles',
            'yazar' => 'Author',
            'author' => 'Author',
            'notlar' => 'Notes',
            'notes' => 'Notes',
            'sitemap.xml' => 'Seo',
            'sitemap_main.xml' => 'Seo',
            'sitemap_notes.xml' => 'Seo',
            'robots.txt' => 'Seo',
            'portfolio' => 'Uploads',
            'media' => 'Image',
            'posta-davet' => 'MailInvite',
            'mail-invitation' => 'MailInvite',
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
            if (count($url) !== 1) {
                $this->render404();
            }
            require_once ROOT . '/app/Controllers/PageController.php';
            $this->controller = new PageController();
            $this->method = 'renderPage';
            $this->params = [$staticPage];
            $this->invokeAction();
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
            $this->invokeAction();
            return;
        }

        if ($controllerClass === 'ArticleController') {
            // A single segment is always an article slug, even if it is "qr" or "index".
            $methodName = count($segments) > 1 ? $this->resolveRoutableMethod($this->controller, $segments[0]) : null;
            if ($methodName !== null) {
                $this->method = $methodName;
                array_shift($segments);
            }
            $this->params = array_values($segments);
            $this->invokeAction();
            return;
        }

        if ($controllerClass === 'UploadsController') {
            $this->params = array_values($segments);
            $this->invokeAction();
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
        $this->invokeAction();
    }

    /** Reject missing/extra route arguments instead of crashing or rendering duplicate pages. */
    private function invokeAction(): void
    {
        $action = new \ReflectionMethod($this->controller, $this->method);
        if (count($this->params) < $action->getNumberOfRequiredParameters()
            || (!$action->isVariadic() && count($this->params) > $action->getNumberOfParameters())) {
            $this->render404();
        }
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
            'donate' => 'donate',
            'bagis' => 'donate',
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
        if (count($url) > 2) {
            $this->render404();
        }
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
        $this->invokeAction();
    }
    public function parseUrl() {
        $path = trim(rawurldecode(explode('?', (string)($_SERVER['REQUEST_URI'] ?? '/'), 2)[0]), '/');
        return $path === '' ? ['home'] : explode('/', $path);
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
