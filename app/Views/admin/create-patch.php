<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | YAMA NOTU GİRİŞİ</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        :root { --bg-paper: #FEF9E1; --bg-secondary: #E5D0AC; --text-main: #6D2323; --text-accent: #A31D1D; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .grid-bg { background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.05; pointer-events: none; }
        
        /* Side Panel İçin Gerekli Navigasyon Stilleri */
        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }

        /* Sayfaya Özel İnput Stilleri */
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 10px; font-family: 'JetBrains Mono', monospace; color: var(--text-main); outline: none; transition: all 0.3s; }
        .brutalist-input:focus { background: var(--bg-secondary); border-color: var(--text-accent); }
        .terminal-area { background: #1a1a1a; color: #00ff00; border: 2px solid var(--line-color); font-family: 'JetBrains Mono', monospace; padding: 15px; width: 100%; min-height: 200px; resize: vertical; outline: none; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; border-left: 1px dashed rgba(109,35,35,0.2); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(109,35,35,0.5); border-radius: 0px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(109,35,35,1); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>

    <?php include __DIR__ . '/_side_panel.php'; ?>

        <form action="/admin/store-patch" method="POST" class="max-w-4xl mx-auto pb-12">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div class="flex flex-col justify-end">
                    <label class="block font-syne font-bold uppercase text-xs mb-2 opacity-50">Sistem Versiyonu</label>
                    <div class="brutalist-input opacity-50 cursor-not-allowed bg-[var(--text-main)]/5 border-[var(--text-main)]/20">
                        OTOMATİK (1.x)
                    </div>
                </div>
                
                <div class="col-span-2">
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Güncelleme Başlığı</label>
                    <input type="text" name="title" placeholder="Örn: Yeni Yazar Sistemi Eklendi" class="brutalist-input" required>
                </div>
            </div>

            <div class="mb-8">
                <label class="block font-syne font-bold uppercase text-xs mb-2 flex justify-between">
                    <span>Detaylar / Loglar</span>
                    <span class="opacity-50 font-mono text-[10px]">Markdown Destekler</span>
                </label>
                <textarea name="content" class="terminal-area custom-scrollbar" placeholder="> Yapılan değişiklikleri buraya girin...&#10;> - Bug fix yapıldı&#10;> - UI güncellendi"></textarea>
            </div>

            <div class="flex justify-end gap-4">
                <a href="/admin/dashboard" class="px-8 py-4 border-2 border-[var(--text-main)] font-bold uppercase hover:bg-[var(--bg-secondary)] transition-colors">İptal</a>
                <button type="submit" class="px-8 py-4 bg-[var(--text-main)] text-[var(--bg-paper)] font-bold uppercase hover:bg-black hover:text-white transition-colors shadow-[8px_8px_0px_var(--text-main)] hover:translate-y-1 hover:shadow-none">
                    SİSTEME İŞLE [ENTER]
                </button>
            </div>

        </form>

    </div> </main> </body>
</html>