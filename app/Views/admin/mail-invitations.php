<?php
/** @var array $invitations */
/** @var array|null $created */
/** @var bool $configured */
$states = ['pending' => 'Bekliyor', 'expired' => 'Süresi doldu', 'revoked' => 'İptal edildi',
    'processing' => 'İşlem sürüyor', 'review' => 'Kontrol bekliyor', 'used' => 'Hesap açıldı', 'closed' => 'Adres mevcut; kapatıldı'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FEZADAN | Posta davetleri</title>
    <link rel="icon" href="/cdn/dark-favicon.ico">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/mail-invitations.css?v=<?= filemtime(ROOT . '/public_html/assets/css/mail-invitations.css') ?>">
</head>
<body class="mail-invite-admin flex h-screen w-full overflow-hidden">
<?php include __DIR__ . '/_side_panel.php'; ?>
<p class="mail-invite-muted">Davetiye yalnızca belirtilen adreste yeni bir posta hesabı açar. Yönetim paneli hesabı oluşturmaz.</p>
<?php if ($message = Flash::pull()): ?>
    <p class="mail-invite-notice" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!$configured): ?>
    <p class="mail-invite-notice">Hesap açma ve şifre değiştirme şu anda kapalı. Davetiye hazırlayabilirsiniz; kullanım için özel cPanel bağlantısı ve <code>MAIL_INVITES_ENABLED</code> ayarı gerekir.</p>
<?php endif; ?>
<?php if ($created): ?>
    <section class="mail-invite-notice" aria-labelledby="new-invite-title">
        <h3 id="new-invite-title">Davetiye hazır</h3>
        <p><?= htmlspecialchars($created['address'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($created['expires_at'], ENT_QUOTES, 'UTF-8') ?> UTC tarihine kadar</p>
        <label for="invite-code">Davetiye kodu</label>
        <input id="invite-code" class="mail-invite-code" readonly autocomplete="off" value="<?= htmlspecialchars($created['code'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="invite-page">Hesap açma sayfası</label>
        <input id="invite-page" readonly value="<?= htmlspecialchars(langUrl('/posta-davet', 'tr'), ENT_QUOTES, 'UTF-8') ?>">
        <p class="mail-invite-muted">Adres, kod ve bağlantıyı yalnızca alıcıya iletin. Kod bu sayfadan ayrıldıktan sonra yeniden gösterilmez.</p>
    </section>
<?php endif; ?>
<div class="mail-invite-columns">
    <div class="mail-invite-stack">
    <section aria-labelledby="create-invite-title">
        <h3 id="create-invite-title">Yeni davetiye</h3>
        <form class="mail-invite-form" method="post" action="/yonetim/store-mail-invitation">
            <?= Csrf::field() ?>
            <div>
                <label for="invite-local-part">Posta hesabı adı</label>
                <div class="mail-invite-address"><input id="invite-local-part" name="local_part" required maxlength="64" pattern="[a-z0-9](?:[a-z0-9._\-]{0,62}[a-z0-9])?" autocomplete="off" aria-describedby="invite-domain"><span id="invite-domain">@fezadan.org</span></div>
            </div>
            <div><label for="invite-quota">Depolama kotası (MB)</label><input id="invite-quota" type="number" name="quota_mb" min="25" max="1024" value="250" required></div>
            <div><label for="invite-hours">Geçerlilik süresi</label><select id="invite-hours" name="hours"><option value="1">1 saat</option><option value="24" selected>24 saat</option><option value="48">48 saat</option><option value="72">3 gün</option><option value="168">7 gün</option></select></div>
            <button type="submit">Davetiye oluştur</button>
        </form>
    </section>
    <section class="mail-invite-password" aria-labelledby="change-password-title">
        <h3 id="change-password-title">Mevcut hesabın şifresini değiştir</h3>
        <p class="mail-invite-muted">Yalnızca var olan bir hesabın şifresini değiştirir. Yeni şifreyi kullanıcıya güvenli bir kanaldan iletin; eski şifreyle açık oturumlar kendiliğinden kapanmaz.</p>
        <form class="mail-invite-form" method="post" action="/yonetim/change-mail-password">
            <?= Csrf::field() ?>
            <div>
                <label for="mail-password-local-part">Posta hesabı adı</label>
                <div class="mail-invite-address"><input id="mail-password-local-part" name="local_part" required maxlength="64" pattern="[a-z0-9](?:[a-z0-9._\-]{0,62}[a-z0-9])?" autocomplete="off" aria-describedby="mail-password-domain"><span id="mail-password-domain">@fezadan.org</span></div>
            </div>
            <div><label for="mail-password-new">Yeni şifre</label><input id="mail-password-new" name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" aria-describedby="mail-password-hint"></div>
            <div><label for="mail-password-confirm">Yeni şifre (tekrar)</label><input id="mail-password-confirm" name="password_confirm" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></div>
            <p id="mail-password-hint" class="mail-invite-muted">En az 12 karakter kullanın. Şifre kaydedilmez; yalnızca posta sunucusuna iletilir.</p>
            <button type="submit" <?= $configured ? '' : 'disabled' ?>>Şifreyi değiştir</button>
        </form>
    </section>
    </div>
    <section class="mail-invite-list" aria-labelledby="invite-list-title">
        <h3 id="invite-list-title">Son davetiyeler</h3>
        <p class="mail-invite-muted">Belirsiz işlemler yeniden gönderilmez. En az 2 dakika sonra “Sunucuda kontrol et” yalnızca adresin varlığını kontrol eder; şifre değiştirmez.</p>
        <div class="mail-invite-table-wrap">
            <table><thead><tr><th>Adres</th><th>Kota</th><th>Durum</th><th>Son geçerlilik (UTC)</th><th>İşlem</th></tr></thead><tbody>
            <?php foreach ($invitations as $invite): ?>
                <tr data-invitation-id="<?= (int)$invite['id'] ?>">
                    <td><?= htmlspecialchars($invite['local_part'], ENT_QUOTES, 'UTF-8') ?>@fezadan.org</td>
                    <td><?= (int)$invite['quota_mb'] ?> MB</td>
                    <td><?= htmlspecialchars($states[$invite['status']] ?? 'Kontrol bekliyor', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($invite['expires_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php if ($invite['status'] === 'pending' || $invite['status'] === 'expired'): ?>
                        <form method="post" action="/yonetim/revoke-mail-invitation"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$invite['id'] ?>"><button type="submit" class="mail-invite-secondary">İptal et</button></form>
                    <?php elseif (in_array($invite['status'], ['processing', 'review'], true)): ?>
                        <form method="post" action="/yonetim/reconcile-mail-invitation"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$invite['id'] ?>"><button type="submit" class="mail-invite-secondary" <?= $configured ? '' : 'disabled' ?>>Sunucuda kontrol et</button></form>
                    <?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$invitations): ?><tr><td colspan="5">Henüz davetiye oluşturulmadı.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>
</div>
</div></main></body></html>
