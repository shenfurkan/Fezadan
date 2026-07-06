<?php
$status = $_GET['status'] ?? '';
$token = $token ?? '';
$valid = (bool)($valid ?? false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | New Password</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">

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
        input:focus { background: var(--bg-secondary); }

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
    </style>
</head>
<body class="h-screen flex items-center justify-center p-6 relative">
    <div class="w-full max-w-md border border-[var(--text-main)] p-8 relative bg-[var(--bg-paper)] shadow-[8px_8px_0px_var(--text-main)] transition-colors">
        <h1 class="font-syne text-4xl font-bold uppercase text-center mb-2">FEZADAN</h1>
        <p class="text-xs uppercase tracking-widest text-center mb-8 opacity-70">New Password</p>

        <?php if (!$valid): ?>
            <div class="mb-6 p-4 font-mono text-xs flex-shrink-0 border-l-4 bg-red-900 text-red-100 border-red-500">
                Password reset link is invalid or has expired. For your security, these links are valid for 15 minutes.
            </div>
            <div class="text-center">
                <a href="/yonetim/forgot-password" class="text-xs uppercase tracking-widest opacity-70 hover:opacity-100 transition-opacity">REQUEST NEW LINK</a>
            </div>
        <?php else: ?>
            <?php if ($status === 'mismatch'): ?>
                <div class="mb-6 p-4 font-mono text-xs flex-shrink-0 border-l-4 bg-red-900 text-red-100 border-red-500">
                    The new passwords you entered do not match. Please try again.
                </div>
            <?php elseif ($status === 'weak'): ?>
                <div class="mb-6 p-4 font-mono text-xs flex-shrink-0 border-l-4 bg-red-900 text-red-100 border-red-500">
                    Your password is not strong enough. For your security, your password must be at least 12 characters long, contain at least one letter and at least one number.
                </div>
            <?php endif; ?>

            <form action="/yonetim/update-reset-password" method="POST" class="space-y-6">
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="new_password" class="text-xs uppercase tracking-widest block mb-2">New Password</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                </div>
                <div>
                    <label for="confirm_password" class="text-xs uppercase tracking-widest block mb-2">New Password (Confirm)</label>
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                </div>
                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
