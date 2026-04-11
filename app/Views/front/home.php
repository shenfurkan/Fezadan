<?php 
$page_title = "FEZADAN | Anasayfa";
require_once ROOT . '/app/Views/inc/header.php'; 
?>
<style>
    .hero-title {
        font-size: 8vw;
        line-height: 0.9;
        font-weight: 800;
        text-align: center;
        letter-spacing: -0.02em;
        color: var(--text-main);
        margin-top: 1rem;
        margin-bottom: 2rem;
        text-transform: uppercase;
        text-shadow: 0px 0px 15px rgba(255, 255, 255, 0.9), 0px 0px 5px rgba(255, 255, 255, 0.7);
    }

    .grid-item h2 {
        text-shadow: 0px 0px 12px rgba(255, 255, 255, 0.9), 0px 0px 4px rgba(255, 255, 255, 0.6);
    }
    .grid-item p {
        text-shadow: 0px 0px 10px rgba(255, 255, 255, 0.8);
    }

    [data-theme="dark"] .hero-title {
        text-shadow: 0px 6px 25px rgba(0, 0, 0, 0.9), 0px 2px 10px rgba(0, 0, 0, 0.7);
    }
    [data-theme="dark"] .grid-item h2 {
        text-shadow: 0px 4px 15px rgba(0, 0, 0, 0.95), 0px 2px 8px rgba(0, 0, 0, 0.8);
    }
    [data-theme="dark"] .grid-item p {
        text-shadow: 0px 2px 10px rgba(0, 0, 0, 0.9);
    }

    .grid-container { display: grid; grid-template-columns: 1fr; border-top: 1px solid var(--line-color); }
    @media (min-width: 768px) { .grid-container { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .grid-container { grid-template-columns: repeat(4, 1fr); } }

    .grid-item {
        border-bottom: 1px solid var(--line-color);
        border-right: 1px solid var(--line-color);
        padding: 2.5rem 2rem;
        transition: background-color 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 320px;
        position: relative;
        overflow: hidden;
    }

    @media (max-width: 767px) { .grid-item { border-right: none; } }
    @media (min-width: 1024px) { .grid-item:nth-child(4n) { border-right: none; } }

    .grid-item:hover { background-color: var(--bg-secondary); }

    .reveal-img {
        position: absolute;
        inset: 0;
        width: 100%; height: 100%; object-fit: cover;
        pointer-events: none; z-index: 0;
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
        mix-blend-mode: normal;
    }

    .grid-item:hover::before {
        opacity: 0;
    }

    .marquee { overflow: hidden; white-space: nowrap; background: var(--text-main); color: var(--bg-paper); padding: 0.8rem 0; font-family: 'Space Grotesk', monospace; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.1em; }
    .marquee-content { display: inline-block; animation: scroll 30s linear infinite; }
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

    .manifesto-section { background-color: var(--bg-secondary); color: var(--text-main); padding: 6rem 2rem; text-align: center; border-bottom: 1px solid var(--line-color); }
    
    .content-layer { position: relative; z-index: 10; }

    [data-theme="dark"] .grid-container { 
        border-top-color: var(--text-main) !important; 
    }
    [data-theme="dark"] .grid-item { 
        border-bottom-color: var(--text-main) !important; 
        border-right-color: var(--text-main) !important; 
    }

    [data-theme="dark"] .archive-section-border {
        border-bottom-color: var(--text-main) !important;
    }
</style>

<main class="flex-grow w-full max-w-[1920px] mx-auto">
    
    <header class="px-4 pt-12 md:pt-20 pb-8 flex flex-col items-center">
        <div class="text-xs md:text-sm uppercase tracking-[0.3em] text-[var(--text-main)] mb-2 font-bold opacity-80"> KOLEKTİF
        </div>
        <h1 class="hero-title select-none">FEZADAN</h1>
        <p class="max-w-xl text-center text-sm md:text-lg leading-relaxed opacity-90 px-6 font-light text-[var(--text-main)]">
            Özgür Bilgi Platformu
        </p>
    </header>

    <div class="marquee" aria-hidden="true">
        <div class="marquee-content">
            <?php 
            if(isset($articles) && is_array($articles)) {
                $titles = array_column($articles, 'title');
                $titles = array_slice($titles, 0, 8);
                $marqueeText = implode(" • ", $titles) . " • "; 
                echo str_repeat($marqueeText, 4); 
            }
            ?>
        </div>
    </div>

    <section class="grid-container">
        <?php 
        $article_counter = 0;
        if(isset($articles) && is_array($articles)):
            $batch = array_slice($articles, 0, 4); 
            
            foreach($batch as $article): 
                $preview_img = !empty($article['image_url']) ? $article['image_url'] : '';
                $article_counter++;
        ?>
        <div class="grid-item group relative">
            
            <a href="/makale/<?php echo $article['slug']; ?>" class="absolute inset-0 z-0" aria-label="<?php echo htmlspecialchars($article['title']); ?> makalesini oku"></a>

            <?php if($preview_img): 
                $loading_attr = ($article_counter > 2) ? 'loading="lazy" decoding="async"' : '';
            ?>
                <img src="<?php echo $preview_img; ?>" 
                     width="600" height="400" 
                     <?php echo $loading_attr; ?>
                     class="reveal-img pointer-events-none" 
                     alt="<?php echo htmlspecialchars($article['title']); ?>">
            <?php endif; ?>
            
            <div class="content-layer relative z-10 flex justify-end items-start pointer-events-none">
    
                <div class="flex flex-wrap justify-end gap-1 max-w-[100%] pointer-events-auto mt-1">
                    <?php if (!empty($article['categories'])): ?>
                        <?php foreach($article['categories'] as $cat): 
                            $cat_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', str_replace(['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'], ['i','g','u','s','o','c','i','g','u','s','o','c'], $cat['name'])), '-'));
                        ?>
                            <a href="/kategori/<?php echo $cat_slug; ?>" aria-label="<?php echo htmlspecialchars($cat['name']); ?> kategorisine git"
                            class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)] font-bold border border-[var(--text-main)] px-2 bg-[var(--bg-paper)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors text-[10px] relative z-20 shadow-sm">
                                <span class="mt-[2px]"><?php echo htmlspecialchars($cat['name']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)]/70 font-bold border border-[var(--text-main)]/50 px-2 bg-[var(--bg-paper)] text-[10px] rounded-sm">
                            <span class="mt-[2px]">GENEL</span>
                        </span>
                    <?php endif; ?>
                </div>

            </div>
            
            <div class="content-layer mt-8 relative z-10 pointer-events-none">
                <h2 class="text-2xl font-bold leading-none mb-3 group-hover:underline decoration-[var(--text-main)] decoration-2 underline-offset-4 text-[var(--text-main)]">
                    <?php echo htmlspecialchars($article['title']); ?>
                </h2>
                <p class="text-sm opacity-80 leading-relaxed text-[var(--text-main)]">
                    <?php echo htmlspecialchars($article['short_desc']); ?>
                </p>
            </div>

            <div class="content-layer mt-4 pt-4 border-t border-[var(--text-main)]/10 pointer-events-none flex justify-between items-center relative z-10">
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

    <section class="grid-container border-t-0">
        <?php 
        if(isset($articles) && is_array($articles)):
            $second_batch = array_slice($articles, 4, 4);
            foreach($second_batch as $article): 
                $preview_img = !empty($article['image_url']) ? $article['image_url'] : '';
        ?>
        <div class="grid-item group relative">
            
            <a href="/makale/<?php echo $article['slug']; ?>" class="absolute inset-0 z-0" aria-label="<?php echo htmlspecialchars($article['title']); ?> makalesini oku"></a>

            <?php if($preview_img): ?>
                <img src="<?php echo $preview_img; ?>" 
                     width="600" height="400" 
                     loading="lazy" decoding="async"
                     class="reveal-img pointer-events-none" 
                     alt="<?php echo htmlspecialchars($article['title']); ?>">
            <?php endif; ?>

            <div class="content-layer relative z-10 flex justify-end items-start pointer-events-none">
                
                <div class="flex flex-wrap justify-end gap-1 max-w-[100%] pointer-events-auto mt-1">
                    <?php if (!empty($article['categories'])): ?>
                        <?php foreach($article['categories'] as $cat): 
                            $cat_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', str_replace(['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'], ['i','g','u','s','o','c','i','g','u','s','o','c'], $cat['name'])), '-'));
                        ?>
                            <a href="/kategori/<?php echo $cat_slug; ?>" aria-label="<?php echo htmlspecialchars($cat['name']); ?> kategorisine git"
                               class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)] font-bold border border-[var(--text-main)] px-2 bg-[var(--bg-paper)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors text-[10px] relative z-20 shadow-sm">
                                <span class="mt-[2px]"><?php echo htmlspecialchars($cat['name']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="flex items-center justify-center leading-none h-6 uppercase text-[var(--text-main)]/70 font-bold border border-[var(--text-main)]/50 px-2 bg-[var(--bg-paper)] text-[10px] rounded-sm">
                            <span class="mt-[2px]">GENEL</span>
                        </span>
                    <?php endif; ?>
                </div>

            </div>
            
            <div class="content-layer mt-8 relative z-10 pointer-events-none">
                <h2 class="text-2xl font-bold leading-none mb-3 group-hover:underline decoration-[var(--text-main)] decoration-2 underline-offset-4 text-[var(--text-main)]">
                    <?php echo htmlspecialchars($article['title']); ?>
                </h2>
                <p class="text-sm opacity-80 leading-relaxed text-[var(--text-main)]">
                    <?php echo htmlspecialchars($article['short_desc']); ?>
                </p>
            </div>

            <div class="content-layer mt-4 pt-4 border-t border-[var(--text-main)]/10 pointer-events-none flex justify-between items-center relative z-10">
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

    <div class="border-b border-[var(--line-color)] archive-section-border bg-[var(--bg-paper)] hover:bg-[var(--bg-secondary)] transition-colors duration-300 cursor-pointer">
        <a href="/makaleler" class="block py-12 text-center group" aria-label="Tüm arşivi incele">
            <span class="font-syne text-xl md:text-3xl font-bold text-[var(--text-main)] uppercase tracking-widest group-hover:opacity-70 transition-colors">
                Tüm Arşivi İncele →
            </span>
            <p class="text-xs mt-2 uppercase tracking-widest opacity-60 text-[var(--text-main)]">Toplam <?php echo isset($articles) ? count($articles) : 0; ?> Makale</p>
        </a>
    </div>

</main>

<?php 
require_once ROOT . '/app/Views/inc/footer.php'; 
?>