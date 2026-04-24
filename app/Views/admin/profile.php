<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | PROFİL AYARLARI</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
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
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <?php if(isset($_GET['status'])): ?>
            <div class="mb-8 p-4 bg-[#6D2323] text-[#FEF9E1] font-mono text-xs uppercase shadow-[4px_4px_0px_#A31D1D]">
                <?php 
                    if($_GET['status'] == 'success') echo "✅ ŞİFRE BAŞARIYLA GÜNCELLENDİ.";
                    elseif($_GET['status'] == 'wrong_pass') echo "❌ MEVCUT ŞİFRE HATALI.";
                    elseif($_GET['status'] == 'mismatch') echo "❌ YENİ ŞİFRELER EŞLEŞMİYOR.";
                ?>
            </div>
        <?php endif; ?>

        <form action="/admin/update-password" method="POST" class="max-w-md space-y-8">
            <div>
                <label class="block font-mono text-[10px] uppercase opacity-60">Aktif Kullanıcı</label>
                <div class="font-bold text-xl uppercase text-[var(--text-main)]"><?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'ADMIN'); ?></div>
            </div>

            <div>
                <label class="block font-syne font-bold uppercase text-xs mb-2">Mevcut Şifre</label>
                <input type="password" name="old_password" class="brutalist-input" required>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Yeni Şifre</label>
                    <input type="password" name="new_password" class="brutalist-input" required>
                </div>
                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Yeni Şifre (Tekrar)</label>
                    <input type="password" name="confirm_password" class="brutalist-input" required>
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all shadow-[8px_8px_0px_#A31D1D]">
                GÜVENLİK PROTOKOLÜNÜ GÜNCELLE
            </button>
        </form>

    </div> </main> </body>
</html>