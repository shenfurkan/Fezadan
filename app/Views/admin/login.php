<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | Admin</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <script src="/assets/js/admin.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin.js'); ?>"></script>
    
    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            const userTheme = localStorage.getItem('theme');
            if (userTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <style>
        :root { 
            --bg-paper: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --text-main: #f0f0f0; 
            --text-accent: #ff3333;
            --line-color: #333333;
        }
        
        body { 
            background-color: var(--bg-paper); 
            color: var(--text-main); 
            font-family: 'Space Grotesk', sans-serif; 
            background-image: radial-gradient(var(--line-color) 1px, transparent 1px);
            background-size: 24px 24px;
            min-height: 100svh;
            height: auto;
            overflow-y: auto;
        }
        .font-syne { font-family: 'Syne', sans-serif; }
        
        input { 
            background: transparent; 
            border: 1px solid var(--text-main); 
            padding: 1rem; 
            width: 100%; 
            outline: none; 
            color: var(--text-main);
            transition: background-color 0.3s, border-color 0.3s;
        }
        input:focus {
            background: var(--bg-secondary);
        }

        input:disabled,
        button:disabled {
            opacity: 0.42;
            cursor: not-allowed !important;
        }
        
        button { 
            background: var(--text-main); 
            color: var(--bg-paper); 
            width: 100%; 
            padding: 1rem; 
            font-weight: bold; 
            text-transform: uppercase; 
            transition: background-color 0.3s, color 0.3s;
        }
        button:hover {
            background: var(--text-accent);
            color: #fff;
        }

        #btn-passkey {
            margin-top: 1rem;
            background: transparent;
            border: 1px solid var(--text-main);
            color: var(--text-main);
            cursor: pointer;
        }
        #btn-passkey:hover {
            background: var(--text-main);
            color: var(--bg-paper);
        }

        .litecaptcha-check-row {
            width: 100%;
            border: 1px solid rgba(240, 240, 240, 0.18);
            background: rgba(255, 255, 255, 0.025);
            color: var(--text-main);
            padding: 0.875rem 1rem;
            overflow: hidden;
        }

        .litecaptcha-mini-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid rgba(240, 240, 240, 0.12);
            font-size: 0.76rem;
            font-weight: 800;
            color: rgba(240, 240, 240, 0.78);
        }

        .litecaptcha-mini-brand svg {
            width: 0.95rem;
            height: 0.95rem;
            color: var(--text-main);
            flex: 0 0 auto;
        }

        .litecaptcha-mini-secure {
            margin-left: auto;
            font-size: 0.64rem;
            font-weight: 700;
            color: rgba(240, 240, 240, 0.42);
        }

        .litecaptcha-check-label {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 700;
            text-transform: none;
            cursor: pointer;
        }

        .litecaptcha-check-label input {
            width: 1.15rem;
            height: 1.15rem;
            padding: 0;
            flex: 0 0 auto;
            accent-color: var(--text-main);
        }

        .litecaptcha-check-status {
            display: block;
            margin-top: 0.65rem;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(240, 240, 240, 0.52);
        }

        .litecaptcha-detail-pop {
            display: none;
            margin-top: 0.75rem;
            padding: 0.75rem 0.8rem;
            border: 1px solid rgba(240, 240, 240, 0.14);
            background: rgba(0, 0, 0, 0.35);
            font-size: 0.78rem;
            line-height: 1.45;
            color: rgba(240, 240, 240, 0.68);
            overflow-wrap: anywhere;
        }

        .litecaptcha-check-row.is-active .litecaptcha-detail-pop,
        .litecaptcha-check-row.is-done .litecaptcha-detail-pop,
        .litecaptcha-check-row.is-error .litecaptcha-detail-pop {
            display: block;
        }

        .litecaptcha-check-row.is-error {
            border-color: rgba(255, 80, 80, 0.42);
        }

        .litecaptcha-check-row.is-done {
            border-color: rgba(120, 255, 170, 0.34);
        }

        .litecaptcha-bridge {
            position: absolute;
            width: 1px;
            height: 1px;
            border: 0;
            opacity: 0;
            pointer-events: none;
        }

        /* Catch button — large touch target, centered on mobile */
        .litecaptcha-page-btn {
            position: fixed;
            z-index: 9999;
            /* generous touch target */
            width: auto !important;
            min-width: 7rem;
            min-height: 3.25rem;
            padding: 0.9rem 1.4rem;
            border: 2px solid rgba(240, 240, 240, 0.55);
            background: #111;
            color: var(--text-main);
            font-size: 1rem;
            font-weight: 800;
            font-family: 'Space Grotesk', sans-serif;
            text-transform: none;
            cursor: pointer;
            /* allow tap without delay */
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
            /* visual affordance */
            box-shadow: 0 0 0 3px rgba(240, 240, 240, 0.08);
            transition: border-color 0.15s, background 0.15s;
        }
        .litecaptcha-page-btn:active {
            background: #222;
            border-color: rgba(240, 240, 240, 0.9);
        }

        @media (max-height: 820px) {
            body {
                align-items: flex-start !important;
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .login-card {
                padding: 1.35rem !important;
            }

            .login-card h1 {
                font-size: 2rem;
                line-height: 1;
            }

            .login-form {
                gap: 1rem !important;
            }

            input,
            button {
                padding: 0.78rem;
            }

            .litecaptcha-check-row {
                padding: 0.7rem 0.8rem;
            }

            .litecaptcha-mini-brand {
                margin-bottom: 0.55rem;
            }

            .litecaptcha-check-status {
                margin-top: 0.45rem;
            }

            .litecaptcha-detail-pop {
                margin-top: 0.5rem;
                padding: 0.6rem 0.65rem;
            }
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-6 relative">
    <?php
        $litecaptchaBaseUrl = rtrim(env_value('LITECAPTCHA_URL', 'https://litecaptcha.fezadan.org'), '/');
        $captchaVerified = (($_SESSION['captcha_verified'] ?? 0) > (time() - 900));
        $isLocalAdminHost = preg_match('/(^localhost(:\d+)?$)|(^127\.0\.0\.1(:\d+)?$)/', $_SERVER['HTTP_HOST'] ?? '') === 1;
        $bypassVal = $_GET['lc_bypass'] ?? $_COOKIE['lc_bypass'] ?? '';
        $bypassQuery = $bypassVal !== '' ? '&lc_bypass=' . urlencode($bypassVal) : '';
        $redirectUrl = SITE_URL . '/yonetim/login' . ($bypassVal !== '' ? '?lc_bypass=' . urlencode($bypassVal) : '');
        $litecaptchaEmbedUrl = $litecaptchaBaseUrl . '/?redirect=' . urlencode($redirectUrl) . '&embed=1' . $bypassQuery;
        $showLitecaptchaEmbed = LITECAPTCHA_ENABLED || $isLocalAdminHost || (($_GET['captcha_preview'] ?? '') === '1');
        $captchaGateActive = $showLitecaptchaEmbed && !$captchaVerified;
    ?>
    <div class="login-card w-full max-w-md border border-[var(--text-main)] p-8 relative bg-[var(--bg-paper)] shadow-[8px_8px_0px_var(--text-main)] transition-colors">
        <h1 class="font-syne text-4xl font-bold uppercase text-center mb-8">FEZADAN</h1>

        <?php
            $loginErr = $_GET['error'] ?? '';
            if ($loginErr === 'locked' || $loginErr === 'blocked'):
        ?>
            <div class="mb-6 p-4 font-mono text-xs uppercase flex-shrink-0 border-l-4 bg-red-900 text-red-100 border-red-500">
                Too many failed login attempts. Please try again in 3 hours.
            </div>
        <?php elseif ($loginErr === 'failed' || $loginErr === '1'): ?>
            <div class="mb-6 p-4 font-mono text-xs uppercase flex-shrink-0 border-l-4 bg-[var(--text-main)] text-[var(--bg-paper)] border-[var(--bg-secondary)]">
                Incorrect username or password.
            </div>
        <?php elseif ($loginErr === 'captcha'): ?>
            <div class="mb-6 p-4 font-mono text-xs uppercase flex-shrink-0 border-l-4 bg-yellow-900 text-yellow-100 border-yellow-500">
                Verification failed<?= isset($_GET['captcha_reason']) ? ': ' . htmlspecialchars((string)$_GET['captcha_reason'], ENT_QUOTES, 'UTF-8') : '' ?>. Please try again.
            </div>
        <?php endif; ?>

        <?php if (($_GET['reset'] ?? '') === 'success'): ?>
            <div class="mb-6 p-4 font-mono text-xs uppercase flex-shrink-0 border-l-4 bg-[var(--text-main)] text-[var(--bg-paper)] border-[var(--text-accent)]">
                PASSWORD UPDATED. YOU MAY NOW LOG IN WITH YOUR NEW PASSWORD.
            </div>
        <?php endif; ?>

        <?php if (($_GET['captcha'] ?? '') === 'ok'): ?>
            <div class="mb-6 p-4 font-mono text-xs uppercase flex-shrink-0 border-l-4 bg-green-900 text-green-100 border-green-500">
                ✓ VERIFICATION SUCCESSFUL. YOU MAY NOW LOG IN.
            </div>
        <?php endif; ?>

        <form action="/yonetim/login" method="POST" class="login-form space-y-6">
            <?= Csrf::field() ?>
            <div>
                <label for="username" class="text-xs uppercase tracking-widest block mb-2">Username</label>
                <input type="text" id="username" name="username" autocomplete="username" required>
            </div>
            <div>
                <label for="password" class="text-xs uppercase tracking-widest block mb-2">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <?php if ($showLitecaptchaEmbed): ?>
                <input type="hidden" name="lc_rt" id="lc_rt" value="">
                <input type="hidden" name="lc_sig" id="lc_sig" value="">
                <input type="hidden" name="lc_exp" id="lc_exp" value="">
                <div class="litecaptcha-check-row" id="litecaptcha-check-row">
                    <div class="litecaptcha-mini-brand" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5 18 6v5.2c0 4-2.3 7.4-6 9.3-3.7-1.9-6-5.3-6-9.3V6l6-2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                        <span>LiteCaptcha</span>
                        <span class="litecaptcha-mini-secure">secure</span>
                    </div>
                    <label class="litecaptcha-check-label" for="litecaptcha-check">
                        <input id="litecaptcha-check" type="checkbox" <?= $captchaVerified ? 'checked' : '' ?>>
                        <span>I'm not a robot</span>
                    </label>
                    <div class="litecaptcha-check-status" id="litecaptcha-status"><?= $captchaVerified ? 'Verified' : 'Ready' ?></div>
                    <div class="litecaptcha-detail-pop" id="litecaptcha-detail">
                        <?= $captchaVerified ? 'Verification complete. You may now log in.' : 'Check the box to start verification.' ?>
                    </div>
                    <iframe
                        class="litecaptcha-bridge"
                        src="<?= htmlspecialchars($litecaptchaEmbedUrl, ENT_QUOTES, 'UTF-8') ?>"
                        title="LiteCaptcha"
                        loading="eager"
                        referrerpolicy="no-referrer"
                        allow="clipboard-write"
                    ></iframe>
                </div>
            <?php endif; ?>
            <button type="submit" id="btn-login" <?= $captchaGateActive ? 'disabled' : '' ?>>Log In</button>
            <button type="button" id="btn-passkey" <?= $captchaGateActive ? 'disabled' : '' ?>>Log In with Passkey</button>
            <div class="text-center pt-2">
                <a href="/yonetim/forgot-password" class="text-xs uppercase tracking-widest opacity-70 hover:opacity-100 transition-opacity">Forgot Password</a>
            </div>
        </form>
    </div>
    
    <script nonce="<?= CSP_NONCE ?>">
        function base64UrlToBuffer(base64url) {
            const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            const pad = base64.length % 4;
            const padded = pad ? base64 + '='.repeat(4 - pad) : base64;
            const binary = atob(padded);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes.buffer;
        }

        function bufferToBase64Url(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            const base64 = btoa(binary);
            return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        }

        document.getElementById('btn-passkey').addEventListener('click', async () => {
            if (!window.PublicKeyCredential) {
                if (window.FezadanToast) window.FezadanToast.error('Your browser does not support Passkey authentication.');
                else alert('Your browser does not support Passkey authentication.');
                return;
            }

            try {
                const data = window.FezadanFetch
                    ? await window.FezadanFetch('/yonetim/loginPasskeyChallenge')
                    : await (await fetch('/yonetim/loginPasskeyChallenge')).json();

                if (!data.success) {
                    const message = 'Could not start passkey session: ' + (data.error || 'Unknown error');
                    if (window.FezadanToast) window.FezadanToast.error(message);
                    else alert(message);
                    return;
                }

                const credential = await navigator.credentials.get({
                    publicKey: {
                        challenge: base64UrlToBuffer(data.challenge),
                        rpId: data.rpId,
                        userVerification: 'required'
                    }
                });

                const verifyRequest = window.FezadanFetch ? window.FezadanFetch('/yonetim/loginPasskeyVerify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                        authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
                        signature: bufferToBase64Url(credential.response.signature),
                        credentialId: bufferToBase64Url(credential.rawId)
                    })
                }) : fetch('/yonetim/loginPasskeyVerify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                        authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
                        signature: bufferToBase64Url(credential.response.signature),
                        credentialId: bufferToBase64Url(credential.rawId)
                    })
                }).then(response => response.json());

                const verifyResult = await verifyRequest;
                if (verifyResult.success) {
                    window.location.href = '/yonetim/dashboard';
                } else {
                    const message = 'Login failed: ' + (verifyResult.error || 'Verification error');
                    if (window.FezadanToast) window.FezadanToast.error(message);
                    else alert(message);
                }
            } catch (err) {
                console.error(err);
                if (window.FezadanToast) window.FezadanToast.error('Passkey authentication failed or was cancelled.');
                else alert('Passkey authentication failed or was cancelled.');
            }
        });

        const litecaptchaFrame = document.querySelector('.litecaptcha-bridge');
        const litecaptchaCheck = document.getElementById('litecaptcha-check');
        const litecaptchaRow = document.getElementById('litecaptcha-check-row');
        const litecaptchaStatus = document.getElementById('litecaptcha-status');
        const litecaptchaDetail = document.getElementById('litecaptcha-detail');
        const litecaptchaOrigin = (() => {
            if (!litecaptchaFrame) return '*';
            try {
                return new URL(litecaptchaFrame.getAttribute('src') || '', window.location.href).origin;
            } catch (error) {
                return '*';
            }
        })();
        let litecaptchaReady = false;
        let litecaptchaActive = false;
        let litecaptchaStartRequested = false;
        let litecaptchaReadyTimer = null;
        let litecaptchaRequired = 3;
        let litecaptchaEscapes = 0;
        let litecaptchaLastEscapeAt = 0;
        let litecaptchaButton = null;
        let litecaptchaParentRafDeltas = [];
        let litecaptchaParentDomRects = [];
        let litecaptchaLastRafAt = performance.now();
        let litecaptchaStartedAt = performance.now();

        function collectLitecaptchaParentRaf(now) {
            if (litecaptchaActive && litecaptchaParentRafDeltas.length < 80) {
                litecaptchaParentRafDeltas.push(Number(Math.max(0.1, now - litecaptchaLastRafAt).toFixed(2)));
            }
            litecaptchaLastRafAt = now;
            requestAnimationFrame(collectLitecaptchaParentRaf);
        }
        requestAnimationFrame(collectLitecaptchaParentRaf);

        function setLitecaptchaStatus(text, detail, state) {
            if (litecaptchaStatus) litecaptchaStatus.textContent = text;
            if (litecaptchaDetail && detail) litecaptchaDetail.textContent = detail;
            if (litecaptchaRow && state) {
                litecaptchaRow.classList.remove('is-active', 'is-done', 'is-error');
                litecaptchaRow.classList.add(state);
            }
        }

        function postLitecaptcha(message) {
            if (litecaptchaFrame && litecaptchaFrame.contentWindow) {
                litecaptchaFrame.contentWindow.postMessage(message, litecaptchaOrigin);
            }
        }

        function sampleLitecaptchaButtonRect() {
            if (!litecaptchaButton || litecaptchaButton.style.display === 'none') return;
            const rect = litecaptchaButton.getBoundingClientRect();
            litecaptchaParentDomRects.push({
                left: Math.round(rect.left),
                top: Math.round(rect.top),
                width: Math.round(rect.width),
                height: Math.round(rect.height),
                time: Math.round(performance.now() - litecaptchaStartedAt)
            });
            litecaptchaParentDomRects = litecaptchaParentDomRects.slice(-24);
        }

        function failLitecaptchaBridge(reason, detail) {
            if (!litecaptchaActive || litecaptchaReady) return;
            if (litecaptchaReadyTimer) {
                clearTimeout(litecaptchaReadyTimer);
                litecaptchaReadyTimer = null;
            }
            setLitecaptchaStatus('Failed', detail || ('Reason: ' + reason + '. LiteCaptcha bridge failed to load.'), 'is-error');
            if (litecaptchaCheck) {
                litecaptchaCheck.checked = false;
                litecaptchaCheck.disabled = false;
            }
            litecaptchaActive = false;
            litecaptchaStartRequested = false;
        }

        function handleLitecaptchaSuccess(data) {
            if (litecaptchaButton) litecaptchaButton.style.display = 'none';
            if (!data.redirectUrl) {
                setLitecaptchaStatus('Failed', 'Reason: REDIRECT_MISSING. Verification completed but no token URL received.', 'is-error');
                return;
            }

            let target;
            try {
                target = new URL(data.redirectUrl, window.location.href);
            } catch (error) {
                setLitecaptchaStatus('Failed', 'Reason: BAD_REDIRECT_URL. Verification URL format invalid.', 'is-error');
                return;
            }

            if (target.origin !== window.location.origin || target.pathname !== '/yonetim/login') {
                setLitecaptchaStatus('Failed', 'Reason: BAD_REDIRECT_TARGET. Verification URL target rejected.', 'is-error');
                return;
            }

            var rt  = target.searchParams.get('rt')  || '';
            var sig = target.searchParams.get('sig') || '';
            var exp = target.searchParams.get('exp') || '';

            if (!rt || !sig || !exp) {
                setLitecaptchaStatus('Failed', 'Reason: MISSING_TOKEN_PARAMS. Token parameters not found in redirect URL.', 'is-error');
                return;
            }

            document.getElementById('lc_rt').value = rt;
            document.getElementById('lc_sig').value = sig;
            document.getElementById('lc_exp').value = exp;

            var submitBtn = document.getElementById('btn-login');
            if (submitBtn) submitBtn.disabled = false;

            var passkeyBtn = document.getElementById('btn-passkey');
            if (passkeyBtn) passkeyBtn.disabled = false;

            setLitecaptchaStatus('Verified', 'Verification complete. You may now log in.', 'is-done');
        }

        function requestLitecaptchaStart() {
            litecaptchaStartRequested = true;
            if (!litecaptchaReadyTimer) {
                litecaptchaReadyTimer = setTimeout(() => {
                    failLitecaptchaBridge(
                        'LITECAPTCHA_IFRAME_NOT_READY',
                        'LiteCaptcha did not respond. Check litecaptcha.fezadan.org embed response, secret/env settings, and frame permissions.'
                    );
                }, 8000);
            }
            if (litecaptchaReady) {
                postLitecaptcha({ type: 'lc:start' });
            }
        }

        /* -------------------------------------------------------
           Catch button — mouse + touch unified
        ------------------------------------------------------- */
        function createLitecaptchaButton() {
            if (litecaptchaButton) return litecaptchaButton;
            litecaptchaButton = document.createElement('button');
            litecaptchaButton.type = 'button';
            litecaptchaButton.className = 'litecaptcha-page-btn';
            litecaptchaButton.textContent = 'Catch';
            litecaptchaButton.style.display = 'none';
            document.body.appendChild(litecaptchaButton);

            function fireFinalClick(clientX, clientY) {
                const rect = litecaptchaButton.getBoundingClientRect();
                sampleLitecaptchaButtonRect();
                postLitecaptcha({
                    type: 'lc:final-click',
                    viewport: { width: window.innerWidth, height: window.innerHeight },
                    buttonRect: {
                        left: Math.round(rect.left),
                        top: Math.round(rect.top),
                        width: Math.round(rect.width),
                        height: Math.round(rect.height)
                    },
                    click: { x: clientX, y: clientY, detail: 1, isTrusted: true },
                    rafDeltas: litecaptchaParentRafDeltas.slice(-80),
                    domRects: litecaptchaParentDomRects.slice(-24)
                });
            }

            litecaptchaButton.addEventListener('click', (event) => {
                fireFinalClick(event.clientX, event.clientY);
            });

            // Dokunmatik: touchend'i butona tıklama olarak değerlendir
            litecaptchaButton.addEventListener('touchend', (event) => {
                event.preventDefault(); // prevent ghost mouse click
                const touch = event.changedTouches[0];
                if (touch) {
                    fireFinalClick(touch.clientX, touch.clientY);
                }
            }, { passive: false });

            return litecaptchaButton;
        }

        function litecaptchaButtonBounds(btn) {
            const margin = Math.max(16, Math.min(32, Math.round(Math.min(window.innerWidth, window.innerHeight) * 0.04)));
            const width  = Math.max(1, btn.offsetWidth  || 112);
            const height = Math.max(1, btn.offsetHeight ||  52);
            return {
                minX: margin,
                minY: margin,
                maxX: Math.max(margin, window.innerWidth  - width  - margin),
                maxY: Math.max(margin, window.innerHeight - height - margin),
                width,
                height
            };
        }

        function clampLitecaptchaButtonPosition(btn, x, y) {
            const bounds = litecaptchaButtonBounds(btn);
            return {
                x: Math.min(bounds.maxX, Math.max(bounds.minX, x)),
                y: Math.min(bounds.maxY, Math.max(bounds.minY, y))
            };
        }

        function getParentButtonCoords(leftPct, topPct) {
            const btn = createLitecaptchaButton();
            const bounds = litecaptchaButtonBounds(btn);
            
            const expectedX = bounds.minX + (leftPct / 100.0) * (bounds.maxX - bounds.minX);
            const expectedY = bounds.minY + (topPct / 100.0) * (bounds.maxY - bounds.minY);
            
            const left = expectedX - bounds.width / 2;
            const top = expectedY - bounds.height / 2;
            
            return clampLitecaptchaButtonPosition(btn, left, top);
        }

        function placeLitecaptchaButton(leftPct, topPct) {
            const btn = createLitecaptchaButton();
            btn.style.display = 'block';
            if (typeof leftPct !== 'number' || typeof topPct !== 'number') {
                const bounds = litecaptchaButtonBounds(btn);
                const x = bounds.minX + Math.random() * Math.max(1, bounds.maxX - bounds.minX);
                const y = bounds.minY + Math.random() * Math.max(1, bounds.maxY - bounds.minY);
                const position = clampLitecaptchaButtonPosition(btn, x, y);
                btn.style.left = position.x + 'px';
                btn.style.top  = position.y + 'px';
            } else {
                const position = getParentButtonCoords(leftPct, topPct);
                btn.style.left = position.x + 'px';
                btn.style.top  = position.y + 'px';
            }
            sampleLitecaptchaButtonRect();
        }

        function moveLitecaptchaButton(pointerX, pointerY) {
            if (!litecaptchaButton || litecaptchaEscapes >= litecaptchaRequired) return;
            const now = performance.now();
            if (now - litecaptchaLastEscapeAt < 260) return;
            const rect = litecaptchaButton.getBoundingClientRect();
            sampleLitecaptchaButtonRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top  + rect.height / 2;
            const distance = Math.hypot(pointerX - centerX, pointerY - centerY);
            // Dokunmatik: yakındaki parmak da sayılsın diye daha geniş tetikleme yarıçapı kullan
            const triggerRadius = window.matchMedia('(pointer: coarse)').matches ? 160 : 130;
            if (distance >= triggerRadius) return;

            litecaptchaLastEscapeAt = now;
            litecaptchaEscapes += 1;
            setLitecaptchaStatus(litecaptchaEscapes + '/' + litecaptchaRequired, 'Tracking movement, follow the button.', 'is-active');

            postLitecaptcha({
                type: 'lc:escape',
                mouse: { x: pointerX, y: pointerY },
                button: {
                    x: centerX,
                    y: centerY,
                    left: rect.left,
                    top: rect.top,
                    width: rect.width,
                    height: rect.height
                },
                viewport: { width: window.innerWidth, height: window.innerHeight },
                distance,
                domRects: litecaptchaParentDomRects.slice(-24)
            });

            if (litecaptchaEscapes >= litecaptchaRequired) {
                setLitecaptchaStatus('Checking', 'Server is verifying escape signals.', 'is-active');
            }
        }

        window.addEventListener('resize', () => {
            if (!litecaptchaButton || litecaptchaButton.style.display === 'none') return;
            const rect = litecaptchaButton.getBoundingClientRect();
            const position = clampLitecaptchaButtonPosition(litecaptchaButton, rect.left, rect.top);
            litecaptchaButton.style.left = position.x + 'px';
            litecaptchaButton.style.top  = position.y + 'px';
            sampleLitecaptchaButtonRect();
        }, { passive: true });

        // Fare hareketi (masaüstü)
        document.addEventListener('mousemove', (event) => {
            if (!litecaptchaActive) return;
            postLitecaptcha({ type: 'lc:pointer', x: event.clientX, y: event.clientY, viewport: { width: window.innerWidth, height: window.innerHeight } });
            moveLitecaptchaButton(event.clientX, event.clientY);
        }, { passive: true });

        // Dokunmatik hareket (mobil / tablet)
        document.addEventListener('touchmove', (event) => {
            if (!litecaptchaActive) return;
            const touch = event.touches[0];
            if (!touch) return;
            postLitecaptcha({ type: 'lc:pointer', x: touch.clientX, y: touch.clientY, viewport: { width: window.innerWidth, height: window.innerHeight } });
            moveLitecaptchaButton(touch.clientX, touch.clientY);
        }, { passive: true });

        if (litecaptchaCheck && litecaptchaRow) {
            litecaptchaCheck.addEventListener('change', () => {
                if (!litecaptchaCheck.checked || litecaptchaActive) return;
                litecaptchaActive = true;
                litecaptchaCheck.disabled = true;
                litecaptchaParentRafDeltas = [];
                litecaptchaParentDomRects = [];
                litecaptchaLastRafAt = performance.now();
                litecaptchaStartedAt = performance.now();
                setLitecaptchaStatus(
                    litecaptchaReady ? 'Checking' : 'Preparing',
                    'Browser and behavior signals are being verified.',
                    'is-active'
                );
                requestLitecaptchaStart();
            });
        }

        if (litecaptchaFrame) {
            litecaptchaFrame.addEventListener('error', () => {
                failLitecaptchaBridge('LITECAPTCHA_IFRAME_LOAD_ERROR', 'LiteCaptcha iframe failed to load. Check domain, HTTPS, and document root settings.');
            });

            litecaptchaFrame.addEventListener('load', () => {
                try {
                    const frameUrl = new URL(litecaptchaFrame.contentWindow.location.href);
                    if ((frameUrl.pathname === '/yonetim' || frameUrl.pathname === '/yonetim/login') && frameUrl.searchParams.get('captcha') === 'ok') {
                        window.location.href = '/yonetim?captcha=ok';
                    }
                } catch (error) {
                    // LiteCaptcha challenge açıkken çapraz kaynak.
                }
            });
        }

        window.addEventListener('message', (event) => {
            if (litecaptchaFrame && event.source !== litecaptchaFrame.contentWindow) return;
            if (litecaptchaOrigin !== '*' && event.origin !== litecaptchaOrigin) return;
            const data = event.data || {};
            if (typeof data.type !== 'string' || !data.type.startsWith('lc:')) return;
            if (data.type === 'lc:ready') {
                litecaptchaReady = true;
                if (litecaptchaReadyTimer) {
                    clearTimeout(litecaptchaReadyTimer);
                    litecaptchaReadyTimer = null;
                }
                litecaptchaRequired = Number(data.requiredEscapes || 3);
                if (litecaptchaStartRequested) {
                    setLitecaptchaStatus('Checking', 'Button will appear on the page shortly.', 'is-active');
                    postLitecaptcha({ type: 'lc:start' });
                }
            } else if (data.type === 'lc:started') {
                litecaptchaRequired = Number(data.requiredEscapes || litecaptchaRequired);
                setLitecaptchaStatus('0/' + litecaptchaRequired, 'Tap or move near the button to chase it, then tap it when it stops.', 'is-active');
                placeLitecaptchaButton(data.initLeftPct, data.initTopPct);
            } else if (data.type === 'lc:status') {
                const escapeCount = Number(data.escapeCount || 0);
                if (escapeCount > 0) litecaptchaEscapes = escapeCount;
                const label  = data.label || ((data.escapeCount || 0) + '/' + litecaptchaRequired);
                const detail = escapeCount >= litecaptchaRequired
                    ? 'Final step: tap the button to complete verification.'
                    : 'Keep chasing the button — tap or swipe near it.';
                setLitecaptchaStatus(label, detail, 'is-active');
                if (escapeCount >= litecaptchaRequired && litecaptchaButton) {
                    litecaptchaButton.textContent = 'Verify ✓';
                }
                if (litecaptchaButton && typeof data.nextLeftPct === 'number' && typeof data.nextTopPct === 'number') {
                    const position = getParentButtonCoords(data.nextLeftPct, data.nextTopPct);
                    litecaptchaButton.style.left = position.x + 'px';
                    litecaptchaButton.style.top  = position.y + 'px';
                    window.setTimeout(sampleLitecaptchaButtonRect, 90);
                }
            } else if (data.type === 'lc:success') {
                handleLitecaptchaSuccess(data);
            } else if (data.type === 'lc:failure') {
                setLitecaptchaStatus('Failed', 'Reason: ' + (data.reason || 'REJECTED') + '. Refresh the page and try again.', 'is-error');
                if (litecaptchaButton) litecaptchaButton.style.display = 'none';
            }
        });
    </script>
</body>
</html>
