<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | İçerik Stüdyosu</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>

    <script>
        (function () {
            const userTheme = localStorage.getItem('theme');
            const htmlElement = document.documentElement;
            if (userTheme === 'dark') {
                htmlElement.setAttribute('data-theme', 'dark');
            } else {
                htmlElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <style>
        :root {
            --bg-paper:    #FEF9E1;
            --bg-secondary:#E5D0AC;
            --text-main:   #6D2323;
            --text-accent: #A31D1D;
            --line-color:  #6D2323;
        }

        [data-theme="dark"] {
            --bg-paper: #120A0A;
            --bg-secondary: #1F1212;
            --text-main: #E5D0AC;
            --text-accent: #FF5C5C;
            --line-color: #E5D0AC;
        }

        body {
            background-color: var(--bg-paper);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Tema Değiştirme Butonu Tasarımı */
        .theme-switch-wrapper { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .theme-switch { width: 48px; height: 24px; background-color: var(--text-main); border: 2px solid var(--text-main); border-radius: 999px; position: relative; transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1); }
        [data-theme="dark"] .theme-switch { background-color: var(--bg-secondary); border-color: var(--text-main); }
        .theme-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background-color: var(--bg-paper); border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); }
        [data-theme="dark"] .theme-switch::after { transform: translateX(24px); background-color: var(--text-main); }
        .theme-icon { width: 14px; height: 14px; color: var(--text-main); }
        .sun-icon { opacity: 1; color: var(--text-main); }
        .moon-icon { opacity: 0.3; color: var(--text-main); }
        [data-theme="dark"] .sun-icon { opacity: 0.3; }
        [data-theme="dark"] .moon-icon { opacity: 1; }

        /* Summernote Karanlık Tema Uyumluluğu */
        .note-editor.note-frame  { border: 2px solid var(--line-color) !important; border-radius: 0; box-shadow: 8px 8px 0px rgba(0,0,0,0.1); }
        .note-toolbar            { background-color: var(--bg-secondary) !important; border-bottom: 2px solid var(--line-color) !important; }
        .note-statusbar          { display: none !important; }
        .note-editable           { background-color: var(--bg-paper) !important; color: var(--text-main) !important; min-height: 500px !important; }

        /* Editör içi figcaption görünümü */
        .note-editable figure { margin: 1rem 0; display: block; }
        .note-editable figure img { display: block; max-width: 100%; height: auto; }
        .note-editable figure figcaption {
            display: block;
            margin-top: 0.4rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.5;
            color: var(--text-accent);
            opacity: 0.75;
            font-style: italic;
            padding: 0.25rem 0.5rem 0.25rem 0.6rem;
            border-left: 2px solid var(--text-accent);
            background: var(--bg-secondary);
            outline: none;
            cursor: text;
            min-width: 60px;
        }
        .note-editable figure figcaption:empty::before {
            content: attr(data-placeholder);
            opacity: 0.4;
            pointer-events: none;
            font-style: italic;
        }

        input, select, textarea {
            background: transparent;
            border: 2px solid var(--line-color);
            padding: 1rem;
            width: 100%;
            font-family: 'Space Grotesk', sans-serif;
            outline: none;
            color: var(--text-main);
            transition: all 0.3s;
        }
        input:focus, select:focus, textarea:focus { background: var(--bg-secondary); opacity: 0.8; }
        input::placeholder, textarea::placeholder { color: var(--text-main); opacity: 0.5; }

        .btn-action { background: var(--text-main); color: var(--bg-paper); font-family: 'Syne', sans-serif; font-weight: 800; text-transform: uppercase; border: 2px solid var(--text-main); transition: all 0.2s; }
        .btn-action:hover { background: transparent; color: var(--text-main); box-shadow: 6px 6px 0px var(--text-main); transform: translate(-2px,-2px); }

        #loadingOverlay { backdrop-filter: blur(5px); background-color: rgba(18, 10, 10, 0.9); }
        .spinner { width:50px;height:50px;border:5px solid var(--bg-paper);border-top:5px solid var(--text-accent);border-radius:50%;animation:spin 1s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
    </style>
</head>

<body class="h-screen flex flex-col relative overflow-hidden">

    <div id="loadingOverlay" class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center text-[var(--bg-paper)]">
        <div class="spinner mb-4"></div>
        <h2 class="font-syne text-2xl font-bold tracking-widest animate-pulse text-[var(--text-main)]">VERİLER İŞLENİYOR...</h2>
        <p class="font-mono text-xs mt-2 opacity-80 text-[var(--text-main)]">Lütfen bekleyiniz...</p>
    </div>

    <header class="h-20 border-b-2 border-[var(--text-main)] flex justify-between items-center px-6 md:px-12 sticky top-0 bg-[var(--bg-paper)] z-50 flex-shrink-0 transition-colors">
        <div class="flex items-center gap-3">
            <a href="/admin/dashboard" class="font-mono text-[10px] uppercase border border-[var(--text-main)] px-3 py-1 hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors">&lt; PANEL</a>
            <a href="/admin/authors"   class="font-mono text-[10px] uppercase border border-[var(--text-accent)] text-[var(--text-accent)] px-3 py-1 hover:bg-[var(--text-accent)] hover:text-[var(--bg-paper)] transition-colors font-bold">YAZARLARI YÖNET</a>
            <h1 class="font-syne text-lg font-bold uppercase tracking-wider ml-2 hidden md:block text-[var(--text-main)]">İÇERİK STÜDYOSU</h1>
        </div>
        <div class="flex items-center gap-6">
            
            <div class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Temayı Değiştir">
                <svg class="theme-icon sun-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <div class="theme-switch"></div>
                <svg class="theme-icon moon-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                    </path>
                </svg>
            </div>

            <div class="flex items-center gap-1">
                <button type="button" id="tabWrite"   onclick="switchTab('write')"
                    class="font-mono text-[10px] uppercase px-4 py-2 border-2 border-[var(--text-main)] bg-[var(--text-main)] text-[var(--bg-paper)] font-bold transition-all">✎ YAZI</button>
                <button type="button" id="tabPreview" onclick="switchTab('preview')"
                    class="font-mono text-[10px] uppercase px-4 py-2 border-2 border-[var(--text-main)] text-[var(--text-main)] font-bold transition-all hover:bg-[var(--text-main)]/10">◉ ÖNİZLEME</button>
            </div>
        </div>
    </header>

    <form id="uploadForm" action="/admin/store" method="POST" enctype="multipart/form-data"
          class="flex-1 grid grid-cols-1 lg:grid-cols-12 overflow-hidden">

        <main class="lg:col-span-9 p-6 md:p-8 border-r-2 border-[var(--text-main)] space-y-6 overflow-y-auto">
            <div>
                <input type="text" name="title"
                    class="text-3xl font-bold font-syne py-6 border-none border-b-2 focus:bg-transparent"
                    placeholder="Buraya Başlık Giriniz..." required autocomplete="off">
            </div>

            <div>
                <div class="flex justify-between items-end mb-2">
                    <label class="font-mono text-xs uppercase opacity-60 block">// İÇERİK EDİTÖRÜ</label>
                    <div class="flex gap-2">
                        <input type="file" id="wordInput" accept=".docx" class="hidden">
                        <button type="button" onclick="document.getElementById('wordInput').click()"
                            class="text-[10px] font-bold bg-[var(--text-main)] text-[var(--bg-paper)] px-3 py-1 hover:bg-[var(--text-accent)] transition-colors flex items-center gap-2">
                            <span>📄 WORD'DEN ÇEK</span>
                        </button>
                    </div>
                </div>
                <textarea id="summernote" name="content"></textarea>
                <div class="mt-8 pt-8 border-t-2 border-[var(--line-color)]">
                    <div class="flex justify-between items-end mb-2">
                        <label class="font-mono text-xs uppercase font-bold block">// KAYNAKÇA & REFERANSLAR</label>
                        <span class="text-[10px] opacity-60 font-mono">Format: 1=https://site.com veya [1] Kitap Adı</span>
                    </div>
                    <textarea name="refs" rows="6" class="font-mono text-sm p-3"
                        placeholder="Her satıra bir kaynak giriniz.&#10;Eğer kaynak bir cümleye referans ise o cümleye linklemek için metinde o cümleye [1] yazıp buraya 1=kaynak ismi/linki şeklinde yazın&#10;Aksi taktirde numara vermeden her satıra birer kaynak yazınız.&#10;1=https://nasa.gov/report&#10;ya da&#10;Einstein, İzafiyet Teorisi, sf.45"></textarea>
                </div>
            </div>
        </main>

        <aside class="lg:col-span-3 bg-[var(--bg-secondary)]/20 p-6 md:p-8 space-y-6 overflow-y-auto">
            <div class="border-b-2 border-[var(--text-main)] pb-4 mb-4">
                <h3 class="font-syne font-bold uppercase text-lg">YAYIN AYARLARI</h3>
            </div>

            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">YAZAR SEÇİMİ</label>
                <select name="author_id" required class="p-3">
                    <option value="" disabled selected>-- Yazar Belirleyin --</option>
                    <?php if (!empty($authors)): foreach ($authors as $author): ?>
                    <option value="<?php echo $author['id']; ?>"><?php echo htmlspecialchars($author['name']); ?></option>
                    <?php endforeach; else: ?>
                    <option value="" disabled>Önce yazar eklemelisiniz</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block font-syne font-bold uppercase text-xs mb-3 text-[var(--text-main)]">Kategoriler</label>
                <div class="grid grid-cols-2 gap-x-2 gap-y-0 p-3 border-2 border-[var(--text-main)]/20 bg-[var(--text-main)]/5 overflow-y-auto" style="height:160px;">
                    <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                    <label class="flex items-center gap-2 cursor-pointer group min-w-0">
                        <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" style="width:1rem;height:1rem;min-width:1rem;padding:0;border:none;background:transparent;" class="accent-[#A31D1D] cursor-pointer flex-shrink-0">
                        <span class="font-mono text-xs uppercase group-hover:text-[var(--text-accent)] truncate"><?php echo htmlspecialchars($cat['name']); ?></span>
                    </label>
                    <?php endforeach; else: ?>
                    <p class="col-span-2 text-[10px] opacity-50 uppercase">Henüz kategori tanımlanmamış.</p>
                    <?php endif; ?>
                </div>
                <small class="text-[10px] opacity-50 mt-2 block font-mono">Birden fazla seçim yapabilirsiniz.</small>
            </div>

            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block text-center">KAPAK GÖRSELİ (16:9)</label>
                <div class="aspect-video w-full border-2 border-dashed border-[var(--text-main)] bg-[var(--bg-paper)] relative group overflow-hidden cursor-pointer">
                    <input type="file" id="coverUpload" name="cover_image" accept="image/png,image/jpeg,image/webp"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-50">
                    <div id="placeholderState" class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 transition-transform group-hover:scale-105 z-10">
                        <div class="text-3xl mb-2">📷</div>
                        <p class="text-[10px] font-mono opacity-70">Tıkla veya Sürükle</p>
                        <div class="text-[9px] text-[var(--text-accent)] font-bold mt-2">MAX: 5MB</div>
                    </div>
                    <div id="previewState" class="hidden absolute inset-0 z-20 bg-[var(--bg-paper)]">
                        <img id="imgPreview" class="w-full h-full object-cover">
                        <div class="absolute bottom-0 left-0 w-full bg-[var(--text-main)]/90 text-[var(--bg-paper)] text-[10px] text-center py-1 opacity-0 group-hover:opacity-100 transition-opacity">GÖRSELİ DEĞİŞTİRMEK İÇİN TIKLA</div>
                    </div>
                </div>
                <p id="fileName" class="text-[10px] font-mono mt-2 text-center opacity-70 truncate min-h-[15px]"></p>
                <div id="uploadError" class="hidden text-[10px] text-red-600 font-bold bg-red-100 p-2 border border-red-600 mt-2"></div>
            </div>

            <div>
                <label class="font-mono text-xs uppercase font-bold mb-2 block">KISA ÖZET</label>
                <textarea name="desc" rows="5" placeholder="Listeleme açıklaması..." class="text-sm p-3"></textarea>
            </div>

            <button type="button" id="draftBtn"
                class="w-full py-3 text-sm font-bold uppercase border-2 border-[var(--text-main)] text-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-all font-syne tracking-wider">
                TASLAK OLARAK KAYDET
            </button>
            <button type="submit" id="submitBtn" class="btn-action w-full py-4 text-lg mt-2 shadow-[4px_4px_0px_var(--text-main)]">
                SİSTEME YÜKLE →
            </button>
            <input type="hidden" name="status" id="statusInput" value="published">
        </aside>
    </form>

    <?php $preview_date = date('d F Y'); require_once __DIR__ . '/_preview_panel.php'; ?>

    <script>
        // ===== SUMMERNOTE =====
        $('#summernote').summernote({
            placeholder: 'İçeriği buraya girin veya Word dosyası yükleyin...',
            tabsize: 2, height: 500,
            toolbar: [['style',['style']],['font',['bold','underline']],['para',['ul','ol','paragraph']],['insert',['link','picture','video']],['view',['codeview']]],
            callbacks: {
                onKeydown: function(e) { if (e.keyCode===9){e.preventDefault();$(this).summernote('pasteHTML','&nbsp;&nbsp;&nbsp;&nbsp;');} },
                onImageUpload: function(files) {
                    for (let i=0;i<files.length;i++) uploadContentImage(files[i]).then(url=>insertImageWithCaption(url)).catch(err=>alert("Resim yüklenemedi: "+err));
                },
                onPaste: function(e) { /* plain paste — intentionally left default */ }
            }
        });

        // ===== UPLOAD HELPERS =====
        function dataURLtoFile(dataurl, filename) {
            var arr=dataurl.split(','),mime=arr[0].match(/:(.*?);/)[1],bstr=atob(arr[1]),n=bstr.length,u8arr=new Uint8Array(n);
            while(n--) u8arr[n]=bstr.charCodeAt(n);
            return new File([u8arr],filename,{type:mime});
        }

        function uploadContentImage(file) {
            return new Promise((resolve,reject) => {
                let data=new FormData(); data.append("file",file);
                $.ajax({data,type:"POST",url:"/admin/upload-content-image",cache:false,contentType:false,processData:false,
                    success: function(res) {
                        // Eğer gelen yanıtta HTML veya DOCTYPE varsa oturum düşmüş demektir.
                        if(res.includes('<html') || res.includes('<!DOCTYPE')) {
                            reject("Oturum süresi dolmuş veya yetkisiz erişim. Lütfen sayfayı yenileyip tekrar giriş yapın.");
                        } else if(res.includes('error') || res.toLowerCase().includes('hata')) {
                            reject(res);
                        } else {
                            resolve(res.trim()); // Boşlukları temizleyerek linki gönder
                        }
                    },
                    error: err => reject("Sunucuya ulaşılamadı. Hata kodu: " + err.status)
                });
            });
        }

        function insertImageWithCaption(url) {
            const figureHtml = `<figure><img src="${url}" style="max-width:100%;height:auto;"><figcaption contenteditable="true" data-placeholder="Görsel açıklaması (isteğe bağlı)..."></figcaption></figure><p><br></p>`;
            $('#summernote').summernote('pasteHTML', figureHtml);
        }

        // ===== WORD IMPORT =====
        document.getElementById('wordInput').addEventListener('change', function() {
            document.getElementById('loadingOverlay').classList.replace('hidden','flex');
            var reader=new FileReader();
            reader.onload=function(e){
                mammoth.convertToHtml({arrayBuffer:e.target.result}).then(async function(result){
                    let html=result.value,parser=new DOMParser(),doc=parser.parseFromString(html,'text/html');
                    let images=Array.from(doc.getElementsByTagName('img'));
                    for(let i=0;i<images.length;i++){
                        let img=images[i];
                        if(img.src.startsWith('data:')){
                            let fname="imported_image_"+Date.now()+"_"+i+(img.src.includes('image/jpeg')?'.jpg':'.png');
                            try{ img.src=await uploadContentImage(dataURLtoFile(img.src,fname)); }
                            catch(err){ img.parentNode.removeChild(img); }
                        }
                    }
                    $('#summernote').summernote('pasteHTML',doc.body.innerHTML);
                    document.getElementById('loadingOverlay').classList.replace('flex','hidden');
                }).catch(err=>{ alert("Word dosyası okunamadı: "+err); document.getElementById('loadingOverlay').classList.replace('flex','hidden'); });
            };
            reader.readAsArrayBuffer(this.files[0]);
        });

        // ===== KAPAK GÖRSELİ =====
        const fileInput    = document.getElementById('coverUpload');
        const placeholder  = document.getElementById('placeholderState');
        const previewState = document.getElementById('previewState');
        const imgPreview   = document.getElementById('imgPreview');
        const fileNameEl   = document.getElementById('fileName');
        const errorDisplay = document.getElementById('uploadError');
        const submitBtn    = document.getElementById('submitBtn');

        fileInput.addEventListener('change', function() {
            const file=this.files[0];
            errorDisplay.classList.add('hidden'); submitBtn.disabled=false; submitBtn.style.opacity="1";
            if(file){
                if(file.size>5*1024*1024){ showError("HATA: Dosya 5MB sınırını aşıyor!"); this.value=""; return; }
                const r=new FileReader();
                r.onload=e=>{ imgPreview.src=e.target.result; placeholder.classList.add('hidden'); previewState.classList.remove('hidden'); };
                r.readAsDataURL(file); fileNameEl.innerText=file.name;
            } else { placeholder.classList.remove('hidden'); previewState.classList.add('hidden'); fileNameEl.innerText=""; }
        });

        function showError(msg){ errorDisplay.innerText=msg; errorDisplay.classList.remove('hidden'); if(msg.includes("HATA")){submitBtn.disabled=true;submitBtn.style.opacity="0.5";} }

        document.getElementById('uploadForm').addEventListener('submit', function(){
            document.getElementById('loadingOverlay').classList.replace('hidden','flex');
            submitBtn.innerHTML="İŞLENİYOR..."; submitBtn.disabled=true;
        });

        document.getElementById('draftBtn').addEventListener('click', function(){
            document.getElementById('statusInput').value='draft';
            document.getElementById('uploadForm').requestSubmit();
        });

        // ===== updatePreview =====
        function updatePreview() {
            const title = document.querySelector('input[name="title"]').value.trim() || 'Başlık buraya gelecek...';
            document.getElementById('prev-title').textContent = title;

            const desc    = document.querySelector('textarea[name="desc"]').value.trim();
            const prevDesc = document.getElementById('prev-desc');
            prevDesc.style.display = desc ? 'block' : 'none';
            if (desc) prevDesc.textContent = '"' + desc + '"';

            let content = $('#summernote').summernote('code');
            const prevContent = document.getElementById('prev-content');
            if (content && content !== '<p><br></p>') {
                const seen = [];
                content = content.replace(/\[(\d+)\]/g, (m, num) => {
                    let id = '';
                    if (!seen.includes(num)) { id = ` id="prev-ref-link-${num}"`; seen.push(num); }
                    return `<sup class="reference-sup"><a href="#prev-ref-item-${num}"${id} class="text-[var(--text-accent)] hover:underline" style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">[${num}]</a></sup>`;
                });
                prevContent.innerHTML = content;
            } else {
                prevContent.innerHTML = '<p class="opacity-40 italic">İçerik editörden yansıyacak...</p>';
            }

            document.getElementById('prev-readtime').textContent =
                Math.max(1, Math.ceil(prevContent.innerText.trim().split(/\s+/).filter(Boolean).length / 200));

            const coverWrap = document.getElementById('prev-cover-wrap');
            const prevCover = document.getElementById('prev-cover');
            if (!previewState.classList.contains('hidden')) {
                prevCover.src = imgPreview.src;
                coverWrap.style.display = 'block';
            } else {
                coverWrap.style.display = 'none';
            }

            const checked  = document.querySelectorAll('#uploadForm input[name="categories[]"]:checked');
            const catNames = Array.from(checked).map(cb => cb.closest('label').querySelector('span').textContent.trim()).filter(Boolean);
            const prevCats = document.getElementById('prev-cats');
            prevCats.textContent   = catNames.length ? catNames.join(', ') : 'KATEGORİ SEÇİLMEDİ';
            prevCats.style.opacity = catNames.length ? '1' : '0.5';

            renderPrevRefs(document.querySelector('textarea[name="refs"]').value);

            buildPrevToc();
            _bindPreviewScroll();
        }

        /* ================================================================
           GÜVENLİK KALKANI: Heartbeat & LocalStorage Oto-Kayıt
           ================================================================ */
        
        // 1. Heartbeat: Her 10 dakikada bir sunucuya "Ben buradayım" sinyali gönder (Oturumu canlı tut)
        setInterval(() => {
            fetch('/admin/dashboard', { method: 'HEAD' })
                .catch(() => console.log('Ping başarısız, internet bağlantısı kopmuş olabilir.'));
        }, 10 * 60 * 1000); // 10 dakika

        // 2. Tarayıcıya Oto-Kayıt: Her 30 saniyede bir yazıyı yazarın bilgisayarına yedekle
        setInterval(() => {
            const title = document.querySelector('input[name="title"]').value;
            const content = $('#summernote').summernote('code');
            
            if (title || (content && content !== '<p><br></p>')) {
                localStorage.setItem('fezadan_create_draft_title', title);
                localStorage.setItem('fezadan_create_draft_content', content);
            }
        }, 30000); // 30 saniye

        // 3. Sayfa Yüklendiğinde Yedek Kontrolü (Felaket Kurtarma)
        document.addEventListener("DOMContentLoaded", () => {
            const savedTitle = localStorage.getItem('fezadan_create_draft_title');
            const savedContent = localStorage.getItem('fezadan_create_draft_content');
            
            if (savedTitle || (savedContent && savedContent !== '<p><br></p>')) {
                if (confirm("Sistemde kaydedilmemiş bir yerel taslağınız (tarayıcı yedeği) bulundu. Geri yüklemek ister misiniz?")) {
                    if (savedTitle) document.querySelector('input[name="title"]').value = savedTitle;
                    if (savedContent) $('#summernote').summernote('code', savedContent);
                    updatePreview(); // Önizlemeyi güncelle
                } else {
                    // Kullanıcı reddederse yedeği temizle
                    localStorage.removeItem('fezadan_create_draft_title');
                    localStorage.removeItem('fezadan_create_draft_content');
                }
            }
        });

        // 4. Başarılı gönderimden sonra yedeği temizle (Sunucuya zaten kaydedildi)
        document.getElementById('uploadForm').addEventListener('submit', function() {
            localStorage.removeItem('fezadan_create_draft_title');
            localStorage.removeItem('fezadan_create_draft_content');
        });

        // ===== TEMA SWITCH JS MANTIĞI =====
        const themeToggleBtns = document.querySelectorAll('.theme-switch-wrapper');
        themeToggleBtns.forEach(btn => {
            btn.addEventListener('keydown', (e) => {
                if(e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
            
            btn.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        });

    </script>
</body>
</html>