<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | Makale Duzenle</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/summernote-lite.min.css">
    <script src="/assets/js/jquery.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/summernote-lite.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/mammoth.browser.min.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/mammoth.browser.min.js'); ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin-editor.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin-editor.js'); ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin.js'); ?>" nonce="<?= CSP_NONCE ?>"></script>

    <script nonce="<?= CSP_NONCE ?>">
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
            --text-main: #E1C89E;
            --text-accent: #FF5C5C;
            --line-color: #E1C89E;
        }

        body {
            background-color: var(--bg-paper);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Tema DeÄŸiÅŸtirme Butonu TasarÄ±mÄ± */
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
        
        /* Summernote KaranlÄ±k Tema UyumluluÄŸu */
        .note-editor.note-frame  { border: 2px solid var(--line-color) !important; border-radius: 0; box-shadow: 8px 8px 0px rgba(0,0,0,0.1); }
        .note-toolbar            { background-color: var(--bg-secondary) !important; border-bottom: 2px solid var(--line-color) !important; }
        .note-statusbar          { display: none !important; }
        .note-editable           { background-color: var(--bg-paper) !important; color: var(--text-main) !important; min-height: 400px !important; }

        /* EditÃ¶r iÃ§i figcaption gÃ¶rÃ¼nÃ¼mÃ¼ */
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

        input, select, textarea {
            background: transparent;
            border: 2px solid var(--line-color);
            padding: 1rem;
            width: 100%;
            font-family: 'Space Grotesk', sans-serif;
            outline: none;
            color: var(--text-main);
            transition: all 0.3s;
        }
        input:focus, select:focus, textarea:focus { background: var(--bg-secondary); opacity: 0.8; }
        input::placeholder, textarea::placeholder { color: var(--text-main); opacity: 0.5; }

        .btn-action { background: var(--text-main); color: var(--bg-paper); font-family: 'Syne', sans-serif; font-weight: 800; text-transform: uppercase; border: 2px solid var(--text-main); transition: all 0.2s; }
        .btn-action:hover { background: transparent; color: var(--text-main); box-shadow: 6px 6px 0px var(--text-main); transform: translate(-2px,-2px); }

        #loadingOverlay { backdrop-filter: blur(5px); background-color: rgba(18, 10, 10, 0.9); }
        .spinner { width:50px;height:50px;border:5px solid var(--bg-paper);border-top:5px solid var(--text-accent);border-radius:50%;animation:spin 1s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }

    </style>
</head>

<body class="flex flex-col h-screen overflow-hidden">

    <div id="loadingOverlay" class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center text-[var(--bg-paper)]">
        <div class="spinner mb-4"></div>
        <h2 class="font-syne text-2xl font-bold tracking-widest animate-pulse text-[var(--text-main)]">YUKLENIYOR...</h2>
        <p class="font-mono text-xs mt-2 opacity-80 text-[var(--text-main)]">LÃ¼tfen bekleyiniz...</p>
    </div>

    <header class="h-20 border-b-2 border-[var(--text-main)] flex justify-between items-center px-6 md:px-12 bg-[var(--bg-paper)] z-50 flex-shrink-0 transition-colors">
        <div class="flex items-center gap-3">
            <a href="/yonetim/dashboard" class="font-mono text-[10px] uppercase border border-[var(--text-main)] px-3 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors text-[var(--text-main)]">â† PANEL</a>
            <h1 class="font-syne text-lg font-bold uppercase tracking-wider ml-2 hidden md:block text-[var(--text-main)]">MAKALE DÃœZENLE</h1>
        </div>
        <div class="flex items-center gap-6">

            <div class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="TemayÄ± DeÄŸiÅŸtir">
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
                <button type="button" id="tabWrite"
                    class="font-mono text-[10px] uppercase px-4 py-2 border-2 border-[var(--text-main)] bg-[var(--text-main)] text-[var(--bg-paper)] font-bold transition-all">âœŽ YAZI</button>
                <button type="button" id="tabPreview"
                    class="font-mono text-[10px] uppercase px-4 py-2 border-2 border-[var(--text-main)] text-[var(--text-main)] font-bold transition-all hover:bg-[var(--text-main)]/10">â—‰ Ã–NÄ°ZLEME</button>
            </div>
        </div>
    </header>

    <?php if ($flashMessage = Flash::pull()): ?>
    <div class="mx-6 md:mx-12 mt-4 p-4 border-2 border-[var(--text-accent)] bg-[var(--bg-paper)] text-[var(--text-accent)] font-mono text-xs font-bold">
        <?php echo htmlspecialchars($flashMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <form id="editForm" action="/yonetim/update" method="POST" enctype="multipart/form-data"
          class="flex-1 grid grid-cols-1 lg:grid-cols-12 overflow-hidden">
        <?= Csrf::field() ?>

        <input type="hidden" name="id"            value="<?php echo $article['id']; ?>">
        <input type="hidden" name="current_image" value="<?php echo $article['image_url']; ?>">
        <input type="hidden" name="status" id="statusInput" value="<?php echo htmlspecialchars($article['status'] ?? 'published'); ?>">

        <main class="lg:col-span-9 p-6 md:p-8 border-r-2 border-[var(--text-main)] space-y-6 overflow-y-auto">
            <div>
                <input type="text" name="title"
                    class="text-3xl font-bold font-syne py-6 border-none border-b-2 focus:bg-transparent"
                    placeholder="Buraya BaÅŸlÄ±k Giriniz..." required autocomplete="off"
                    value="<?php echo htmlspecialchars($article['title']); ?>">
                <div id="titleAnalysis" class="font-mono text-xs mt-2 opacity-70">
                    <span id="titleCharCount"><?php echo strlen($article['title'] ?? ''); ?></span> karakter | <span id="titleStatus">Kontrol ediliyor...</span>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-end mb-2">
                    <label class="font-mono text-xs uppercase opacity-60 block">ICERIK</label>
                    <div id="wordCounter" class="font-mono text-xs opacity-70">0 kelime / ~0 dk</div>
                    <div class="flex gap-2">
                        <!-- Åžablon SeÃ§imi -->
                        <select id="templateSelect" class="text-[10px] font-bold bg-[var(--bg-paper)] text-[var(--text-main)] border border-[var(--text-main)] px-2 py-0.5 cursor-pointer">
                            <option value="">-- ÅžABLON EKLE --</option>
                            <option value="interview">RÃ–PORTAJ ÅžABLONU</option>
                            <option value="review">Ä°NCELEME ÅžABLONU</option>
                        </select>
                        <input type="file" id="editWordInput" accept=".docx" class="hidden">
                        <button type="button" id="wordImportTrigger"
                            class="text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-3 py-1 hover:bg-[var(--text-accent)] transition-colors flex items-center gap-2">
                            <span>ðŸ“„ WORD'DEN Ã‡EK</span>
                        </button>
                    </div>
                </div>
                <textarea id="summernote" name="content"><?php echo htmlspecialchars($article['content'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>

                <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                    <div class="flex justify-between items-end mb-2">
                        <label class="font-mono text-xs uppercase font-bold block">KAYNAKCA & REFERANSLAR</label>
                        <span class="text-[10px] opacity-60 font-mono">Format: 1=https://site.com veya [1] Kitap AdÄ±</span>
                    </div>
                    <textarea name="refs" rows="6" class="font-mono text-sm p-3"
                        placeholder="Her satÄ±ra bir kaynak giriniz.&#10;EÄŸer kaynak bir cÃ¼mleye referans ise o cÃ¼mleye linklemek iÃ§in metinde o cÃ¼mleye [1] yazÄ±p buraya 1=kaynak ismi/linki ÅŸeklinde yazÄ±n&#10;Aksi taktirde numara vermeden her satÄ±ra birer kaynak yazÄ±nÄ±z.&#10;1=https://nasa.gov/report&#10;ya da&#10;Einstein, Ä°zafiyet Teorisi, sf.45"><?php echo htmlspecialchars($article['refs'] ?? ''); ?></textarea>
                </div>

                <!-- TTS Container -->
                <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                    <div class="border-2 border-[var(--text-main)] p-4 bg-[var(--bg-secondary)]/10 space-y-3">
                        <label class="block font-syne font-bold uppercase text-xs">SESLI MAKALE (TTS)</label>
                        <div id="ttsContainer" class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <?php if (!empty($article['audio_url'])): ?>
                                <audio id="ttsAudio" src="<?php echo \App\Core\Upload::assetUrl($article['audio_url']); ?>" controls class="flex-1"></audio>
                                <button type="button" id="generateTtsBtn" class="px-4 py-2 bg-[var(--text-accent)] text-white text-xs font-bold uppercase hover:bg-red-700 transition-colors">YENÄ°DEN SES DOSYASI ÃœRET</button>
                            <?php else: ?>
                                <p class="text-xs opacity-60 font-mono flex-1">Bu makale iÃ§in henÃ¼z bir ses dosyasÄ± Ã¼retilmemiÅŸ.</p>
                                <button type="button" id="generateTtsBtn" class="px-4 py-2 bg-[var(--text-main)] text-[var(--bg-paper)] text-xs font-bold uppercase hover:bg-black transition-colors">SES DOSYASI ÃœRET</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Revision Log -->
                <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                    <div class="border-2 border-[var(--text-accent)]/40 p-4 bg-[var(--bg-secondary)]/5 space-y-3">
                        <label class="block font-syne font-bold uppercase text-xs text-[var(--text-accent)]">DUZELTME NOTU</label>
                        <p class="text-[10px] opacity-60 font-mono">Makalede yapÄ±lan maddi hatalarÄ± veya gÃ¼ncellemeleri belirtmek iÃ§in buraya bir not ekleyebilirsiniz. Makalenin en altÄ±nda tarihÃ§esiyle gÃ¶sterilecektir.</p>
                        <textarea name="correction_text" rows="3" class="brutalist-input border-2 border-b-2" placeholder="Ã–rn: YazÄ±m hatasÄ± giderildi, bilgi kutusu gÃ¼ncellendi..."></textarea>

                        <?php if (!empty($corrections)): ?>
                        <div class="mt-4 pt-3 border-t border-[var(--line-color)]/20">
                            <label class="block font-mono text-[10px] uppercase font-bold mb-2">GEÃ‡MÄ°Åž DÃœZELTMELER</label>
                            <ul class="space-y-2 max-h-40 overflow-y-auto font-mono text-[11px]">
                                <?php foreach ($corrections as $corr): ?>
                                <li class="border-b border-[var(--line-color)]/10 pb-1">
                                    <span class="text-[var(--text-accent)]">[<?php echo date('d.m.Y H:i', strtotime($corr['created_at'])); ?>]</span>
                                    <span><?php echo htmlspecialchars($corr['correction_text']); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <aside class="lg:col-span-3 bg-[var(--bg-secondary)]/20 p-6 md:p-8 space-y-6 overflow-y-auto">
            <div class="border-b-2 border-[var(--text-main)] pb-4 mb-4">
                <h3 class="font-syne font-bold uppercase text-lg">YAYIN AYARLARI</h3>
            </div>

            <!-- Yazar SeÃ§imi -->
            <div class="mb-6">
                <label class="block font-syne font-bold uppercase text-xs mb-3 text-[var(--text-main)]">Yazar SeÃ§imi (Ã‡oklu SeÃ§im)</label>
                <div class="flex gap-2 mb-2">
                    <button type="button" class="select-all-btn text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 hover:bg-[var(--text-accent)] transition-colors" data-target="authors">TÃ¼mÃ¼</button>
                    <button type="button" class="clear-all-btn text-[10px] font-bold border border-[var(--text-main)] text-[var(--text-main)] px-2 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors" data-target="authors">SÄ±fÄ±rla</button>
                </div>
                <div class="grid grid-cols-2 gap-x-2 gap-y-0 p-3 border-2 border-[var(--text-main)]/20 bg-[var(--text-main)]/5 overflow-y-auto" style="height:120px;">
                    <?php if (!empty($authors)): foreach ($authors as $author): ?>
                    <label class="flex items-center gap-2 cursor-pointer group min-w-0">
                        <input type="checkbox" name="authors[]" value="<?php echo $author['id']; ?>" style="width:1rem;height:1rem;min-width:1rem;padding:0;border:none;background:transparent;" class="accent-[#A31D1D] cursor-pointer flex-shrink-0" <?php echo (in_array($author['id'], $selectedAuthors)) ? 'checked' : ''; ?>>
                        <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] truncate <?php echo in_array($author['id'], $selectedAuthors) ? 'font-bold text-[var(--text-accent)]' : ''; ?>"><?php echo htmlspecialchars($author['name']); ?></span>
                    </label>
                    <?php endforeach; endif; ?>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Ä°lk seÃ§ilen yazar birincil (ana) yazar olacaktÄ±r. En az bir yazar seÃ§ilmelidir.</small>
            </div>

            <!-- Kategoriler -->
            <div class="mb-6">
                <label class="block font-syne font-bold uppercase text-xs mb-3 text-[var(--text-main)]">Kategoriler</label>
                <div class="flex gap-2 mb-2">
                    <button type="button" class="select-all-btn text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 hover:bg-[var(--text-accent)] transition-colors" data-target="categories">TÃ¼mÃ¼</button>
                    <button type="button" class="clear-all-btn text-[10px] font-bold border border-[var(--text-main)] text-[var(--text-main)] px-2 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors" data-target="categories">SÄ±fÄ±rla</button>
                </div>
                <div class="grid grid-cols-2 gap-x-2 gap-y-0 p-3 border-2 border-[var(--text-main)]/20 bg-[var(--text-main)]/5 overflow-y-auto" style="height:160px;">
                    <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                    <label class="flex items-center gap-2 cursor-pointer group min-w-0">
                        <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" style="width:1rem;height:1rem;min-width:1rem;padding:0;border:none;background:transparent;" class="accent-[#A31D1D] cursor-pointer flex-shrink-0" <?php echo in_array($cat['id'], $selectedCategories) ? 'checked' : ''; ?>>
                        <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] truncate <?php echo in_array($cat['id'], $selectedCategories) ? 'font-bold text-[var(--text-accent)]' : ''; ?>"><?php echo htmlspecialchars($cat['name']); ?></span>
                    </label>
                    <?php endforeach; else: ?>
                    <p class="col-span-2 text-[10px] opacity-50 uppercase">HenÃ¼z kategori tanÄ±mlanmamÄ±ÅŸ.</p>
                    <?php endif; ?>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Birden fazla seÃ§im yapabilirsiniz.</small>
            </div>

            <!-- Kapak GÃ¶rseli (aspect-ratio, drag-and-drop identical to create.php) -->
            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block text-center">KAPAK GÃ–RSELÄ° (16:9)</label>
                <div class="aspect-video w-full border-2 border-dashed border-[var(--text-main)] bg-[var(--bg-paper)] relative group overflow-hidden cursor-pointer">
                    <input type="file" id="coverUpload" name="cover_image" accept="image/png,image/jpeg,image/webp"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-50">
                    <div id="placeholderState" class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 transition-transform group-hover:scale-105 z-10 <?php echo !empty($article['image_url']) ? 'hidden' : ''; ?>">
                        <div class="text-3xl mb-2">ðŸ“·</div>
                        <p class="text-[10px] font-mono opacity-70">TÄ±kla veya SÃ¼rÃ¼kle</p>
                        <div class="text-[9px] text-[var(--text-accent)] font-bold mt-2">MAX: 5MB</div>
                    </div>
                    <div id="previewState" class="absolute inset-0 z-20 bg-[var(--bg-paper)] <?php echo empty($article['image_url']) ? 'hidden' : ''; ?>">
                        <img id="imgPreview" class="w-full h-full object-cover" src="<?php echo !empty($article['image_url']) ? htmlspecialchars($article['image_url']) : ''; ?>">
                        <div class="absolute bottom-0 left-0 w-full bg-[var(--text-main)]/90 text-[var(--bg-paper)] text-[10px] text-center py-1 opacity-0 group-hover:opacity-100 transition-opacity">GÃ–RSELÄ° DEÄžÄ°ÅžTÄ°RMEK Ä°Ã‡Ä°N TIKLA</div>
                    </div>
                </div>
                <p id="fileName" class="text-[10px] font-mono mt-2 text-center opacity-70 truncate min-h-[15px]"></p>
                <div id="uploadError" class="hidden text-[10px] text-red-600 font-bold bg-red-100 p-2 border border-red-600 mt-2"></div>
            </div>

            <!-- Slug -->
            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">SLUG (URL)</label>
                <div class="flex gap-2">
                    <input type="text" name="manual_slug" id="manualSlug" placeholder="title'dan otomatik" class="text-sm p-3 flex-1" value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>">
                    <button type="button" id="generateSlugBtn" class="text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-3 py-1 hover:bg-[var(--text-accent)] transition-colors whitespace-nowrap">BaÅŸlÄ±ktan Ãœret</button>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">BoÅŸ bÄ±rakÄ±lÄ±rsa title'dan otomatik Ã¼retilir.</small>
            </div>

            <!-- YayÄ±n Tarihi -->
            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">YAYIN TARÄ°HÄ° (ZAMANLAMA)</label>
                <input type="datetime-local" name="publish_at" id="publishAt" class="text-sm p-3" value="<?php echo !empty($article['publish_at']) ? date('Y-m-d\TH:i', strtotime($article['publish_at'])) : ''; ?>">
                <small class="text-[10px] opacity-50 mt-2 block font-mono">BoÅŸ bÄ±rakÄ±lÄ±rsa hemen yayÄ±nlanÄ±r. Tarih seÃ§ilirse zamanlÄ± yayÄ±n yapÄ±lÄ±r.</small>
            </div>

            <!-- Dil SeÃ§imi -->
            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">DÄ°L SEÃ‡Ä°MÄ°</label>
                <select name="lang" id="langSelect" required class="p-3">
                    <option value="TR" <?php echo ($article['lang'] === 'TR') ? 'selected' : ''; ?>>TR (TÃ¼rkÃ§e)</option>
                    <option value="EN" <?php echo ($article['lang'] === 'EN') ? 'selected' : ''; ?>>EN (Ä°ngilizce)</option>
                </select>
            </div>

            <!-- Spot -->
            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">KISA Ã–ZET (SPOT)</label>
                <textarea name="desc" rows="5" placeholder="Listeleme aÃ§Ä±klamasÄ±..." class="text-sm p-3" required><?php echo htmlspecialchars($article['short_desc']); ?></textarea>
            </div>

            <!-- SEO Metadata -->
            <div class="border-t-2 border-[var(--text-main)] pt-4 mt-4">
                <h3 class="font-syne font-bold uppercase text-xs mb-3">SEO METADATA</h3>

                <div class="space-y-4">
                    <div>
                        <label class="font-mono text-xs uppercase font-bold mb-1 block">SEO BAÅžLIÄžI (SEO TITLE)</label>
                        <input type="text" name="seo_title" id="seoTitle" placeholder="SEO baÅŸlÄ±ÄŸÄ±..." class="text-sm p-3" value="<?php echo htmlspecialchars($article['seo_title'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="font-mono text-xs uppercase font-bold mb-1 block">SEO AÃ‡IKLAMASI (SEO DESC)</label>
                        <textarea name="seo_description" id="seoDescription" rows="4" placeholder="SEO aÃ§Ä±klamasÄ±..." class="text-sm p-3"><?php echo htmlspecialchars($article['seo_description'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="font-mono text-xs uppercase font-bold mb-1 block">META ANAHTAR KELÄ°MELER</label>
                        <textarea name="meta_keywords" id="metaKeywords" rows="3" placeholder="virgÃ¼l, ile, ayÄ±rÄ±n..." class="text-sm p-3"><?php echo htmlspecialchars($article['meta_keywords'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- OG Sosyal Medya GÃ¶rseli ve Ãœretme Butonu -->
                    <div class="pt-2 border-t border-[var(--line-color)]/20">
                        <label class="font-mono text-[10px] uppercase font-bold mb-2 block">OG Sosyal Medya GÃ¶rseli</label>
                        <?php if (!empty($article['og_image'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo \App\Core\Upload::assetUrl($article['og_image']); ?>" class="h-20 border border-[var(--text-main)]" alt="OG GÃ¶rseli">
                        </div>
                        <?php endif; ?>
                        <button type="button" id="generateOgBtn" class="w-full py-2 bg-[var(--text-main)] text-[var(--bg-paper)] text-xs font-bold uppercase hover:bg-black transition-colors font-syne">OG GÃ¶rseli Ãœret</button>
                    </div>
                </div>
            </div>

            <!-- Google Ã–nizlemesi -->
            <div class="border-t-2 border-[var(--text-main)] pt-4 mt-4">
                <h3 class="font-syne font-bold uppercase text-xs mb-3">GOOGLE ONIZLEMESI</h3>
                <div id="seoSnippet" class="bg-white p-4 border border-gray-300 rounded">
                    <div id="snippetTitle" class="text-blue-700 text-lg font-normal hover:underline cursor-pointer truncate" style="font-family:Arial,sans-serif;"><?php echo htmlspecialchars($article['seo_title'] ?: $article['title']); ?></div>
                    <div id="snippetUrl" class="text-green-700 text-sm truncate" style="font-family:Arial,sans-serif;">fezadan.org/tr/yazar/<?php echo htmlspecialchars($article['slug'] ?? ''); ?></div>
                    <div id="snippetDesc" class="text-gray-600 text-sm mt-1 line-clamp-2" style="font-family:Arial,sans-serif;"><?php echo htmlspecialchars($article['seo_description'] ?: $article['short_desc'] ?: 'AÃ§Ä±klama buraya gelecek...'); ?></div>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Google arama sonuÃ§larÄ±nda nasÄ±l gÃ¶rÃ¼neceÄŸi</small>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-2 pt-4 border-t-2 border-[var(--text-main)]">
                <?php if (($article['status'] ?? 'published') === 'draft'): ?>
                <button type="button" id="draftBtn"
                    class="w-full py-3 text-sm font-bold uppercase border-2 border-yellow-700 bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-all font-syne tracking-wider">
                    TASLAK OLARAK KAYDET
                </button>
                <button type="submit" id="publishBtn" class="btn-action w-full py-4 text-lg shadow-[4px_4px_0px_var(--text-main)]">
                    YAYINLA â†’
                </button>
                <?php else: ?>
                <button type="button" id="draftBtn"
                    class="w-full py-3 text-sm font-bold uppercase border-2 border-[var(--text-main)] text-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-all font-syne tracking-wider">
                    TASLAÄžA AL
                </button>
                <button type="submit" id="publishBtn" class="btn-action w-full py-4 text-lg shadow-[4px_4px_0px_var(--text-main)]">
                    GÃœNCELLEMELERÄ° KAYDET â†’
                </button>
                <?php endif; ?>
            </div>

            <!-- Delete Form -->
            <div class="pt-4 mt-4 border-t-2 border-[var(--text-main)]">
                <button type="submit" form="deleteArticleForm" class="w-full py-2 border-2 border-red-600 text-red-600 font-bold text-xs uppercase hover:bg-red-600 hover:text-white transition-all font-syne tracking-wider">
                    MAKALEYÄ° SÄ°L
                </button>
            </div>
        </aside>
    </form>

    <form id="deleteArticleForm" method="POST" action="/yonetim/delete" data-confirm="Bu makaleyi silmek istediÄŸinize emin misiniz? Bu iÅŸlem geri alÄ±namaz." class="hidden">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?php echo (int)$article['id']; ?>">
    </form>

    <?php $preview_date = date('d F Y'); require_once __DIR__ . '/_preview_panel.php'; ?>

    <script nonce="<?= CSP_NONCE ?>">
        if (!window.FezadanAdminEditor) {
            console.error('FEZADAN admin editor could not be loaded. Inline legacy editor fallback is disabled.');
            document.body.dataset.editorLoadFailed = '1';
        }


        window.FezadanAdminEditor && window.FezadanAdminEditor.init({
            formSelector: '#editForm',
            editorSelector: '#summernote',
            wordInputSelector: '#editWordInput',
            coverInputSelector: '#coverUpload',
            coverPreviewSelector: '#imgPreview',
            coverPlaceholderSelector: '#placeholderState',
            coverPreviewStateSelector: '#previewState',
            coverFileNameSelector: '#fileName',
            existingCoverUrl: '<?php echo addslashes($article['image_url'] ?? ''); ?>',
            statusInputSelector: '#statusInput',
            draftButtonSelector: '#draftBtn',
            publishButtonSelector: '#publishBtn',
            draftKey: 'fezadan_edit_draft_<?php echo (int)$article['id']; ?>',
            csrfToken: '<?= Csrf::token() ?>',
            uploadUrl: '/yonetim/upload-content-image',
            templateSelectSelector: '#templateSelect',
            height: 400,
            placeholder: 'Yazmaya baÅŸla...'
        });
    </script>
</body>
</html>
