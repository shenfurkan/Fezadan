<?php
$preview_date = !empty($article['created_at']) ? date('d F Y', strtotime($article['created_at'])) : date('d F Y');
?>
<dialog id="previewPanel" class="editor-preview" aria-labelledby="previewHeading" aria-describedby="previewHelp" data-site-url="<?= htmlspecialchars(rtrim(SITE_URL, '/'), ENT_QUOTES, 'UTF-8') ?>">
    <header class="editor-preview-toolbar">
        <div class="editor-preview-heading">
            <h2 id="previewHeading">Makale önizlemesi</h2>
            <p id="previewHelp">Kaydedilmemiş değişiklikler dahil. Bu ekran yazıyı kaydetmez veya yayınlamaz.</p>
        </div>
        <div class="editor-preview-devices" role="group" aria-label="Önizleme genişliği">
            <button type="button" data-preview-device="desktop" aria-pressed="true">Masaüstü</button>
            <button type="button" data-preview-device="tablet" aria-pressed="false">Tablet</button>
            <button type="button" data-preview-device="mobile" aria-pressed="false">Telefon</button>
        </div>
        <button type="button" id="previewClose" class="editor-preview-close" autofocus>Düzenlemeye dön <span aria-hidden="true">×</span></button>
    </header>
    <div id="previewStage" class="editor-preview-stage" data-device="desktop">
        <iframe id="previewFrame" title="Makalenin okuyucu görünümü" sandbox="allow-same-origin" referrerpolicy="no-referrer"></iframe>
    </div>
</dialog>
<template id="previewTemplate">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(ROOT . '/public_html/assets/css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/article-math.css?v=<?= filemtime(ROOT . '/public_html/assets/css/article-math.css') ?>">
    <link rel="stylesheet" href="/assets/css/admin-preview.css?v=<?= filemtime(ROOT . '/public_html/assets/css/admin-preview.css') ?>">
    <main class="preview-reader">
        <article class="preview-reader-article">
            <header class="preview-reader-header">
                <div class="preview-reader-meta">
                    <span id="prev-date"><?= htmlspecialchars($preview_date, ENT_QUOTES, 'UTF-8') ?></span>
                    <span aria-hidden="true">/</span>
                    <span id="prev-cats">GENEL</span>
                    <span aria-hidden="true">/</span>
                    <span id="prev-readtime">1 dk okuma</span>
                </div>
                <h1 id="prev-title" class="main-article-title font-syne text-5xl md:text-7xl font-bold leading-[0.9] tracking-tight mb-8">Başlık buraya gelecek…</h1>
                <p id="prev-desc" class="font-body text-xl md:text-2xl italic leading-normal text-[var(--text-main)] opacity-90 pl-6 border-l-4 border-[var(--text-accent)]" hidden></p>
                <p id="prev-authors" class="preview-reader-authors" hidden></p>
            </header>
            <figure id="prev-cover-wrap" class="preview-reader-cover" hidden>
                <img id="prev-cover" alt="" width="1200" height="675">
            </figure>
            <div id="prev-content" class="journal-text font-body">
                <p>İçerik eklediğinizde burada görünür.</p>
            </div>
            <section id="prev-refs-wrap" class="preview-reader-refs" hidden>
                <h2 id="prev-refs-heading">Kaynakça</h2>
                <ol id="prev-refs"></ol>
            </section>
        </article>
        <aside id="prev-toc-wrap" class="preview-reader-toc" hidden>
            <h2 id="prev-toc-heading">İçindekiler</h2>
            <nav id="prev-toc" aria-labelledby="prev-toc-heading"></nav>
        </aside>
    </main>
</template>
