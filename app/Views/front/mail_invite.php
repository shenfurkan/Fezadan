<?php
/** @var bool $configured */
/** @var string $error */
/** @var string $code */
/** @var string $address */
/** @var string $success */
/** @var string $state */
/** @var string $formPath */
/** @var string $captchaReturn */
/** @var string $captchaUrl */
$en = App::getLang() === 'EN';
$errors = $en ? ['invalid' => 'The invitation or assigned email address is invalid, expired or already used.',
    'password' => 'Use matching passwords of at least 12 characters (up to 128 bytes), without control characters.',
    'captcha' => 'Please complete verification again.', 'disabled' => 'Account creation is currently unavailable.',
    'rate_limited' => 'Too many attempts. Please wait 15 minutes before trying again.',
    'unavailable' => 'The mail server could not be checked. Your invitation has not been used. Please try later.',
    'review' => 'The result needs an administrator’s check. Do not submit again; contact the person who invited you.']
    : ['invalid' => 'Davetiye veya verilen e-posta adresi geçersiz, süresi dolmuş ya da kullanılmış.',
    'password' => 'Şifreler eşleşmeli; en az 12 karakter kullanın. Çok uzun şifrelerden ve kontrol karakterlerinden kaçının.',
    'captcha' => 'Lütfen doğrulamayı yeniden tamamlayın.', 'disabled' => 'Hesap açma şu anda kapalı.',
    'rate_limited' => 'Çok fazla deneme yapıldı. Yeniden denemeden önce 15 dakika bekleyin.',
    'unavailable' => 'Posta sunucusu kontrol edilemedi. Davetiyeniz kullanılmadı; daha sonra yeniden deneyin.',
    'review' => 'İşlemin sonucu yönetici kontrolü bekliyor. Yeniden göndermeyin; sizi davet eden kişiyle iletişime geçin.'];
$labels = $en ? ['ready' => 'Ready', 'preparing' => 'Preparing', 'checking' => 'Checking',
    'follow' => 'Follow the button, then click or tap it when it stops.', 'catch' => 'Catch', 'verify' => 'Verify',
    'final' => 'Click or tap the button to finish verification.', 'verified' => 'Verified',
    'error' => 'Verification failed. Check the box to try again.', 'expired' => 'Verification expired. Please verify again.']
    : ['ready' => 'Hazır', 'preparing' => 'Hazırlanıyor', 'checking' => 'Kontrol ediliyor',
    'follow' => 'Butonu takip edin; durduğunda tıklayın veya dokunun.', 'catch' => 'Yakala', 'verify' => 'Doğrula',
    'final' => 'Doğrulamayı bitirmek için butona tıklayın veya dokunun.', 'verified' => 'Doğrulandı',
    'error' => 'Doğrulama tamamlanamadı. Yeniden denemek için kutuyu işaretleyin.', 'expired' => 'Doğrulamanın süresi doldu. Lütfen yeniden doğrulayın.'];
?>
<!DOCTYPE html>
<html lang="<?= $en ? 'en' : 'tr' ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= $en ? 'Mail invitation' : 'Posta daveti' ?> | FEZADAN</title>
    <link rel="icon" href="/cdn/dark-favicon.ico">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/mail-invitations.css?v=<?= filemtime(ROOT . '/public_html/assets/css/mail-invitations.css') ?>">
    <?php if ($configured && $success === ''): ?><script src="/assets/js/mail-invite-captcha.js?v=<?= filemtime(ROOT . '/public_html/assets/js/mail-invite-captcha.js') ?>" nonce="<?= CSP_NONCE ?>" defer></script><?php endif; ?>
</head>
<body class="mail-invite-page">
<main class="mail-invite-public">
    <a href="<?= htmlspecialchars(langUrl('/'), ENT_QUOTES, 'UTF-8') ?>" class="mail-invite-brand"><span aria-hidden="true"></span>Fezadan Posta</a>
    <h1><?= $en ? 'Create your email account' : 'E-posta hesabınızı oluşturun' ?></h1>
    <?php if ($success !== ''): ?>
        <p role="status"><strong><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></strong><br><?= $en ? 'Your account is ready. Sign in with the password you chose.' : 'Hesabınız hazır. Belirlediğiniz şifreyle giriş yapabilirsiniz.' ?></p>
    <?php elseif (!$configured): ?>
        <p><?= $en ? 'Invitation-based account creation is currently unavailable. Contact the person who invited you.' : 'Davetiyeyle hesap açma şu anda kapalı. Sizi davet eden kişiyle iletişime geçin.' ?></p>
    <?php else: ?>
        <p class="mail-invite-muted"><?= $en ? 'Use the invitation code and email address provided by your administrator.' : 'Yöneticinizin verdiği davetiye kodunu ve e-posta adresini kullanın.' ?></p>
        <?php if ($error !== ''): ?><p class="mail-invite-notice" role="alert"><?= htmlspecialchars($errors[$error] ?? $errors['invalid'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form id="mail-invite-form" class="mail-invite-form" method="post" action="<?= htmlspecialchars($formPath, ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <div><label for="invitation-code"><?= $en ? 'Invitation code' : 'Davetiye kodu' ?></label><input id="invitation-code" name="code" required minlength="8" maxlength="8" autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"></div>
            <div><label for="invitation-address"><?= $en ? 'Assigned email address' : 'Verilen e-posta adresi' ?></label><input id="invitation-address" name="address" type="email" required autocomplete="username" maxlength="76" value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>"></div>
            <div><label for="invitation-password"><?= $en ? 'Password' : 'Şifre' ?></label><input id="invitation-password" name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" aria-describedby="password-hint"><p id="password-hint" class="mail-invite-muted"><?= $en ? 'Use at least 12 characters. This code is not your password.' : 'En az 12 karakter kullanın. Davetiye kodu şifreniz değildir.' ?></p></div>
            <div><label for="invitation-confirm"><?= $en ? 'Repeat password' : 'Şifre tekrar' ?></label><input id="invitation-confirm" name="confirm_password" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></div>
            <input type="hidden" name="lc_state" id="lc_state" value="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="lc_rt" id="lc_rt"><input type="hidden" name="lc_sig" id="lc_sig"><input type="hidden" name="lc_exp" id="lc_exp">
            <div id="mail-invite-captcha" class="mail-invite-captcha" data-return="<?= htmlspecialchars($captchaReturn, ENT_QUOTES, 'UTF-8') ?>" <?php foreach ($labels as $key => $text): ?>data-<?= $key ?>="<?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?>" <?php endforeach; ?>>
                <div class="mail-captcha-brand" aria-hidden="true">LiteCaptcha</div>
                <div class="mail-captcha-line"><label for="litecaptcha-check"><input id="litecaptcha-check" type="checkbox" aria-describedby="litecaptcha-status litecaptcha-detail"><span><?= $en ? "I'm not a robot" : 'Ben robot değilim' ?></span></label><span id="litecaptcha-status" role="status"><?= $labels['ready'] ?></span></div>
                <div id="litecaptcha-detail" hidden></div>
                <iframe class="litecaptcha-bridge" data-src="<?= htmlspecialchars($captchaUrl, ENT_QUOTES, 'UTF-8') ?>" title="LiteCaptcha" tabindex="-1" aria-hidden="true" referrerpolicy="no-referrer"></iframe>
            </div>
            <button id="mail-invite-submit" type="submit" disabled><?= $en ? 'Create account' : 'Hesabı oluştur' ?></button>
            <noscript><p><?= $en ? 'JavaScript is required for verification.' : 'Doğrulama için JavaScript’i etkinleştirin.' ?></p></noscript>
        </form>
    <?php endif; ?>
    <a class="mail-invite-back" href="<?= htmlspecialchars(rtrim(SITE_URL, '/'), ENT_QUOTES, 'UTF-8') ?>"><?= $en ? 'Back to home' : 'Ana sayfaya dön' ?></a>
</main>
</body></html>
