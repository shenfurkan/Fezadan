<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | YAMA NOTU GİRİŞİ</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        :root { --bg-paper: #FEF9E1; --text-main: #6D2323; --text-accent: #A31D1D; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .grid-bg { background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.05; pointer-events: none; }
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid #6D2323; padding: 10px; font-family: 'JetBrains Mono', monospace; color: #6D2323; outline: none; transition: all 0.3s; }
        .brutalist-input:focus { background: rgba(109, 35, 35, 0.05); border-color: #A31D1D; }
        .terminal-area { background: #1a1a1a; color: #00ff00; border: 2px solid #6D2323; font-family: 'JetBrains Mono', monospace; padding: 15px; width: 100%; min-height: 200px; resize: vertical; outline: none; }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>

    <aside class="w-20 md:w-72 bg-[var(--bg-paper)] border-r-2 border-[var(--text-main)] flex flex-col z-20 flex-shrink-0">
        <div class="h-24 flex items-center justify-center md:justify-start md:px-8 border-b-2 border-[var(--text-main)]">
            <h1 class="hidden md:block font-syne text-3xl font-bold tracking-tighter text-[var(--text-main)]">FEZADAN</h1>
            <div class="md:hidden font-syne text-2xl font-bold">FZD</div>
        </div>
        <nav class="flex-1 py-8 flex flex-col gap-2">
            <a href="/admin/dashboard" class="py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3 hover:bg-[#6D2323] hover:text-[#FEF9E1] transition-colors">
                <span>←</span> <span class="hidden md:inline">Geri Dön</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col relative z-10 overflow-hidden">
        <header class="h-24 border-b-2 border-[var(--text-main)] bg-[var(--bg-paper)]/90 flex justify-between items-center px-6 md:px-12">
            <h2 class="font-syne text-xl md:text-3xl font-bold uppercase text-[var(--text-main)]">YAMA SİSTEMİ</h2>
            <div class="text-xs font-mono text-[var(--text-accent)]">MODE: INSERT</div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12">
            
            <form action="/admin/store-patch" method="POST" class="max-w-4xl mx-auto">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div class="flex flex-col justify-end">
                        <label class="block font-syne font-bold uppercase text-xs mb-2 opacity-50">Sistem Versiyonu</label>
                        <div class="brutalist-input opacity-50 cursor-not-allowed bg-[#6D2323]/5">
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
                    <textarea name="content" class="terminal-area" placeholder="> Yapılan değişiklikleri buraya girin...&#10;> - Bug fix yapıldı&#10;> - UI güncellendi"></textarea>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="/admin/dashboard" class="px-8 py-4 border-2 border-[var(--text-main)] font-bold uppercase hover:bg-[var(--bg-secondary)] transition-colors">İptal</a>
                    <button type="submit" class="px-8 py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-colors shadow-[8px_8px_0px_#A31D1D] hover:translate-y-1 hover:shadow-none">
                        SİSTEME İŞLE [ENTER]
                    </button>
                </div>

            </form>

        </div>
    </main>
</body>
</html>