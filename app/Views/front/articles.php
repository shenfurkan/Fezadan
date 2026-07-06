<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';

$lang = App::getLang();
$isEn = ($lang === 'EN');

// SEO stratejisi:
//   - Hiç filtre yok           → /makaleler            (index, kanonik kendisi)
//   - Sadece tek kategori (?cat=X) → /makaleler?cat=X  (index, kanonik kendisi → kategori SEO değeri)
//   - Diğer her kombinasyon (search/sort/page/author/cat+...) → noindex,follow + canonical=/makaleler
$catOnly = !empty($_GET['cat']) && (int)$_GET['cat'] > 0
    && empty($_GET['author']) && empty($_GET['q']) && empty($_GET['sort'])
    && (empty($_GET['page']) || (int)$_GET['page'] === 1);
$hasOtherFilters = !$catOnly && (
    !empty($_GET['author']) || !empty($_GET['q']) || !empty($_GET['sort'])
    || (!empty($_GET['page']) && (int)$_GET['page'] > 1)
    || !empty($_GET['cat']) // cat + başka filtre kombinasyonu da buraya düşer
);

// Aktif kategori adını yakala (title/description için)
$activeCategoryName = null;
if ($catOnly) {
    try {
        $catStmt = Db::pdo()->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
        $catStmt->execute([(int)$_GET['cat']]);
        $activeCategoryName = $catStmt->fetchColumn() ?: null;
    } catch (\Throwable $e) { /* yoksa default'a düşer */ }
}

if ($catOnly && $activeCategoryName) {
    $page_title       = htmlspecialchars($activeCategoryName) . ($isEn ? ' — Articles | FEZADAN' : ' — Makaleler | FEZADAN');
    $page_description = $isEn 
        ? 'FEZADAN articles in the category ' . htmlspecialchars($activeCategoryName) . '.'
        : htmlspecialchars($activeCategoryName) . ' kategorisindeki FEZADAN makaleleri.';
    $page_canonical   = langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler') . '?cat=' . (int)$_GET['cat'];
} elseif ($hasOtherFilters) {
    $page_title       = $isEn ? 'Articles — Search & Filters | FEZADAN' : 'Makaleler — Arama & Filtre | FEZADAN';
    $page_description = $isEn
        ? 'Search the FEZADAN article archive. Filter by author, category, or keyword.'
        : 'FEZADAN makale arşivinde arama yapın. Yazara, kategoriye veya anahtar kelimeye göre filtreleyin.';
    $page_canonical   = langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler');
    $page_robots      = 'noindex, follow';
} else {
    $page_title       = $isEn ? 'All Articles — Archive of Independent Essays & Thought | FEZADAN' : 'Tüm Makaleler — Bağımsız Düşünce ve Makale Arşivi | FEZADAN';
    $page_description = $isEn
        ? 'FEZADAN article archive — an independent publication on science, aesthetics, and thought. All writings in one list.'
        : 'FEZADAN makale arşivi — bilim, estetik ve fikir üzerine bağımsız yayın. Tüm yazılar tek liste.';
    $page_canonical   = langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler');
}
$og_url = $page_canonical;

require_once ROOT . '/app/Views/inc/header.php'; 

function build_url($new_params = []) {
    // Apache mod_rewrite 'url' değişkenini $_GET'e enjekte eder (örn. url=en/articles).
    // Bunu asla query param olarak geri iletme — Apache kodlanmış slash'leri (%2F) reddeder.
    $params = $_GET;
    unset($params['url']);
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return '?' . http_build_query($params);
}
?>
<style>
    .grid-container { 
        display: grid; 
        grid-template-columns: 1fr; 
        gap: 2rem; 
        padding: 2.5rem 1rem;
        border-top: 1px solid var(--line-color); 
    }
    @media (min-width: 768px) { 
        .grid-container { 
            grid-template-columns: repeat(2, 1fr); 
            gap: 2.5rem; 
            padding: 4rem 2rem;
        } 
    }
    @media (min-width: 1024px) { 
        .grid-container { 
            grid-template-columns: repeat(4, 1fr); 
            gap: 2.5rem; 
            padding: 4rem 2rem;
        } 
    }
    
    .grid-item {
        background-color: var(--bg-paper);
        border: 2px solid var(--line-color);
        box-shadow: 6px 6px 0px var(--line-color);
        border-radius: 12px;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    
    .grid-item:hover { 
        background-color: var(--bg-paper);
    }

    .card-image-wrapper {
        position: relative;
        overflow: hidden;
        aspect-ratio: 16/9;
        width: 100%;
        border-bottom: 2px solid var(--line-color);
        transition: border-color 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .grid-item:hover .card-image-wrapper {
        border-bottom-color: var(--text-accent);
    }

    .card-image-wrapper::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        background-color: var(--text-main);
        opacity: 0.4;
        mix-blend-mode: color;
        transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    [data-theme="dark"] .card-image-wrapper::before {
        opacity: 0.15;
        mix-blend-mode: lighten;
    }

    .grid-item:hover .card-image-wrapper::before {
        opacity: 0;
    }

    .reveal-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        pointer-events: none;
        transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        filter: grayscale(100%) contrast(110%);
        opacity: 0.85;
    }

    .grid-item:hover .reveal-img {
        filter: grayscale(0%) contrast(100%);
        opacity: 1;
    }
    
    .font-bebas { font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.05em; }
    
    .text-outline {
        color: var(--bg-secondary); 
        -webkit-text-stroke: 2px var(--text-main); 
        paint-order: stroke fill;
    }
    .group:hover .text-outline { 
        color: var(--text-main); 
        -webkit-text-stroke: 0px transparent; 
    }
    
    .pagination-link {
        width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
        border: 1px solid var(--line-color); font-family: 'Syne', sans-serif; font-weight: 700;
        transition: all 0.3s; color: var(--text-main);
    }
    .pagination-link:hover { background: var(--bg-secondary); color: var(--text-main); }
    .pagination-link.active { background: var(--text-main); color: var(--bg-paper); border-color: var(--text-main); }
    
    .filter-accent-border {
        border: none !important;
        border-bottom: 2px solid var(--text-accent) !important;
        transition: border-color 0.3s ease;
    }

    [data-theme="dark"] .filter-accent-border {
        border-bottom-color: var(--text-main) !important;
    }

    [data-theme="dark"] .grid-container { 
        border-top-color: var(--text-main) !important; 
    }
    [data-theme="dark"] .grid-item { 
        box-shadow: 6px 6px 0px var(--text-main);
        border-color: var(--text-main);
    }
    [data-theme="dark"] .grid-item:hover { 
    }
    [data-theme="dark"] .dynamic-border-color { 
        border-color: var(--text-main) !important; 
    }
    [data-theme="dark"] .pagination-link { 
        border-color: var(--text-main) !important; 
    }

    .filter-select {
        background: transparent; 
        font-family: 'Space Grotesk', sans-serif; 
        font-size: 0.75rem; 
        color: var(--text-main);
        padding: 5px 20px 5px 0; 
        cursor: pointer; 
        outline: none; 
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236D2323' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right center;
        background-size: 1.2em;
    }

    .filter-select option {
        background-color: var(--bg-paper);
        color: var(--text-main);
    }

    [data-theme="dark"] .filter-select {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23E5D0AC' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    }
</style>

<main id="main-content" class="flex-grow w-full max-w-[1920px] mx-auto">
    
    <header class="px-4 py-12 flex flex-col items-center border-b border-[var(--line-color)] dynamic-border-color bg-[var(--bg-paper)]">
        <h1 class="text-4xl md:text-6xl font-syne font-bold uppercase text-[var(--text-main)]"><?= $isEn ? 'All Articles' : 'Tüm Dosyalar' ?></h1>
        
        <form action="" method="GET" id="article-filter-form" class="mt-8 flex flex-wrap justify-center items-end gap-6 md:gap-8 w-full max-w-5xl">
            
            <div class="flex flex-col w-full md:w-auto flex-grow max-w-xs relative" id="search-input-wrapper">
                <label for="filter-search" class="text-[10px] uppercase tracking-widest opacity-60 mb-1 text-[var(--text-main)]"><?= $isEn ? 'Search' : 'Arama' ?></label>
                <input type="text" name="q" id="filter-search" value="<?php echo htmlspecialchars($filters['q']); ?>" 
                       placeholder="<?= $isEn ? 'Title or content...' : 'Başlık veya içerik...' ?>" 
                       class="bg-transparent py-1 text-[var(--text-main)] font-mono text-sm outline-none placeholder-[var(--text-main)]/40 filter-accent-border"
                       autocomplete="off">
                <div id="search-autocomplete-dropdown" class="absolute left-0 right-0 top-full bg-[var(--bg-paper)] border-2 border-[var(--line-color)] mt-1 z-50 hidden shadow-[6px_6px_0px_var(--line-color)] max-h-80 overflow-y-auto"></div>
            </div>

            <div class="flex flex-col">
                <label for="filter-cat" class="text-[10px] uppercase tracking-widest opacity-60 mb-1 text-[var(--text-main)]"><?= $isEn ? 'Category' : 'Kategori' ?></label>
                <select name="cat" id="filter-cat" class="filter-select w-32 md:w-40 filter-accent-border">
                    <option value=""><?= $isEn ? 'ALL' : 'TÜMÜ' ?></option>
                    <?php if(!empty($categories)): foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($filters['cat'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div class="flex flex-col">
                <label for="filter-author" class="text-[10px] uppercase tracking-widest opacity-60 mb-1 text-[var(--text-main)]"><?= $isEn ? 'Author' : 'Yazar' ?></label>
                <select name="author" id="filter-author" class="filter-select w-32 md:w-40 filter-accent-border">
                    <option value=""><?= $isEn ? 'ALL' : 'TÜMÜ' ?></option>
                    <?php if(!empty($authors)): foreach($authors as $aut): ?>
                        <option value="<?php echo $aut['id']; ?>" <?php echo ($filters['author'] == $aut['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($aut['name']); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div class="flex items-end gap-2 pb-1">
                <button type="submit" class="bg-[var(--text-main)] text-[var(--bg-paper)] px-4 py-1 text-xs font-bold uppercase border border-[var(--text-main)] hover:bg-transparent hover:text-[var(--text-main)] transition-colors">
                    <?= $isEn ? 'FILTER' : 'FİLTRELE' ?>
                </button>
                
                <?php if(!empty($filters['cat']) || !empty($filters['author']) || !empty($filters['q'])): ?>
                <a href="<?php echo langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler'); ?>" class="bg-transparent text-[var(--text-main)] px-3 py-1 text-xs font-bold uppercase border border-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors">
                    <?= $isEn ? 'CLEAR' : 'TEMİZLE' ?>
                </a>
                <?php endif; ?>
            </div>

        </form>

        <p class="mt-8 text-xs font-mono uppercase tracking-widest opacity-50 text-[var(--text-main)]">
            <?php if ($isEn): ?>
                Total <?php echo $totalArticles; ?> Records Found (Page <?php echo $currentPage; ?> / <?php echo $totalPages; ?>)
            <?php else: ?>
                Toplam <?php echo $totalArticles; ?> Kayıt Bulundu (Sayfa <?php echo $currentPage; ?> / <?php echo $totalPages; ?>)
            <?php endif; ?>
        </p>
    </header>
 
    <section class="grid-container">
        <?php if (empty($articles)): ?>
            <div class="md:col-span-2 lg:col-span-4 border-2 border-[var(--line-color)] bg-[var(--bg-paper)] p-8 md:p-12 text-center shadow-[6px_6px_0px_var(--line-color)]">
                <h2 class="font-syne text-2xl md:text-4xl font-bold uppercase text-[var(--text-main)] mb-4">
                    <?= $isEn ? 'No English Articles Yet' : 'Sonuç Bulunamadı' ?>
                </h2>
                <p class="max-w-2xl mx-auto text-sm md:text-base leading-relaxed text-[var(--text-main)] opacity-80">
                    <?= $isEn ? 'The English archive only lists English articles. Turkish articles remain available on the Turkish site.' : 'Filtreleri temizleyerek veya farklı bir arama yaparak tekrar deneyin.' ?>
                </p>
                <?php if ($isEn): ?>
                    <a href="/tr/makaleler" class="inline-block mt-6 px-5 py-3 border-2 border-[var(--text-main)] font-bold uppercase text-xs hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors">
                        Türkçe Arşive Git
                    </a>
                <?php elseif(!empty($filters['cat']) || !empty($filters['author']) || !empty($filters['q'])): ?>
                    <a href="<?php echo langUrl('/makaleler'); ?>" class="inline-block mt-6 px-5 py-3 border-2 border-[var(--text-main)] font-bold uppercase text-xs hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors">
                        Filtreleri Temizle
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php 
        if(!empty($articles)):
            foreach($articles as $article): 
                $preview_img = !empty($article['image_url']) ? $article['image_url'] : '';
        ?>
        <div class="grid-item group relative">
            
            <!-- Link to the article (occupies the entire card) -->
            <a href="<?php echo articleUrl($article['author_slug'] ?? 'yazar', $article['slug']); ?>" class="absolute inset-0 z-10" aria-label="<?php echo htmlspecialchars($article['title']); ?> makalesini oku"></a>

            <!-- Card Image Header -->
            <?php if($preview_img): 
                $preview_webp = Upload::webpUrl($preview_img);
                $preview_url = Upload::assetUrl($preview_img);
                $preview_fallback = Upload::assetUrl((string)(parse_url($preview_img, PHP_URL_PATH) ?: $preview_img));
            ?>
                <div class="card-image-wrapper">
                    <picture>
                        <?php if ($preview_webp): ?>
                            <source type="image/webp" srcset="<?php echo htmlspecialchars($preview_webp, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>
                        <img src="<?php echo htmlspecialchars($preview_url, ENT_QUOTES, 'UTF-8'); ?>" 
                             width="600" height="400" 
                             loading="lazy" decoding="async"
                             data-fallback="<?php echo htmlspecialchars($preview_fallback, ENT_QUOTES, 'UTF-8'); ?>"
                             class="reveal-img pointer-events-none" 
                             alt="<?php echo htmlspecialchars($article['title']); ?>">
                    </picture>
                </div>
            <?php else: ?>
                <div class="card-image-wrapper bg-[var(--bg-secondary)]/20 flex items-center justify-center">
                    <span class="text-xl font-syne font-bold opacity-30 select-none tracking-widest text-[var(--text-main)]">FEZADAN</span>
                </div>
            <?php endif; ?>
            
            <!-- Card Body -->
            <div class="flex-grow p-6 flex flex-col justify-between">
                <div>
                    <!-- Categories -->
                    <div class="flex flex-wrap gap-1.5 mb-4 relative z-20 pointer-events-auto">
                        <?php if (!empty($article['categories'])): ?>
                            <?php foreach($article['categories'] as $cat): ?>
                                <a href="<?php echo langUrl((App::getLang() === 'EN' ? 'articles' : 'makaleler') . '?cat=' . (int)$cat['id']); ?>" aria-label="<?php echo htmlspecialchars($cat['name']); ?> kategorisindeki makalelere git"
                                class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)] font-bold border border-[var(--text-main)] px-2 bg-[var(--bg-paper)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors text-[10px] shadow-sm rounded-sm">
                                    <span class="mt-[2px]"><?php echo htmlspecialchars($cat['name']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php elseif (!empty($article['category'])): ?>
                            <span class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)]/70 font-bold border border-[var(--text-main)]/50 px-2 bg-[var(--bg-paper)] text-[10px] rounded-sm select-none">
                                <span class="mt-[2px]"><?php echo htmlspecialchars($article['category']); ?></span>
                            </span>
                        <?php else: ?>
                            <span class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)]/70 font-bold border border-[var(--text-main)]/50 px-2 bg-[var(--bg-paper)] text-[10px] rounded-sm select-none">
                                <span class="mt-[2px]"><?= $isEn ? 'GENERAL' : 'GENEL' ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title & Short Description -->
                    <h2 class="text-xl font-bold leading-[1.4] mb-4 text-[var(--text-main)] group-hover:text-[var(--text-accent)] transition-colors line-clamp-2">
                        <?php echo htmlspecialchars($article['title']); ?>
                    </h2>
                    <p class="text-sm opacity-85 leading-[1.6] text-[var(--text-main)] line-clamp-3">
                        <?php echo htmlspecialchars($article['short_desc']); ?>
                    </p>
                </div>

                <!-- Card Footer -->
                <div class="mt-8 pt-4 border-t border-[var(--line-color)] flex justify-between items-center text-[10px] font-mono uppercase opacity-70 text-[var(--text-main)]">
                    <?php if(!empty($article['author_name'])): ?>
                        <span><?= $isEn ? 'Author: ' : 'Yazar: ' ?><?php echo htmlspecialchars($article['author_name']); ?></span>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <?php if(!empty($article['created_at'])): ?>
                        <span>
                            <?php echo date('d.m.Y', strtotime($article['created_at'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </section>

    <?php if($totalPages > 1): ?>
    <div class="py-16 flex flex-wrap justify-center items-center gap-2 md:gap-3 border-t border-[var(--line-color)] dynamic-border-color">
        <?php if($currentPage > 1): ?>
            <a href="<?php echo build_url(['page' => $currentPage - 1]); ?>" class="pagination-link">←</a>
        <?php endif; ?>

        <?php $range = 1;
        for ($i = 1; $i <= $totalPages; $i++):
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)): ?>
            <a href="<?php echo build_url(['page' => $i]); ?>" class="pagination-link <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php elseif (($i == $currentPage - $range - 1) || ($i == $currentPage + $range + 1)): ?>
                <span class="font-mono text-[var(--text-main)] opacity-50 px-1">...</span>
            <?php endif;
        endfor; ?>

        <?php if($currentPage < $totalPages): ?>
            <a href="<?php echo build_url(['page' => $currentPage + 1]); ?>" class="pagination-link">→</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script nonce="<?= CSP_NONCE ?>">
    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.querySelector('#search-input-wrapper input[name="q"]');
        const dropdown = document.getElementById('search-autocomplete-dropdown');
        if (!searchInput || !dropdown) return;

        let debounceTimer = null;

        const style = document.createElement('style');
        style.textContent = `
            .autocomplete-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px;
                border-bottom: 1px solid var(--line-color);
                text-decoration: none;
                color: var(--text-main);
                transition: background-color 0.2s;
            }
            .autocomplete-item:last-child {
                border-bottom: none;
            }
            .autocomplete-item:hover {
                background-color: var(--bg-secondary);
                color: var(--text-accent);
            }
            .autocomplete-thumb {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border: 1px solid var(--line-color);
                flex-shrink: 0;
            }
            .autocomplete-info {
                display: flex;
                flex-direction: column;
                min-width: 0;
            }
            .autocomplete-title {
                font-family: 'Syne', sans-serif;
                font-weight: 700;
                font-size: 0.85rem;
                line-height: 1.2;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .autocomplete-desc {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 0.7rem;
                opacity: 0.7;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .autocomplete-no-results {
                padding: 12px;
                text-align: center;
                font-family: 'Space Grotesk', sans-serif;
                font-size: 0.75rem;
                opacity: 0.6;
            }
        `;
        document.head.appendChild(style);

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 2) {
                dropdown.innerHTML = '';
                dropdown.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch('/<?= strtolower(App::getLang()) ?>/search/autocomplete?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const row = document.createElement('a');
                                row.href = item.url;
                                row.className = 'autocomplete-item';
                                
                                const imgHtml = item.image 
                                    ? `<img src="${item.image}" class="autocomplete-thumb" alt="${item.title}">`
                                    : `<div class="autocomplete-thumb bg-[var(--bg-secondary)]/30 flex items-center justify-center text-xs opacity-50 font-bold font-syne">F</div>`;
                                
                                row.innerHTML = `
                                    ${imgHtml}
                                    <div class="autocomplete-info">
                                        <span class="autocomplete-title">${item.title}</span>
                                        <span class="autocomplete-desc">${item.desc}</span>
                                    </div>
                                `;
                                dropdown.appendChild(row);
                            });
                            dropdown.classList.remove('hidden');
                        } else {
                            dropdown.innerHTML = `<div class="autocomplete-no-results"><?= $isEn ? 'No results found.' : 'Sonuç bulunamadı.' ?></div>`;
                            dropdown.classList.remove('hidden');
                        }
                    })
                    .catch(err => {
                        console.error('Autocomplete error:', err);
                    });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 2 && dropdown.children.length > 0) {
                dropdown.classList.remove('hidden');
            }
        });
    });
</script>

<script nonce="<?= CSP_NONCE ?>">
(function(){var f=document.getElementById('article-filter-form');if(f){var s=f.querySelectorAll('select[name="cat"], select[name="author"]');s.forEach(function(e){e.addEventListener('change',function(){f.submit()})})}})();
</script>
<script nonce="<?= CSP_NONCE ?>">
document.addEventListener('error',function(e){var t=e.target;if(t&&t.tagName==='IMG'&&t.dataset.fallback){t.onerror=null;t.src=t.dataset.fallback}},true);
</script>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
