<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | KULLANICI YÖNETİMİ</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <style>
        :root { --bg-paper: #FEF9E1; --bg-secondary: #E5D0AC; --text-main: #6D2323; --text-accent: #A31D1D; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .grid-bg { background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.05; pointer-events: none; }
        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; transition: 0.3s; }
        .brutalist-input:focus { background: rgba(109,35,35,0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; border-left: 1px dashed rgba(109,35,35,0.2); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(109,35,35,0.5); border-radius: 0; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(109,35,35,1); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>

    <?php include __DIR__ . '/_side_panel.php'; ?>

    <?php if ($flashMessage = Flash::pull()): ?>
        <div class="mb-8 p-4 bg-[#6D2323] text-[#FEF9E1] font-mono text-xs uppercase shadow-[4px_4px_0px_#A31D1D]">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col xl:flex-row gap-12 h-full pb-6">
        <div class="w-full xl:w-1/3">
            <h3 class="font-syne text-xl font-bold uppercase mb-6 flex items-center gap-2">
                <span class="w-3 h-3 bg-[#6D2323]"></span> Yeni Kullanıcı Oluştur
            </h3>

            <form action="/yonetim/store-admin" method="POST" class="border-2 border-[var(--text-main)] p-6 shadow-[8px_8px_0px_#A31D1D] bg-[var(--bg-paper)] space-y-5">
                <?= Csrf::field() ?>
                <div>
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Kullanıcı Adı</label>
                    <input type="text" name="username" class="brutalist-input font-bold" required autocomplete="username" placeholder="ornek.editor" pattern="[A-Za-z0-9._-]{3,64}">
                </div>

                <div>
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Ad Soyad</label>
                    <input type="text" name="name" class="brutalist-input font-bold" required autocomplete="name" maxlength="150" placeholder="Ad Soyad">
                </div>

                <div>
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">E-posta</label>
                    <input type="email" name="email" class="brutalist-input" required autocomplete="email" placeholder="name@fezadan.org" pattern="^[^@\s]+@fezadan\.org$">
                </div>

                <div>
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Rol</label>
                    <select name="role" class="brutalist-input font-bold uppercase" required>
                        <option value="editor" selected>Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>

                <div>
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Şifre</label>
                    <input type="password" name="password" class="brutalist-input" required autocomplete="new-password" minlength="12">
                </div>

                <div>
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Şifre (Tekrar)</label>
                    <input type="password" name="confirm_password" class="brutalist-input" required autocomplete="new-password" minlength="12">
                </div>

                <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all">
                    Kullanıcı Oluştur
                </button>
            </form>
        </div>

        <div class="w-full xl:w-2/3 flex flex-col h-full min-h-0">
            <h3 class="font-syne text-xl font-bold uppercase mb-6 flex items-center gap-2 flex-shrink-0">
                <span class="w-3 h-3 bg-[#6D2323]"></span> Kullanıcılar
            </h3>

            <div class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] overflow-y-auto custom-scrollbar flex-1 shadow-[8px_8px_0px_rgba(163,29,29,0.1)] h-[calc(100vh-180px)]">
                <table class="w-full text-left font-mono text-xs border-collapse">
                    <thead class="bg-[#6D2323] text-[#FEF9E1] uppercase sticky top-0 z-20">
                        <tr>
                            <th class="p-4 border-b border-[var(--text-main)]">ID</th>
                            <th class="p-4 border-b border-[var(--text-main)]">Kullanıcı</th>
                            <th class="p-4 border-b border-[var(--text-main)]">E-posta</th>
                            <th class="p-4 border-b border-[var(--text-main)]">Rol</th>
                            <th class="p-4 border-b border-[var(--text-main)]">Son Giriş</th>
                            <th class="p-4 border-b border-[var(--text-main)]">Passkey</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--text-main)]/10">
                        <?php if (!empty($admins)): foreach ($admins as $admin): ?>
                            <tr class="hover:bg-[var(--bg-secondary)]/30 transition-colors">
                                <td class="p-4 opacity-50">#<?php echo (int)$admin['id']; ?></td>
                                <td class="p-4">
                                    <div class="font-bold text-[var(--text-accent)] uppercase">
                                        <?php echo htmlspecialchars((string)$admin['username'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ((int)$admin['id'] === (int)($_SESSION['admin_id'] ?? 0)): ?>
                                            <span class="ml-2 text-[10px] text-[var(--text-main)] opacity-60">AKTİF</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="opacity-70">
                                        <?php echo htmlspecialchars((string)$admin['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <?php echo htmlspecialchars((string)($admin['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block px-2 py-1 bg-[#6D2323]/10 text-[var(--text-main)] font-bold uppercase">
                                        <?php echo htmlspecialchars((string)$admin['role'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php echo $admin['last_login'] ? htmlspecialchars((string)$admin['last_login'], ENT_QUOTES, 'UTF-8') : '<span class="opacity-50">Henüz yok</span>'; ?>
                                </td>
                                <td class="p-4">
                                    <?php if ((int)$admin['passkey_count'] > 0): ?>
                                        <span class="text-[var(--text-accent)] font-bold"><?php echo (int)$admin['passkey_count']; ?> kayıtlı</span>
                                    <?php else: ?>
                                        <span class="opacity-50">Yok</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center opacity-50 uppercase font-mono italic">// Henüz kullanıcı bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </div> </main> </body>
</html>
