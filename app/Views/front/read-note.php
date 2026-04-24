<?php 
    $page_title = htmlspecialchars($note['title']);
    include __DIR__ . '/../inc/notes_header.php'; 
?>
    
    <style>
        /* Ana Gövde Sabitleme (Sadece masaüstünde kaydırmayı kilitleriz) */
        @media (min-width: 1024px) {
            body { overflow: hidden; }
        }
        
        /* ÖZEL SCROLLBAR TASARIMI (Brutalist Stil) */
        * {
            scrollbar-width: auto;
            scrollbar-color: var(--text-main) var(--bg-paper);
        }

        .custom-scrollbar::-webkit-scrollbar { width: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: var(--bg-paper); border-left: 2px solid var(--text-main); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--text-main); border: 2px solid var(--bg-paper); }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--text-accent); }

        /* Metin Katmanı Ayarları (PDF.js Selectable Text) */
        .textLayer { position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow: hidden; line-height: 1.0; z-index: 10; }
        .textLayer span { position: absolute; color: transparent; cursor: text; transform-origin: 0% 0%; white-space: pre; }
        .textLayer span::selection { color: var(--bg-paper); background-color: var(--text-accent); }

        /* --- MOBİL İÇİN METADATA ACCORDION ANİMASYONLARI --- */
        #mobile-metadata-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s ease-in-out;
        }
        #mobile-metadata-content.open {
            grid-template-rows: 1fr;
        }
        .metadata-inner { overflow: hidden; }
        
        .arrow-icon { transition: transform 0.3s ease; }
        .arrow-icon.open { transform: rotate(180deg); }

        /* PDF Sayfa Boyutlandırması (Responsive Scale) */
        .pdf-page-wrapper {
            transform-origin: top center;
            width: 100% !important; /* JS'ten gelen width'i eziyoruz */
            height: auto !important; /* JS'ten gelen height'ı eziyoruz */
            max-width: 800px; /* Maksimum genişlik */
            aspect-ratio: 1 / 1.414; /* A4 oranı */
            position: relative;
        }
        
        /* Canvas'ı Wrapper'a uydurma */
        .pdf-page-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain;
        }
    </style>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'rate_limit'): ?>
    <div class="w-full bg-[#6D2323] text-[#FEF9E1] p-4 text-center font-mono font-bold uppercase text-sm border-b-2 border-[#FEF9E1] relative z-50">
        // SİSTEM UYARISI: ÇOK FAZLA İSTEK GÖNDERİLDİ. LÜTFEN 1 DAKİKA BEKLEYİP TEKRAR DENEYİN.
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
                    <a href="/not/download/<?php echo $note['slug']; ?>" class="flex-1 text-center bg-[#6D2323] text-[#FEF9E1] py-3 font-bold uppercase font-syne text-sm border-2 border-[#6D2323] hover:bg-black hover:border-black transition-colors">
                        İNDİR [↓]
                    </a>
                </div>
            </div>
        </div>
    </div>

    <main class="flex flex-col lg:flex-row w-full lg:border-t-2 border-[var(--text-main)]" style="min-height: calc(100vh - 85px);">
        
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
                    <a href="/not/download/<?php echo $note['slug']; ?>" class="block w-full text-center bg-[#6D2323] text-[#FEF9E1] px-6 py-4 font-bold uppercase font-syne hover:bg-black transition-colors border-2 border-[#6D2323] hover:border-black shadow-[4px_4px_0px_rgba(109,35,35,0.3)] hover:shadow-none hover:translate-y-1">
                        DOSYAYI İNDİR [↓]
                    </a>
                </div>
            </div>
        </aside>
    </main>

    <script>
        const metaBtn = document.getElementById('mobile-meta-btn');
        const metaContent = document.getElementById('mobile-metadata-content');
        const arrow = metaBtn.querySelector('.arrow-icon');

        metaBtn.addEventListener('click', () => {
            metaContent.classList.toggle('open');
            arrow.classList.toggle('open');
        });
    </script>

    <script type="module">
        import * as pdfjsLib from '/assets/js/pdf.mjs';

        const url = '<?php echo $pdfUrl; ?>';
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.mjs';
        pdfjsLib.GlobalWorkerOptions.wasmUrl = '/assets/js/pdf.worker.wasm'; 

        const viewerContainer = document.getElementById('pdf-pages');
        const thumbContainer = document.getElementById('thumbnail-container');
        const loadingBanner = document.getElementById('loading-banner');
        
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

        pdfjsLib.getDocument(url).promise.then(async pdfDoc => {
            loadingBanner.style.display = 'none';
            document.getElementById('total-pages-thumb').textContent = pdfDoc.numPages;

            for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                const page = await pdfDoc.getPage(pageNum);
                
                // Yüksek çözünürlük (scale: 2.0) alıp CSS ile daraltarak daha net (retina) görüntü sağlıyoruz
                const scale = 2.0; 
                const viewport = page.getViewport({ scale });

                const pageWrapper = document.createElement('div');
                // 'pdf-page-wrapper' sınıfı CSS tarafında responsive davranışı sağlar
                pageWrapper.className = 'pdf-page-wrapper bg-white border-2 border-[var(--text-main)] shadow-[4px_4px_0px_var(--text-main)] lg:shadow-[8px_8px_0px_var(--text-main)] mb-2';
                pageWrapper.id = 'page-' + pageNum;
                pageWrapper.dataset.page = pageNum;

                const canvas = document.createElement('canvas');
                canvas.className = 'absolute top-0 left-0 w-full h-full object-contain';
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                pageWrapper.appendChild(canvas);

                const renderContext = { canvasContext: canvas.getContext('2d'), viewport: viewport };
                await page.render(renderContext).promise;

                // TEXT LAYER (Kopyalanabilir Metin)
                const textContent = await page.getTextContent();
                const textLayer = document.createElement('div');
                textLayer.className = 'textLayer';
                
                textContent.items.forEach(item => {
                    const span = document.createElement('span');
                    span.textContent = item.str + ' ';
                    
                    // Metni responsive yapmak için yüzde (%) değerlerine çeviriyoruz
                    let xPercent = (item.transform[4] * scale / viewport.width) * 100;
                    let yPercent = ((viewport.height - (item.transform[5] * scale) - (item.height * scale)) / viewport.height) * 100;
                    let fontHeightPercent = ((item.height * scale) / viewport.height) * 100;

                    span.style.left = xPercent + '%';
                    span.style.top = yPercent + '%';
                    // CSS 'vh' veya yüzde ile font boyutunu ayarlıyoruz (kabaca div yüksekliğine oranla)
                    span.style.height = fontHeightPercent + '%';
                    span.style.fontSize = `calc(var(--wrapper-height) * ${fontHeightPercent / 100})`;
                    span.style.fontFamily = item.fontName;
                    
                    textLayer.appendChild(span);
                });
                
                pageWrapper.appendChild(textLayer);
                viewerContainer.appendChild(pageWrapper);
                observer.observe(pageWrapper);

                // THUMBNAILS (Sadece Sol Panel Açıkken İşlenmesi Mantıklı Ama Cache İçin Oluşturuyoruz)
                const thumbScale = 0.3;
                const thumbViewport = page.getViewport({ scale: thumbScale });
                
                const thumbWrapper = document.createElement('div');
                thumbWrapper.id = 'thumb-' + pageNum;
                thumbWrapper.className = 'thumb-wrapper cursor-pointer border-4 border-transparent hover:border-[var(--text-main)] transition-colors bg-white shadow-[4px_4px_0px_rgba(0,0,0,0.1)] relative';
                thumbWrapper.onclick = () => pageWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });

                const thumbCanvas = document.createElement('canvas');
                thumbCanvas.className = 'w-full h-auto block';
                thumbCanvas.width = thumbViewport.width;
                thumbCanvas.height = thumbViewport.height;
                
                const pageNumberBadge = document.createElement('div');
                pageNumberBadge.className = 'absolute bottom-0 right-0 bg-[var(--text-main)] text-[var(--bg-paper)] font-mono text-[10px] px-2 py-1 z-10';
                pageNumberBadge.textContent = pageNum;

                thumbWrapper.appendChild(thumbCanvas);
                thumbWrapper.appendChild(pageNumberBadge);
                thumbContainer.appendChild(thumbWrapper);

                page.render({ canvasContext: thumbCanvas.getContext('2d'), viewport: thumbViewport });
            }

        }).catch(err => {
            console.error("PDF Yükleme Hatası:", err);
            loadingBanner.textContent = '// BELGE YÜKLENEMEDİ: BAĞLANTI HATASI.';
            loadingBanner.classList.remove('animate-pulse');
            loadingBanner.classList.add('bg-red-600', 'text-white');
        });
    </script>
</body>
</html>