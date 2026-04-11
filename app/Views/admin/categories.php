<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | KATEGORİ YÖNETİMİ</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        :root { --bg-paper: #FEF9E1; --text-main: #6D2323; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; }
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; }
        .brutalist-input:focus { background: rgba(109, 35, 35, 0.05); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-20 md:w-72 border-r-2 border-[var(--text-main)] p-6 flex flex-col bg-[var(--bg-paper)]">
        <h1 class="font-syne text-3xl font-bold mb-12 hidden md:block">FEZADAN</h1>
        <nav class="flex flex-col gap-4">
            <a href="/admin/dashboard" class="font-bold uppercase text-sm hover:underline">← Geri Dön</a>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-12 overflow-y-auto flex flex-col md:flex-row gap-12">
        
        <div class="w-full md:w-1/3">
            <h2 class="font-syne text-2xl font-bold uppercase mb-6 flex items-center gap-2">
                <span class="w-3 h-3 bg-[#6D2323]"></span> YENİ KATEGORİ
            </h2>
            
            <form action="/admin/store-category" method="POST" class="border-2 border-[var(--text-main)] p-6 shadow-[8px_8px_0px_#A31D1D] bg-white">
                <div class="mb-6">
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Kategori Adı</label>
                    <input type="text" name="name" class="brutalist-input uppercase font-bold text-lg" placeholder="ÖRN: BİLİM KURGU" required autocomplete="off">
                </div>
                <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all">
                    KAYDET [+]
                </button>
                
                <?php if(isset($_GET['status'])): ?>
                    <div class="mt-4 text-xs font-mono text-center">
                        <?php if($_GET['status'] == 'error') echo '<span class="text-red-600">⚠ BU KATEGORİ ZATEN VAR</span>'; ?>
                        <?php if($_GET['status'] == 'success') echo '<span class="text-green-700">✓ BAŞARIYLA EKLENDİ</span>'; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="w-full md:w-2/3">
            <h2 class="font-syne text-2xl font-bold uppercase mb-6 flex items-center gap-2">
                <span class="w-3 h-3 bg-[#6D2323]"></span> MEVCUT LİSTE
            </h2>

            <div class="border-2 border-[var(--text-main)] bg-white overflow-hidden">
                <table class="w-full text-left font-mono text-xs">
                    <thead class="bg-[#6D2323] text-[#FEF9E1] uppercase">
                        <tr>
                            <th class="p-4">ID</th>
                            <th class="p-4">Kategori Adı</th>
                            <th class="p-4">Makale Sayısı</th>
                            <th class="p-4 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($categories)): foreach($categories as $cat): ?>
                        <tr class="border-b border-[var(--text-main)]/10 hover:bg-[var(--bg-paper)]">
                            <td class="p-4 opacity-50">#<?php echo $cat['id']; ?></td>
                            <td class="p-4 font-bold text-[var(--text-accent)] text-sm uppercase">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </td>
                            <td class="p-4">
                                <span class="bg-[#6D2323]/10 px-2 py-1 rounded">
                                    <?php echo $cat['article_count']; ?> Yazı
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="/admin/delete-category?id=<?php echo $cat['id']; ?>" 
                                   onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?');"
                                   class="text-[var(--text-accent)] hover:bg-[var(--text-accent)] hover:text-white px-2 py-1 transition-colors font-bold">
                                    [SİL]
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="p-8 text-center opacity-50">HİÇ KATEGORİ YOK.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>