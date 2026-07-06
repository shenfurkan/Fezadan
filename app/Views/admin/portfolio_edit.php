<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | PORTFOLYO DÜZENLE</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
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
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>

    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="flex flex-col xl:flex-row gap-8 h-full pb-6 w-full">

        <!-- Sol Kısım: Düzenleme Formu -->
        <div class="w-full xl:w-5/12 flex-shrink-0">
            <div class="flex items-center gap-4 mb-6">
                <a href="/yonetim/portfolio" class="text-xs font-mono uppercase opacity-60 hover:opacity-100 transition-opacity">← Geri</a>
                <h3 class="font-syne text-xl font-bold uppercase flex items-center gap-2 text-[var(--text-main)]">
                    <span class="w-3 h-3 bg-[#A31D1D]"></span> Portfolyo Düzenle
                </h3>
            </div>

            <form action="/yonetim/update-portfolio" method="POST" enctype="multipart/form-data" class="border-2 border-[var(--text-main)] p-6 shadow-[8px_8px_0px_#A31D1D] bg-[var(--bg-paper)] space-y-4 max-h-[calc(100vh-170px)] overflow-y-auto custom-scrollbar">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                <!-- Mevcut görsel önizleme -->
                <?php if (!empty($item['image_url'])): ?>
                <div class="border-2 border-[var(--line-color)] p-2 bg-white relative">
                    <img src="<?= htmlspecialchars(Upload::assetUrl($item['image_url'])) ?>" alt="<?= htmlspecialchars($item['title_tr']) ?>" class="max-h-48 w-full object-cover">
                    <div class="mt-2 text-xs font-mono opacity-60">Mevcut görsel — yeni görsel yüklenirse değiştirilecek.</div>
                </div>
                <?php endif; ?>

                <!-- Görsel Yükle (Değiştirme için boş bırakılabilir) -->
                <div class="space-y-2">
                    <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Görseli Değiştir (İsteğe Bağlı)</label>
                    <div id="form-dropzone" class="border-2 border-dashed border-[var(--line-color)] p-8 text-center cursor-pointer rounded transition-all hover:bg-[var(--line-color)]/5 flex flex-col items-center justify-center gap-2">
                        <svg class="w-8 h-8 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="text-xs font-mono font-bold uppercase" id="dropzone-text">Yeni Görsel Seç veya Sürükle</span>
                        <span class="text-[10px] font-mono opacity-50">(Max 20MB)</span>
                        <input type="file" name="image" id="image-input" accept="image/*" class="hidden">
                    </div>
                    <div id="image-preview-container" class="hidden border-2 border-[var(--line-color)] p-2 bg-white relative rounded">
                        <img id="image-preview" src="#" alt="Preview" class="max-h-48 w-full object-cover">
                        <button type="button" id="remove-preview-btn" class="absolute top-2 right-2 bg-red-600 text-white font-mono font-bold text-[9px] p-1 px-2 hover:bg-black transition-all">SİL</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Başlık (TR)*</label>
                        <input type="text" name="title_tr" id="title-tr-input" class="brutalist-input font-bold text-sm" placeholder="e.g. Dolunay Altında" required autocomplete="off" value="<?= htmlspecialchars($item['title_tr']) ?>">
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Başlık (EN)</label>
                        <input type="text" name="title_en" class="brutalist-input font-bold text-sm" placeholder="e.g. Under the Full Moon" autocomplete="off" value="<?= htmlspecialchars($item['title_en'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Açıklama (TR)</label>
                        <textarea name="description_tr" rows="3" class="brutalist-input text-xs" placeholder="Türkçe açıklama yazın..." autocomplete="off"><?= htmlspecialchars($item['description_tr'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Açıklama (EN)</label>
                        <textarea name="description_en" rows="3" class="brutalist-input text-xs" placeholder="İngilizce açıklama..." autocomplete="off"><?= htmlspecialchars($item['description_en'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Tür*</label>
                        <select name="type" class="brutalist-input text-xs" required>
                            <option value="photo" <?= ($item['type'] ?? 'photo') === 'photo' ? 'selected' : '' ?>>Fotoğraf</option>
                            <option value="drawing" <?= ($item['type'] ?? '') === 'drawing' ? 'selected' : '' ?>>Çizim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase opacity-60 mb-1">Gösterim Sırası</label>
                        <input type="number" name="display_order" class="brutalist-input text-xs" value="<?= (int)($item['display_order'] ?? 0) ?>" required min="0">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-[#A31D1D] text-[#FEF9E1] font-bold uppercase hover:bg-black transition-all">
                    GÜNCELLE
                </button>

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

    </div>

<script nonce="<?= CSP_NONCE ?>">
document.addEventListener('DOMContentLoaded', () => {
    const formDropzone = document.getElementById('form-dropzone');
    const imageInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('image-preview');
    const removePreviewBtn = document.getElementById('remove-preview-btn');

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
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                formDropzone.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        };

        removePreviewBtn.addEventListener('click', () => {
            imageInput.value = '';
            previewImage.src = '#';
            previewContainer.classList.add('hidden');
            formDropzone.classList.remove('hidden');
        });
    }
});
</script>

</body>
</html>
