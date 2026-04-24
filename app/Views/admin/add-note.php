<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | YENİ NOT EKLE (PDF)</title>
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
        .brutalist-input { width: 100%; background: transparent; border: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; transition: 0.3s; }
        .brutalist-input:focus { background: rgba(109,35,35,0.05); box-shadow: 4px 4px 0px var(--text-accent); }
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; border-left: 1px dashed rgba(109,35,35,0.2); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(109,35,35,0.5); border-radius: 0px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(109,35,35,1); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>

    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="h-full flex flex-col">

        <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
            <div class="mb-6 p-4 bg-[#6D2323] text-[#FEF9E1] font-mono text-xs uppercase flex-shrink-0 shadow-[4px_4px_0px_#A31D1D]">
                🗑️ NOT VE PDF DOSYASI SİSTEMDEN BAŞARIYLA SİLİNDİ.
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['message'])): ?>
            <?php if($_GET['message'] == 'duplicate_title'): ?>
                <div class="mb-6 p-4 bg-orange-600 text-[#FEF9E1] font-mono text-xs uppercase flex-shrink-0 animate-pulse shadow-[4px_4px_0px_#A31D1D]">
                    ⚠️ BU BAŞLIĞA SAHİP BİR NOT ZATEN MEVCUT. LÜTFEN FARKLI BİR BAŞLIK SEÇİN.
                </div>
            <?php elseif($_GET['message'] == 'limit_exceeded'): ?>
                <div class="mb-6 p-4 bg-red-600 text-[#FEF9E1] font-mono text-xs uppercase flex-shrink-0 shadow-[4px_4px_0px_#A31D1D]">
                    ⚠️ DOSYA BOYUTU LİMİTİ AŞILDI (MAX 20MB).
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-12 flex-1 min-h-0 h-[calc(100vh-180px)]">
            
            <div class="lg:w-2/5 flex flex-col h-full">
                <h3 class="font-syne text-xl font-bold uppercase mb-4 flex items-center gap-2 text-[var(--text-main)] flex-shrink-0">
                    <span class="w-3 h-3 bg-[#6D2323]"></span> YENİ YÜKLEME
                </h3>
                
                <form action="/admin/storeNote" method="POST" enctype="multipart/form-data" class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] p-6 shadow-[8px_8px_0px_#A31D1D] space-y-6 flex-1 overflow-y-auto custom-scrollbar">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block font-syne font-bold uppercase text-xs mb-2">Not Başlığı</label>
                            <input type="text" name="title" required class="brutalist-input" placeholder="Örn: Kuantum Fiziği">
                        </div>
                        <div class="w-24">
                            <label class="block font-syne font-bold uppercase text-xs mb-2">DİL</label>
                            <select name="lang" class="brutalist-input cursor-pointer px-2" style="height: 52px;">
                                <option value="tr">TR</option>
                                <option value="en">EN</option>
                            </select>
                        </div>
                    </div>

                    <div class="relative">
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Kategoriler (Çoklu Seçim)</label>
                        <div id="selected-tags" class="flex flex-wrap gap-2 mb-2"></div>                       
                        <input type="text" id="cat-search" class="brutalist-input" placeholder="Kategori ara ve ekle...">                        
                        <div id="search-results" class="absolute z-50 left-0 right-0 bg-[var(--bg-paper)] border-2 border-t-0 border-[var(--line-color)] max-h-40 overflow-y-auto hidden shadow-[4px_4px_0px_var(--text-main)]"></div>
                    </div>

                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">PDF Dosyası Seç</label>
                        <input type="file" name="pdf_file" accept=".pdf" required class="brutalist-input cursor-pointer" style="padding-top: 10px;">
                        
                        <div id="file-limit-warning" class="hidden mt-2 p-2 bg-red-100 text-red-700 font-mono text-[10px] border-l-4 border-red-700 uppercase">
                            ⚠️ Dosya boyutu çok büyük (Maksimum 20MB). Lütfen daha küçük bir dosya seçin.
                        </div>
                    </div>

                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">İçerik Detayı</label>
                        <textarea name="description" rows="5" class="brutalist-input" placeholder="Kısa bir bilgi girin..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase text-sm hover:bg-black transition-all border-2 border-[#6D2323] hover:border-black mt-4">
                        SİSTEME İŞLE VE YÜKLE
                    </button>
                    <?php 
                        $host = $_SERVER['HTTP_HOST'];
                        $baseUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . (strpos($host, 'notlar.') === 0 ? $host : "notlar." . $host);
                    ?>
                    <a href="<?php echo $baseUrl; ?>" target="_blank" class="block w-full py-4 mt-1 border-2 border-[var(--text-main)] text-[var(--text-main)] font-bold uppercase text-center text-sm hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-all">
                        NOTLARI GÖRÜNTÜLE
                    </a>
                </form>
            </div>

            <div class="lg:w-3/5 flex flex-col h-full min-h-0">
                <div class="flex justify-between items-center mb-4 flex-shrink-0">
                    <h3 class="font-syne text-xl font-bold uppercase flex items-center gap-2 text-[var(--text-main)]">
                        <span class="w-3 h-3 bg-[#6D2323]"></span> MEVCUT HAVUZ
                    </h3>
                    <div class="font-syne text-xs font-bold uppercase text-[var(--text-main)] text-right leading-tight">
                        TOPLAM NOT: <?php echo $stats['total_count'] ?? 0; ?><br>
                        TOPLAM BOYUT: <?php echo number_format(($stats['total_size'] ?? 0) / 1048576, 2); ?> MB
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto pr-4 space-y-4 border-2 border-transparent hover:border-[var(--line-color)]/10 transition-colors p-1 custom-scrollbar">
                    
                    <?php if(!empty($notes)): foreach($notes as $note): ?>
                        <div class="bg-[var(--bg-paper)] border-2 border-[var(--text-main)] p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-[var(--bg-secondary)] transition-colors">
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-[10px] bg-[var(--text-main)] text-[var(--bg-paper)] px-1.5 py-0.5 font-bold uppercase">
                                        <?php echo htmlspecialchars($note['lang'] ?? 'TR'); ?>
                                    </span>
                                    <h4 class="font-syne font-bold text-lg leading-tight line-clamp-1" title="<?php echo htmlspecialchars($note['title']); ?>">
                                        <?php echo htmlspecialchars($note['title']); ?>
                                    </h4>
                                    <span class="font-mono text-[9px] opacity-50">#<?php echo $note['id']; ?></span>
                                </div>

                                <div class="mb-2">
                                    <?php 
                                    $catList = explode(', ', $note['category_names']); 
                                    foreach($catList as $cName): 
                                    ?>
                                    <span class="font-mono text-[9px] bg-[#6D2323]/10 text-[var(--text-accent)] px-1 font-bold border border-[var(--text-accent)]/30 uppercase mr-1">
                                        <?php echo htmlspecialchars($cName); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="font-mono text-[10px] opacity-60 flex gap-3">
                                    <span>Yükleyen: <?php echo htmlspecialchars($note['uploader_name']); ?></span>
                                    <span>-</span>
                                    <span>Tarih: <?php echo date('d.m.Y', strtotime($note['created_at'])); ?></span>
                                    <span>-</span>
                                    <span class="text-[var(--text-accent)] font-bold">İndirme: <?php echo $note['downloads']; ?></span>
                                </div>
                            </div>

                            <div class="flex gap-2 w-full md:w-auto">
                                <a href="/admin/editNote?id=<?php echo $note['id']; ?>" class="flex-1 md:flex-none text-center px-3 py-2 bg-[var(--bg-secondary)] border-2 border-[var(--text-main)] text-[var(--text-main)] text-[10px] font-bold font-mono hover:bg-[var(--text-main)] hover:text-[#FEF9E1] transition-colors">
                                    DÜZENLE
                                </a>
                                
                                <?php 
                                    $host = $_SERVER['HTTP_HOST'];
                                    if (strpos($host, 'notlar.') !== 0) {
                                        $noteUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . "notlar." . $host . "/not/" . $note['slug'];
                                    } else {
                                        $noteUrl = "/not/" . $note['slug'];
                                    }
                                ?>
                                <a href="<?php echo $noteUrl; ?>" target="_blank" class="flex-1 md:flex-none text-center px-3 py-2 border-2 border-[var(--text-main)] text-[10px] font-bold font-mono hover:bg-[#6D2323] hover:text-[#FEF9E1] transition-colors">
                                    GÖRÜNTÜLE
                                </a>
                                <a href="/admin/deleteNote?id=<?php echo $note['id']; ?>" onclick="return confirm('Bu notu ve PDF dosyasını kalıcı olarak silmek istediğinize emin misiniz?');" class="flex-1 md:flex-none text-center px-3 py-2 bg-[var(--text-accent)] border-2 border-[var(--text-accent)] text-white text-[10px] font-bold font-mono hover:bg-black hover:border-black transition-colors">
                                    SİL
                                </a>
                            </div>

                        </div>
                    <?php endforeach; else: ?>
                        <div class="border-2 border-dashed border-[var(--text-main)]/50 p-8 text-center text-[var(--text-main)] opacity-50 font-mono text-sm uppercase">
                            // Havuzda kayıtlı not bulunamadı.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div> </div> </main> </body>

<script>
    const categories = <?php echo json_encode($categories); ?>;
    const searchInput = document.getElementById('cat-search');
    const resultsContainer = document.getElementById('search-results');
    const tagsContainer = document.getElementById('selected-tags');
    const fileInput = document.querySelector('input[name="pdf_file"]');
    const warning = document.getElementById('file-limit-warning');
    const submitBtn = document.querySelector('button[type="submit"]');
    const MAX_SIZE = 20 * 1024 * 1024; // 20MB
    let selectedIds = [];

    searchInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase();
        resultsContainer.innerHTML = '';
        if (val.length < 1) { resultsContainer.classList.add('hidden'); return; }

        const filtered = categories.filter(c => 
            c.name.toLowerCase().includes(val) && !selectedIds.includes(c.id)
        );

        if (filtered.length > 0) {
            filtered.forEach(cat => {
                const div = document.createElement('div');
                div.className = 'p-2 hover:bg-[var(--bg-secondary)] cursor-pointer font-mono text-xs uppercase border-b border-black/10';
                div.textContent = cat.name;
                div.onclick = () => addCategory(cat);
                resultsContainer.appendChild(div);
            });
            resultsContainer.classList.remove('hidden');
        } else {
            resultsContainer.classList.add('hidden');
        }
    });

    function addCategory(cat) {
        selectedIds.push(cat.id);
        const tag = document.createElement('div');
        tag.className = 'bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 text-[10px] font-bold uppercase flex items-center gap-2';
        tag.innerHTML = `${cat.name} <span class="cursor-pointer hover:text-red-400" onclick="removeCategory(${cat.id}, this)">✕</span>`;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'categories[]';
        input.value = cat.id;
        input.id = `input-cat-${cat.id}`;
        tagsContainer.appendChild(tag);
        tagsContainer.appendChild(input);
        searchInput.value = '';
        resultsContainer.classList.add('hidden');
    }

    function removeCategory(id, el) {
        selectedIds = selectedIds.filter(i => i !== id);
        el.parentElement.remove();
        document.getElementById(`input-cat-${id}`).remove();
    }

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target)) resultsContainer.classList.add('hidden');
    });

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            if (this.files[0].size > MAX_SIZE) {
                warning.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            } else {
                warning.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        }
    });
</script>
</html>