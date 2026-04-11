<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FEZADAN | Yazar Yönetimi</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        :root { --bg-paper: #FEF9E1; --text-main: #6D2323; --line-color: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .brutalist-card { border: 2px solid var(--line-color); box-shadow: 8px 8px 0px var(--line-color); transition: all 0.2s; }
        input, textarea { background: transparent; border: 2px solid var(--line-color); padding: 1rem; width: 100%; outline: none; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-72 border-r-2 border-[var(--text-main)] flex flex-col bg-[var(--bg-paper)]">
        <div class="h-24 flex items-center px-8 border-b-2 border-[var(--text-main)]">
            <h1 class="font-syne text-2xl font-bold tracking-tighter text-[var(--text-main)]">FEZADAN</h1>
        </div>
        <nav class="flex-1 py-8 flex flex-col gap-2">
            <a href="/admin/dashboard" class="py-4 px-8 font-bold uppercase text-sm opacity-60 hover:opacity-100">▣ Genel Bakış</a>
            <a href="/admin/authors" class="py-4 px-8 font-bold uppercase text-sm bg-[#6D2323] text-[#FEF9E1]">▣ Yazar Yönetimi</a>
            <a href="/admin/create" class="py-4 px-8 font-bold uppercase text-sm opacity-60 hover:opacity-100">✎ Yeni Makale Yaz</a>
        </nav>
    </aside>

    <main class="flex-1 overflow-y-auto p-12">
        <h2 class="font-syne text-4xl font-bold uppercase mb-12 border-b-2 border-[var(--text-main)] pb-4">Yazar Havuzu</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
            <section class="space-y-6">
                <div class="brutalist-card p-8 bg-white/30">
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
                            <input type="file" name="image" accept="image/*">
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
                        <div id="featuredSection" class="hidden border-t border-[var(--line-color)] border-opacity-30 pt-4 mt-4">
                            <label class="font-mono text-[10px] uppercase font-bold mb-2 block text-[#A31D1D]">ÖNE ÇIKAN MAKALELER (Maks. 4)</label>
                            <div id="articlesCheckboxContainer" class="space-y-2 max-h-40 overflow-y-auto p-3 border border-[var(--line-color)] bg-white/50">
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
                <div class="brutalist-card p-4 bg-white flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo $author['image_url'] ?: 'https://via.placeholder.com/100'; ?>" class="w-14 h-14 object-cover border border-[var(--text-main)] grayscale hover:grayscale-0 transition-all">
                        <div>
                            <div class="font-bold text-sm"><?php echo htmlspecialchars($author['name']); ?></div>
                            <div class="text-[9px] font-mono opacity-50">ID: #<?php echo $author['id']; ?></div>
                        </div>
                    </div>
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
    </main>

    <script>
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

            // --- Öne çıkan makaleler ---
            const container = document.getElementById('articlesCheckboxContainer');
            const featuredSection = document.getElementById('featuredSection');
            container.innerHTML = ''; // Önceki listeyi temizle
            
            // Sadece bu yazara ait makaleleri filtrele
            const authorArticles = allArticles.filter(a => a.author_id == data.id);
            
            if(authorArticles.length > 0) {
                featuredSection.classList.remove('hidden');
                // Mevcut seçilmiş ID'leri virgülle ayırıp diziye at
                let featuredIds = data.featured_articles ? data.featured_articles.split(',') : [];
                
                // Makaleleri Checkbox olarak bas
                authorArticles.forEach(article => {
                    const isChecked = featuredIds.includes(article.id.toString()) ? 'checked' : '';
                    container.innerHTML += `
                        <label class="flex items-center gap-3 text-xs font-mono cursor-pointer hover:text-[#A31D1D] transition-colors p-2 border border-transparent hover:bg-white/50 hover:border-[var(--line-color)]/30">
                            <input type="checkbox" name="featured[]" value="${article.id}" ${isChecked} style="width: 16px; padding: 0; border: none;" class="accent-[#6D2323] flex-shrink-0 cursor-pointer">
                            <span class="truncate" title="${article.title}">${article.title}</span>
                        </label>
                    `;
                });
            } else {
                // Yazarın makalesi yoksa alanı gizle
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

            document.getElementById('featuredSection').classList.add('hidden');
            document.getElementById('articlesCheckboxContainer').innerHTML = '';

            document.getElementById('cancelBtn').classList.add('hidden');
        }
    </script>
</body>
</html>