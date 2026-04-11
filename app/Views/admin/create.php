<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>FEZADAN | Editör Pro</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>

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

        .font-syne {
            font-family: 'Syne', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .note-editor.note-frame {
            border: 2px solid var(--line-color) !important;
            border-radius: 0;
            box-shadow: 8px 8px 0px rgba(109, 35, 35, 0.1);
        }

        .note-toolbar {
            background-color: #E5D0AC !important;
            border-bottom: 2px solid var(--line-color) !important;
        }

        .note-statusbar {
            display: none !important;
        }

        .note-editable {
            background-color: #fff !important;
            min-height: 500px !important;
        }

        input,
        select,
        textarea {
            background: transparent;
            border: 2px solid var(--line-color);
            padding: 1rem;
            width: 100%;
            font-family: 'Space Grotesk', sans-serif;
            outline: none;
            transition: all 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            background: rgba(229, 208, 172, 0.3);
        }

        .btn-action {
            background: var(--text-main);
            color: #FEF9E1;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            border: 2px solid var(--text-main);
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: transparent;
            color: var(--text-main);
            box-shadow: 6px 6px 0px var(--text-main);
            transform: translate(-2px, -2px);
        }

        #loadingOverlay {
            backdrop-filter: blur(5px);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #FEF9E1;
            border-top: 5px solid #A31D1D;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col relative">

    <div id="loadingOverlay"
        class="hidden fixed inset-0 bg-[#6D2323]/90 z-[9999] flex flex-col items-center justify-center text-[#FEF9E1]">
        <div class="spinner mb-4"></div>
        <h2 class="font-syne text-2xl font-bold tracking-widest animate-pulse">VERİLER İŞLENİYOR...</h2>
        <p class="font-mono text-xs mt-2 opacity-80">Lütfen bekleyiniz...</p>
    </div>

    <header
        class="h-20 border-b-2 border-[var(--text-main)] flex justify-between items-center px-6 md:px-12 sticky top-0 bg-[var(--bg-paper)] z-50">
        <div class="flex items-center gap-3">
            <a href="/admin/dashboard"
                class="font-mono text-[10px] uppercase border border-[var(--text-main)] px-3 py-1 hover:bg-[#6D2323] hover:text-[#FEF9E1] transition-colors">
                < PANEL</a>
            <a href="/admin/authors"
                class="font-mono text-[10px] uppercase border border-[var(--text-accent)] text-[var(--text-accent)] px-3 py-1 hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-colors font-bold">YAZARLARI
                YÖNET</a>
            <h1 class="font-syne text-lg font-bold uppercase tracking-wider ml-2 hidden md:block">İÇERİK
                STÜDYOSU</h1>
        </div>
        <div class="font-mono text-[10px] text-[var(--text-accent)] animate-pulse">SYSTEM: READY ●</div>
    </header>

    <form id="uploadForm" action="/admin/store" method="POST" enctype="multipart/form-data"
        class="flex-1 grid grid-cols-1 lg:grid-cols-12">

        <main class="lg:col-span-9 p-6 md:p-8 border-r-2 border-[var(--text-main)] space-y-6">
            <div>
                <input type="text" name="title"
                    class="text-3xl font-bold font-syne py-6 border-none border-b-2 placeholder-[#6D2323]/50 focus:bg-transparent"
                    placeholder="Buraya Başlık Giriniz..." required autocomplete="off">
            </div>

            <div>
                <div class="flex justify-between items-end mb-2">
                    <label class="font-mono text-xs uppercase opacity-60 block">// İÇERİK EDİTÖRÜ</label>
                    <div class="flex gap-2">
                        <input type="file" id="wordInput" accept=".docx" class="hidden">
                        <button type="button" onclick="document.getElementById('wordInput').click()"
                            class="text-[10px] font-bold bg-[#6D2323] text-[#FEF9E1] px-3 py-1 hover:bg-[var(--text-accent)] transition-colors flex items-center gap-2">
                            <span>📄 WORD'DEN ÇEK</span>
                        </button>
                    </div>
                </div>
                <textarea id="summernote" name="content"></textarea>
                <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                    <div class="flex justify-between items-end mb-2">
                        <label class="font-mono text-xs uppercase font-bold block">// KAYNAKÇA & REFERANSLAR</label>
                        <span class="text-[10px] opacity-60 font-mono">Format: 1=https://site.com veya [1] Kitap
                            Adı</span>
                    </div>
                    <textarea name="refs" rows="6" class="font-mono text-sm p-3"
                        placeholder="Her satıra bir kaynak giriniz:&#10;1=https://nasa.gov/report&#10;2=Einstein, İzafiyet Teorisi, sf.45"></textarea>
                </div>
            </div>
        </main>

        <aside class="lg:col-span-3 bg-[var(--bg-secondary)]/20 p-6 md:p-8 space-y-6 h-full overflow-y-auto">
            <div class="border-b-2 border-[var(--text-main)] pb-4 mb-4">
                <h3 class="font-syne font-bold uppercase text-lg">YAYIN AYARLARI</h3>
            </div>

            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">YAZAR SEÇİMİ</label>
                <select name="author_id" required class="p-3">
                    <option value="" disabled selected>-- Yazar Belirleyin --</option>
                    <?php if (isset($authors) && !empty($authors)): ?>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?php echo $author['id']; ?>">
                                <?php echo htmlspecialchars($author['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Önce yazar eklemelisiniz</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block font-syne font-bold uppercase text-xs mb-3 text-[var(--text-main)]">Kategoriler</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 border-2 border-[var(--text-main)]/20 bg-[#6D2323]/5">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"
                                    class="w-4 h-4 accent-[#A31D1D] cursor-pointer">
                                <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)]">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-[10px] opacity-50 uppercase">Henüz kategori tanımlanmamış.</p>
                    <?php endif; ?>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Birden fazla seçim yapabilirsiniz.</small>
            </div>

            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block text-center">KAPAK GÖRSELİ (16:9)</label>
                <div class="aspect-video w-full border-2 border-dashed border-[var(--text-main)] bg-[var(--bg-paper)] relative group overflow-hidden cursor-pointer">
                    <input type="file" id="coverUpload" name="cover_image" accept="image/png, image/jpeg, image/webp"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-50">
                    <div id="placeholderState"
                        class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 transition-transform group-hover:scale-105 z-10">
                        <div class="text-3xl mb-2">📷</div>
                        <p class="text-[10px] font-mono opacity-70">Tıkla veya Sürükle</p>
                        <div class="text-[9px] text-[var(--text-accent)] font-bold mt-2">MAX: 5MB</div>
                    </div>
                    <div id="previewState" class="hidden absolute inset-0 z-20 bg-[var(--bg-paper)]">
                        <img id="imgPreview" class="w-full h-full object-cover">
                        <div class="absolute bottom-0 left-0 w-full bg-[#6D2323]/80 text-[#FEF9E1] text-[10px] text-center py-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            GÖRSELİ DEĞİŞTİRMEK İÇİN TIKLA
                        </div>
                    </div>
                </div>
                <p id="fileName" class="text-[10px] font-mono mt-2 text-center opacity-70 truncate min-h-[15px]"></p>
                <div id="uploadError" class="hidden text-[10px] text-red-600 font-bold bg-red-100 p-2 border border-red-600 mt-2"></div>
            </div>

            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">KISA ÖZET</label>
                <textarea name="desc" rows="5" placeholder="Listeleme açıklaması..." class="text-sm p-3"></textarea>
            </div>

            <button type="submit" id="submitBtn"
                class="btn-action w-full py-4 text-lg mt-4 shadow-[4px_4px_0px_#6D2323]">
                SİSTEME YÜKLE →
            </button>
        </aside>
    </form>

    <script>
        $('#summernote').summernote({
            placeholder: 'İçeriği buraya girin veya Word dosyası yükleyin...',
            tabsize: 2, height: 500,
            toolbar: [['style', ['style']], ['font', ['bold', 'underline']], ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link', 'picture', 'video']], ['view', ['codeview']]],
            callbacks: {
                onKeydown: function (e) {
                    if (e.keyCode === 9) {
                        e.preventDefault();
                        $(this).summernote('pasteHTML', '&nbsp;&nbsp;&nbsp;&nbsp;');
                    }
                },
                onImageUpload: function (files) {
                    for (let i = 0; i < files.length; i++) {
                        uploadContentImage(files[i])
                            .then(url => {
                                $('#summernote').summernote('insertImage', url);
                            })
                            .catch(err => {
                                console.error(err);
                                alert("Resim yüklenirken hata oluştu: " + err);
                            });
                    }
                },
                onPaste: function (e) {
                    var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                }
            }
        });

        function dataURLtoFile(dataurl, filename) {
            var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
                bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new File([u8arr], filename, { type: mime });
        }

        function uploadContentImage(file) {
            return new Promise((resolve, reject) => {
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
                            reject(url);
                        } else {
                            resolve(url);
                        }
                    },
                    error: function (err) {
                        reject(err);
                    }
                });
            });
        }

        document.getElementById('wordInput').addEventListener('change', function (e) {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');

            var reader = new FileReader();
            reader.onload = function (e) {
                mammoth.convertToHtml({ arrayBuffer: e.target.result })
                    .then(async function (result) {
                        let html = result.value;
                        let parser = new DOMParser();
                        let doc = parser.parseFromString(html, 'text/html');
                        let images = doc.getElementsByTagName('img');
                        let imageArray = Array.from(images);
                        
                        console.log("Bulunan görsel sayısı:", imageArray.length);

                        for (let i = 0; i < imageArray.length; i++) {
                            let img = imageArray[i];
                            
                            if (img.src.startsWith('data:')) {
                                console.log(`İşleniyor: Görsel ${i+1} / ${imageArray.length}...`);
                                
                                let filename = "imported_image_" + new Date().getTime() + "_" + i + ".png";
                                if (img.src.includes('image/jpeg')) filename = filename.replace('.png', '.jpg');
                                
                                let file = dataURLtoFile(img.src, filename);
                                
                                try {
                                    let newUrl = await uploadContentImage(file);
                                    console.log(`Görsel ${i+1} başarıyla yüklendi: ${newUrl}`);
                                    img.src = newUrl; 
                                } catch (err) {
                                    console.error(`Görsel ${i+1} yüklenemedi. Base64 verisi HTML'den temizleniyor.`, err);
                                    img.parentNode.removeChild(img); 
                                }
                            }
                        }

                        console.log("Görsel işleme tamamlandı. HTML aktarılıyor...");
                        $('#summernote').summernote('pasteHTML', doc.body.innerHTML);
                        document.getElementById('loadingOverlay').classList.add('hidden');
                        document.getElementById('loadingOverlay').classList.remove('flex');
                    })
                    .catch(function (err) {
                        alert("Word dosyası okunamadı: " + err);
                        document.getElementById('loadingOverlay').classList.add('hidden');
                        document.getElementById('loadingOverlay').classList.remove('flex');
                    });
            };
            reader.readAsArrayBuffer(this.files[0]);
        });

        const fileInput = document.getElementById('coverUpload');
        const placeholder = document.getElementById('placeholderState');
        const previewState = document.getElementById('previewState');
        const imgPreview = document.getElementById('imgPreview');
        const fileNameDisplay = document.getElementById('fileName');
        const errorDisplay = document.getElementById('uploadError');
        const submitBtn = document.getElementById('submitBtn');

        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            errorDisplay.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";

            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showError("HATA: Dosya 5MB sınırını aşıyor!");
                    this.value = ""; return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    imgPreview.src = e.target.result;
                    placeholder.classList.add('hidden');
                    previewState.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
                fileNameDisplay.innerText = file.name;
            } else {
                placeholder.classList.remove('hidden');
                previewState.classList.add('hidden');
                fileNameDisplay.innerText = "";
            }
        });

        function showError(msg) {
            errorDisplay.innerText = msg;
            errorDisplay.classList.remove('hidden');
            if (msg.includes("HATA")) { submitBtn.disabled = true; submitBtn.style.opacity = "0.5"; }
        }

        document.getElementById('uploadForm').addEventListener('submit', function () {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');
            submitBtn.innerHTML = "İŞLENİYOR...";
            submitBtn.disabled = true;
        });
    </script>
</body>

</html>