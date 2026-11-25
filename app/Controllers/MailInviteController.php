<?php
require_once dirname(__DIR__) . '/Services/MailInvitationService.php';

class MailInviteController extends Controller
{
    public function index(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
            $this->headers();
            http_response_code(405);
            header('Allow: GET, HEAD');
            return;
        }
        $address = $_SESSION['mail_invitation_success'] ?? '';
        unset($_SESSION['mail_invitation_success']);
        $this->render('', '', '', is_string($address) ? $address : '');
    }

    public function redeem(): void
    {
        $this->headers();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return;
        }
        Csrf::verify();
        $service = new MailInvitationService(Db::pdo());
        if (!$service->configured()) {
            http_response_code(503);
            $this->render('disabled');
            return;
        }
        if (!$service->allowAttempt($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            http_response_code(429);
            header('Retry-After: 900');
            $this->render('rate_limited');
            return;
        }
        $code = $this->field('code');
        $address = $this->field('address');
        $password = $this->field('password');
        $confirmation = $this->field('confirm_password');
        $state = $this->field('lc_state');
        $pending = $_SESSION['mail_invite_captcha_state'] ?? [];
        unset($_SESSION['mail_invite_captcha_state']);
        unset($_POST['password'], $_POST['confirm_password']);
        try {
            MailInvitationService::validatePassword($password);
            if (!hash_equals($password, $confirmation)) {
                throw new InvalidArgumentException('invalid_password');
            }
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $this->render('password', $code, $address);
            return;
        }
        unset($confirmation);
        // This endpoint only accepts POST proof fields, never query-string tokens.
        foreach (['rt', 'sig', 'exp', 'lc_rt', 'lc_sig', 'lc_exp'] as $key) {
            unset($_GET[$key]);
        }
        $captchaError = null;
        $exp = $this->field('lc_exp');
        if (!is_array($pending) || !is_string($pending['state'] ?? null)
            || !hash_equals($pending['state'], $state) || (int)($pending['until'] ?? 0) < time()
            || preg_match('/\A[a-f0-9]{32}\z/D', $this->field('lc_rt')) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/D', $this->field('lc_sig')) !== 1
            || preg_match('/\A[0-9]{10}\z/D', $exp) !== 1 || (int)$exp < time() || (int)$exp > time() + 90
            || !litecaptcha_verify($captchaError, $this->path() . '/redeem', false)) {
            http_response_code(422);
            $this->render('captcha', $code, $address);
            return;
        }
        // Refuse a stale encryption form rather than silently discard its keys.
        if (array_key_exists('vault_payload', $_POST)) {
            unset($password, $_POST['vault_payload']);
            http_response_code(422);
            $this->render('invalid', $code, $address);
            return;
        }
        $result = $service->redeem($code, $address, $password);
        unset($password);
        if ($result === 'created') {
            $_SESSION['mail_invitation_success'] = strtolower(trim($address));
            header('Location: ' . $this->path(), true, 303);
            return;
        }
        http_response_code(in_array($result, ['unavailable', 'disabled', 'review'], true) ? 503 : 422);
        $this->render($result, $code, $address);
    }

    private function headers(): void
    {
        // PHP 8.1 has no SensitiveParameter attribute; suppress trace arguments
        // before reading passwords so DB/transport failures cannot log them.
        ini_set('zend.exception_ignore_args', '1');
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Referrer-Policy: no-referrer');
    }

    private function path(): string
    {
        return App::getLang() === 'EN' ? '/en/mail-invitation' : '/tr/posta-davet';
    }

    private function field(string $name): string
    {
        return is_string($_POST[$name] ?? null) && strlen($_POST[$name]) <= 256 ? $_POST[$name] : '';
    }

    private function render(string $error = '', string $code = '', string $address = '', string $success = ''): void
    {
        $this->headers();
        $state = bin2hex(random_bytes(16));
        $_SESSION['mail_invite_captcha_state'] = ['state' => $state, 'until' => time() + 600];
        $return = rtrim(SITE_URL, '/') . $this->path() . '/redeem?lc_state=' . $state;
        $captchaBase = rtrim(env_value('LITECAPTCHA_URL', 'https://litecaptcha.fezadan.org'), '/');
        $this->view('front/mail_invite', ['configured' => (new CpanelMailService())->configured(),
            'error' => $error, 'code' => $code, 'address' => $address, 'success' => $success,
            'state' => $state, 'formPath' => $this->path() . '/redeem', 'captchaReturn' => $return,
            'captchaUrl' => $captchaBase . '/?' . http_build_query(['redirect' => $return, 'embed' => 1])]);
    }
}
