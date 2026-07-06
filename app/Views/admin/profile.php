<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | PROFIL AYARLARI</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <script src="/assets/js/admin.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin.js'); ?>"></script>
    <style>
        :root { --bg-paper: #FEF9E1; --bg-secondary: #E5D0AC; --text-main: #6D2323; --text-accent: #A31D1D; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne  { font-family: 'Syne', sans-serif; }
        .font-mono  { font-family: 'JetBrains Mono', monospace; }
        .grid-bg { background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.05; pointer-events: none; }
        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; transition: 0.3s; }
        .brutalist-input:focus { background: rgba(109,35,35,0.05); }
        .brutalist-input:disabled { opacity: 0.4; cursor: not-allowed; border-bottom-style: dashed; }
        .brutalist-select { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; color: var(--text-main); }
        .brutalist-textarea { width: 100%; background: transparent; border: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; resize: vertical; min-height: 100px; transition: 0.3s; }
        .brutalist-textarea:focus { background: rgba(109,35,35,0.05); }
        .brutalist-textarea:disabled { opacity: 0.4; cursor: not-allowed; border-style: dashed; }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <?php if(isset($_GET['status'])): ?>
            <div class="mb-8 p-4 bg-[#6D2323] text-[#FEF9E1] font-mono text-xs uppercase shadow-[4px_4px_0px_#A31D1D]">
                <?php 
                    if($_GET['status'] == 'success') echo "PROFIL GUVENLIGI BASARIYLA GUNCELLENDI.";
                    elseif($_GET['status'] == 'profile_success') echo "PROFIL BILGILERI BASARIYLA GUNCELLENDI.";
                    elseif($_GET['status'] == 'wrong_pass') echo "MEVCUT SIFRE HATALI.";
                    elseif($_GET['status'] == 'mismatch') echo "YENI SIFRELER ESLESMIYOR.";
                    elseif($_GET['status'] == 'weak') echo "SIFRE ZAYIF (EN AZ 12 KARAKTER OLMALI, HARF VE RAKAM ICERMELI).";
                    elseif($_GET['status'] == 'invalid_email') echo "GECERSIZ E-POSTA ADRESI.";
                    elseif($_GET['status'] == 'email_domain') echo "YALNIZCA @FEZADAN.ORG E-POSTA ADRESINE IZIN VERILIR.";
                    elseif($_GET['status'] == 'duplicate_email') echo "BU E-POSTA BASKA BIR ADMINE ATANMIS.";
                    elseif($_GET['status'] == 'invalid_username') echo "KULLANICI ADI 3-64 KARAKTER OLMALI; YALNIZ HARF, RAKAM, NOKTA, TIRE VE ALT CIZGI KULLANILABILIR.";
                    elseif($_GET['status'] == 'duplicate_username') echo "BU KULLANICI ADI ZATEN KULLANILIYOR.";
                    elseif($_GET['status'] == 'invalid_name') echo "GORUNEN AD ZORUNLUDUR VE 150 KARAKTERI GECEMEZ.";
                    elseif($_GET['status'] == 'invalid_bio') echo "BIYOGRAFI 2000 KARAKTERI GECEMEZ.";
                    elseif($_GET['status'] == 'invalid_image') echo "GECERLI BIR PROFIL FOTOGRAFI URL'I GIRIN.";
                ?>
            </div>
        <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Sol Kolon: Profil Bilgileri + Sifre -->
        <div class="space-y-12">
            <!-- Profil Bilgileri -->
            <div>
                <h3 class="font-syne font-bold uppercase text-lg mb-6 border-b border-[var(--text-main)] pb-2">PROFIL BILGILERI</h3>
                <form action="/yonetim/update-profile" method="POST" class="space-y-6">
                    <?= Csrf::field() ?>
                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Kullanici Adi</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($admin['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="brutalist-input" required minlength="3" maxlength="64" pattern="[a-zA-Z0-9._-]{3,64}" autocomplete="username">
                        <p class="font-mono text-[10px] opacity-50 mt-1">3-64 karakter; harf, rakam, nokta, tire, alt cizgi</p>
                    </div>

                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Gorunen Ad</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($admin['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="brutalist-input" required maxlength="150">
                    </div>

                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">E-posta Adresi</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="brutalist-input" autocomplete="email" pattern="^[^@\s]+@fezadan\.org$">
                    </div>

                    <!-- Yazar Baglantisi -->
                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Yazar Profili Baglantisi</label>
                        <select name="author_id" class="brutalist-select" onchange="toggleAuthorFields(this.value)">
                            <option value="">-- Bagli Degil --</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= $author['id'] ?>" <?= ($admin['author_id'] ?? null) == $author['id'] ? 'selected' : '' ?>><?= htmlspecialchars($author['name']) ?> (<?= htmlspecialchars($author['slug']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="font-mono text-[10px] opacity-50 mt-1">Baglandiginizda bio ve profil fotografi ekleyebilirsiniz</p>
                    </div>

                    <!-- Bio (yazara bagli) -->
                    <div id="bio-section" class="<?= ($admin['author_id'] ?? null) ? '' : 'opacity-40 pointer-events-none' ?>">
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Biyografi</label>
                        <textarea name="bio" class="brutalist-textarea" maxlength="2000" <?= ($admin['author_id'] ?? null) ? '' : 'disabled' ?>><?= htmlspecialchars($linkedAuthor['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <p class="font-mono text-[10px] opacity-50 mt-1"><?= ($admin['author_id'] ?? null) ? 'Max 2000 karakter' : 'Biyografi eklemek icin bir yazar profiline baglanin' ?></p>
                    </div>

                    <!-- Profil fotografi (yazara bagli) -->
                    <div id="image-section" class="<?= ($admin['author_id'] ?? null) ? '' : 'opacity-40 pointer-events-none' ?>">
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Profil Fotografi URL</label>
                        <input type="url" name="image_url" value="<?= htmlspecialchars($linkedAuthor['image_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="brutalist-input" placeholder="https://..." <?= ($admin['author_id'] ?? null) ? '' : 'disabled' ?>>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all shadow-[8px_8px_0px_#A31D1D] cursor-pointer">
                        PROFILI GUNCELLE
                    </button>
                </form>
            </div>

            <!-- Sifre Degistirme -->
            <div>
                <h3 class="font-syne font-bold uppercase text-lg mb-6 border-b border-[var(--text-main)] pb-2">SIFRE DEGISTIR</h3>
                <form action="/yonetim/update-password" method="POST" class="space-y-6">
                    <?= Csrf::field() ?>
                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Mevcut Sifre</label>
                        <input type="password" name="old_password" class="brutalist-input" autocomplete="current-password" required>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block font-syne font-bold uppercase text-xs mb-2">Yeni Sifre</label>
                            <input type="password" name="new_password" class="brutalist-input" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="block font-syne font-bold uppercase text-xs mb-2">Yeni Sifre (Tekrar)</label>
                            <input type="password" name="confirm_password" class="brutalist-input" autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all shadow-[8px_8px_0px_#A31D1D] cursor-pointer">
                        SIFREYI GUNCELLE
                    </button>
                </form>
                <a href="/yonetim/reset-mail-preview" target="_blank" rel="noopener" class="inline-block mt-4 font-mono text-[10px] uppercase underline opacity-70 hover:opacity-100">Sifre sifirlama e-posta sablonunu goruntule</a>
            </div>
        </div>

        <!-- Sag Kolon: Giris Anahtarlari (Passkey) -->
        <div>
            <h3 class="font-syne font-bold uppercase text-lg mb-6 border-b border-[var(--text-main)] pb-2">GIRIS ANAHTARLARI (PASSKEY)</h3>
            
            <div class="space-y-6 mb-8">
                <?php
                $pdo = Db::pdo();
                $stmt = $pdo->prepare("SELECT * FROM admin_passkeys WHERE admin_id = ? ORDER BY created_at DESC");
                $stmt->execute([$_SESSION['admin_id']]);
                $passkeys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                if (empty($passkeys)):
                ?>
                    <p class="font-mono text-xs uppercase opacity-70">Kayitli passkey bulunamadi.</p>
                <?php else: ?>
                    <div class="border border-[var(--text-main)] divide-y divide-[var(--text-main)] bg-[var(--bg-secondary)]/10">
                        <?php foreach ($passkeys as $pk): ?>
                            <div class="p-4 flex items-center justify-between font-mono text-xs">
                                <div>
                                    <div class="font-bold">ANAHTAR #<?= $pk['id'] ?></div>
                                    <div class="text-[10px] opacity-60">Kayit: <?= $pk['created_at'] ?></div>
                                </div>
                                <form action="/yonetim/deletePasskey" method="POST" class="inline" data-confirm="Bu passkey kaydini silmek istediginize emin misiniz?">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $pk['id'] ?>">
                                    <button type="submit" class="py-2 px-4 bg-red-950 text-red-100 border-2 border-red-900 font-mono text-[10px] uppercase hover:bg-red-800 hover:text-white transition-colors cursor-pointer">SIL</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" id="btn-register-passkey" class="w-full py-4 bg-transparent border-2 border-[var(--text-main)] text-[var(--text-main)] font-bold uppercase hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-all shadow-[8px_8px_0px_#A31D1D] cursor-pointer">
                YENI PASSKEY EKLE
            </button>
        </div>
    </div>

    </div> </main>

    <script nonce="<?= CSP_NONCE ?>">
        function toggleAuthorFields(authorId) {
            const bioEl = document.getElementById('bio-section');
            const imgEl = document.getElementById('image-section');
            const bioTextarea = bioEl.querySelector('textarea');
            const imgInput = imgEl.querySelector('input');

            if (authorId) {
                bioEl.classList.remove('opacity-40', 'pointer-events-none');
                imgEl.classList.remove('opacity-40', 'pointer-events-none');
                bioTextarea.disabled = false;
                imgInput.disabled = false;
            } else {
                bioEl.classList.add('opacity-40', 'pointer-events-none');
                imgEl.classList.add('opacity-40', 'pointer-events-none');
                bioTextarea.disabled = true;
                imgInput.disabled = true;
                bioTextarea.value = '';
                imgInput.value = '';
            }
        }

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

        document.getElementById('btn-register-passkey').addEventListener('click', async () => {
            if (!window.PublicKeyCredential) {
                if (window.FezadanToast) window.FezadanToast.error('Tarayiciniz Passkey desteklemiyor.');
                else alert('Tarayiciniz Passkey desteklemiyor.');
                return;
            }

            try {
                const data = window.FezadanFetch
                    ? await window.FezadanFetch('/yonetim/registerPasskeyChallenge')
                    : await (await fetch('/yonetim/registerPasskeyChallenge')).json();

                if (!data.success) {
                    const message = 'Passkey kaydi baslatilamadi: ' + (data.error || 'Bilinmeyen hata');
                    if (window.FezadanToast) window.FezadanToast.error(message);
                    else alert(message);
                    return;
                }

                const credential = await navigator.credentials.create({
                    publicKey: {
                        challenge: base64UrlToBuffer(data.challenge),
                        rp: data.rp,
                        user: {
                            id: base64UrlToBuffer(data.user.id),
                            name: data.user.name,
                            displayName: data.user.displayName
                        },
                        pubKeyCredParams: data.pubKeyCredParams,
                        timeout: 60000,
                        attestation: "none",
                        authenticatorSelection: {
                            residentKey: "required",
                            userVerification: "required"
                        }
                    }
                });

                const csrfToken = document.querySelector('input[name="_csrf"]').value;

                const verifyRequest = window.FezadanFetch ? window.FezadanFetch('/yonetim/registerPasskeyVerify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64Url(credential.response.attestationObject),
                        _csrf: csrfToken
                    })
                }) : fetch('/yonetim/registerPasskeyVerify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64Url(credential.response.attestationObject),
                        _csrf: csrfToken
                    })
                }).then(response => response.json());

                const verifyResult = await verifyRequest;
                if (verifyResult.success) {
                    if (window.FezadanToast) window.FezadanToast.success('Passkey basariyla kaydedildi.');
                    else alert('Passkey basariyla kaydedildi.');
                    window.location.reload();
                } else {
                    const message = 'Kayit dogrulanamadi: ' + (verifyResult.error || 'Dogrulama hatasi');
                    if (window.FezadanToast) window.FezadanToast.error(message);
                    else alert(message);
                }
            } catch (err) {
                console.error(err);
                if (window.FezadanToast) window.FezadanToast.error('Passkey kaydi basarisiz oldu veya iptal edildi.');
                else alert('Passkey kaydi basarisiz oldu veya iptal edildi.');
            }
        });
    </script>
</body>
</html>
