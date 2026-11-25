<?php
/**
 * @var array $article
 * @var array $selectedAuthors
 * @var array $selectedCategories
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | Makale Düzenle</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin-preview.css?v=<?= filemtime(ROOT . '/public_html/assets/css/admin-preview.css') ?>">
    <link rel="preload" href="/assets/fonts/space-grotesk-v22-latin-ext-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="/assets/css/summernote-lite.min.css">
    <script src="/assets/js/jquery.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/summernote-lite.min.js" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/mammoth.browser.min.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/mammoth.browser.min.js'); ?>" nonce="<?= CSP_NONCE ?>"></script>
    <link rel="stylesheet" href="/assets/css/article-math.css?v=<?= filemtime(ROOT . '/public_html/assets/css/article-math.css') ?>">
    <script src="/assets/js/article-math.js?v=<?= filemtime(ROOT . '/public_html/assets/js/article-math.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin-editor.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin-editor.js'); ?>" nonce="<?= CSP_NONCE ?>"></script>
    <script src="/assets/js/admin.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin.js'); ?>" nonce="<?= CSP_NONCE ?>"></script>

    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            var theme = 'light';
            try { theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light'; } catch (_) { /* Storage is optional. */ }
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link rel="stylesheet" href="/assets/css/admin-editor.css?v=<?= filemtime(ROOT . '/public_html/assets/css/admin-editor.css') ?>">
</head>

<body class="editor-workspace min-h-screen">

    <div id="loadingOverlay" class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center text-[var(--bg-paper)]">
        <div class="spinner mb-4"></div>
        <h2 class="font-syne text-2xl font-bold tracking-widest animate-pulse text-[var(--text-main)]">YÜKLENİYOR...</h2>
        <p class="font-mono text-xs mt-2 opacity-80 text-[var(--text-main)]">Lütfen bekleyiniz...</p>
    </div>

    <form id="editForm" action="/yonetim/update" method="POST" enctype="multipart/form-data" class="editor-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
        <input type="hidden" name="status" id="statusInput" value="<?php echo htmlspecialchars($article['status'] ?? 'published'); ?>">
        <?php require __DIR__ . '/_editor_header.php'; ?>

    <?php if ($flashMessage = Flash::pull()): ?>
    <div class="editor-flash" role="status">
        <?php echo htmlspecialchars($flashMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

        <main class="editor-main">
            <div class="title-card">
                <label for="articleTitle">Başlık</label>
                <input type="text" id="articleTitle" name="title"
                    class="text-3xl font-bold font-syne py-6 border-none border-b-2 focus:bg-transparent"
                    placeholder="Buraya Başlık Giriniz..." required autocomplete="off"
                    value="<?php echo htmlspecialchars($article['title']); ?>">
                <div id="titleAnalysis" hidden>
                    <span id="titleCharCount"><?php echo mb_strlen($article['title'] ?? '', 'UTF-8'); ?></span> karakter | <span id="titleStatus">Kontrol ediliyor...</span>
                </div>
            </div>

            <div>
                <div class="editor-head">
                    <label for="summernote">Metin</label>
                    <div id="wordCounter">0 kelime</div>
                </div>
                    <div class="editor-import-tools">
                        <!-- Şablon Seçimi -->
                        <select id="templateSelect" aria-label="Metne şablon ekle">
                            <option value="">Şablon ekle</option>
                            <option value="interview">Röportaj</option>
                            <option value="review">İnceleme</option>
                        </select>
                        <input type="file" id="editWordInput" accept=".docx" class="hidden">
                        <button type="button" id="wordImportTrigger"
                            class="btn-secondary">
                            <span>Word aktar</span>
                        </button>
                        <?php require __DIR__ . '/_scientific_tools.php'; ?>
                    </div>
                <textarea id="summernote" name="content"><?php echo htmlspecialchars($article['content'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>

                <details class="editor-disclosure editor-references">
                    <summary>Kaynakça</summary>
                    <div class="editor-help">
                        <label for="articleRefs">Kaynaklar ve referanslar</label>
                        <span class="text-[10px] opacity-60 font-mono">Format: 1=https://site.com veya [1] Kitap Adı</span>
                    </div>
                    <textarea id="articleRefs" name="refs" rows="4" class="font-mono text-sm p-3"
                        placeholder="Her satıra bir kaynak giriniz.&#10;Eğer kaynak bir cümleye referans ise o cümleye linklemek için metinde o cümleye [1] yazıp buraya 1=kaynak ismi/linki şeklinde yazın&#10;Aksi taktirde numara vermeden her satıra birer kaynak yazınız.&#10;1=https://nasa.gov/report&#10;ya da&#10;Einstein, İzafiyet Teorisi, sf.45"><?php echo htmlspecialchars($article['refs'] ?? ''); ?></textarea>
                </details>

                <!-- Revision Log -->
                <details class="editor-disclosure">
                    <summary>Düzeltme notu ve geçmişi</summary>
                    <div class="border-2 border-[var(--text-accent)]/40 p-4 bg-[var(--bg-secondary)]/5 space-y-3">
                        <label class="block font-syne font-bold uppercase text-xs text-[var(--text-accent)]">DÜZELTME NOTU</label>
                        <p class="text-[10px] opacity-60 font-mono">Makalede yapılan maddi hataları veya güncellemeleri belirtmek için buraya bir not ekleyebilirsiniz. Makalenin en altında tarihçesiyle gösterilecektir.</p>
                        <textarea name="correction_text" rows="3" class="brutalist-input border-2 border-b-2" placeholder="Örn: Yazım hatası giderildi, bilgi kutusu güncellendi..."></textarea>

                        <?php if (!empty($corrections)): ?>
                        <div class="mt-4 pt-3 border-t border-[var(--line-color)]/20">
                            <label class="block font-mono text-[10px] uppercase font-bold mb-2">GEÇMİŞ DÜZELTMELER</label>
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
                </details>
            </div>
        </main>

        <aside id="editorSettings" class="editor-settings" aria-label="Yayın bilgileri" tabindex="-1">
            <h2 class="panel-title">Yayın bilgileri</h2>
            <div>
                <label for="langSelect" class="block mb-2">Dil</label>
                <select name="lang" id="langSelect" required>
                    <option value="TR" <?php echo ($article['lang'] === 'TR') ? 'selected' : ''; ?>>Türkçe</option>
                    <option value="EN" <?php echo ($article['lang'] === 'EN') ? 'selected' : ''; ?>>İngilizce</option>
                </select>
            </div>
            <div>
                <label for="articleSummary" class="block mb-2">Kısa özet</label>
                <textarea id="articleSummary" name="desc" rows="3" placeholder="Yazının listeleme sayfasında görünen özeti" required><?php echo htmlspecialchars($article['short_desc']); ?></textarea>
            </div>

            <!-- Yazar Seçimi -->
            <div class="mb-6">
                <label class="block mb-3">Yazarlar</label>
                <div class="flex gap-2 mb-2">
                    <button type="button" class="select-all-btn text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 hover:bg-[var(--text-accent)] transition-colors" data-target="authors">Tümü</button>
                    <button type="button" class="clear-all-btn text-[10px] font-bold border border-[var(--text-main)] text-[var(--text-main)] px-2 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors" data-target="authors">Sıfırla</button>
                </div>
                <div class="choice-list">
                    <?php if (!empty($authors)): foreach ($authors as $author): ?>
                    <label class="choice-item">
                        <input type="checkbox" name="authors[]" value="<?php echo $author['id']; ?>" data-author-slug="<?= htmlspecialchars((string)($author['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:1rem;height:1rem;min-width:1rem;padding:0;border:none;background:transparent;" class="accent-[#A31D1D] cursor-pointer flex-shrink-0" <?php echo (in_array($author['id'], $selectedAuthors)) ? 'checked' : ''; ?>>
                        <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] truncate <?php echo in_array($author['id'], $selectedAuthors) ? 'font-bold text-[var(--text-accent)]' : ''; ?>"><?php echo htmlspecialchars($author['name']); ?></span>
                    </label>
                    <?php endforeach; endif; ?>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">İlk seçilen yazar birincil (ana) yazar olacaktır. En az bir yazar seçilmelidir.</small>
            </div>

            <!-- Kategoriler -->
            <details class="editor-disclosure">
                <summary>Kategoriler</summary>
                <div class="flex gap-2 mb-2">
                    <button type="button" class="select-all-btn text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 hover:bg-[var(--text-accent)] transition-colors" data-target="categories">Tümü</button>
                    <button type="button" class="clear-all-btn text-[10px] font-bold border border-[var(--text-main)] text-[var(--text-main)] px-2 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors" data-target="categories">Sıfırla</button>
                </div>
                <div class="choice-list">
                    <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                    <label class="choice-item">
                        <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" style="width:1rem;height:1rem;min-width:1rem;padding:0;border:none;background:transparent;" class="accent-[#A31D1D] cursor-pointer flex-shrink-0" <?php echo in_array($cat['id'], $selectedCategories) ? 'checked' : ''; ?>>
                        <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] truncate <?php echo in_array($cat['id'], $selectedCategories) ? 'font-bold text-[var(--text-accent)]' : ''; ?>"><?php echo htmlspecialchars($cat['name']); ?></span>
                    </label>
                    <?php endforeach; else: ?>
                    <p class="col-span-2 text-[10px] opacity-50 uppercase">Henüz kategori tanımlanmamış.</p>
                    <?php endif; ?>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Birden fazla seçim yapabilirsiniz.</small>
            </details>

            <!-- Kapak Görseli (aspect-ratio, drag-and-drop identical to create.php) -->
            <details class="editor-disclosure">
                <summary>Kapak görseli</summary>
                <div class="aspect-video w-full border-2 border-dashed border-[var(--text-main)] bg-[var(--bg-paper)] relative group overflow-hidden cursor-pointer">
                    <input type="file" id="coverUpload" name="cover_image" accept="image/png,image/jpeg,image/webp"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-50">
                    <div id="placeholderState" class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 transition-transform group-hover:scale-105 z-10 <?php echo !empty($article['image_url']) ? 'hidden' : ''; ?>">
                        <div class="text-3xl mb-2">GÖRSEL</div>
                        <p class="text-[10px] font-mono opacity-70">Tıkla veya Sürükle</p>
                        <div class="text-[9px] text-[var(--text-accent)] font-bold mt-2">MAX: 5MB</div>
                    </div>
                    <div id="previewState" class="absolute inset-0 z-20 bg-[var(--bg-paper)] <?php echo empty($article['image_url']) ? 'hidden' : ''; ?>">
                        <img id="imgPreview" class="w-full h-full object-cover" src="<?php echo !empty($article['image_url']) ? htmlspecialchars(Upload::assetUrl($article['image_url']), ENT_QUOTES, 'UTF-8') : ''; ?>" alt="Kapak önizlemesi">
                        <div class="absolute bottom-0 left-0 w-full bg-[var(--text-main)]/90 text-[var(--bg-paper)] text-[10px] text-center py-1 opacity-0 group-hover:opacity-100 transition-opacity">GÖRSELİ DEĞİŞTİRMEK İÇİN TIKLA</div>
                    </div>
                </div>
                <p id="fileName" class="text-[10px] font-mono mt-2 text-center opacity-70 truncate min-h-[15px]"></p>
                <div id="uploadError" class="hidden text-[10px] text-red-600 font-bold bg-red-100 p-2 border border-red-600 mt-2"></div>
            </details>

            <details class="editor-disclosure">
                <summary>Adres ve zamanlama</summary>
            <!-- Slug -->
            <div>
                <label for="manualSlug" class="block mb-2">Yazının adresi</label>
                <div class="flex gap-2">
                    <input type="text" name="manual_slug" id="manualSlug" placeholder="title'dan otomatik" class="text-sm p-3 flex-1" value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>">
                    <button type="button" id="generateSlugBtn" class="text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-3 py-1 hover:bg-[var(--text-accent)] transition-colors whitespace-nowrap">Başlıktan Üret</button>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Boş bırakılırsa title'dan otomatik üretilir.</small>
            </div>

            <!-- Yayın Tarihi -->
            <div>
                <label for="publishAt" class="block mb-2">Yayın zamanı</label>
                <input type="datetime-local" name="publish_at" id="publishAt" class="text-sm p-3" value="<?php echo !empty($article['publish_at']) ? date('Y-m-d\TH:i', strtotime($article['publish_at'])) : ''; ?>">
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Boş bırakılırsa hemen yayınlanır. Tarih seçilirse zamanlı yayın yapılır.</small>
            </div>
            </details>

            <!-- SEO Metadata -->
            <details class="editor-disclosure">
                <summary>SEO ve paylaşım</summary>

                <div class="space-y-4">
                    <div>
                        <label for="seoTitle" class="block mb-1">SEO başlığı</label>
                        <input type="text" name="seo_title" id="seoTitle" placeholder="SEO başlığı..." class="text-sm p-3" value="<?php echo htmlspecialchars($article['seo_title'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="seoDescription" class="block mb-1">SEO açıklaması</label>
                        <textarea name="seo_description" id="seoDescription" rows="4" placeholder="SEO açıklaması..." class="text-sm p-3"><?php echo htmlspecialchars($article['seo_description'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="font-mono text-xs uppercase font-bold mb-1 block">META ANAHTAR KELİMELER</label>
                        <textarea name="meta_keywords" id="metaKeywords" rows="3" placeholder="virgül, ile, ayırın..." class="text-sm p-3"><?php echo htmlspecialchars($article['meta_keywords'] ?? ''); ?></textarea>
                    </div>

                    <!-- OG Sosyal Medya Görseli ve Üretme Butonu -->
                    <div class="pt-2 border-t border-[var(--line-color)]/20">
                        <label class="font-mono text-[10px] uppercase font-bold mb-2 block">OG Sosyal Medya Görseli</label>
                        <div id="ogImagePreview" class="mb-2 <?php echo empty($article['og_image']) ? 'hidden' : ''; ?>">
                            <img id="ogImagePreviewImage" src="<?php echo !empty($article['og_image']) ? \Upload::assetUrl($article['og_image']) : ''; ?>" class="h-20 border border-[var(--text-main)]" alt="OG Görseli">
                        </div>
                        <button type="button" id="generateOgBtn" class="w-full py-2 bg-[var(--text-main)] text-[var(--bg-paper)] text-xs font-bold uppercase hover:bg-black transition-colors font-syne">OG Görseli Üret</button>
                    </div>
                </div>
            <!-- Google Önizlemesi -->
            <div class="border-t-2 border-[var(--text-main)] pt-4 mt-4">
                <h3 class="font-syne font-bold uppercase text-xs mb-3">GOOGLE ÖNİZLEMESİ</h3>
                <?php require __DIR__ . '/_seo_preview.php'; ?>
            </div>
            </details>
            <?php require __DIR__ . '/_editor_checks.php'; ?>

            <!-- Delete Form -->
            <details class="editor-disclosure">
                <summary>Diğer işlemler</summary>
                <button type="submit" form="deleteArticleForm" class="w-full py-2 border-2 border-red-600 text-red-600 font-bold text-xs uppercase hover:bg-red-600 hover:text-white transition-all font-syne tracking-wider">
                    MAKALEYİ SİL
                </button>
            </details>
        </aside>
    </form>

    <form id="deleteArticleForm" method="POST" action="/yonetim/delete" data-confirm="Bu makaleyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz." class="hidden">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?php echo (int)$article['id']; ?>">
    </form>

    <?php require __DIR__ . '/_preview_panel.php'; ?>

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
            existingCoverUrl: <?= json_encode(!empty($article['image_url']) ? Upload::assetUrl($article['image_url']) : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            statusInputSelector: '#statusInput',
            draftButtonSelector: '#draftBtn',
            publishButtonSelector: '#submitBtn',
            draftKey: 'fezadan_edit_draft_<?php echo (int)$article['id']; ?>',
            csrfToken: '<?= Csrf::token() ?>',
            uploadUrl: '/yonetim/upload-content-image',
            templateSelectSelector: '#templateSelect',
            height: 400,
            placeholder: 'Yazmaya başla...'
        });

        (function () {
            const button = document.getElementById('generateOgBtn');
            if (!button) return;
            button.addEventListener('click', async function () {
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'ÜRETİLİYOR...';
                try {
                    const response = await fetch('/yonetim/generate-og-image', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: new URLSearchParams({
                            _csrf: <?= json_encode(Csrf::token(), JSON_UNESCAPED_SLASHES) ?>,
                            article_id: <?= (int)$article['id'] ?>
                        })
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'OG görseli üretilemedi.');
                    }
                    const image = document.getElementById('ogImagePreviewImage');
                    const preview = document.getElementById('ogImagePreview');
                    if (image) image.src = result.url;
                    if (preview) preview.classList.remove('hidden');
                    if (window.FezadanToast) {
                        window.FezadanToast.success('OG görseli üretildi.');
                    } else {
                        alert('OG görseli üretildi.');
                    }
                } catch (error) {
                    if (window.FezadanToast) {
                        window.FezadanToast.error(error.message || 'OG görseli üretilemedi.');
                    } else {
                        alert(error.message || 'OG görseli üretilemedi.');
                    }
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        })();
    </script>
</body>
</html>
