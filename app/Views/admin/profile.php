<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | PROFİL AYARLARI</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        :root { --bg-paper: #FEF9E1; --text-main: #6D2323; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; }
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; transition: 0.3s; }
        .brutalist-input:focus { background: rgba(109, 35, 35, 0.05); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-72 border-r-2 border-[var(--text-main)] p-8 flex flex-col justify-between">
        <div>
            <h1 class="font-syne text-3xl font-bold mb-8">FEZADAN</h1>
            <nav class="flex flex-col gap-4">
                <a href="/admin/dashboard" class="font-bold uppercase tracking-widest text-sm hover:underline">← Geri Dön</a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 p-12 overflow-y-auto">
        <h2 class="font-syne text-4xl font-bold uppercase mb-12">PROFİL GÜVENLİĞİ</h2>

        <?php if(isset($_GET['status'])): ?>
            <div class="mb-8 p-4 bg-[#6D2323] text-[#FEF9E1] font-mono text-xs uppercase">
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
                <div class="font-bold text-xl uppercase"><?php echo $_SESSION['admin_user']; ?></div>
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
    </main>
</body>
</html>