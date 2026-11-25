<?php
$postMaxBytes = static function (): int {
    $value = trim((string)ini_get('post_max_size'));
    if ($value === '') return 0;
    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    switch ($unit) {
        case 'g': return (int)($number * 1073741824);
        case 'm': return (int)($number * 1048576);
        case 'k': return (int)($number * 1024);
        default: return (int)$number;
    }
};
$categories = is_array($categories ?? null) ? $categories : [];
$authors = is_array($authors ?? null) ? $authors : [];
$articlesList = is_array($articlesList ?? null) ? $articlesList : [];
$csrfToken = Csrf::token();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | Yeni Makale</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin-preview.css?v=<?= filemtime(ROOT . '/public_html/assets/css/admin-preview.css') ?>">
    <link rel="preload" href="/assets/fonts/space-grotesk-v22-latin-ext-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/summernote-lite.min.css">
    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            var theme = 'light';
            try { theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light'; } catch (_) { /* Storage is optional. */ }
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <script src="/assets/js/jquery.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/summernote-lite.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/mammoth.browser.min.js?v=<?= filemtime(ROOT . '/public_html/assets/js/mammoth.browser.min.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <link rel="stylesheet" href="/assets/css/article-math.css?v=<?= filemtime(ROOT . '/public_html/assets/css/article-math.css') ?>">
    <script src="/assets/js/article-math.js?v=<?= filemtime(ROOT . '/public_html/assets/js/article-math.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin-editor.js?v=<?= filemtime(ROOT . '/public_html/assets/js/admin-editor.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin.js?v=<?= filemtime(ROOT . '/public_html/assets/js/admin.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <link rel="stylesheet" href="/assets/css/admin-editor.css?v=<?= filemtime(ROOT . '/public_html/assets/css/admin-editor.css') ?>">
</head>
<body class="editor-workspace min-h-screen">
    <div id="loadingOverlay" class="hidden fixed inset-0 z-[9999] items-center justify-center text-[var(--bg-paper)]">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <div class="font-syne font-black uppercase tracking-widest">İşleniyor</div>
            <div class="font-mono text-xs mt-2 opacity-80">Lütfen sayfayı kapatmayın.</div>
        </div>
    </div>

    <form id="uploadForm" action="/yonetim/store" method="POST" enctype="multipart/form-data" data-post-max-bytes="<?= $postMaxBytes() ?>" class="editor-form create-form">
        <?= Csrf::field() ?>
        <?php require __DIR__ . '/_editor_header.php'; ?>

    <?php if ($flashMessage = Flash::pull()): ?>
        <div class="editor-flash" role="status">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </div>
    <?php endif; ?>

        <main class="editor-main create-main">
            <section class="panel title-card p-4 md:p-6 space-y-4">
                <label for="articleTitle">Başlık</label>
                <input type="text" id="articleTitle" name="title" placeholder="Makale başlığı" required autocomplete="off">
            </section>

            <section class="panel p-4 md:p-6 space-y-4">
                <div class="editor-head">
                    <label for="summernote">Metin</label>
                    <div id="wordCounter">0 kelime</div>
                </div>
                <div class="editor-import-tools">
                    <input type="file" id="wordInput" accept=".docx" hidden>
                    <button type="button" id="wordImportTrigger" class="btn-secondary">Word aktar</button>
                    <?php require __DIR__ . '/_scientific_tools.php'; ?>
                </div>
                <textarea id="summernote" name="content"></textarea>
            </section>

            <details class="editor-disclosure editor-references">
                <summary>Kaynakça</summary>
                <label for="articleRefs" class="editor-help">Her satıra bir kaynak yazın. Metindeki [1] için 1=https://... kullanabilirsiniz.</label>
                <textarea id="articleRefs" name="refs" rows="4" placeholder="1=https://... veya [1] Kaynak adı"></textarea>
            </details>
        </main>

        <aside id="editorSettings" class="editor-settings create-meta-grid" aria-label="Yayın bilgileri" tabindex="-1">
            <section class="panel meta-publish p-4 space-y-4">
                <h2 class="panel-title flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M440-280h80v-240h-80v240Zm40-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                    Yayın Bilgileri
                </h2>
                <div>
                    <label for="langSelect" class="block mb-2">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M400-240v-80h160v80H400ZM240-400v-80h480v80H240ZM120-560v-80h720v80H120Z"/></svg>
                        Dil
                    </label>
                    <select id="langSelect" name="lang">
                        <option value="TR">Türkçe</option>
                        <option value="EN">İngilizce</option>
                    </select>
                </div>
                <details class="editor-disclosure">
                    <summary>Adres ve zamanlama</summary>
                    <div class="editor-disclosure-body space-y-4">
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M440-183v-274L200-596v274l240 139Zm80 0 240-139v-274L520-457v274Zm-40-343 237-137-237-137-237 137 237 137ZM160-252q-19-11-29.5-29T120-321v-318q0-22 10.5-40t29.5-29l280-161q19-11 40-11t40 11l280 161q19 11 29.5 29t10.5 40v318q0 22-10.5 40T800-252L520-91q-19 11-40 11t-40-11L160-252Zm320-228Z"/></svg>
                        Yazının adresi
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="manual_slug" id="manualSlug" aria-label="Yazının adresi" placeholder="Başlıktan otomatik oluşturulur">
                        <button type="button" id="generateSlugBtn" class="btn-secondary px-3 text-[10px] whitespace-nowrap">Üret</button>
                    </div>
                </div>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-160 0q-17 0-28.5-11.5T280-440q0-17 11.5-28.5T320-480q17 0 28.5 11.5T360-440q0 17-11.5 28.5T320-400Zm320 0q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-300q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-160 0q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-300q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm320 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-300q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z"/></svg>
                        Yayın zamanı
                    </label>
                    <input type="datetime-local" id="publishAt" name="publish_at" aria-label="Yayın zamanı">
                </div>
                    </div>
                </details>
                <div>
                    <label for="articleSummary" class="block mb-2">Kısa özet</label>
                    <textarea id="articleSummary" name="desc" rows="3" placeholder="Yazının listeleme sayfasında görünen özeti"></textarea>
                </div>
            </section>

            <section class="panel meta-authors p-4 space-y-4">
                <h2 class="panel-title flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm366-160q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T509-792q14-5 28-7.5t29-2.5q66 0 113 47t47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0 320Zm0-400Z"/></svg>
                    Yazarlar
                </h2>
                <div class="choice-list">
                    <?php foreach ($authors as $author): ?>
                        <label class="choice-item">
                            <input type="checkbox" name="authors[]" value="<?= (int)$author['id'] ?>" data-author-slug="<?= htmlspecialchars((string)($author['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="text-sm font-bold"><?= htmlspecialchars((string)$author['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($authors)): ?>
                        <p class="font-mono text-xs text-[var(--text-accent)]">Önce yazar ekleyin.</p>
                    <?php endif; ?>
                </div>
            </section>

            <details class="panel meta-categories editor-disclosure">
                <summary class="panel-title">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640H447l-80-80H160v480l96-320h684L837-217q-8 26-29.5 41.5T760-160H160Zm84-80h516l72-240H316l-72 240Zm0 0 72-240-72 240Zm-84-400v-80 80Z"/></svg>
                    Kategoriler
                </summary>
                <div class="choice-list">
                    <?php foreach ($categories as $category): ?>
                        <label class="choice-item">
                            <input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>">
                            <span class="text-sm"><?= htmlspecialchars((string)$category['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </details>

            <details class="panel meta-cover editor-disclosure">
                <summary class="panel-title">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M480-260q75 0 127.5-52.5T660-440q0-75-52.5-127.5T480-620q-75 0-127.5 52.5T300-440q0 75 52.5 127.5T480-260Zm0-80q-42 0-71-29t-29-71q0-42 29-71t71-29q42 0 71 29t29 71q0 42-29 71t-71 29ZM160-120q-33 0-56.5-23.5T80-200v-480q0-33 23.5-56.5T160-760h126l74-80h240l74 80h126q33 0 56.5 23.5T880-680v480q0 33-23.5 56.5T800-120H160Zm0-80h640v-480H638l-73-80H395l-73 80H160v480Zm320-240Z"/></svg>
                    Kapak
                </summary>
                <input type="file" name="cover_image" id="coverUpload" accept="image/*" class="p-3 font-mono text-xs">
                <div id="placeholderState" class="border-2 border-dashed border-[var(--line-color)] p-6 text-center font-mono text-xs opacity-70">Kapak seçilmedi</div>
                <div id="previewState" class="hidden space-y-2">
                    <img id="imgPreview" src="" alt="Kapak önizleme" class="w-full aspect-video object-cover border-2 border-[var(--line-color)]">
                    <div id="fileName" class="font-mono text-[10px] opacity-70"></div>
                </div>
            </details>

            <details class="panel meta-seo editor-disclosure">
                <summary class="panel-title">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg>
                    SEO
                </summary>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-1">Sayfa Başlığı</label>
                    <p class="font-mono text-[9px] opacity-50 mb-2">Google arama sonucunda görünen başlık. Boş bırakırsan makale başlığı kullanılır.</p>
                    <input type="text" name="seo_title" id="seoTitleInput" maxlength="70" aria-label="SEO başlığı" placeholder="Boş bırakırsanız makale başlığı kullanılır">
                    <div class="flex justify-between mt-1">
                        <span class="font-mono text-[9px] opacity-40">Tavsiye: 50-60 karakter</span>
                        <span id="seoTitleCount" class="font-mono text-[9px] opacity-40">0 / 70</span>
                    </div>
                </div>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-1">Meta Açıklama</label>
                    <p class="font-mono text-[9px] opacity-50 mb-2">Arama sonucunda başlığın altındaki kısa yazı. Boş bırakırsan kısa açıklama veya içerikten alınır.</p>
                    <textarea name="seo_description" id="seoDescInput" rows="3" maxlength="160" aria-label="SEO açıklaması" placeholder="Arama sonuçlarında görünecek kısa açıklama"></textarea>
                    <div class="flex justify-between mt-1">
                        <span class="font-mono text-[9px] opacity-40">Tavsiye: 120-155 karakter</span>
                        <span id="seoDescCount" class="font-mono text-[9px] opacity-40">0 / 160</span>
                    </div>
                </div>
                <?php require __DIR__ . '/_seo_preview.php'; ?>
            </details>
            <?php require __DIR__ . '/_editor_checks.php'; ?>

            <input type="hidden" name="status" id="statusInput" value="published">
        </aside>
    </form>

    <?php require __DIR__ . '/_preview_panel.php'; ?>

    <script nonce="<?= CSP_NONCE ?>">
        window.FezadanAdminEditor.init({
            formSelector: '#uploadForm',
            editorSelector: '#summernote',
            wordInputSelector: '#wordInput',
            coverInputSelector: '#coverUpload',
            coverPreviewSelector: '#imgPreview',
            coverPlaceholderSelector: '#placeholderState',
            coverPreviewStateSelector: '#previewState',
            coverFileNameSelector: '#fileName',
            statusInputSelector: '#statusInput',
            draftButtonSelector: '#draftBtn',
            publishButtonSelector: '#submitBtn',
            loadingSelector: '#loadingOverlay',
            draftKey: 'fezadan_create_v2_draft',
            csrfToken: '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>',
            uploadUrl: '/yonetim/upload-content-image',
            deferWordImages: true,
            height: 520,
            placeholder: 'Makale metnini buraya yazın.'
        });
    </script>
    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            function bindCounter(inputId, countId, warn, max) {
                var el = document.getElementById(inputId);
                var ct = document.getElementById(countId);
                if (!el || !ct) return;
                function update() {
                    var len = el.value.length;
                    ct.textContent = len + '\u202f/\u202f' + max;
                    ct.style.color = len >= warn && len <= max
                        ? 'var(--text-accent)'
                        : len > max ? '#b91c1c' : '';
                }
                el.addEventListener('input', update);
                update();
            }
            bindCounter('seoTitleInput', 'seoTitleCount', 50, 70);
            bindCounter('seoDescInput',  'seoDescCount',  120, 160);
        })();
    </script>
</body>
</html>
