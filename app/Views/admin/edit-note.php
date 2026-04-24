<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | NOT DÜZENLE</title>
    
    <script>
        (function () {
            const userTheme = localStorage.getItem('theme');
            if (userTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    
    <style>
        :root { 
            --bg-paper: #FEF9E1; 
            --bg-secondary: #E5D0AC; 
            --text-main: #6D2323; 
            --text-accent: #A31D1D; 
            --line-color: #6D2323; 
        }

        /* DARK THEME DEĞİŞKENLERİ */
        [data-theme="dark"] {
            --bg-paper: #120A0A;
            --bg-secondary: #1F1212;
            --text-main: #E5D0AC;
            --text-accent: #FF5C5C;
            --line-color: #3D1F1F;
        }

        body { 
            background-color: var(--bg-paper); 
            color: var(--text-main); 
            font-family: 'Space Grotesk', sans-serif; 
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .font-syne  { font-family: 'Syne', sans-serif; }
        .font-mono  { font-family: 'JetBrains Mono', monospace; }
        
        .grid-bg { 
            background-image: linear-gradient(var(--line-color) 1px, transparent 1px), 
                            linear-gradient(90deg, var(--line-color) 1px, transparent 1px); 
            background-size: 40px 40px; 
            opacity: 0.05; 
            pointer-events: none; 
        }

        /* Form Elemanları İçin Tema Uyumu */
        input, select, textarea {
            background-color: transparent;
            color: var(--text-main);
            border-color: var(--line-color) !important;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--text-accent) !important;
        }

        .brutalist-container {
            background-color: var(--bg-paper);
            border-color: var(--text-main);
            box-shadow: 8px 8px 0px var(--text-main);
        }

        #category-results {
            background-color: var(--bg-paper);
            border-color: var(--text-main);
        }

        .cat-option:hover {
            background-color: var(--text-main);
            color: var(--bg-paper);
        }

        .scanline { 
            width: 100%; height: 100px; z-index: 9999; 
            background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(109, 35, 35, 0.1) 50%, rgba(0,0,0,0) 100%); 
            opacity: 0.1; position: absolute; bottom: 100%; 
            animation: scanline 10s linear infinite; pointer-events: none; 
        }
        @keyframes scanline { 0% { bottom: 100%; } 100% { bottom: -100%; } }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-paper); border-left: 1px solid var(--line-color); }
        ::-webkit-scrollbar-thumb { background: var(--line-color); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">
    <div class="grid-bg fixed inset-0 z-0"></div>
    <div class="scanline fixed"></div>

    <?php include __DIR__ . '/_side_panel.php'; ?>

    <main class="flex-1 flex flex-col relative z-10 overflow-y-auto">
        
        <header class="h-24 border-b-2 border-[var(--text-main)] bg-[var(--bg-paper)]/90 backdrop-blur-sm flex justify-between items-center px-6 md:px-12 flex-shrink-0 sticky top-0 z-50">
            <div>
                <h2 class="font-syne text-xl md:text-3xl font-bold uppercase text-[var(--text-main)]">NOT DÜZENLE</h2>
                <div class="flex items-center gap-2 text-xs font-mono text-[var(--text-accent)] mt-1">
                    > ID: #<?php echo $note['id']; ?> | <?php echo htmlspecialchars($note['slug']); ?>
                </div>
            </div>
            <a href="/admin/addNote" class="bg-[var(--text-main)] text-[var(--bg-paper)] px-4 py-2 font-mono text-xs uppercase font-bold hover:bg-[var(--text-accent)] transition-colors">
                ← İPTAL ET
            </a>
        </header>

        <div class="p-6 md:p-12 max-w-4xl">
            <form action="/admin/updateNote" method="POST" class="space-y-8 brutalist-container border-2 p-8">
                
                <input type="hidden" name="id" value="<?php echo $note['id']; ?>">

                <div>
                    <label class="block font-syne font-bold uppercase text-sm mb-2">// BELGE BAŞLIĞI</label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($note['title']); ?>"
                           class="w-full border-2 px-4 py-3 font-mono outline-none transition-colors">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block font-syne font-bold uppercase text-sm mb-2">// BELGE DİLİ</label>
                        <select name="lang" class="w-full border-2 px-4 py-3 font-mono outline-none transition-colors">
                            <option value="TR" <?php echo ($note['lang'] ?? 'TR') == 'TR' ? 'selected' : ''; ?>>TÜRKÇE (TR)</option>
                            <option value="EN" <?php echo ($note['lang'] ?? 'TR') == 'EN' ? 'selected' : ''; ?>>İNGİLİZCE (EN)</option>
                        </select>
                    </div>

                    <div class="relative">
                        <label class="block font-syne font-bold uppercase text-sm mb-2">// KATEGORİLER</label>
                        <input type="text" id="category-search" placeholder="Kategori ara..." autocomplete="off"
                               class="w-full border-2 px-4 py-3 font-mono outline-none transition-colors">
                        
                        <div id="category-results" class="absolute z-20 w-full border-x-2 border-b-2 hidden max-h-48 overflow-y-auto">
                            <?php foreach($categories as $cat): ?>
                                <div class="cat-option p-3 cursor-pointer font-mono text-sm transition-colors border-b border-[var(--text-main)]/10"
                                     data-id="<?php echo $cat['id']; ?>" data-name="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="selected-categories" class="flex flex-wrap gap-2 mt-3">
                            <?php foreach($categories as $cat): ?>
                                <?php if(in_array($cat['id'], $noteCategoryIds)): ?>
                                    <span class="bg-[var(--text-accent)] text-[var(--bg-paper)] px-3 py-1 text-xs font-mono font-bold uppercase flex items-center gap-2">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                        <span class="cursor-pointer hover:opacity-70" onclick="removeCategory(<?php echo $cat['id']; ?>, this)">✕</span>
                                    </span>
                                    <input type="hidden" name="categories[]" value="<?php echo $cat['id']; ?>" id="input-cat-<?php echo $cat['id']; ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-syne font-bold uppercase text-sm mb-2">// İÇERİK NOTU / ÖZET</label>
                    <textarea name="description" rows="5"
                              class="w-full border-2 px-4 py-3 font-mono outline-none transition-colors"><?php echo htmlspecialchars($note['description']); ?></textarea>
                </div>

                <div class="pt-6 border-t-2 border-dashed border-[var(--text-main)]/30">
                    <button type="submit" class="w-full bg-[var(--text-main)] text-[var(--bg-paper)] font-syne font-bold uppercase py-4 text-xl border-2 border-[var(--text-main)] hover:bg-[var(--text-accent)] hover:border-[var(--text-accent)] transition-colors">
                        GÜNCELLEMEYİ KAYDET [ENTER]
                    </button>
                </div>

            </form>
        </div>
    </main>
    
    <script>
        const searchInput = document.getElementById('category-search');
        const resultsContainer = document.getElementById('category-results');
        const tagsContainer = document.getElementById('selected-categories');
        const options = document.querySelectorAll('.cat-option');
        let selectedIds = [<?php echo implode(',', $noteCategoryIds); ?>]; 

        searchInput.addEventListener('focus', () => resultsContainer.classList.remove('hidden'));
        
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            let hasMatch = false;
            options.forEach(opt => {
                const name = opt.getAttribute('data-name').toLowerCase();
                if(name.includes(term)) {
                    opt.style.display = 'block';
                    hasMatch = true;
                } else {
                    opt.style.display = 'none';
                }
            });
            resultsContainer.classList.toggle('hidden', !hasMatch);
        });

        options.forEach(opt => {
            opt.addEventListener('click', function() {
                const id = parseInt(this.getAttribute('data-id'));
                const name = this.getAttribute('data-name');
                if(!selectedIds.includes(id)) {
                    selectedIds.push(id);
                    addCategoryTag({id, name});
                }
            });
        });

        function addCategoryTag(cat) {
            const tag = document.createElement('span');
            tag.className = 'bg-[var(--text-accent)] text-[var(--bg-paper)] px-3 py-1 text-xs font-mono font-bold uppercase flex items-center gap-2';
            tag.innerHTML = `${cat.name} <span class="cursor-pointer hover:opacity-70" onclick="removeCategory(${cat.id}, this)">✕</span>`;
            
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

        window.removeCategory = function(id, el) {
            selectedIds = selectedIds.filter(i => i !== id);
            el.parentElement.remove();
            const input = document.getElementById(`input-cat-${id}`);
            if(input) input.remove();
        }

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target)) resultsContainer.classList.add('hidden');
        });
    </script>
</body>
</html>