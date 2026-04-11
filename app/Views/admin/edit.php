<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>FEZADAN | EDİTÖR</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <style>
        :root {
            --bg-paper: #FEF9E1;
            --text-main: #6D2323;
            --line-color: #6D2323;
        }

        body {
            background-color: var(--bg-paper);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
        }

        .brutalist-input {
            width: 100%;
            background: rgba(109, 35, 35, 0.05);
            border-bottom: 2px solid var(--line-color);
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
            transition: 0.3s;
        }

        .brutalist-input:focus {
            background: rgba(109, 35, 35, 0.08);
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden">

    <aside class="w-20 md:w-72 border-r-2 border-[var(--text-main)] p-6 flex flex-col bg-[var(--bg-paper)]">
        <h1 class="font-syne text-3xl font-bold mb-12 hidden md:block">FEZADAN</h1>
        <nav class="flex flex-col gap-4">
            <a href="/admin/dashboard" class="font-bold uppercase text-sm hover:underline">← Vazgeç / Geri Dön</a>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-12 overflow-y-auto">
        <h2 class="font-syne text-4xl font-bold uppercase mb-8 flex items-center gap-3">
            <span class="w-4 h-4 bg-[#6D2323]"></span> MAKALE DÜZENLE
        </h2>

        <form action="/admin/update" method="POST" enctype="multipart/form-data" class="max-w-4xl space-y-8 pb-20">

            <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
            <input type="hidden" name="current_image" value="<?php echo $article['image_url']; ?>">

            <div>
                <label class="block font-syne font-bold uppercase text-xs mb-2">Makale Başlığı</label>
                <input type="text" name="title" class="brutalist-input text-2xl font-bold"
                    value="<?php echo htmlspecialchars($article['title']); ?>" required>
            </div>

            <div class="mb-6">
                <label
                    class="block font-syne font-bold uppercase text-xs mb-3 text-[var(--text-main)]">Kategoriler</label>
                <div
                    class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 border-2 border-[var(--text-main)]/20 bg-[#6D2323]/5">
                    <?php if (!empty($categories)):
    foreach ($categories as $cat): ?>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"
                            class="w-4 h-4 accent-[#A31D1D] cursor-pointer" <?php echo in_array($cat['id'],
            $selectedCategories) ? 'checked' : ''; ?>>
                        <span
                            class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] 
                                         <?php echo in_array($cat['id'], $selectedCategories) ? 'font-bold text-[var(--text-accent)]' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </span>
                    </label>
                    <?php
    endforeach;
else: ?>
                    <p class="text-[10px] opacity-50 uppercase">Kategori bulunamadı.</p>
                    <?php
endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Kısa Açıklama (Spot)</label>
                    <textarea name="desc" rows="4" class="brutalist-input"
                        required><?php echo htmlspecialchars($article['short_desc']); ?></textarea>
                </div>
                <div>
                    <label class="block font-syne font-bold uppercase text-xs mb-2">Yazar Seçimi</label>
                    <select name="author_id" class="brutalist-input">
                        <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id']; ?>" <?php echo ($article['author_id'] == $author['id'])
        ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                        <?php
endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-syne font-bold uppercase text-xs mb-2">Kapak Görseli (Değiştirmek için dosya
                    seçin)</label>
                <input type="file" name="cover_image" class="brutalist-input mb-2">
                <?php if (!empty($article['image_url'])): ?>
                <p class="text-[10px] font-mono opacity-60 mb-2">Mevcut Görsel:</p>
                <img src="<?php echo $article['image_url']; ?>" class="h-20 border border-[var(--text-main)]">
                <?php
endif; ?>
            </div>

            <div>
                <label class="block font-syne font-bold uppercase text-xs mb-2">İçerik</label>
                <textarea id="summernote" name="content"><?php echo $article['content']; ?></textarea>
            </div>
            <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                <label class="block font-syne font-bold uppercase text-xs mb-2">Kaynakça & Referanslar</label>
                <p class="text-[10px] opacity-60 font-mono mb-2">Format: "1=Link" veya düz metin. Her satıra yeni bir
                    kaynak.</p>
                <textarea name="refs" rows="6"
                    class="brutalist-input font-mono text-sm"><?php echo htmlspecialchars($article['refs'] ?? ''); ?></textarea>
            </div>

            <button type="submit"
                class="w-full py-5 bg-[#6D2323] text-[#FEF9E1] font-bold text-xl uppercase hover:bg-black transition-all shadow-[8px_8px_0px_#A31D1D]">
                GÜNCELLEMELERİ KAYDET
            </button>
        </form>
    </main>

    <script>
        $('#summernote').summernote({
            placeholder: 'Yazmaya başla...',
            tabsize: 2,
            height: 400,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onKeydown: function (e) {
                    if (e.keyCode === 9) {
                        e.preventDefault();
                        $(this).summernote('pasteHTML', '&nbsp;&nbsp;&nbsp;&nbsp;');
                    }
                },
                onImageUpload: function (files) {
                    for (let i = 0; i < files.length; i++) {
                        uploadImage(files[i]);
                    }
                },
                onPaste: function (e) {
                    var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                    e.preventDefault();
                    // Clean up Word content roughly
                    document.execCommand('insertText', false, bufferText);
                }
            }
        });

        function uploadImage(file) {
            let data = new FormData();
            data.append("file", file);
            $.ajax({
                data: data,
                type: "POST",
                url: "/admin/upload-content-image",
                cache: false,
                contentType: false,
                processData: false,
                success: function (url) {
                    if (url.includes('error')) {
                        alert('Hata: ' + url);
                    } else {
                        $('#summernote').summernote('insertImage', url);
                    }
                },
                error: function (data) {
                    console.log(data);
                    alert("Resim yüklenirken bir hata oluştu.");
                }
            });
        }
    </script>
</body>

</html>