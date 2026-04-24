<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | Yazar Yönetimi</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        :root { --bg-paper: #FEF9E1; --bg-secondary: #E5D0AC; --text-main: #6D2323; --text-accent: #A31D1D; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne  { font-family: 'Syne', sans-serif; }
        .font-mono  { font-family: 'JetBrains Mono', monospace; }
        .grid-bg { background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.05; pointer-events: none; }
        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }
        .brutalist-input { width: 100%; background: transparent; border-bottom: 2px solid var(--line-color); padding: 12px; font-family: 'JetBrains Mono', monospace; outline: none; transition: 0.3s; }
        .brutalist-input:focus { background: rgba(109,35,35,0.05); }
        
        .brutalist-card { border: 2px solid var(--line-color); box-shadow: 8px 8px 0px var(--line-color); transition: all 0.2s; }
        input, textarea { background: transparent; border: 2px solid var(--line-color); padding: 1rem; width: 100%; outline: none; }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 pb-12">
            <section class="space-y-6">
                <div class="brutalist-card p-8 bg-[var(--bg-secondary)]/20">
                    <h3 class="font-syne text-xl font-bold mb-6 uppercase" id="formTitle">Yeni Yazar Tanımla</h3>
                    <form action="/admin/authorStore" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="id" id="authorId">
                        <input type="hidden" name="current_image" id="authorCurrentImage">
                        
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">YAZAR ADI</label>
                            <input type="text" name="name" id="authorName" required placeholder="İsim Soyisim">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">BİYOGRAFİ</label>
                            <textarea name="bio" id="authorBio" rows="3" placeholder="Kısa biyografi..."></textarea>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">PROFİL RESMİ</label>
                            <input type="file" name="image" accept="image/*" class="p-2 cursor-pointer">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">TWITTER (X) URL</label>
                            <input type="url" name="twitter" id="authorTwitter" placeholder="https://twitter.com/kullaniciadi">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">INSTAGRAM URL</label>
                            <input type="url" name="instagram" id="authorInstagram" placeholder="https://instagram.com/kullaniciadi">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">KİŞİSEL WEBSİTESİ</label>
                            <input type="url" name="website" id="authorWebsite" placeholder="https://siteadresiniz.com">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase font-bold mb-1 block">E-POSTA</label>
                            <input type="email" name="email" id="authorEmail" placeholder="ornek@mail.com">
                        </div>
                        <div id="featuredSection" class="hidden border-t border-[var(--line-color)] border-opacity-30 pt-4 mt-4">
                            <label class="font-mono text-[10px] uppercase font-bold mb-2 block text-[#A31D1D]">ÖNE ÇIKAN MAKALELER (Maks. 4)</label>
                            <div id="articlesCheckboxContainer" class="space-y-2 max-h-40 overflow-y-auto p-3 border border-[var(--line-color)] bg-[var(--bg-secondary)]/20 custom-scrollbar">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-[#6D2323] text-[#FEF9E1] py-4 font-bold uppercase hover:bg-black transition-colors shadow-[4px_4px_0px_rgba(0,0,0,0.2)]">
                            VERİYİ SİSTEME İŞLE
                        </button>
                        <button type="button" onclick="resetAuthorForm()" id="cancelBtn" class="hidden w-full text-xs underline opacity-60">Düzenlemeyi İptal Et</button>
                    </form>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="font-syne text-xl font-bold mb-6 uppercase">Kayıtlı Profiler</h3>
                <?php if(!empty($authors)): foreach($authors as $author): ?>
                <div class="brutalist-card p-4 bg-[var(--bg-paper)] flex items-center justify-between">
                    <a href="/yazar/<?php echo htmlspecialchars($author['slug']); ?>" target="_blank" class="flex items-center gap-4 hover:opacity-80 transition-opacity">
                        <img src="<?php echo $author['image_url'] ?: 'https://via.placeholder.com/100'; ?>" class="w-14 h-14 object-cover border border-[var(--text-main)] grayscale hover:grayscale-0 transition-all">
                        <div>
                            <div class="font-bold text-sm hover:text-[var(--text-accent)] transition-colors"><?php echo htmlspecialchars($author['name']); ?></div>
                            <div class="text-[9px] font-mono opacity-50">ID: #<?php echo $author['id']; ?></div>
                        </div>
                    </a>
                    <div class="flex gap-2">
                        <button onclick='editAuthor(<?php echo json_encode($author); ?>)' class="text-[9px] font-bold border border-[var(--text-main)] px-2 py-1 hover:bg-[#6D2323] hover:text-[#FEF9E1]">DÜZENLE</button>
                        <a href="/admin/authorDelete?id=<?php echo $author['id']; ?>" onclick="return confirm('Kayıt silinsin mi?')" class="text-[9px] font-bold border border-red-600 text-red-600 px-2 py-1 hover:bg-red-600 hover:text-white">SİL</a>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <p class="font-mono text-xs opacity-50">// Havuzda kayıtlı yazar bulunamadı.</p>
                <?php endif; ?>
            </section>
        </div>

    </div> </main> <script>
        const allArticles = <?php echo json_encode($all_author_articles ?? []); ?>;

        function editAuthor(data) {
            document.getElementById('formTitle').innerText = "Yazarı Düzenle: " + data.name;
            document.getElementById('authorId').value = data.id;
            document.getElementById('authorName').value = data.name;
            document.getElementById('authorBio').value = data.bio;
            document.getElementById('authorCurrentImage').value = data.image_url;
            
            document.getElementById('authorTwitter').value = data.twitter || "";
            document.getElementById('authorInstagram').value = data.instagram || "";
            document.getElementById('authorWebsite').value = data.website || "";
            document.getElementById('authorEmail').value = data.email || "";

            const container = document.getElementById('articlesCheckboxContainer');
            const featuredSection = document.getElementById('featuredSection');
            container.innerHTML = ''; 
            
            const authorArticles = allArticles.filter(a => a.author_id == data.id);
            
            if(authorArticles.length > 0) {
                featuredSection.classList.remove('hidden');
                let featuredIds = data.featured_articles ? data.featured_articles.split(',') : [];
                
                authorArticles.forEach(article => {
                    const isChecked = featuredIds.includes(article.id.toString()) ? 'checked' : '';
                    container.innerHTML += `
                        <label class="flex items-center gap-3 text-xs font-mono cursor-pointer hover:text-[#A31D1D] transition-colors p-2 border border-transparent hover:bg-[var(--bg-secondary)]/30 hover:border-[var(--line-color)]/30">
                            <input type="checkbox" name="featured[]" value="${article.id}" ${isChecked} style="width: 16px; height: 16px; padding: 0; border: none;" class="accent-[#6D2323] flex-shrink-0 cursor-pointer" onchange="enforceMaxFeatured(this)">
                            <span class="truncate" title="${article.title}">${article.title}</span>
                        </label>
                    `;
                });
            } else {
                featuredSection.classList.add('hidden');
            }

            document.getElementById('cancelBtn').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetAuthorForm() {
            document.getElementById('formTitle').innerText = "Yeni Yazar Tanımla";
            document.getElementById('authorId').value = "";
            document.getElementById('authorName').value = "";
            document.getElementById('authorBio').value = "";
            document.getElementById('authorCurrentImage').value = "";
            
            document.getElementById('authorTwitter').value = "";
            document.getElementById('authorInstagram').value = "";
            document.getElementById('authorWebsite').value = "";
            document.getElementById('authorEmail').value = "";

            document.getElementById('featuredSection').classList.add('hidden');
            document.getElementById('articlesCheckboxContainer').innerHTML = '';

            document.getElementById('cancelBtn').classList.add('hidden');
        }

        function enforceMaxFeatured(changedCheckbox) {
            const MAX = 4;
            const allCheckboxes = document.querySelectorAll('#articlesCheckboxContainer input[type="checkbox"]');
            const checked = Array.from(allCheckboxes).filter(cb => cb.checked);
            if (checked.length > MAX) {
                changedCheckbox.checked = false;
                alert('En fazla 4 makale öne çıkarılabilir.');
            }
        }
    </script>
</body>
</html>