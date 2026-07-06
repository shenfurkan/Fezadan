<?php
    $page_title = htmlspecialchars($note['title']);
    $page_description = !empty($note['description'])
        ? mb_substr(strip_tags($note['description']), 0, 160)
        : 'FEZADAN Notlar - ' . htmlspecialchars($note['title']);
    include __DIR__ . '/../inc/notes_header.php';

    $isLocal = function_exists('litecaptcha_is_local_request') && litecaptcha_is_local_request();
    $requireCaptcha = defined('LITECAPTCHA_ENABLED') && LITECAPTCHA_ENABLED && !$isLocal;
?>


    <?php if(isset($_GET['error']) && $_GET['error'] == 'captcha_fail'): ?>
    <div class="w-full bg-[#6D2323] text-[#FEF9E1] p-4 text-center font-mono font-bold text-sm border-b-2 border-[#FEF9E1] relative z-50">
        Do&#287;rulama ge&#231;ersiz veya s&#252;resi dolmu&#351;. L&#252;tfen tekrar deneyin.
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'rate_limit'): ?>
    <div class="w-full bg-[#6D2323] text-[#FEF9E1] p-4 text-center font-mono font-bold uppercase text-sm border-b-2 border-[#FEF9E1] relative z-50">
        Cok fazla istek gonderildi. Lutfen 1 dakika bekleyip tekrar deneyin.
    </div>
    <?php endif; ?>

    <div class="lg:hidden w-full border-b-2 border-[var(--text-main)] bg-[var(--bg-paper)] relative z-40 shadow-[0_4px_0px_rgba(0,0,0,0.1)]">
        <button id="mobile-meta-btn" class="w-full p-4 flex justify-between items-center bg-[var(--text-main)] text-[var(--bg-paper)] hover:bg-black transition-colors focus:outline-none">
            <span class="font-syne font-bold uppercase text-sm truncate flex-1 text-left mr-4">
                <?php echo htmlspecialchars($note['title']); ?>
            </span>
            <span class="font-mono text-xs flex items-center gap-2 shrink-0">
                METADATA <span class="arrow-icon inline-block">▼</span>
            </span>
        </button>

        <div id="mobile-metadata-content" class="bg-[var(--bg-paper)] text-[var(--text-main)]">
            <div class="metadata-inner">
                <div class="p-4 border-b-2 border-dashed border-[var(--text-main)]/30 font-mono text-[10px] space-y-2">
                    <div class="flex justify-between">
                        <span class="font-bold opacity-60">YÜKLEYEN:</span>
                        <span><?php echo htmlspecialchars($note['uploader_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold opacity-60">TARİH:</span>
                        <span><?php echo date('d.m.Y', strtotime($note['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold opacity-60">BOYUT:</span>
                        <span><?php echo number_format(($note['file_size'] ?? 0) / 1048576, 2); ?> MB</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold opacity-60">DİL:</span>
                        <span class="font-bold text-[var(--text-accent)] uppercase"><?php echo htmlspecialchars($note['lang'] ?? 'TR'); ?></span>
                    </div>
                </div>
                <div class="p-4 flex gap-2">
                    <?php if ($requireCaptcha): ?>
                        <button id="dl-trigger-1" class="flex-1 text-center bg-[#6D2323] text-[#FEF9E1] py-3 font-bold uppercase font-syne text-sm border-2 border-[#6D2323] hover:bg-black hover:border-black transition-colors">
                            İNDİR [↓]
                        </button>
                    <?php else: ?>
                        <a href="/not/download/<?php echo $note['slug']; ?>" class="flex-1 block text-center bg-[#6D2323] text-[#FEF9E1] py-3 font-bold uppercase font-syne text-sm border-2 border-[#6D2323] hover:bg-black hover:border-black transition-colors">
                            İNDİR [↓]
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <main id="main-content" class="flex flex-col lg:flex-row w-full lg:border-t-2 border-[var(--text-main)]" style="min-height: calc(100vh - 85px);">

        <aside class="w-64 border-r-2 border-[var(--text-main)] bg-[var(--bg-paper)] overflow-y-auto custom-scrollbar hidden xl:flex flex-col shrink-0" style="height: calc(100vh - 85px);">
            <div class="sticky top-0 bg-[var(--text-main)] text-[var(--bg-paper)] p-3 font-syne font-bold uppercase text-xs z-20 border-b-2 border-[var(--text-main)]">
                // SAYFALAR (<span id="total-pages-thumb">0</span>)
            </div>
            <div id="thumbnail-container" class="p-4 flex flex-col gap-4"></div>
        </aside>

        <section id="viewer-container" class="flex-1 bg-[var(--bg-secondary)]/30 lg:overflow-y-auto custom-scrollbar relative p-4 md:p-8 flex flex-col items-center h-auto lg:h-[calc(100vh-85px)]">

            <div id="loading-banner" class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] p-6 font-mono font-bold uppercase text-center w-full max-w-md animate-pulse mt-12 shadow-[8px_8px_0px_var(--text-main)] mx-auto">
                Belge İşleniyor... Lütfen Bekleyin.
            </div>

            <div id="pdf-pages" class="w-full flex flex-col items-center gap-6 pb-12"></div>
        </section>

        <aside class="w-80 border-l-2 border-[var(--text-main)] bg-[var(--bg-paper)] overflow-y-auto custom-scrollbar hidden lg:flex flex-col shrink-0" style="height: calc(100vh - 85px);">
            <div class="sticky top-0 bg-[var(--text-main)] text-[var(--bg-paper)] p-3 font-syne font-bold uppercase text-xs z-20 border-b-2 border-[var(--text-main)]">
                // BELGE METADATASI
            </div>

            <div class="p-6 flex flex-col gap-6">
                <div>
                    <h1 class="font-syne text-2xl font-bold leading-tight mb-3">
                        <?php echo htmlspecialchars($note['title']); ?>
                    </h1>
                    <div class="flex flex-wrap gap-1">
                        <span class="text-[10px] font-mono border border-[var(--text-accent)] text-[var(--text-accent)] px-2 py-1 uppercase font-bold tracking-widest mr-1">
                            <?php echo htmlspecialchars($note['lang'] ?? 'TR'); ?>
                        </span>

                        <?php
                        if(!empty($note['category_names'])):
                            $cats = explode(', ', $note['category_names']);
                            foreach($cats as $c):
                        ?>
                            <span class="text-[10px] font-mono bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 uppercase font-bold">
                                <?php echo htmlspecialchars($c); ?>
                            </span>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <div class="font-mono text-xs space-y-3 opacity-80 border-y-2 border-dashed border-[var(--text-main)]/30 py-4">
                    <div class="flex justify-between">
                        <span class="font-bold">YÜKLEYEN:</span>
                        <span><?php echo htmlspecialchars($note['uploader_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold">TARİH:</span>
                        <span><?php echo date('d.m.Y', strtotime($note['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold">BOYUT:</span>
                        <span><?php echo number_format(($note['file_size'] ?? 0) / 1048576, 2); ?> MB</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold">İNDİRME:</span>
                        <span class="text-[var(--text-accent)] font-bold"><?php echo $note['downloads']; ?></span>
                    </div>
                </div>

                <?php if(!empty($note['description'])): ?>
                <div>
                    <h3 class="font-syne font-bold text-sm mb-2 uppercase flex items-center gap-2">
                        <span class="w-2 h-2 bg-[var(--text-main)]"></span> İÇERİK NOTU
                    </h3>
                    <p class="font-sans text-sm opacity-80 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($note['description'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <div class="mt-auto pt-6 pb-6">
                    <?php if ($requireCaptcha): ?>
                        <button id="dl-trigger-2" class="block w-full text-center bg-[#6D2323] text-[#FEF9E1] px-6 py-4 font-bold uppercase font-syne hover:bg-black transition-colors border-2 border-[#6D2323] hover:border-black shadow-[4px_4px_0px_rgba(109,35,35,0.3)] hover:shadow-none hover:translate-y-1">
                            DOSYAYI İNDİR [↓]
                        </button>
                    <?php else: ?>
                        <a href="/not/download/<?php echo $note['slug']; ?>" class="block w-full text-center bg-[#6D2323] text-[#FEF9E1] px-6 py-4 font-bold uppercase font-syne hover:bg-black transition-colors border-2 border-[#6D2323] hover:border-black shadow-[4px_4px_0px_rgba(109,35,35,0.3)] hover:shadow-none hover:translate-y-1">
                            DOSYAYI İNDİR [↓]
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </main>

    <?php if ($requireCaptcha): ?>
    <dialog id="downloadModal" class="fezadan-modal p-6 text-[var(--text-main)] w-full max-w-sm border-2 border-[var(--text-main)] bg-[var(--bg-paper)] shadow-[8px_8px_0px_var(--text-main)] transition-all">
        <div class="flex justify-between items-start mb-4 border-b-2 border-dashed border-[var(--text-main)]/30 pb-4">
            <div>
                <h3 class="font-syne font-bold uppercase text-lg">DOGRULAMA</h3>
                <p class="font-mono text-xs opacity-70 mt-1">İndirmeyi başlatmak için lütfen robot olmadığınızı doğrulayın.</p>
            </div>
            <button id="dl-close" class="font-mono text-xl leading-none hover:text-[var(--text-accent)] transition-colors">&times;</button>
        </div>

        <?php
            $litecaptchaBaseUrl = rtrim(env_value('LITECAPTCHA_URL', 'https://litecaptcha.fezadan.org'), '/');
            $litecaptchaEmbedUrl = $litecaptchaBaseUrl . '/?redirect=' . urlencode($downloadUrl) . '&embed=1';
        ?>

        <form action="<?= htmlspecialchars($downloadPath, ENT_QUOTES, 'UTF-8') ?>" method="GET" class="space-y-4">
            <input type="hidden" name="lc_rt" id="lc_rt" value="">
            <input type="hidden" name="lc_sig" id="lc_sig" value="">
            <input type="hidden" name="lc_exp" id="lc_exp" value="">

            <div class="litecaptcha-check-row" id="litecaptcha-check-row">
                <div class="litecaptcha-mini-brand" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 3.5 18 6v5.2c0 4-2.3 7.4-6 9.3-3.7-1.9-6-5.3-6-9.3V6l6-2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    <span>LiteCaptcha</span>
                    <span class="litecaptcha-mini-secure">secure</span>
                </div>
                <label class="litecaptcha-check-label" for="litecaptcha-check">
                    <input id="litecaptcha-check" type="checkbox">
                    <span>Ben robot değilim</span>
                </label>
                <div class="litecaptcha-check-status" id="litecaptcha-status">Hazır</div>
                <div class="litecaptcha-detail-pop" id="litecaptcha-detail">
                    Doğrulamayı başlatmak için kutuyu işaretleyin.
                </div>
                <iframe
                    class="litecaptcha-bridge"
                    src="<?= htmlspecialchars($litecaptchaEmbedUrl, ENT_QUOTES, 'UTF-8') ?>"
                    title="LiteCaptcha"
                    loading="eager"
                    referrerpolicy="no-referrer"
                    allow="clipboard-write"
                ></iframe>
            </div>

            <button type="submit" id="btn-modal-download" disabled class="block w-full text-center bg-[#6D2323] text-[#FEF9E1] px-6 py-4 font-bold uppercase font-syne hover:bg-black transition-colors border-2 border-[#6D2323] hover:border-black shadow-[4px_4px_0px_rgba(109,35,35,0.3)] hover:shadow-none hover:translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed">
                İNDİRMEYİ BAŞLAT [↓]
            </button>
        </form>
    </dialog>

    <script type="module" nonce="<?= CSP_NONCE ?>">
        import { LiteCaptchaWidget } from '/assets/js/litecaptcha.mjs';
        new LiteCaptchaWidget({
            containerSelector: '#litecaptcha-check-row',
            onSuccess: (tokens) => {
                document.getElementById('lc_rt').value = tokens.rt;
                document.getElementById('lc_sig').value = tokens.sig;
                document.getElementById('lc_exp').value = tokens.exp;
                const btn = document.getElementById('btn-modal-download');
                if (btn) btn.disabled = false;
            }
        });
    </script>
    <?php endif; ?>

    <script nonce="<?= CSP_NONCE ?>">
        const metaBtn = document.getElementById('mobile-meta-btn');
        const metaContent = document.getElementById('mobile-metadata-content');
        const arrow = metaBtn.querySelector('.arrow-icon');

        metaBtn.addEventListener('click', () => {
            metaContent.classList.toggle('open');
            arrow.classList.toggle('open');
        });
    </script>

    <script type="module" nonce="<?= CSP_NONCE ?>">
        import * as pdfjsLib from '/assets/js/pdf.js';

        const url = '<?php echo $pdfUrl; ?>';
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.js';

        const viewerContainer = document.getElementById('pdf-pages');
        const thumbContainer = document.getElementById('thumbnail-container');
        const loadingBanner = document.getElementById('loading-banner');

        const renderedPages = new Set();
        const renderingPages = new Map();
        const renderedThumbs = new Set();

        // Masaüstü Scroll Vurgulama
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    const pageNum = entry.target.dataset.page;
                    document.querySelectorAll('.thumb-wrapper').forEach(w => w.classList.remove('border-[var(--text-accent)]'));
                    document.querySelectorAll('.thumb-wrapper').forEach(w => w.classList.add('border-transparent'));

                    const activeThumb = document.getElementById('thumb-' + pageNum);
                    if(activeThumb) {
                        activeThumb.classList.remove('border-transparent');
                        activeThumb.classList.add('border-[var(--text-accent)]');
                        // Mobilde veya panel gizliyse scroll hatasını önlemek için kontrol
                        if(activeThumb.offsetParent !== null) {
                            activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                }
            });
        }, { threshold: 0.3 });

        const pageRenderObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const pageNum = parseInt(entry.target.dataset.page || '0', 10);
                if (pageNum > 0) renderPage(pageNum);
            });
        }, { rootMargin: '900px 0px', threshold: 0.01 });

        const thumbRenderObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const pageNum = parseInt(entry.target.dataset.page || '0', 10);
                if (pageNum > 0) renderThumb(pageNum);
            });
        }, { rootMargin: '400px 0px', threshold: 0.01 });

        let loadedPdf = null;
        const pageWrappers = new Map();
        const thumbWrappers = new Map();

        async function renderPage(pageNum) {
            if (!loadedPdf || renderedPages.has(pageNum)) return;
            if (renderingPages.has(pageNum)) return renderingPages.get(pageNum);

            const task = (async () => {
                const pageWrapper = pageWrappers.get(pageNum);
                if (!pageWrapper) return;

                const page = await loadedPdf.getPage(pageNum);
                const scale = 2.0;
                const viewport = page.getViewport({ scale });

                pageWrapper.innerHTML = '';
                const canvas = document.createElement('canvas');
                canvas.className = 'absolute top-0 left-0 w-full h-full object-contain';
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                pageWrapper.appendChild(canvas);

                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                const textContent = await page.getTextContent();
                const textLayer = document.createElement('div');
                textLayer.className = 'textLayer';

                textContent.items.forEach(item => {
                    const span = document.createElement('span');
                    span.textContent = item.str + ' ';
                    const xPercent = (item.transform[4] * scale / viewport.width) * 100;
                    const yPercent = ((viewport.height - (item.transform[5] * scale) - (item.height * scale)) / viewport.height) * 100;
                    const fontHeightPercent = ((item.height * scale) / viewport.height) * 100;
                    span.style.left = xPercent + '%';
                    span.style.top = yPercent + '%';
                    span.style.height = fontHeightPercent + '%';
                    span.style.fontSize = `calc(var(--wrapper-height) * ${fontHeightPercent / 100})`;
                    span.style.fontFamily = item.fontName;
                    textLayer.appendChild(span);
                });

                pageWrapper.appendChild(textLayer);
                renderedPages.add(pageNum);
                renderPage(pageNum + 1);
            })().finally(() => renderingPages.delete(pageNum));

            renderingPages.set(pageNum, task);
            return task;
        }

        async function renderThumb(pageNum) {
            if (!loadedPdf || renderedThumbs.has(pageNum)) return;
            const thumbWrapper = thumbWrappers.get(pageNum);
            if (!thumbWrapper || thumbWrapper.offsetParent === null) return;

            const canvas = thumbWrapper.querySelector('canvas');
            if (!canvas) return;

            const page = await loadedPdf.getPage(pageNum);
            const thumbViewport = page.getViewport({ scale: 0.3 });
            canvas.width = thumbViewport.width;
            canvas.height = thumbViewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: thumbViewport }).promise;
            renderedThumbs.add(pageNum);
        }

        pdfjsLib.getDocument(url).promise.then(async pdfDoc => {
            loadedPdf = pdfDoc;
            loadingBanner.style.display = 'none';
            document.getElementById('total-pages-thumb').textContent = pdfDoc.numPages;

            for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                const pageWrapper = document.createElement('div');
                pageWrapper.className = 'pdf-page-wrapper bg-white border-2 border-[var(--text-main)] shadow-[4px_4px_0px_var(--text-main)] lg:shadow-[8px_8px_0px_var(--text-main)] mb-2';
                pageWrapper.id = 'page-' + pageNum;
                pageWrapper.dataset.page = pageNum;
                pageWrapper.innerHTML = '<div class="absolute inset-0 flex items-center justify-center font-mono text-xs text-[#6D2323]/50">Sayfa yükleniyor...</div>';
                viewerContainer.appendChild(pageWrapper);
                pageWrappers.set(pageNum, pageWrapper);
                observer.observe(pageWrapper);
                pageRenderObserver.observe(pageWrapper);

                const thumbWrapper = document.createElement('div');
                thumbWrapper.id = 'thumb-' + pageNum;
                thumbWrapper.dataset.page = pageNum;
                thumbWrapper.className = 'thumb-wrapper cursor-pointer border-4 border-transparent hover:border-[var(--text-main)] transition-colors bg-white shadow-[4px_4px_0px_rgba(0,0,0,0.1)] relative';
                thumbWrapper.onclick = () => pageWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });

                const thumbCanvas = document.createElement('canvas');
                thumbCanvas.className = 'w-full h-auto block';

                const pageNumberBadge = document.createElement('div');
                pageNumberBadge.className = 'absolute bottom-0 right-0 bg-[var(--text-main)] text-[var(--bg-paper)] font-mono text-[10px] px-2 py-1 z-10';
                pageNumberBadge.textContent = pageNum;

                thumbWrapper.appendChild(thumbCanvas);
                thumbWrapper.appendChild(pageNumberBadge);
                thumbContainer.appendChild(thumbWrapper);
                thumbWrappers.set(pageNum, thumbWrapper);
                thumbRenderObserver.observe(thumbWrapper);
            }

            renderPage(1);
            renderThumb(1);

        }).catch(err => {
            console.error("PDF Yükleme Hatası:", err);
            loadingBanner.textContent = '// BELGE YÜKLENEMEDİ: CORS VEYA ERİŞİM HATASI.';
            loadingBanner.classList.remove('animate-pulse');
            loadingBanner.classList.add('bg-red-600', 'text-white');
        });
    </script>
    <script nonce="<?= CSP_NONCE ?>">
(function(){var m=document.getElementById('downloadModal');if(m){var b1=document.getElementById('dl-trigger-1'),b2=document.getElementById('dl-trigger-2'),bc=document.getElementById('dl-close');if(b1)b1.addEventListener('click',function(){m.showModal()});if(b2)b2.addEventListener('click',function(){m.showModal()});if(bc)bc.addEventListener('click',function(){m.close()})}})();
</script>
</body>
</html>
