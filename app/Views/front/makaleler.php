<?php 
$page_title = "FEZADAN | Tüm Makaleler";
require_once ROOT . '/app/Views/inc/header.php'; 

function build_url($new_params = []) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return '?' . http_build_query($params);
}
?>
<style>
    .grid-container { display: grid; grid-template-columns: 1fr; border-top: 1px solid var(--line-color); }
    @media (min-width: 768px) { .grid-container { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .grid-container { grid-template-columns: repeat(4, 1fr); } }
    
    .grid-item {
        border-bottom: 1px solid var(--line-color);
        border-right: 1px solid var(--line-color);
        padding: 2.5rem 2rem;
        transition: background-color 0.3s ease;
        display: flex; flex-direction: column; justify-content: space-between;
        min-height: 320px; position: relative; overflow: hidden;
    }
    @media (max-width: 767px) { .grid-item { border-right: none; } }
    @media (min-width: 1024px) { .grid-item:nth-child(4n) { border-right: none; } }
    .grid-item:hover { background-color: var(--bg-secondary); }

    .reveal-img {
        position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: cover; pointer-events: none; z-index: 0;
        opacity: var(--img-opacity); 
        filter: grayscale(100%) contrast(110%);
        mix-blend-mode: var(--img-blend); 
        transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        transform: scale(1.01);
    }

    .grid-item::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        background-color: var(--text-main);
        opacity: 0.5;
        mix-blend-mode: color;
        transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    [data-theme="dark"] .grid-item::before {
        opacity: 0.15; 
        mix-blend-mode: lighten; 
    }

    .grid-item:hover .reveal-img {
        opacity: 0.8; 
        filter: grayscale(0%) contrast(100%); 
        transform: scale(1.05);
        mix-blend-mode: normal;
    }
    
    .grid-item:hover::before {
        opacity: 0;
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
        border-bottom-color: var(--text-main) !important; 
        border-right-color: var(--text-main) !important; 
    }
    [data-theme="dark"] .dynamic-border-color { 
        border-color: var(--text-main) !important; 
    }
    [data-theme="dark"] .pagination-link { 
        border-color: var(--text-main) !important; 
    }

    .filter-select {
        background: transparent; 
        font-family: 'JetBrains Mono', monospace; 
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

<main class="flex-grow w-full max-w-[1920px] mx-auto">
    
    <header class="px-4 py-12 flex flex-col items-center border-b border-[var(--line-color)] dynamic-border-color bg-[var(--bg-paper)]">
        <h1 class="text-4xl md:text-6xl font-syne font-bold uppercase text-[var(--text-main)]">Tüm Dosyalar</h1>
        
        <form action="" method="GET" class="mt-8 flex flex-wrap justify-center items-end gap-6 md:gap-8 w-full max-w-5xl">
            
            <div class="flex flex-col w-full md:w-auto flex-grow max-w-xs">
                <label class="text-[10px] uppercase tracking-widest opacity-60 mb-1 text-[var(--text-main)]">Arama</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($filters['q']); ?>" 
                       placeholder="Başlık veya içerik..." 
                       class="bg-transparent py-1 text-[var(--text-main)] font-mono text-sm outline-none placeholder-[var(--text-main)]/40 filter-accent-border">
            </div>

            <div class="flex flex-col">
                <label class="text-[10px] uppercase tracking-widest opacity-60 mb-1 text-[var(--text-main)]">Kategori</label>
                <select name="cat" onchange="this.form.submit()" class="filter-select w-32 md:w-40 filter-accent-border">
                    <option value="">TÜMÜ</option>
                    <?php if(!empty($categories)): foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($filters['cat'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div class="flex flex-col">
                <label class="text-[10px] uppercase tracking-widest opacity-60 mb-1 text-[var(--text-main)]">Yazar</label>
                <select name="author" onchange="this.form.submit()" class="filter-select w-32 md:w-40 filter-accent-border">
                    <option value="">TÜMÜ</option>
                    <?php if(!empty($authors)): foreach($authors as $aut): ?>
                        <option value="<?php echo $aut['id']; ?>" <?php echo ($filters['author'] == $aut['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($aut['name']); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div class="flex items-end gap-2 pb-1">
                <button type="submit" class="bg-[var(--text-main)] text-[var(--bg-paper)] px-4 py-1 text-xs font-bold uppercase border border-[var(--text-main)] hover:bg-transparent hover:text-[var(--text-main)] transition-colors">
                    FİLTRELE
                </button>
                
                <?php if(!empty($filters['cat']) || !empty($filters['author']) || !empty($filters['q'])): ?>
                <a href="/makaleler" class="bg-transparent text-[var(--text-main)] px-3 py-1 text-xs font-bold uppercase border border-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors">
                    TEMİZLE
                </a>
                <?php endif; ?>
            </div>

        </form>

        <p class="mt-8 text-xs font-mono uppercase tracking-widest opacity-50 text-[var(--text-main)]">
            Toplam <?php echo $totalArticles; ?> Kayıt Bulundu (Sayfa <?php echo $currentPage; ?> / <?php echo $totalPages; ?>)
        </p>
    </header>

    <section class="grid-container">
        <?php 
        if(!empty($articles)):
            foreach($articles as $article): 
                $preview_img = !empty($article['image_url']) ? $article['image_url'] : '';
        ?>
        <div class="grid-item group relative">
            <a href="<?php echo SITE_URL; ?>/makale/<?php echo $article['slug']; ?>" class="absolute inset-0 z-0"></a>

            <?php if($preview_img): ?>
                <img src="<?php echo $preview_img; ?>" class="reveal-img pointer-events-none" alt="">
            <?php endif; ?>

            <div class="relative z-10 flex justify-end items-start pointer-events-none">
                
                <div class="flex flex-wrap justify-end gap-1 max-w-[100%] pointer-events-auto mt-1">
                    <?php if (!empty($article['categories'])): 
                        foreach($article['categories'] as $cat): ?>
                        <a href="?cat=<?php echo $cat['id']; ?>" 
                        class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)] font-bold border border-[var(--text-main)] px-2 bg-[var(--bg-paper)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors text-[10px] relative z-20 shadow-sm">
                            <span class="mt-[2px]"><?php echo htmlspecialchars($cat['name']); ?></span>
                        </a>
                    <?php endforeach; elseif (!empty($article['category'])): ?>
                        <span class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)]/70 font-bold border border-[var(--text-main)]/50 px-2 bg-[var(--bg-paper)] text-[10px] rounded-sm">
                            <span class="mt-[2px]"><?php echo htmlspecialchars($article['category']); ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="relative z-10 mt-8 pointer-events-none">
                <h2 class="text-2xl font-bold leading-none mb-3 text-[var(--text-main)] group-hover:underline decoration-[var(--text-main)] decoration-2 underline-offset-4">
                    <?php echo htmlspecialchars($article['title']); ?>
                </h2>
                <p class="text-sm opacity-80 leading-relaxed text-[var(--text-main)]">
                    <?php echo htmlspecialchars($article['short_desc']); ?>
                </p>
            </div>
            
            <div class="relative z-10 mt-4 pt-4 border-t border-[var(--text-main)]/10 pointer-events-none flex justify-between items-center">
                <?php if(!empty($article['author_name'])): ?>
                    <p class="text-[10px] font-mono uppercase opacity-60 text-[var(--text-main)]">Yazar: <?php echo htmlspecialchars($article['author_name']); ?></p>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if(!empty($article['created_at'])): ?>
                    <p class="text-[10px] font-mono uppercase opacity-60 text-[var(--text-main)]">
                        <?php echo date('d.m.Y', strtotime($article['created_at'])); ?>
                    </p>
                <?php endif; ?>
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

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>