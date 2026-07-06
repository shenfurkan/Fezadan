<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';

$lang = App::getLang();
$isEn = ($lang === 'EN');

$page_title       = $isEn
    ? 'FEZADAN | Independent Essays on Science, Culture and Thought'
    : 'FEZADAN | Bilim, Kültür ve Düşünce Üzerine Bağımsız Denemeler';
$page_description = $isEn
    ? 'FEZADAN is an independent digital publication for essays on science, technology, culture, aesthetics and critical thought.'
    : 'FEZADAN; bilim, teknoloji, kültür, estetik ve eleştirel düşünce üzerine bağımsız dijital bir yayın platformudur.';
$page_canonical   = $siteBase . ($isEn ? '/en' : '/tr');
$og_url           = $page_canonical;
$og_type          = 'website';
$og_image         = $siteBase . '/cdn/notlar-social-preview.png';
$extra_jsonld = [
    [
        '@context'  => 'https://schema.org',
        '@type'     => 'WebSite',
        'name'      => 'FEZADAN',
        'description' => $page_description,
        'url'       => $page_canonical,
        'inLanguage'=> $isEn ? 'en-US' : 'tr-TR',
        'potentialAction' => [
            '@type'  => 'SearchAction',
            'target' => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $siteBase . '/makaleler?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => 'FEZADAN',
        'url'      => $page_canonical,
        'logo'     => $siteBase . '/cdn/logo-light.png',
    ],
];

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

    .marquee { overflow: hidden; white-space: nowrap; background: var(--text-main); color: var(--bg-paper); padding: 0.8rem 0; font-family: 'Space Grotesk', monospace; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.1em; }
    .marquee-content { display: inline-block; animation: scroll 30s linear infinite; }
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

    .manifesto-section { background-color: var(--bg-secondary); color: var(--text-main); padding: 6rem 2rem; text-align: center; border-bottom: 1px solid var(--line-color); }
    
    .content-layer { position: relative; z-index: 10; padding: 1.5rem; }

    [data-theme="dark"] .grid-container { 
        border-top-color: var(--text-main) !important; 
    }
    [data-theme="dark"] .grid-item { 
        box-shadow: 6px 6px 0px var(--text-main);
        border-color: var(--text-main);
    }
    [data-theme="dark"] .grid-item:hover { 
    }

    [data-theme="dark"] .archive-section-border {
        border-bottom-color: var(--text-main) !important;
    }

    [data-theme="dark"] .hero-title {
        text-shadow: none;
    }
</style>

<main id="main-content" class="flex-grow w-full max-w-[1920px] mx-auto">
    
    <header class="px-4 pt-12 md:pt-20 pb-8 flex flex-col items-center">
        <div class="text-xs md:text-sm uppercase tracking-[0.3em] text-[var(--text-main)] mb-2 font-bold opacity-80"> <?= $isEn ? 'COLLECTIVE' : 'KOLEKTİF' ?>
        </div>
        <h1 class="hero-title select-none">FEZADAN</h1>
        <p class="max-w-xl text-center text-sm md:text-lg leading-relaxed opacity-90 px-6 font-light text-[var(--text-main)]">
            <?= $isEn ? 'Free Knowledge Platform' : 'Özgür Bilgi Platformu' ?>
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
        <?php if (empty($articles)): ?>
            <div class="md:col-span-2 lg:col-span-4 border-2 border-[var(--line-color)] bg-[var(--bg-paper)] p-8 md:p-12 text-center shadow-[6px_6px_0px_var(--line-color)]">
                <h2 class="font-syne text-2xl md:text-4xl font-bold uppercase text-[var(--text-main)] mb-4">
                    <?= $isEn ? 'English Articles Are Being Prepared' : 'Henüz Makale Yok' ?>
                </h2>
                <p class="max-w-2xl mx-auto text-sm md:text-base leading-relaxed text-[var(--text-main)] opacity-80">
                    <?= $isEn ? 'We do not show Turkish articles on the English page. New English pieces will appear here as they are published.' : 'Yayınlanan makaleler burada görünecek.' ?>
                </p>
                <?php if ($isEn): ?>
                    <a href="/tr" class="inline-block mt-6 px-5 py-3 border-2 border-[var(--text-main)] font-bold uppercase text-xs hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors">
                        Türkçe İçerikleri Gör
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php 
        $article_counter = 0;
        if(isset($articles) && is_array($articles)):
            $batch = array_slice($articles, 0, 8); 
            
            foreach($batch as $article): 
                $preview_img = !empty($article['image_url']) ? $article['image_url'] : '';
                $article_counter++;
                
                if ($article_counter === 1) {
                    $loading_attr = 'fetchpriority="high" decoding="async"';
                } elseif ($article_counter <= 2) {
                    $loading_attr = 'decoding="async"';
                } else {
                    $loading_attr = 'loading="lazy" decoding="async"';
                }
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
                             <?php echo $loading_attr; ?>
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

    <div class="border-b border-[var(--line-color)] archive-section-border bg-[var(--bg-paper)] hover:bg-[var(--bg-secondary)] transition-colors duration-300 cursor-pointer">
        <a href="<?= langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler') ?>" class="block py-12 text-center group" aria-label="<?= $isEn ? 'Explore entire archive' : 'Tüm arşivi incele' ?>">
            <span class="font-syne text-xl md:text-3xl font-bold text-[var(--text-main)] uppercase tracking-widest group-hover:opacity-70 transition-colors">
                <?= $isEn ? 'Explore Entire Archive →' : 'Tüm Arşivi İncele →' ?>
            </span>
            <p class="text-xs mt-2 uppercase tracking-widest opacity-60 text-[var(--text-main)]"><?= $isEn ? 'Total' : 'Toplam' ?> <?php echo isset($articles) ? count($articles) : 0; ?> <?= $isEn ? 'Articles' : 'Makale' ?></p>
        </a>
    </div>
</main>

<script nonce="<?= CSP_NONCE ?>">
document.addEventListener('error',function(e){var t=e.target;if(t&&t.tagName==='IMG'&&t.dataset.fallback){t.onerror=null;t.src=t.dataset.fallback}},true);
</script>

<?php 
require_once ROOT . '/app/Views/inc/footer.php'; 
?>
