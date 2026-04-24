<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | EDİTÖR</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        (function () {
            const userTheme = localStorage.getItem('theme');
            const htmlElement = document.documentElement;
            if (userTheme === 'dark') {
                htmlElement.setAttribute('data-theme', 'dark');
            } else {
                htmlElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <style>
        :root {
            --bg-paper:    #FEF9E1;
            --bg-secondary:#E5D0AC;
            --text-main:   #6D2323;
            --text-accent: #A31D1D;
            --line-color:  #6D2323;
        }

        [data-theme="dark"] {
            --bg-paper: #120A0A;
            --bg-secondary: #1F1212;
            --text-main: #E5D0AC;
            --text-accent: #FF5C5C;
            --line-color: #E5D0AC;
        }

        body {
            background-color: var(--bg-paper);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Tema Değiştirme Butonu Tasarımı */
        .theme-switch-wrapper { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .theme-switch { width: 48px; height: 24px; background-color: var(--text-main); border: 2px solid var(--text-main); border-radius: 999px; position: relative; transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1); }
        [data-theme="dark"] .theme-switch { background-color: var(--bg-secondary); border-color: var(--text-main); }
        .theme-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background-color: var(--bg-paper); border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); }
        [data-theme="dark"] .theme-switch::after { transform: translateX(24px); background-color: var(--text-main); }
        .theme-icon { width: 14px; height: 14px; color: var(--text-main); }
        .sun-icon { opacity: 1; color: var(--text-main); }
        .moon-icon { opacity: 0.3; color: var(--text-main); }
        [data-theme="dark"] .sun-icon { opacity: 0.3; }
        [data-theme="dark"] .moon-icon { opacity: 1; }

        .brutalist-input {
            width: 100%;
            background: transparent;
            border-bottom: 2px solid var(--line-color);
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
            color: var(--text-main);
            transition: 0.3s;
        }
        .brutalist-input:focus { background: var(--bg-secondary); opacity: 0.8; }
        
        /* Summernote Karanlık Tema Uyumluluğu */
        .note-editor.note-frame  { border: 2px solid var(--line-color) !important; border-radius: 0; box-shadow: 8px 8px 0px rgba(0,0,0,0.1); }
        .note-toolbar            { background-color: var(--bg-secondary) !important; border-bottom: 2px solid var(--line-color) !important; }
        .note-statusbar          { display: none !important; }
        .note-editable           { background-color: var(--bg-paper) !important; color: var(--text-main) !important; min-height: 400px !important; }

        /* Editör içi figcaption görünümü */
        .note-editable figure { margin: 1rem 0; display: block; }
        .note-editable figure img { display: block; max-width: 100%; height: auto; }
        .note-editable figure figcaption {
            display: block;
            margin-top: 0.4rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.5;
            color: var(--text-accent);
            opacity: 0.75;
            font-style: italic;
            padding: 0.25rem 0.5rem 0.25rem 0.6rem;
            border-left: 2px solid var(--text-accent);
            background: var(--bg-secondary);
            outline: none;
            cursor: text;
            min-width: 60px;
        }
        .note-editable figure figcaption:empty::before {
            content: attr(data-placeholder);
            opacity: 0.4;
            pointer-events: none;
            font-style: italic;
        }
    </style>
</head>

<body class="flex flex-col h-screen overflow-hidden">

    <header class="h-16 border-b-2 border-[var(--text-main)] flex justify-between items-center px-6 md:px-12 bg-[var(--bg-paper)] z-50 flex-shrink-0 transition-colors">
        <div class="flex items-center gap-3">
            <a href="/admin/dashboard" class="font-mono text-[10px] uppercase border border-[var(--text-main)] px-3 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors text-[var(--text-main)]">← PANEL</a>
            <h1 class="font-syne text-lg font-bold uppercase tracking-wider ml-2 hidden md:block text-[var(--text-main)]">MAKALE DÜZENLE</h1>
        </div>
        <div class="flex items-center gap-6">

            <div class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Temayı Değiştir">
                <svg class="theme-icon sun-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <div class="theme-switch"></div>
                <svg class="theme-icon moon-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                    </path>
                </svg>
            </div>

            <div class="flex items-center gap-1">
                <button type="button" id="tabWrite"   onclick="switchTab('write')"
                    class="font-mono text-[10px] uppercase px-4 py-2 border-2 border-[var(--text-main)] bg-[var(--text-main)] text-[var(--bg-paper)] font-bold transition-all">✎ YAZI</button>
                <button type="button" id="tabPreview" onclick="switchTab('preview')"
                    class="font-mono text-[10px] uppercase px-4 py-2 border-2 border-[var(--text-main)] text-[var(--text-main)] font-bold transition-all hover:bg-[var(--text-main)]/10">◉ ÖNİZLEME</button>
            </div>
        </div>
    </header>

    <div id="writePanel" class="flex flex-1 overflow-hidden">
        
        <main class="flex-1 p-6 md:p-12 overflow-y-auto">
            <form id="editForm" action="/admin/update" method="POST" enctype="multipart/form-data" class="max-w-4xl mx-auto space-y-8 pb-20">

                <input type="hidden" name="id"            value="<?php echo $article['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo $article['image_url']; ?>">
                <input type="hidden" name="status" id="statusInput" value="<?php echo htmlspecialchars($article['status'] ?? 'published'); ?>">

                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Makale Başlığı</label>
                    <input type="text" name="title" class="brutalist-input text-2xl font-bold border-t-0 border-l-0 border-r-0"
                        value="<?php echo htmlspecialchars($article['title']); ?>" required>
                </div>

                <div class="mb-6">
                    <label class="block font-syne font-bold uppercase text-xs mb-3 text-[var(--text-main)]">Kategoriler</label>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-0 p-3 border-2 border-[var(--text-main)]/20 bg-[var(--text-main)]/5 overflow-y-auto" style="height:160px;">
                        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                        <label class="flex items-center gap-2 cursor-pointer group min-w-0">
                            <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"
                                style="width:1rem;height:1rem;min-width:1rem;padding:0;border:none;background:transparent;" class="accent-[#A31D1D] cursor-pointer flex-shrink-0" <?php echo in_array($cat['id'], $selectedCategories) ? 'checked' : ''; ?>>
                            <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] truncate <?php echo in_array($cat['id'], $selectedCategories) ? 'font-bold text-[var(--text-accent)]' : ''; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </span>
                        </label>
                        <?php endforeach; else: ?>
                        <p class="col-span-2 text-[10px] opacity-50 uppercase">Kategori bulunamadı.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Kısa Açıklama (Spot)</label>
                        <textarea name="desc" rows="4" class="brutalist-input border-2 border-b-2" required><?php echo htmlspecialchars($article['short_desc']); ?></textarea>
                    </div>
                    <div>
                        <label class="block font-syne font-bold uppercase text-xs mb-2">Yazar Seçimi</label>
                        <select name="author_id" class="brutalist-input border-2 border-b-2">
                            <?php foreach ($authors as $author): ?>
                            <option value="<?php echo $author['id']; ?>" <?php echo ($article['author_id'] == $author['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($author['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Kapak Görseli (Değiştirmek için dosya seçin)</label>
                    <input type="file" id="coverFileInput" name="cover_image" class="brutalist-input border-2 border-b-2 mb-2" accept="image/png,image/jpeg,image/webp">
                    <?php if (!empty($article['image_url'])): ?>
                    <p class="text-[10px] font-mono opacity-60 mb-2">Mevcut Görsel:</p>
                    <img id="currentCoverThumb" src="<?php echo $article['image_url']; ?>" class="h-20 border border-[var(--text-main)]">
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">İçerik</label>
                    <textarea id="summernote" name="content"><?php echo $article['content']; ?></textarea>
                </div>

                <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Kaynakça & Referanslar</label>
                    <p class="text-[10px] opacity-60 font-mono mb-2">Format: "1=Link" veya düz metin. Her satıra yeni bir kaynak.</p>
                    <textarea name="refs" rows="6" class="brutalist-input border-2 border-b-2 font-mono text-sm"><?php echo htmlspecialchars($article['refs'] ?? ''); ?></textarea>
                </div>

                <div class="flex flex-col gap-3">
                    <?php if (($article['status'] ?? 'published') === 'draft'): ?>
                    <button type="button" id="draftBtn"
                        class="w-full py-4 border-2 border-yellow-700 bg-yellow-100 text-yellow-800 font-bold text-lg uppercase hover:bg-yellow-200 transition-all font-syne tracking-wider">
                        TASLAK OLARAK KAYDET
                    </button>
                    <button type="submit" id="publishBtn"
                        class="w-full py-5 bg-[var(--text-main)] text-[var(--bg-paper)] font-bold text-xl uppercase hover:bg-black hover:text-white transition-all shadow-[8px_8px_0px_var(--text-main)]">
                        YAYINLA →
                    </button>
                    <?php else: ?>
                    <button type="button" id="draftBtn"
                        class="w-full py-3 border-2 border-[var(--text-main)] text-[var(--text-main)] font-bold text-sm uppercase hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-all font-syne tracking-wider">
                        TASLAĞA AL
                    </button>
                    <button type="submit" id="publishBtn"
                        class="w-full py-5 bg-[var(--text-main)] text-[var(--bg-paper)] font-bold text-xl uppercase hover:bg-black hover:text-white transition-all shadow-[8px_8px_0px_var(--text-main)]">
                        GÜNCELLEMELERİ KAYDET
                    </button>
                    <?php endif; ?>
                </div>

            </form>
        </main>
    </div>

    <?php $preview_date = date('d F Y'); require_once __DIR__ . '/_preview_panel.php'; ?>

    <script>
        // ===== SUMMERNOTE =====
        $('#summernote').summernote({
            placeholder: 'Yazmaya başla...',
            tabsize: 2, height: 400,
            toolbar: [
                ['style',['style']],['font',['bold','underline','clear']],
                ['color',['color']],['para',['ul','ol','paragraph']],
                ['insert',['link','picture','video']],['view',['fullscreen','codeview','help']]
            ],
            callbacks: {
                onKeydown: function(e) { if(e.keyCode===9){e.preventDefault();$(this).summernote('pasteHTML','&nbsp;&nbsp;&nbsp;&nbsp;');} },
                onImageUpload: function(files) { for(let i=0;i<files.length;i++) uploadImage(files[i]); },
                onPaste: function(e) { var t=((e.originalEvent||e).clipboardData||window.clipboardData).getData('Text'); e.preventDefault(); document.execCommand('insertText',false,t); },
                onInit: function() {
                    // Mevcut içerikteki figure içinde olmayan img'leri figure+figcaption'a dönüştür
                    const editable = $('.note-editable');
                    editable.find('img').each(function() {
                        const $img = $(this);
                        if ($img.closest('figure').length) return; // zaten figure içindeyse atla
                        $img.wrap('<figure></figure>');
                        $img.after('<figcaption contenteditable="true" data-placeholder="Görsel açıklaması (isteğe bağlı)..."></figcaption>');
                    });
                }
            }
        });

        function uploadImage(file) {
            let data=new FormData(); data.append("file",file);
            $.ajax({data,type:"POST",url:"/admin/upload-content-image",cache:false,contentType:false,processData:false,
                success: function(res) {
                    // Güvenlik: Gelen veri login sayfası HTML'i mi diye kontrol et
                    if(res.includes('<html') || res.includes('<!DOCTYPE')) {
                        alert('Oturum süresi dolmuş olabilir. Lütfen sayfayı yenileyip tekrar giriş yapın.');
                        return;
                    }
                    if(res.includes('error') || res.toLowerCase().includes('hata')){ 
                        alert('Hata: '+res); 
                        return; 
                    }
                    
                    const url = res.trim();
                    const figureHtml = `<figure><img src="${url}" style="max-width:100%;height:auto;"><figcaption contenteditable="true" data-placeholder="Görsel açıklaması (isteğe bağlı)..."></figcaption></figure><p><br></p>`;
                    $('#summernote').summernote('pasteHTML', figureHtml);
                },
                error: () => alert("Resim yüklenirken sunucu tarafında bir hata oluştu.")
            });
        }

        document.getElementById('draftBtn').addEventListener('click', function() {
            document.getElementById('statusInput').value = 'draft';
            document.getElementById('editForm').requestSubmit();
        });
        document.getElementById('publishBtn').addEventListener('click', function() {
            document.getElementById('statusInput').value = 'published';
        });

        // Yeni kapak seçilince thumb'ı güncelle
        var _newCoverDataUrl = null;
        document.getElementById('coverFileInput').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) { _newCoverDataUrl = null; return; }
            const r = new FileReader();
            r.onload = e => {
                _newCoverDataUrl = e.target.result;
                const thumb = document.getElementById('currentCoverThumb');
                if (thumb) thumb.src = _newCoverDataUrl;
            };
            r.readAsDataURL(file);
        });

        // ===== updatePreview — edit.php'ye özgü (kapak: mevcut URL veya yeni seçim) =====
        function updatePreview() {
            // Başlık
            const title = document.querySelector('#editForm input[name="title"]').value.trim() || 'Başlık buraya gelecek...';
            document.getElementById('prev-title').textContent = title;

            // Kısa açıklama
            const desc     = document.querySelector('#editForm textarea[name="desc"]').value.trim();
            const prevDesc = document.getElementById('prev-desc');
            prevDesc.style.display = desc ? 'block' : 'none';
            if (desc) prevDesc.textContent = '"' + desc + '"';

            // İçerik
            let content = $('#summernote').summernote('code');
            const prevContent = document.getElementById('prev-content');
            if (content && content !== '<p><br></p>') {
                const seen = [];
                content = content.replace(/\[(\d+)\]/g, (m, num) => {
                    let id = '';
                    if (!seen.includes(num)) { id = ` id="prev-ref-link-${num}"`; seen.push(num); }
                    return `<sup class="reference-sup"><a href="#prev-ref-item-${num}"${id} class="text-[var(--text-accent)] hover:underline" style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">[${num}]</a></sup>`;
                });
                prevContent.innerHTML = content;
            } else {
                prevContent.innerHTML = '<p class="opacity-40 italic">İçerik editörden yansıyacak...</p>';
            }

            // Okuma süresi
            document.getElementById('prev-readtime').textContent =
                Math.max(1, Math.ceil(prevContent.innerText.trim().split(/\s+/).filter(Boolean).length / 200));

            // Kapak — yeni seçim varsa onu, yoksa PHP'den gelen URL
            const coverWrap = document.getElementById('prev-cover-wrap');
            const prevCover = document.getElementById('prev-cover');
            if (_newCoverDataUrl) {
                prevCover.src = _newCoverDataUrl;
                coverWrap.style.display = 'block';
            } else {
                const existingUrl = '<?php echo addslashes($article['image_url'] ?? ''); ?>';
                coverWrap.style.display = existingUrl ? 'block' : 'none';
                if (existingUrl) prevCover.src = existingUrl;
            }

            // Kategoriler
            const checked  = document.querySelectorAll('#editForm input[name="categories[]"]:checked');
            const catNames = Array.from(checked).map(cb => cb.closest('label').querySelector('span').textContent.trim()).filter(Boolean);
            const prevCats = document.getElementById('prev-cats');
            prevCats.textContent   = catNames.length ? catNames.join(', ') : 'KATEGORİ SEÇİLMEDİ';
            prevCats.style.opacity = catNames.length ? '1' : '0.5';

            // Kaynakça
            renderPrevRefs(document.querySelector('#editForm textarea[name="refs"]').value);

            // TOC + scroll
            buildPrevToc();
            _bindPreviewScroll();
        }

        /* ================================================================
           GÜVENLİK KALKANI: Heartbeat & LocalStorage Oto-Kayıt
           ================================================================ */
        
        const articleId = document.querySelector('input[name="id"]').value;

        // 1. Heartbeat: Oturumu canlı tut
        setInterval(() => {
            fetch('/admin/dashboard', { method: 'HEAD' })
                .catch(() => console.log('Ping başarısız.'));
        }, 10 * 60 * 1000);

        // 2. Tarayıcıya Oto-Kayıt
        setInterval(() => {
            const title = document.querySelector('input[name="title"]').value;
            const content = $('#summernote').summernote('code');
            
            localStorage.setItem('fezadan_edit_draft_title_' + articleId, title);
            localStorage.setItem('fezadan_edit_draft_content_' + articleId, content);
        }, 30000);

        // 3. Felaket Kurtarma
        document.addEventListener("DOMContentLoaded", () => {
            const savedTitle = localStorage.getItem('fezadan_edit_draft_title_' + articleId);
            const savedContent = localStorage.getItem('fezadan_edit_draft_content_' + articleId);
            
            // Eğer yedeklenen metin, şu an veritabanından gelen metinden farklıysa sor
            const currentContent = $('#summernote').summernote('code');
            
            if (savedContent && savedContent !== currentContent && savedContent !== '<p><br></p>') {
                if (confirm("Bu makale için tarayıcınızda daha güncel bir yerel yedek bulundu (Bağlantı kopması vs. sonucu kaydedilmemiş olabilir). Geri yüklemek ister misiniz?")) {
                    if (savedTitle) document.querySelector('input[name="title"]').value = savedTitle;
                    if (savedContent) $('#summernote').summernote('code', savedContent);
                    updatePreview();
                } else {
                    localStorage.removeItem('fezadan_edit_draft_title_' + articleId);
                    localStorage.removeItem('fezadan_edit_draft_content_' + articleId);
                }
            }
        });

        // 4. Başarılı kayıttan sonra temizle
        document.getElementById('editForm').addEventListener('submit', function() {
            localStorage.removeItem('fezadan_edit_draft_title_' + articleId);
            localStorage.removeItem('fezadan_edit_draft_content_' + articleId);
        });

        // ===== TEMA SWITCH JS MANTIĞI =====
        const themeToggleBtns = document.querySelectorAll('.theme-switch-wrapper');
        themeToggleBtns.forEach(btn => {
            btn.addEventListener('keydown', (e) => {
                if(e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
            
            btn.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        });
    </script>
</body>
</html>