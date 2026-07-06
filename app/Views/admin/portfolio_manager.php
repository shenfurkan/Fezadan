<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | PORTFOLYO YÖNETİMİ</title>
    <link class="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link class="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link class="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link class="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <script src="/assets/js/admin.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin.js'); ?>"></script>
    <style>
        :root {
            --bg-paper: #FEF9E1;
            --bg-secondary: #E5D0AC;
            --text-main: #6D2323;
            --text-accent: #A31D1D;
            --line-color: #6D2323;
        }
        body {
            background-color: var(--bg-paper);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
            overflow-x: hidden;
        }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        .grid-bg {
            background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.05;
            pointer-events: none;
        }
        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }

        .brutalist-input {
            width: 100%;
            background: transparent;
            border-bottom: 2px solid var(--line-color);
            padding: 10px;
            font-family: 'Space Grotesk', sans-serif;
            outline: none;
            transition: 0.3s;
            color: var(--text-main);
        }
        .brutalist-input:focus {
            background: rgba(109,35,35,0.05);
        }
        [data-theme="dark"] .brutalist-input:focus {
            background: rgba(229,208,172,0.05);
        }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; border-left: 1px dashed rgba(109,35,35,0.2); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(109,35,35,0.5); border-radius: 0px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(109,35,35,1); }

        /* Sürükleme katmanı animasyon stilleri */
        #global-drag-overlay.show {
            opacity: 1 !important;
            pointer-events: all !important;
        }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="flex flex-col xl:flex-row gap-8 h-full pb-6 w-full">
            
        <!-- Sol Kısım: Yeni Öge Ekleme -->
        <div class="w-full xl:w-5/12 flex-shrink-0">
            <h3 class="font-syne text-xl font-bold uppercase mb-4 flex items-center gap-2 text-[var(--text-main)]">
                <span class="w-3 h-3 bg-[#6D2323]"></span> Yeni Öğe Ekle
            </h3>
            
            <form action="/yonetim/store-portfolio" method="POST" enctype="multipart/form-data" class="border-2 border-[var(--text-main)] p-6 shadow-[8px_8px_0px_#A31D1D] bg-[var(--bg-paper)] space-y-4 max-h-[calc(100vh-170px)] overflow-y-auto custom-scrollbar">
                <?= Csrf::field() ?>
                
                <!-- Form içi modern sürükle bırak alanı -->
                <div class="space-y-2">
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Görsel Yükle (En Yüksek Kalite Orijinal)*</label>
                    
                    <div id="form-dropzone" class="border-2 border-dashed border-[var(--line-color)] p-8 text-center cursor-pointer rounded transition-all hover:bg-[var(--line-color)]/5 flex flex-col items-center justify-center gap-2">
                        <svg class="w-8 h-8 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="text-xs font-mono font-bold uppercase" id="dropzone-text">Görsel Seç veya Sürükle</span>
                        <span class="text-[10px] font-mono opacity-50">(Max 20MB)</span>
                        <input type="file" name="image" id="image-input" accept="image/*" class="hidden" required>
                    </div>
                    
                    <div id="image-preview-container" class="hidden border-2 border-[var(--line-color)] p-2 bg-white relative rounded">
                        <img id="image-preview" src="#" alt="Preview" class="max-h-48 w-full object-cover">
                        <button type="button" id="remove-preview-btn" class="absolute top-2 right-2 bg-red-600 text-white font-mono font-bold text-[9px] p-1 px-2 hover:bg-black transition-all">SİL</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Başlık (TR)*</label>
                        <input type="text" name="title_tr" id="title-tr-input" class="brutalist-input font-bold text-sm" placeholder="e.g. Dolunay Altında" required autocomplete="off">
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Başlık (EN)</label>
                        <input type="text" name="title_en" class="brutalist-input font-bold text-sm" placeholder="e.g. Under the Full Moon" autocomplete="off">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Açıklama (TR)</label>
                        <textarea name="description_tr" rows="3" class="brutalist-input text-xs" placeholder="Türkçe açıklama yazın..." autocomplete="off"></textarea>
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Açıklama (EN)</label>
                        <textarea name="description_en" rows="3" class="brutalist-input text-xs" placeholder="İngilizce açıklama..." autocomplete="off"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Tür*</label>
                        <select name="type" class="brutalist-input text-xs" required>
                            <option value="photo">Fotoğraf</option>
                            <option value="drawing">Çizim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Gösterim Sırası</label>
                        <input type="number" name="display_order" class="brutalist-input text-xs" value="0" required min="0">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-[#6D2323] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all">
                    YÜKLE VE KAYDET [+]
                </button>

                <!-- Bildirimler -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="text-xs font-mono text-center text-red-600 font-bold bg-red-100 p-2 rounded">
                        ⚠ <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="text-xs font-mono text-center text-green-700 font-bold bg-green-100 p-2 rounded">
                        ✓ <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Sağ Kısım: Mevcut Ögelerin Listesi -->
        <div class="w-full xl:w-7/12 flex flex-col h-full min-h-0">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <h3 class="font-syne text-xl font-bold uppercase flex items-center gap-2 text-[var(--text-main)]">
                    <span class="w-3 h-3 bg-[#6D2323]"></span> Portfolyo Havuzu
                </h3>
                <button type="button" id="saveOrderBtn" class="px-4 py-2 border-2 border-[var(--text-main)] text-[var(--text-main)] font-mono text-[10px] font-bold uppercase hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-all opacity-50 cursor-not-allowed" disabled>
                    Sıralamayı Kaydet
                </button>
            </div>

            <div class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] overflow-y-auto custom-scrollbar flex-1 shadow-[8px_8px_0px_rgba(163,29,29,0.1)] h-[calc(100vh-170px)]">
                <table class="w-full text-left font-mono text-xs border-collapse">
                    <thead class="bg-[#6D2323] text-[#FEF9E1] uppercase sticky top-0 z-20">
                        <tr>
                            <th class="p-3 border-b border-[var(--text-main)] w-12 text-center">Sürükle</th>
                            <th class="p-3 border-b border-[var(--text-main)] w-16">Görsel</th>
                            <th class="p-3 border-b border-[var(--text-main)]">Başlık (TR/EN)</th>
                            <th class="p-3 border-b border-[var(--text-main)] w-24">Tür</th>
                            <th class="p-3 border-b border-[var(--text-main)] w-24">Sıra</th>
                            <th class="p-3 border-b border-[var(--text-main)] w-20 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--text-main)]/10">
                        <?php if (!empty($items)): foreach ($items as $item): 
                            $thumbUrl = Upload::assetUrl($item['image_url']);
                        ?>
                        <tr class="hover:bg-[var(--bg-secondary)]/30 transition-colors draggable-row cursor-grab" draggable="true" data-id="<?= (int)$item['id'] ?>">
                            <td class="p-3 text-center drag-handle select-none text-base opacity-40 hover:opacity-100 transition-opacity">☰</td>
                            <td class="p-3">
                                <a href="<?= htmlspecialchars($thumbUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="" class="w-10 h-10 object-cover border border-[var(--line-color)] rounded pointer-events-none">
                                </a>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-[var(--text-accent)] text-sm">
                                    <?= htmlspecialchars($item['title_tr']) ?>
                                </div>
                                <?php if (!empty($item['title_en'])): ?>
                                    <div class="opacity-50 text-[10px]">
                                        <?= htmlspecialchars($item['title_en']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-bold uppercase text-[10px]">
                                <span class="px-2 py-1 rounded bg-[var(--text-main)]/10 text-[var(--text-main)]">
                                    <?= $item['type'] === 'drawing' ? 'Çizim' : 'Fotoğraf' ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <input type="number" 
                                       class="w-16 p-1 border border-[var(--line-color)] bg-transparent text-center order-input" 
                                       data-id="<?= (int)$item['id'] ?>"
                                       value="<?= (int)$item['display_order'] ?>"
                                       min="0">
                            </td>
                            <td class="p-3 text-right flex gap-2 justify-end">
                                <a href="/yonetim/portfolio-edit?id=<?= (int)$item['id'] ?>" class="text-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] px-2 py-1 transition-colors font-bold border border-[var(--line-color)]">DÜZENLE</a>
                                <form method="POST" action="/yonetim/delete-portfolio"
                                      data-confirm="Bu portfolyo öğesini silmek istediğinize emin misiniz? Görsel R2 sunucusundan da tamamen silinecek."
                                      class="inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="text-[var(--text-accent)] hover:bg-[var(--text-accent)] hover:text-white px-2 py-1 transition-colors font-bold border border-[var(--text-accent)]">
                                        [SİL]
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="p-8 text-center opacity-50 uppercase font-mono italic">// Henüz portfolyo öğesi eklenmedi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    
    <!-- ═══════════════════════════════════════════════════════════
         GLOBAL PAGE-LEVEL DRAG OVERLAYS
         ═══════════════════════════════════════════════════════════ -->
    <!-- Drag over overlay -->
    <div id="global-drag-overlay" class="fixed inset-0 bg-black/85 backdrop-blur-md z-[99999] flex flex-col items-center justify-center gap-4 text-[#FEF9E1] transition-opacity duration-300 opacity-0 pointer-events-none">
        <div class="border-4 border-dashed border-[var(--bg-secondary)] p-12 rounded-xl flex flex-col items-center justify-center gap-4 max-w-lg text-center m-4 pointer-events-none">
            <svg class="w-16 h-16 animate-bounce text-[var(--bg-secondary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <h2 class="font-syne text-2xl font-bold uppercase">Yüklemek İçin Görseli Bırak</h2>
            <p class="font-mono text-xs opacity-75">Bırakılan görsel otomatik olarak portfolyoya yüklenecek ve dosya adı başlık olarak atanacak.</p>
        </div>
    </div>
    
    <!-- Upload progress spinner overlay -->
    <div id="global-uploading-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[99999] flex flex-col items-center justify-center gap-4 text-[#FEF9E1] hidden">
        <div class="flex flex-col items-center justify-center gap-4">
            <div class="w-12 h-12 border-4 border-dashed border-[var(--bg-secondary)] border-t-[var(--text-accent)] rounded-full animate-spin"></div>
            <h3 class="font-syne text-lg font-bold uppercase tracking-wider">Portfolyo Öğesi Yükleniyor...</h3>
            <p class="font-mono text-[10px] opacity-60">Görsel bulut depolamaya (Cloudflare R2) kaydediliyor, lütfen pencereyi kapatmayın.</p>
        </div>
    </div>

</main>

<script nonce="<?= CSP_NONCE ?>">
document.addEventListener('DOMContentLoaded', () => {
    const saveOrderBtn = document.getElementById('saveOrderBtn');
    const orderInputs = document.querySelectorAll('.order-input');
    const tbody = document.querySelector('tbody');
    let draggedRow = null;
    
    // Herhangi bir input elle değiştiğinde kaydet butonunu etkinleştir
    orderInputs.forEach(input => {
        input.addEventListener('change', () => {
            enableSaveButton();
        });
    });

    function enableSaveButton() {
        saveOrderBtn.disabled = false;
        saveOrderBtn.style.opacity = '1';
        saveOrderBtn.classList.remove('cursor-not-allowed');
    }

    // Tablo satırları için sürükle bırak uygulaması
    tbody.addEventListener('dragstart', (e) => {
        const row = e.target.closest('tr');
        if (row && row.classList.contains('draggable-row')) {
            draggedRow = row;
            row.classList.add('opacity-40', 'bg-[var(--bg-secondary)]/50');
            e.dataTransfer.effectAllowed = 'move';
        }
    });

    tbody.addEventListener('dragend', (e) => {
        if (draggedRow) {
            draggedRow.classList.remove('opacity-40', 'bg-[var(--bg-secondary)]/50');
            draggedRow = null;
        }
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const targetRow = e.target.closest('tr');
        if (targetRow && targetRow !== draggedRow && targetRow.classList.contains('draggable-row') && targetRow.parentElement === tbody) {
            const rect = targetRow.getBoundingClientRect();
            const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
            tbody.insertBefore(draggedRow, next ? targetRow.nextSibling : targetRow);
            
            recalculateDisplayOrders();
        }
    });

    function recalculateDisplayOrders() {
        const rows = tbody.querySelectorAll('.draggable-row');
        rows.forEach((row, index) => {
            const input = row.querySelector('.order-input');
            if (input) {
                input.value = (index + 1) * 10;
            }
        });
        enableSaveButton();
    }

    saveOrderBtn.addEventListener('click', () => {
        const payload = new URLSearchParams();
        const freshInputs = document.querySelectorAll('.order-input');

        // Düz anahtar→değer yapısı: orders=1:10&orders=2:20
        // Backend $_POST verisini $id => $orderVal şeklinde işler
        freshInputs.forEach(input => {
            const id = input.getAttribute('data-id');
            const val = input.value;
            payload.append('orders[' + id + ']', val);
        });

        // CSRF token
        const csrfToken = document.querySelector('input[name="_csrf"]').value;
        payload.append('_csrf', csrfToken);

        saveOrderBtn.disabled = true;
        saveOrderBtn.textContent = 'KAYDEDİLİYOR...';

        const orderRequest = window.FezadanFetch ? window.FezadanFetch('/yonetim/portfolio-reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString()
        }) : fetch('/yonetim/portfolio-reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString()
        }).then(response => {
            if (!response.ok) throw new Error('Sıralama güncellenemedi.');
            return response.json();
        });
        orderRequest
        .then(data => {
            if (data.success) {
                if (window.FezadanToast) window.FezadanToast.success('Sıralama başarıyla güncellendi!');
                else alert('Sıralama başarıyla güncellendi!');
                location.reload();
            } else {
                const message = 'Hata: ' + (data.error || 'Bilinmeyen bir hata oluştu.');
                if (window.FezadanToast) window.FezadanToast.error(message);
                else alert(message);
                saveOrderBtn.disabled = false;
                saveOrderBtn.textContent = 'Sıralamayı Kaydet';
            }
        })
        .catch(err => {
            if (!window.FezadanFetch) alert('Ağ hatası: ' + err.message);
            saveOrderBtn.disabled = false;
            saveOrderBtn.textContent = 'Sıralamayı Kaydet';
        });
    });

    /* ── Localized Form Dropzone & Preview Behaviors ── */
    const formDropzone = document.getElementById('form-dropzone');
    const imageInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('image-preview');
    const removePreviewBtn = document.getElementById('remove-preview-btn');
    const titleTrInput = document.getElementById('title-tr-input');

    if (formDropzone && imageInput) {
        formDropzone.addEventListener('click', () => imageInput.click());

        formDropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            formDropzone.style.backgroundColor = 'rgba(109, 35, 35, 0.08)';
        });

        formDropzone.addEventListener('dragleave', () => {
            formDropzone.style.backgroundColor = '';
        });

        formDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            formDropzone.style.backgroundColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                handleFileSelected(files[0]);
            }
        });

        imageInput.addEventListener('change', () => {
            if (imageInput.files.length > 0) {
                handleFileSelected(imageInput.files[0]);
            }
        });

        const handleFileSelected = (file) => {
            if (!file.type.startsWith('image/')) {
                if (window.FezadanToast) window.FezadanToast.error('Lütfen geçerli bir görsel yükleyin.');
                else alert('Lütfen geçerli bir görsel yükleyin.');
                return;
            }
            
            // Önizlemeyi oku ve göster
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                formDropzone.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);

            // Başlık alanı boşsa otomatik doldur
            if (titleTrInput && titleTrInput.value.trim() === '') {
                titleTrInput.value = cleanFilenameToTitle(file.name);
            }
        };

        removePreviewBtn.addEventListener('click', () => {
            imageInput.value = '';
            previewImage.src = '#';
            previewContainer.classList.add('hidden');
            formDropzone.classList.remove('hidden');
        });
    }

    /* ── Dosya adını başlığa çeviren yardımcı ── */
    function cleanFilenameToTitle(filename) {
        let title = filename.substring(0, filename.lastIndexOf('.')) || filename;
        title = title.replace(/[-_]/g, ' ');
        title = title.replace(/\s+/g, ' ');
        return title.trim().replace(/\b\w/g, c => c.toUpperCase());
    }

    /* ── Sayfa genelinde sürükle bırak yükleyici ── */
    const globalDragOverlay = document.getElementById('global-drag-overlay');
    const uploadingOverlay = document.getElementById('global-uploading-overlay');
    let dragCounter = 0;

    if (globalDragOverlay && uploadingOverlay) {
        window.addEventListener('dragenter', (e) => {
            e.preventDefault();
            dragCounter++;
            if (dragCounter === 1) {
                globalDragOverlay.classList.add('show');
            }
        });

        window.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dragCounter--;
            if (dragCounter === 0) {
                globalDragOverlay.classList.remove('show');
            }
        });

        window.addEventListener('dragover', (e) => {
            e.preventDefault(); // Required to trigger 'drop'
        });

        window.addEventListener('drop', (e) => {
            e.preventDefault();
            dragCounter = 0;
            globalDragOverlay.classList.remove('show');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadFileDirectly(files[0]);
            }
        });

        function uploadFileDirectly(file) {
            if (!file.type.startsWith('image/')) {
                if (window.FezadanToast) window.FezadanToast.error('Lütfen geçerli bir görsel yükleyin.');
                else alert('Lütfen geçerli bir görsel yükleyin.');
                return;
            }

            uploadingOverlay.classList.remove('hidden');

            const title = cleanFilenameToTitle(file.name);
            const csrfToken = document.querySelector('input[name="_csrf"]').value;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('title_tr', title);
            formData.append('type', 'photo'); // Varsayılan yükleme türü
            formData.append('display_order', 0);
            formData.append('_csrf', csrfToken);

            const uploadRequest = window.FezadanFetch ? window.FezadanFetch('/yonetim/store-portfolio', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }) : fetch('/yonetim/store-portfolio', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => {
                if (!response.ok) throw new Error('Dosya veritabanına yüklenemedi.');
                return response.text();
            });
            uploadRequest
            .then(data => {
                if (data && data.success === false) {
                    throw new Error(data.error || 'Dosya veritabanına yüklenemedi.');
                }
                location.reload();
            })
            .catch(err => {
                uploadingOverlay.classList.add('hidden');
                if (!window.FezadanFetch) alert('Bir hata oluştu: ' + err.message);
            });
        }
    }
});
</script>

</body>
</html>
