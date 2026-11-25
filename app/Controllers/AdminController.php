<?php

require_once __DIR__ . '/Admin/AdminArticles.php';
require_once __DIR__ . '/Admin/AdminNotes.php';
require_once __DIR__ . '/Admin/AdminPasskeys.php';
require_once __DIR__ . '/Admin/AdminPortfolio.php';
require_once __DIR__ . '/Admin/AdminSystem.php';
require_once __DIR__ . '/Admin/AdminAuthAndLogs.php';
require_once __DIR__ . '/Admin/AdminMailInvitations.php';

require_once dirname(__DIR__) . '/Services/SystemHealthService.php';
require_once dirname(__DIR__) . '/Services/SitemapService.php';
require_once dirname(__DIR__) . '/Services/IndexNowService.php';
require_once dirname(__DIR__) . '/Services/MediaService.php';
require_once dirname(__DIR__) . '/Services/AuthorService.php';
require_once dirname(__DIR__) . '/Services/ScientificContent.php';
require_once dirname(__DIR__) . '/Services/ArticleQueries.php';

class AdminController extends Controller
{
    use AdminArticles, AdminNotes, AdminPasskeys, AdminPortfolio, AdminSystem, AdminAuthAndLogs, AdminMailInvitations;

    private const ADMIN_IDLE_TIMEOUT_SECONDS = 2700;
    private const ADMIN_ABSOLUTE_TIMEOUT_SECONDS = 43200;

    // Database bağlantı fonksiyonu
    /** @deprecated Yeni kodda doğrudan Db::pdo() kullanın. */
    private function getPDO()
    {
        return Db::pdo();
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function refreshSessionCookie(): void
    {
        if (headers_sent() || !ini_get('session.use_cookies')) {
            return;
        }

        $params = session_get_cookie_params();
        $options = [
            'expires' => 0,
            'path' => $params['path'],
            'secure' => (bool)$params['secure'],
            'httponly' => (bool)$params['httponly'],
            'samesite' => $params['samesite'],
        ];
        if (!empty($params['domain'])) {
            $options['domain'] = $params['domain'];
        }

        setcookie(session_name(), session_id(), $options);
    }

    /** CSRF verify gerektirmeyen istisnai metodlar (login: session henüz açılmamış olabilir) */
    private static $csrfExempt = ['login', 'loginPasskeyChallenge', 'loginPasskeyVerify'];

    private static $publicMethods = ['forgotPassword', 'sendResetLink', 'resetPassword', 'updateResetPassword'];

    private static $jsonMethods = ['prepareScientificImport', 'uploadContentImage', 'registerPasskeyChallenge', 'registerPasskeyVerify', 'loginPasskeyChallenge', 'loginPasskeyVerify', 'generateOgImage', 'portfolioReorder'];

    private static $writeMethods = [
        'login','logout','store','update','delete','publish',
        'storeCategory','deleteCategory',
        'storePatch','deletePatch','patchDelete',
        'storeAuthor','authorStore','deleteAuthor','authorDelete',
        'storeNote','updateNote','deleteNote',
        'updatePassword','uploadContentImage','prepareScientificImport',
        'sendResetLink','updateResetPassword',
        'generateSitemap',
        'registerPasskeyVerify',
        'loginPasskeyVerify',
        'deletePasskey',
        'generateOgImage',
        'storePortfolio','portfolioStore',
        'updatePortfolio','portfolioUpdate',
        'deletePortfolio','portfolioDelete',
        'deleteAllPortfolio','portfolioDeleteAll',
        'portfolioReorder',
        'storeAdmin',
        'updateProfile',
        'unlockAdmin', 'adminResetLink', 'deleteAdmin', 'updateAdminRole',
        'storeMailInvitation', 'revokeMailInvitation', 'reconcileMailInvitation', 'changeMailPassword'
    ];

    private static $viewerReadMethods = ['index', 'dashboard', 'profile', 'registerPasskeyChallenge'];

    private static $viewerSelfServiceWriteMethods = [
        'login',
        'logout',
        'updatePassword',
        'updateProfile',
        'registerPasskeyChallenge',
        'registerPasskeyVerify',
        'deletePasskey',
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
        $wantsJson = $isJsonMethod || $this->isAjaxRequest();
        $isPublicMethod = in_array($action, self::$publicMethods, true)
                        || in_array($camel, self::$publicMethods, true);

        if (!isset($_SESSION['admin_logged_in']) && $wantsJson) {
            $publicJsonMethods = ['loginPasskeyChallenge', 'loginPasskeyVerify'];
            if (!in_array($action, $publicJsonMethods, true) && !in_array($camel, $publicJsonMethods, true)) {
                header('Content-Type: application/json; charset=utf-8');
                $this->jsonError(403, 'Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar giriş yapın.', [
                    'endpoint' => $camel ?: $action,
                ], 'auth_required');
            }
            return;
        }

        if (!isset($_SESSION['admin_logged_in']) && !in_array($currentMethod, ['yonetim', 'yonetim/login']) && !$isPublicMethod) {
            header('Location: /yonetim');
            exit;
        }

        if (isset($_SESSION['admin_logged_in'])) {
            $this->enforceAdminSessionLifetime($wantsJson, $camel ?: $action);
            $this->revalidateAdminSession($wantsJson, $camel ?: $action);

            $resolvedAction = $camel ?: $action ?: 'index';
            if (($_SESSION['admin_role'] ?? 'viewer') === 'viewer'
                && $_SERVER['REQUEST_METHOD'] !== 'POST'
                && !in_array($resolvedAction, self::$viewerReadMethods, true)) {
                Flash::set('Salt okunur erişim. Bu ekrana erişemezsiniz.');
                header('Location: /yonetim/dashboard');
                exit;
            }
        }

        // POST tabanlı yazma uçlarında CSRF doğrulaması
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMaxBytes = $this->postMaxBytes();
            if ($postMaxBytes > 0 && $contentLength > $postMaxBytes && count($_POST) === 0) {
                $requestId = $this->requestId();
                $message = 'Yüklenen veri sunucu limitini aşıyor. Lütfen görsel boyutunu küçültüp tekrar deneyin.';
                $context = [
                    'endpoint' => $camel ?: $action,
                    'request_id' => $requestId,
                    'content_length' => $contentLength,
                    'post_max_bytes' => $postMaxBytes,
                    'php_post_max' => ini_get('post_max_size'),
                ];

                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    $this->jsonError(413, $message, $context, 'post_max_size_exceeded');
                }
                AdminLog::write('error', 'POST_MAX_SIZE_EXCEEDED', [
                    'endpoint' => $camel ?: $action,
                    'request_id' => $requestId,
                    'content_length' => $contentLength,
                    'post_max_bytes' => $postMaxBytes,
                    'php_post_max' => ini_get('post_max_size'),
                ]);
                Flash::set($message . ' (Hata kodu: ' . $requestId . ')');
                $redirect = $_SERVER['HTTP_REFERER'] ?? '/yonetim/dashboard';
                header('Location: ' . $redirect);
                exit;
            }

            $isWrite = in_array($action, self::$writeMethods, true)
                    || in_array($camel,  self::$writeMethods, true);

            if ($isWrite
                && !in_array($action, self::$csrfExempt, true)
                && !in_array($camel,  self::$csrfExempt, true)) {
                if ($wantsJson && !$this->csrfValid()) {
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

        // Role-based access: non-POST methods like deleteAuthor (GET-based)
        if (PHP_SAPI !== 'cli' && isset($_SESSION['admin_logged_in'])) {
            $role = $_SESSION['admin_role'] ?? 'viewer';
            $superAdminOnly = ['authors', 'storeAuthor', 'authorStore', 'deleteAuthor', 'authorDelete', 'admins', 'storeAdmin', 'logs',
                'unlockAdmin', 'adminResetLink', 'deleteAdmin', 'updateAdminRole',
                'mailInvitations', 'storeMailInvitation', 'revokeMailInvitation', 'reconcileMailInvitation', 'changeMailPassword'];
            if (in_array($action, $superAdminOnly, true) || in_array($camel, $superAdminOnly, true)) {
                if ($role !== 'superadmin') {
                    Flash::set('Bu işlem için yetkiniz bulunmamaktadır.');
                    header('Location: /yonetim/dashboard');
                    exit;
                }
            }

            $superAdminOrViewerOnly = ['portfolio', 'portfolio-edit', 'portfolio-update', 'portfolio-reorder',
                'store-portfolio', 'delete-portfolio', 'update-portfolio', 'portfolio-store', 'portfolio-delete',
                'generate-sitemap',
                'portfolioEdit', 'portfolioUpdate', 'portfolioReorder', 'storePortfolio', 'deletePortfolio',
                'updatePortfolio', 'portfolioStore', 'portfolioDelete',
                'generateSitemap',
            ];
            if (in_array($action, $superAdminOrViewerOnly, true) || in_array($camel, $superAdminOrViewerOnly, true)) {
                if ($role === 'editor') {
                    Flash::set('Bu işlem için yetkiniz bulunmamaktadır.');
                    header('Location: /yonetim/create');
                    exit;
                }
            }
        }
    }

    /** Yazma uçlarında GET ile çağrılmayı engelle + rol kontrolü */
    private function requirePost(): void
    {
        if (PHP_SAPI === 'cli') {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1]['function'] ?? '';
            $cliAllowed = ['generateSitemap', 'generateSitemapInternal'];
            if (!in_array($caller, $cliAllowed, true)) {
                fwrite(STDERR, "Error: Method '$caller' not allowed from CLI.\n");
                exit(1);
            }
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }

        // Role check: superadmin-only methods
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1]['function'] ?? '';
        if (in_array($caller, ['sendResetLink', 'updateResetPassword'], true)) {
            return;
        }
        $role = $_SESSION['admin_role'] ?? 'viewer';
        $superAdminMethods = ['storeAuthor', 'deleteAuthor', 'storeAdmin', 'unlockAdmin', 'adminResetLink', 'deleteAdmin', 'updateAdminRole',
            'storeMailInvitation', 'revokeMailInvitation', 'reconcileMailInvitation', 'changeMailPassword',
            'storePortfolio', 'updatePortfolio', 'deletePortfolio', 'deleteAllPortfolio', 'portfolioReorder',
            'generateSitemap'];
        if (in_array($caller, $superAdminMethods, true) && $role !== 'superadmin') {
            Flash::set('Bu işlem için yetkiniz bulunmamaktadır.');
            header('Location: /yonetim/dashboard');
            exit;
        }
        // Viewer: content/admin writes are blocked; own profile/session actions remain available.
        if ($role === 'viewer' && !in_array($caller, self::$viewerSelfServiceWriteMethods, true)) {
            if (in_array($caller, self::$jsonMethods, true) || $this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                $this->jsonError(403, 'Salt okunur erişim. Yazma işlemi yapamazsınız.', [
                    'endpoint' => $caller,
                ], 'role_forbidden');
            }
            Flash::set('Salt okunur erişim. Yazma işlemi yapamazsınız.');
            header('Location: /yonetim/dashboard');
            exit;
        }
    }

    /**
     * Role-based access control.
     * superadmin: full access
     * editor: can manage articles/notes/categories (not authors)
     * viewer: read-only (dashboard only)
     */
    private function requireRole(array $allowedRoles): void
    {
        $role = $_SESSION['admin_role'] ?? 'viewer';
        if (!in_array($role, $allowedRoles, true)) {
            Flash::set('Bu işlem için yetkiniz bulunmamaktadır.');
            header('Location: /yonetim/dashboard');
            exit;
        }
    }

    private function csrfValid(): bool
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        $createdAt = (int)($_SESSION['csrf_token_created_at'] ?? 0);
        return is_string($sent)
            && is_string($stored)
            && $stored !== ''
            && $createdAt > 0
            && (time() - $createdAt) <= 14400
            && hash_equals($stored, $sent);
    }

    private function enforceAdminSessionLifetime(bool $isJsonMethod = false, string $endpoint = ''): void
    {
        $now = time();
        $loginAt = (int)($_SESSION['admin_login_at'] ?? 0);
        $lastSeen = (int)($_SESSION['admin_last_seen_at'] ?? 0);

        if ($loginAt <= 0) {
            $_SESSION['admin_login_at'] = $now;
            $loginAt = $now;
        }
        if ($lastSeen <= 0) {
            $_SESSION['admin_last_seen_at'] = $now;
            $lastSeen = $now;
        }

        $expired = ($now - $lastSeen) > self::ADMIN_IDLE_TIMEOUT_SECONDS
            || ($now - $loginAt) > self::ADMIN_ABSOLUTE_TIMEOUT_SECONDS;
        if (!$expired) {
            $_SESSION['admin_last_seen_at'] = $now;
            return;
        }

        AdminLog::write('warning', 'Admin oturumu zaman aşımına uğradı.', [
            'endpoint' => $endpoint,
            'idle_seconds' => $now - $lastSeen,
            'absolute_seconds' => $now - $loginAt,
        ]);
        $this->endAdminSession('Oturum süresi doldu. Lütfen tekrar giriş yapın.', 'session_expired', $isJsonMethod, $endpoint);
    }

    /** Ties a session to the account's current password: changing or resetting it ends every other session. */
    private static function credentialFingerprint(string $passwordHash): string
    {
        return hash_hmac('sha256', $passwordHash, APP_SALT);
    }

    /**
     * The session only caches the login; the admins row decides. A deleted account or a changed
     * password ends the session on its next request, and role changes apply immediately.
     */
    private function revalidateAdminSession(bool $wantsJson, string $endpoint): void
    {
        $stmt = Db::pdo()->prepare("SELECT role, password FROM admins WHERE id = ?");
        $stmt->execute([(int)($_SESSION['admin_id'] ?? 0)]);
        $account = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($account && hash_equals(self::credentialFingerprint((string)$account['password']), (string)($_SESSION['admin_credential'] ?? ''))) {
            $_SESSION['admin_role'] = $account['role'] ?: 'viewer';
            return;
        }

        AdminLog::write('warning', 'Admin oturumu sonlandırıldı: hesap silinmiş veya şifresi değişmiş.', [
            'endpoint' => $endpoint,
            'admin_id' => (int)($_SESSION['admin_id'] ?? 0),
        ]);
        $this->endAdminSession('Oturumunuz sonlandırıldı. Lütfen tekrar giriş yapın.', 'session_revoked', $wantsJson, $endpoint);
    }

    private function endAdminSession(string $message, string $code, bool $wantsJson, string $endpoint): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $this->refreshSessionCookie();
        }

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            $this->jsonError(403, $message, ['endpoint' => $endpoint], $code);
        }

        Flash::set($message);
        header('Location: /yonetim');
        exit;
    }

    private function adminRateLimitHash(string $value, string $purpose): string
    {
        return hash('sha256', $purpose . '|' . $value . '|' . APP_SALT);
    }

    /**
     * Forget failed logins for an account, and for this IP unless a superadmin is unlocking
     * someone else's account (after a successful login, a password reset or an unlock).
     */
    private function clearLoginAttempts(\PDO $pdo, string $username, bool $includeThisIp = true): void
    {
        $pdo->prepare("DELETE FROM login_attempts WHERE ip_hash = ? OR (username_hash IS NOT NULL AND username_hash = ?)")
            ->execute([
                $includeThisIp ? $this->adminRateLimitHash($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'admin_login_ip') : '',
                $this->adminRateLimitHash($username, 'admin_login_username'),
            ]);
    }

    /** Same id as the 500 page and the crash log entry; CLI (cron) runs without ErrorHandler. */
    private function requestId(): string
    {
        return class_exists('ErrorHandler', false) ? ErrorHandler::requestId() : bin2hex(random_bytes(6));
    }

    private function elapsedMs(float $started): int
    {
        return (int)round((microtime(true) - $started) * 1000);
    }

    private function checkpointEnabled(): bool
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return true;
        }

        $env = strtolower((string)getenv('APP_ENV'));
        if (in_array($env, ['local', 'development', 'testing'], true)) {
            return true;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $host === '' || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
    }

    private function adminCheckpoint(string $step, array $context = [], float $started = null): void
    {
        if (!$this->checkpointEnabled()) {
            return;
        }

        $context['step'] = $step;
        $context['request_id'] = $context['request_id'] ?? $this->requestId();
        if ($started !== null) {
            $context['ms'] = $this->elapsedMs($started);
        }

        AdminLog::write('DEBUG', 'CHECKPOINT', $context);
    }

    private function postMaxBytes(): int
    {
        $value = trim((string)ini_get('post_max_size'));
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        switch ($unit) {
            case 'g': return (int)($number * 1073741824);
            case 'm': return (int)($number * 1048576);
            case 'k': return (int)($number * 1024);
            default: return (int)$number;
        }
    }

    private function guardPostSizeOrFail(string $redirectUrl, string $endpoint): void
    {
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes = $this->postMaxBytes();

        $this->adminCheckpoint($endpoint . '_entry', [
            'endpoint' => $endpoint,
            'post_count' => count($_POST),
            'post_fields' => array_keys($_POST),
            'file_fields' => array_keys($_FILES),
            'content_length' => $contentLength,
            'post_max_bytes' => $postMaxBytes,
            'php_post_max' => ini_get('post_max_size'),
            'php_upload_max' => ini_get('upload_max_filesize'),
            'php_memory_limit' => ini_get('memory_limit'),
        ]);

        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes && count($_POST) === 0) {
            $message = 'Yüklenen veri sunucu limitini aşıyor. Lütfen görsel boyutunu küçültüp tekrar deneyin.';
            $this->failBackWithFlash($redirectUrl, $message, [
                'endpoint' => $endpoint,
                'content_length' => $contentLength,
                'post_max_bytes' => $postMaxBytes,
                'php_post_max' => ini_get('post_max_size'),
            ], 'post_max_size_exceeded');
        }
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

    // Admin giriş sayfası
    public function index()
    {
        if (isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim/dashboard');
            exit;
        }

        $this->view('admin/login');
    }

    // Giriş yapma
    public function login()
    {
        $this->requirePost();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ipHash = $this->adminRateLimitHash($userIp, 'admin_login_ip');
        $usernameHash = $this->adminRateLimitHash($username, 'admin_login_username');

        try {
            $pdo = $this->getPDO();

            // Brute force protection: son 3 saatte ayni IP veya ayni kullanici adiyla 10'dan fazla basarisiz deneme
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM login_attempts
                 WHERE (ip_hash = ? OR username_hash = ?) AND attempt_time > NOW() - INTERVAL 3 HOUR"
            );
            $checkStmt->execute([$ipHash, $usernameHash]);
            $attempts = (int)$checkStmt->fetchColumn();

            if ($attempts >= 10) {
                AdminLog::write('warning', 'Brute force engellemesi tetiklendi.', [
                    'ip_hash' => $ipHash,
                    'username_hash' => $usernameHash,
                ]);
                Flash::set('Çok fazla başarısız giriş denemesi. Lütfen 3 saat sonra tekrar deneyin.');
                header('Location: /yonetim?error=blocked');
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Basarili giris: bu IP ve kullanici icin basarisiz denemeleri temizle
                $this->clearLoginAttempts($pdo, $username);

                // Session fixation protection: regenerate session id
                session_regenerate_id(true);
                $this->refreshSessionCookie();
                $_SESSION = [];
                Csrf::rotate();

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = (int)$user['id'];
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_role'] = $user['role'] ?? 'viewer';
                $_SESSION['admin_credential'] = self::credentialFingerprint((string)$user['password']);
                $_SESSION['admin_login_at'] = time();
                $_SESSION['admin_last_seen_at'] = time();

                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                AdminLog::write('info', 'Giriş yapıldı.', ['endpoint' => 'login', 'method' => 'password']);



                $loginTarget = '/yonetim/dashboard';
                header('Location: ' . $loginTarget);
                exit;
            } else {
                // Basarisiz denemeyi kaydet
                $logStmt = $pdo->prepare("INSERT INTO login_attempts (ip_hash, username_hash, attempt_time) VALUES (?, ?, NOW())");
                $logStmt->execute([$ipHash, $usernameHash]);

                // Only name real accounts: a mistyped "username" is sometimes the password.
                AdminLog::write('warning', 'Hatalı giriş denemesi.', [
                    'username' => $user ? $user['username'] : '(bilinmeyen kullanıcı adı)',
                ]);

                // Captcha session'unu temizle; böylece bir sonraki yükleme
                // captcha durumunu sıfırdan değerlendirir ve buton pasif kalmaz.
                unset($_SESSION['captcha_verified']);

                Flash::set('Kullanıcı adı veya şifre hatalı.');
                header('Location: /yonetim?error=failed');
                exit;
            }
        }
        catch (\PDOException $e) {
            throw new \Exception("Giriş Hatası: " . $e->getMessage(), 0, $e);
        }
    }

    // Dashboard anasayfa
    public function dashboard()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            // İstatistikler
            $stats = [];
            $stats['total_articles'] = $pdo->query("SELECT COUNT(*) FROM articles a WHERE " . ArticleQueries::PUBLISHED)->fetchColumn();
            $stats['total_drafts']   = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn();
            $stats['total_scheduled'] = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'scheduled'")->fetchColumn();

            // Health Checks (Sistem Sağlık Taraması)
            $healthService = new \App\Services\SystemHealthService();
            [$healthChecks, $stats] = $healthService->runHealthChecks($pdo, $stats);

            // Pagination and Search
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $sortCol = $_GET['sort'] ?? 'id';
            $sortOrder = strtoupper($_GET['order'] ?? 'DESC');
            $allowedSorts = ['id' => 'a.id', 'title' => 'a.title', 'author' => 'author_name', 'category' => 'category_names'];
            if (!isset($allowedSorts[$sortCol])) {
                $sortCol = 'id';
            }
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'DESC';
            }

            $where = "1=1";
            $params = [];
            if ($search !== '') {
                if (preg_match('/^id=(\d+)$/i', $search, $m)) {
                    $where .= " AND a.id = ?";
                    $params[] = (int)$m[1];
                } else {
                    $where .= " AND (a.title LIKE ? OR a.slug LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
            }

            $countSql = "SELECT COUNT(*) FROM articles a WHERE $where";
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total_records = $stmt->fetchColumn();
            $total_pages = max(1, (int)ceil($total_records / $limit));

            $orderBy = $allowedSorts[$sortCol] . " " . $sortOrder;

            $sql = "SELECT a.*, au.name AS author_name, au.slug AS author_slug,
                    (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM article_categories ac JOIN categories c ON ac.category_id = c.id WHERE ac.article_id = a.id) AS category_names
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE $where
                    ORDER BY $orderBy LIMIT $limit OFFSET $offset";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $pagination = [
                'current' => $page,
                'total' => $total_pages,
                'search' => $search
            ];
            $sort = [
                'column' => $sortCol,
                'order' => $sortOrder
            ];

            $this->view('admin/dashboard', [
                'stats'            => $stats,
                'articles'         => $articles,
                'pagination'       => $pagination,
                'sort'             => $sort,
                'healthChecks'     => $healthChecks,
                'lastDeploy'       => ($_SESSION['admin_role'] ?? '') === 'superadmin'
                    ? \App\Services\SystemHealthService::lastDeploy(ROOT . '/.cpanel/logs')
                    : null,
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veri Çekme Hatası: " . $e->getMessage(), 0, $e);
        }
    }

    // Çıkış yapma
    public function logout()
    {
        $this->requirePost();
        session_regenerate_id(true);
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: /yonetim');
        exit;
    }

    public function generateSitemap()
    {
        $this->requirePost();
        \App\Services\SitemapService::generateSitemapInternal();
        header('Location: /yonetim/dashboard?status=sitemap');
        exit;
    }

    public function generateOgImage()
    {
        $this->requirePost();
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['admin_logged_in'])) {
            $this->jsonError(403, 'Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.', [
                'endpoint' => 'generateOgImage',
            ], 'auth_required');
        }

        if (!$this->csrfValid()) {
            $this->jsonError(403, 'Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.', [
                'endpoint' => 'generateOgImage',
            ], 'csrf_failed');
        }

        $articleId = (int)($_POST['article_id'] ?? 0);
        if ($articleId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz makale ID.']);
            exit;
        }

        try {
            $mediaService = new \App\Services\MediaService();
            $result = $mediaService->generateAndUploadOgImage($this->getPDO(), $articleId);

            AdminLog::write('info', 'OG görsel üretildi.', [
                'article_id' => $articleId,
                'path' => $result['path'],
            ]);

            echo json_encode(['success' => true, 'url' => $result['url'], 'path' => $result['path']]);
        } catch (\Throwable $e) {
            error_log('OG image error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()]);
        }
        exit;
    }

    public function authors()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $authorService = new \App\Services\AuthorService();
            $pdo = $this->getPDO();

            $authors = $authorService->getAllAuthors($pdo);
            $all_author_articles = $authorService->getAllAuthorArticles($pdo);

            $this->view('admin/authors', [
                'authors' => $authors,
                'all_author_articles' => $all_author_articles
            ]);
        }
        catch (\Exception $e) {
            throw new \Exception("Yazar Listesi Hatası: " . $e->getMessage(), 0, $e);
        }
    }

    public function storeAuthor()
    {
        $this->requirePost();
        try {
            $authorService = new \App\Services\AuthorService();
            $authorService->storeAuthor($this->getPDO(), $_POST, $_FILES);

            header('Location: /yonetim/authors?status=success');
            exit;
        }
        catch (\Exception $e) {
            throw new \Exception("Yazar Kayıt Hatası: " . $e->getMessage(), 0, $e);
        }
    }

    public function authorStore()
    {
        $this->storeAuthor();
    }

    public function deleteAuthor()
    {
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $authorService = new \App\Services\AuthorService();
                $authorService->deleteAuthor($this->getPDO(), $id);
            }
            catch (\Exception $e) {
                throw new \Exception("Yazar Silme Hatası: " . $e->getMessage(), 0, $e);
            }
        }
        header('Location: /yonetim/authors?status=deleted');
        exit;
    }

    public function authorDelete()
    {
        $this->deleteAuthor();
    }
}
