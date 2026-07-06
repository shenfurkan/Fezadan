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
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/summernote-lite.min.css">
    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') === 'dark' ? 'dark' : 'light');
        })();
    </script>
    <script src="/assets/js/jquery.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/summernote-lite.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/mammoth.browser.min.js?v=<?= filemtime(ROOT . '/public_html/assets/js/mammoth.browser.min.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin-editor.js?v=<?= filemtime(ROOT . '/public_html/assets/js/admin-editor.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin.js?v=<?= filemtime(ROOT . '/public_html/assets/js/admin.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <style>
        :root{--bg-paper:#FEF9E1;--bg-secondary:#E5D0AC;--text-main:#6D2323;--text-accent:#A31D1D;--line-color:#6D2323;--panel-bg:rgba(255,250,229,.78);--soft-line:rgba(109,35,35,.24);}
        [data-theme="dark"]{--bg-paper:#120A0A;--bg-secondary:#1F1212;--text-main:#E1C89E;--text-accent:#FF5C5C;--line-color:#E1C89E;--panel-bg:rgba(31,18,18,.78);--soft-line:rgba(225,200,158,.28);}
        *{box-sizing:border-box;}
        body{background:var(--bg-paper);color:var(--text-main);font-family:'Space Grotesk',sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden;}
        input,select,textarea{background:rgba(254,249,225,.35);border:1px solid var(--soft-line);color:var(--text-main);outline:none;width:100%;transition:border-color .18s,background .18s,box-shadow .18s;}
        [data-theme="dark"] input,[data-theme="dark"] select,[data-theme="dark"] textarea{background:rgba(18,10,10,.22);}
        input[type="checkbox"],input[type="radio"]{width:auto;accent-color:var(--text-main);}
        input:focus,select:focus,textarea:focus{background:var(--bg-paper);border-color:var(--text-main);box-shadow:0 0 0 3px rgba(109,35,35,.09);}
        .create-header{position:sticky;top:0;z-index:50;background:rgba(254,249,225,.95);border-bottom:1px solid var(--soft-line);padding:1rem clamp(1rem,3vw,2rem);backdrop-filter:blur(12px);}
        [data-theme="dark"] .create-header{background:rgba(18,10,10,.95);}
        .create-header-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;max-width:1500px;margin:0 auto;}
        .create-title-row{display:flex;align-items:center;gap:1rem;min-width:0;}
        .create-heading{min-width:0;}
        .create-heading p{margin:.15rem 0 0;font-family:'JetBrains Mono',monospace;font-size:.68rem;opacity:.62;}
        .create-actions{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;justify-content:flex-end;}
        .create-form{width:min(100%,1500px);margin:0 auto;padding:1.4rem clamp(1rem,3vw,2rem) 2.5rem;display:grid;grid-template-columns:minmax(0,1fr) minmax(19rem,22rem);gap:1.35rem;align-items:start;}
        .create-main{display:flex;flex-direction:column;gap:1.15rem;min-width:0;}
        .create-meta-grid{display:flex;flex-direction:column;gap:1rem;position:sticky;top:5.75rem;max-height:calc(100vh - 6.75rem);overflow-y:auto;overflow-x:hidden;padding:.1rem .35rem .5rem .1rem;min-width:0;}
        .panel{min-width:0;border:1px solid var(--soft-line);background:var(--panel-bg);box-shadow:none;}
        .panel-title{font-family:'Syne',sans-serif;font-weight:800;text-transform:uppercase;letter-spacing:.01em;margin:0 0 .85rem;font-size:.95rem;}
        .meta-actions{order:-1;display:flex;flex-direction:column;gap:.75rem;border:2px solid var(--text-main);background:rgba(109,35,35,.06);padding:1rem;}
        .meta-actions .quality-panel{width:100%;margin:0;padding:.85rem;border:1px solid var(--soft-line);background:var(--panel-bg);}
        .meta-actions .quality-panel h4{font-size:.92rem;margin:0;}
        .meta-actions .quality-panel>div:first-child{align-items:flex-start;flex-wrap:wrap;}
        .meta-actions .quality-state{font-size:.58rem;padding:.32rem .45rem;white-space:nowrap;}
        .meta-actions .quality-list{gap:.38rem;margin-top:.7rem;}
        .meta-actions .quality-item{font-size:.68rem;line-height:1.28;}
        .choice-list{display:grid;grid-template-columns:1fr;gap:.45rem;max-height:14rem;overflow:auto;padding-right:.15rem;}
        .choice-item{display:flex;align-items:center;justify-content:flex-start;gap:.6rem;border:1px solid var(--soft-line);padding:.58rem .68rem;cursor:pointer;min-height:2.45rem;background:rgba(254,249,225,.22);}
        .choice-item:hover{border-color:var(--text-main);background:rgba(109,35,35,.07);}
        .choice-item span{line-height:1.2;overflow-wrap:anywhere;}
        .editor-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.45rem;}
        .editor-tools{display:flex;align-items:center;justify-content:flex-end;gap:.55rem;flex-wrap:wrap;max-width:100%;}
        .editor-tools select{min-width:12rem;}
        .btn-main{background:var(--text-main);border:2px solid var(--text-main);color:var(--bg-paper);font-family:'Syne',sans-serif;font-weight:800;text-transform:uppercase;transition:background .18s,color .18s,transform .18s;}
        .btn-main:hover{background:var(--text-accent);color:var(--bg-paper);transform:translateY(-1px);}
        .btn-secondary{border:1px solid var(--text-main);color:var(--text-main);font-family:'Syne',sans-serif;font-weight:800;text-transform:uppercase;background:transparent;transition:background .18s,color .18s;}
        .btn-secondary:hover{background:var(--text-main);color:var(--bg-paper);}
        .title-card input{border:0;border-bottom:2px solid var(--text-main);background:transparent;padding:1rem 0 .85rem;}
        .note-editor.note-frame{border:1px solid var(--soft-line)!important;border-radius:0;box-shadow:none;overflow:hidden;}
        .note-toolbar{background:rgba(229,208,172,.5)!important;border-bottom:1px solid var(--soft-line)!important;padding:.55rem!important;}
        [data-theme="dark"] .note-toolbar{background:rgba(31,18,18,.9)!important;}
        .note-editable{background:rgba(254,249,225,.46)!important;color:var(--text-main)!important;min-height:58vh!important;padding:1.1rem!important;}
        [data-theme="dark"] .note-editable{background:rgba(18,10,10,.28)!important;}
        .theme-switch-wrapper{display:flex;align-items:center;gap:.45rem;cursor:pointer;}
        .theme-switch{width:42px;height:22px;border:2px solid var(--text-main);border-radius:999px;position:relative;}
        .theme-switch:after{content:'';position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:var(--text-main);transition:transform .2s;}
        [data-theme="dark"] .theme-switch:after{transform:translateX(20px);}
        #loadingOverlay{background:rgba(18,10,10,.86);}
        .spinner{width:42px;height:42px;border:4px solid var(--bg-paper);border-top-color:var(--text-accent);border-radius:50%;animation:spin 1s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg)}}
        @media (max-width:1180px){.create-header-inner{align-items:flex-start;flex-direction:column}.create-actions{justify-content:flex-start}.create-form{grid-template-columns:1fr}.create-meta-grid{position:static;max-height:none;overflow:visible;padding:0}.meta-actions{order:0}.note-editable{min-height:460px!important}}
        @media (max-width:720px){.create-title-row{align-items:flex-start}.create-heading h1{font-size:1.55rem}.editor-tools{justify-content:stretch;width:100%}.editor-tools select,.editor-tools button{width:100%}.create-form{padding-inline:.85rem}.panel{padding:1rem!important}}
    </style>
</head>
<body class="min-h-screen">
    <div id="loadingOverlay" class="hidden fixed inset-0 z-[9999] items-center justify-center text-[var(--bg-paper)]">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <div class="font-syne font-black uppercase tracking-widest">İşleniyor</div>
            <div class="font-mono text-xs mt-2 opacity-80">Lütfen sayfayı kapatmayın.</div>
        </div>
    </div>

    <header class="create-header">
        <div class="create-header-inner">
            <div class="create-title-row">
                <a href="/yonetim/dashboard" class="btn-secondary px-3 py-2 text-[10px] inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M313-440 481-272l-57 57-264-264 264-264 57 57-168 168h448v80H313Z"/></svg>
                    Panel
                </a>
                <div class="create-heading">
                    <h1 class="font-syne text-xl md:text-3xl font-black uppercase tracking-tight">Yeni Makale</h1>
                    <p>Önce yazıyı oluştur, sağ panelden yayın hazırlığını tamamla.</p>
                </div>
            </div>
            <div class="create-actions">
                <button type="button" id="tabWrite" class="btn-main px-4 py-2 text-xs" aria-pressed="true">Yazı</button>
                <button type="button" id="tabPreview" class="btn-secondary px-4 py-2 text-xs" aria-pressed="false">Önizleme</button>
                <label for="wordInput" class="btn-secondary px-4 py-2 text-xs inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
                    .docx Aktar
                </label>
                <input type="file" id="wordInput" accept=".docx" class="hidden">
                <div class="theme-switch-wrapper" role="button" tabindex="0" aria-label="Temayı değiştir"><span class="font-mono text-[10px] uppercase">Tema</span><span class="theme-switch"></span></div>
            </div>
        </div>
    </header>

    <?php if ($flashMessage = Flash::pull()): ?>
        <div class="mx-4 md:mx-8 mt-4 panel p-4 text-[var(--text-accent)] font-mono text-xs font-bold">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form id="uploadForm" action="/yonetim/store" method="POST" enctype="multipart/form-data" data-post-max-bytes="<?= $postMaxBytes() ?>" class="create-form">
        <?= Csrf::field() ?>
        <main class="create-main">
            <section class="panel title-card p-4 md:p-6 space-y-4">
                <label class="block font-mono text-[10px] uppercase font-bold opacity-70">Başlık</label>
                <input type="text" name="title" class="text-2xl md:text-4xl font-syne font-black p-4" placeholder="Makale başlığı" required autocomplete="off">
            </section>

            <section class="panel p-4 md:p-6 space-y-4">
                <div class="editor-head">
                    <div>
                        <label class="block font-mono text-[10px] uppercase font-bold opacity-70">İçerik</label>
                        <div id="wordCounter" class="font-mono text-xs opacity-70 mt-1">0 kelime / ~0 dk</div>
                    </div>
                    <div class="editor-tools">
                        <!-- word input üst bara taşındı -->
                    </div>
                </div>
                <textarea id="summernote" name="content"></textarea>
            </section>

            <section class="panel p-4 md:p-6 space-y-4">
                <label class="block font-mono text-[10px] uppercase font-bold opacity-70">Kaynakça</label>
                <textarea name="refs" rows="6" class="p-4 font-mono text-xs" placeholder="1=https://... veya [1] Kaynak adı"></textarea>
            </section>
        </main>

        <aside class="create-meta-grid">
            <section class="panel meta-publish p-4 space-y-4">
                <h2 class="panel-title flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M440-280h80v-240h-80v240Zm40-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                    Yayın Bilgileri
                </h2>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M400-240v-80h160v80H400ZM240-400v-80h480v80H240ZM120-560v-80h720v80H120Z"/></svg>
                        Dil
                    </label>
                    <select name="lang" class="p-3 font-mono text-xs">
                        <option value="TR">TR</option>
                        <option value="EN">EN</option>
                    </select>
                </div>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M440-183v-274L200-596v274l240 139Zm80 0 240-139v-274L520-457v274Zm-40-343 237-137-237-137-237 137 237 137ZM160-252q-19-11-29.5-29T120-321v-318q0-22 10.5-40t29.5-29l280-161q19-11 40-11t40 11l280 161q19 11 29.5 29t10.5 40v318q0 22-10.5 40T800-252L520-91q-19 11-40 11t-40-11L160-252Zm320-228Z"/></svg>
                        Manuel slug
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="manual_slug" id="manualSlug" class="p-3 font-mono text-xs" placeholder="bos-birakirsan-otomatik">
                        <button type="button" id="generateSlugBtn" class="btn-secondary px-3 text-[10px] whitespace-nowrap">Üret</button>
                    </div>
                </div>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-160 0q-17 0-28.5-11.5T280-440q0-17 11.5-28.5T320-480q17 0 28.5 11.5T360-440q0 17-11.5 28.5T320-400Zm320 0q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-300q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-160 0q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-300q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm320 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-300q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z"/></svg>
                        Yayın zamanı
                    </label>
                    <input type="datetime-local" name="publish_at" class="p-3 font-mono text-xs">
                </div>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M160-400v-80h280v80H160Zm0-160v-80h440v80H160Zm0-160v-80h440v80H160Zm360 560v-123l221-220q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T863-380L643-160H520Zm300-263-37-37 37 37ZM580-220h38l121-122-18-19-19-18-122 121v38Zm141-141-19-18 37 37-18-19Z"/></svg>
                        Kısa açıklama
                    </label>
                    <textarea name="desc" rows="4" class="p-3 text-sm" placeholder="Listeleme ve SEO için kısa özet"></textarea>
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
                            <input type="checkbox" name="authors[]" value="<?= (int)$author['id'] ?>">
                            <span class="text-sm font-bold"><?= htmlspecialchars((string)$author['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($authors)): ?>
                        <p class="font-mono text-xs text-[var(--text-accent)]">Önce yazar ekleyin.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel meta-categories p-4 space-y-4">
                <h2 class="panel-title flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640H447l-80-80H160v480l96-320h684L837-217q-8 26-29.5 41.5T760-160H160Zm84-80h516l72-240H316l-72 240Zm0 0 72-240-72 240Zm-84-400v-80 80Z"/></svg>
                    Kategoriler
                </h2>
                <div class="choice-list">
                    <?php foreach ($categories as $category): ?>
                        <label class="choice-item">
                            <input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>">
                            <span class="text-sm"><?= htmlspecialchars((string)$category['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel meta-cover p-4 space-y-4">
                <h2 class="panel-title flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M480-260q75 0 127.5-52.5T660-440q0-75-52.5-127.5T480-620q-75 0-127.5 52.5T300-440q0 75 52.5 127.5T480-260Zm0-80q-42 0-71-29t-29-71q0-42 29-71t71-29q42 0 71 29t29 71q0 42-29 71t-71 29ZM160-120q-33 0-56.5-23.5T80-200v-480q0-33 23.5-56.5T160-760h126l74-80h240l74 80h126q33 0 56.5 23.5T880-680v480q0 33-23.5 56.5T800-120H160Zm0-80h640v-480H638l-73-80H395l-73 80H160v480Zm320-240Z"/></svg>
                    Kapak
                </h2>
                <input type="file" name="cover_image" id="coverUpload" accept="image/*" class="p-3 font-mono text-xs">
                <div id="placeholderState" class="border-2 border-dashed border-[var(--line-color)] p-6 text-center font-mono text-xs opacity-70">Kapak seçilmedi</div>
                <div id="previewState" class="hidden space-y-2">
                    <img id="imgPreview" src="" alt="Kapak önizleme" class="w-full aspect-video object-cover border-2 border-[var(--line-color)]">
                    <div id="fileName" class="font-mono text-[10px] opacity-70"></div>
                </div>
            </section>

            <section class="panel meta-seo p-4 space-y-4">
                <h2 class="panel-title flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-70" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg>
                    SEO
                </h2>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-1">Sayfa Başlığı</label>
                    <p class="font-mono text-[9px] opacity-50 mb-2">Google arama sonucunda görünen başlık. Boş bırakırsan makale başlığı kullanılır.</p>
                    <input type="text" name="seo_title" id="seoTitleInput" maxlength="70" class="p-3 text-sm" placeholder="Makale başlığından farklı bir Google başlığı isteğine yaz">
                    <div class="flex justify-between mt-1">
                        <span class="font-mono text-[9px] opacity-40">Tavsiye: 50–60 karakter</span>
                        <span id="seoTitleCount" class="font-mono text-[9px] opacity-40">0 / 70</span>
                    </div>
                </div>
                <div>
                    <label class="block font-mono text-[10px] uppercase font-bold opacity-70 mb-1">Meta Açıklama</label>
                    <p class="font-mono text-[9px] opacity-50 mb-2">Arama sonucunda başlığın altındaki kısa yazı. Boş bırakırsan kısa açıklama veya içerikten alınır.</p>
                    <textarea name="seo_description" id="seoDescInput" rows="3" maxlength="160" class="p-3 text-sm" placeholder="Okuyucuyu tıklamaya teşvik eden 1-2 cümle"></textarea>
                    <div class="flex justify-between mt-1">
                        <span class="font-mono text-[9px] opacity-40">Tavsiye: 120–155 karakter</span>
                        <span id="seoDescCount" class="font-mono text-[9px] opacity-40">0 / 160</span>
                    </div>
                </div>
            </section>

            <section class="panel meta-actions p-4">
                <input type="hidden" name="status" id="statusInput" value="published">
                <button type="button" id="draftBtn" class="btn-secondary w-full py-3 inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM565-275q35-35 35-85t-35-85q-35-35-85-35t-85 35q-35 35-35 85t35 85q35 35 85 35t85-35ZM255-560h310v-160H255v160Zm-55-86v446-560 114Z"/></svg>
                    Taslak Kaydet
                </button>
                <button type="submit" id="submitBtn" class="btn-main w-full py-4 inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="m226-559 78 33q14-28 29-54t33-52l-56-11-84 84Zm142 83 114 113q42-16 90-49t90-75q70-70 109.5-155.5T806-800q-72-5-158 34.5T492-656q-42 42-75 90t-49 90Zm155-121.5q0-33.5 23-56.5t57-23q34 0 57 23t23 56.5q0 33.5-23 56.5t-57 23q-34 0-57-23t-23-56.5ZM565-220l84-84-11-56q-26 18-52 32.5T532-299l33 79Zm313-653q19 121-23.5 235.5T708-419l20 99q4 20-2 39t-20 33L538-80l-84-197-171-171-197-84 168-168q14-14 33-20t39-2l99 20q57-56 171.5-98.5T878-873ZM157-321q35-35 85-35.5t85 34.5q35 35 34.5 85T326-152q-25 25-57.5 43T175-80q-1-32 17-64.5t43-58.5q-18 2-38 5t-40 9q17-22 33.5-41t33.5-33q17-17 17.5-41.5T224-346l-67 25Z"/></svg>
                    Yayınla
                </button>
            </section>
        </aside>
    </form>

    <?php $preview_date = date('d F Y'); require_once __DIR__ . '/_preview_panel.php'; ?>

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
