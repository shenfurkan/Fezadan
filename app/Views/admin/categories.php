<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | KATEGORİ YÖNETİMİ</title>
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
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; border-left: 1px dashed rgba(109,35,35,0.2); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(109,35,35,0.5); border-radius: 0px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(109,35,35,1); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="flex flex-col md:flex-row gap-12 h-full pb-6">
            
            <div class="w-full md:w-1/3">
                <h3 class="font-syne text-xl font-bold uppercase mb-6 flex items-center gap-2">
                    <span class="w-3 h-3 bg-[#6D2323]"></span> YENİ KATEGORİ
                </h3>
                
                <form action="/admin/store-category" method="POST" class="border-2 border-[var(--text-main)] p-6 shadow-[8px_8px_0px_#A31D1D] bg-[var(--bg-paper)]">
                    <div class="mb-6">
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-2">Kategori Adı</label>
                        <input type="text" name="name" class="brutalist-input uppercase font-bold text-lg" placeholder="ÖRN: BİLİM KURGU" required autocomplete="off">
                    </div>
                    <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all">
                        KAYDET [+]
                    </button>
                    
                    <?php if(isset($_GET['status'])): ?>
                        <div class="mt-4 text-xs font-mono text-center">
                            <?php if($_GET['status'] == 'error') echo '<span class="text-red-600 font-bold tracking-wider">⚠ BU KATEGORİ ZATEN VAR</span>'; ?>
                            <?php if($_GET['status'] == 'success') echo '<span class="text-[#6D2323] font-bold tracking-wider bg-[var(--bg-secondary)] px-2 py-1">✓ BAŞARIYLA EKLENDİ</span>'; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <div class="w-full md:w-2/3 flex flex-col h-full min-h-0">
                <h3 class="font-syne text-xl font-bold uppercase mb-6 flex items-center gap-2 flex-shrink-0">
                    <span class="w-3 h-3 bg-[#6D2323]"></span> MEVCUT LİSTE
                </h3>

                <div class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] overflow-y-auto custom-scrollbar flex-1 shadow-[8px_8px_0px_rgba(163,29,29,0.1)] h-[calc(100vh-180px)]">
                    <table class="w-full text-left font-mono text-xs border-collapse">
                        <thead class="bg-[#6D2323] text-[#FEF9E1] uppercase sticky top-0 z-20">
                            <tr>
                                <th class="p-4 border-b border-[var(--text-main)]">ID</th>
                                <th class="p-4 border-b border-[var(--text-main)]">Kategori Adı</th>
                                <th class="p-4 border-b border-[var(--text-main)]">Yazı Sayısı</th>
                                <th class="p-4 border-b border-[var(--text-main)]">Not Sayısı</th>
                                <th class="p-4 border-b border-[var(--text-main)] text-right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--text-main)]/10">
                            <?php if(!empty($categories)): foreach($categories as $cat): ?>
                            <tr class="hover:bg-[var(--bg-secondary)]/30 transition-colors">
                                <td class="p-4 opacity-50">#<?php echo $cat['id']; ?></td>
                                <td class="p-4 font-bold text-[var(--text-accent)] text-sm uppercase">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </td>
                                <td class="p-4">
                                    <span class="bg-[#6D2323]/10 px-2 py-1 rounded">
                                        <?php echo $cat['article_count']; ?> Yazı
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="bg-[var(--text-accent)]/10 px-2 py-1 rounded text-[var(--text-accent)] font-bold">
                                        <?php echo $cat['note_count']; ?> Not
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
                                <tr><td colspan="5" class="p-8 text-center opacity-50 uppercase font-mono italic">// Henüz kategori tanımlanmamış.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div> </main> </body>
</html>