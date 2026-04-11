<?php
$page_title = htmlspecialchars($author['name']) . ' | FEZADAN Yazar Profili';
require_once ROOT . '/app/Views/inc/header.php';
?>

<style>
    .font-body { font-family: 'EB Garamond', serif; }
    .texture-overlay {
        position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.06;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
        mix-blend-mode: multiply;
    }
    .brutalist-card {
        border: 2px solid var(--line-color);
        box-shadow: 6px 6px 0px var(--line-color);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .brutalist-card:hover {
        transform: translate(-4px, -4px);
        box-shadow: 10px 10px 0px var(--text-accent);
        border-color: var(--text-accent);
    }
    
    .author-sidebar-card, 
    .author-sidebar-card h1, 
    .author-sidebar-card a, 
    .author-sidebar-card .author-bio-text {
        color: #1a1a1a !important;
    }

    .author-sidebar-card a:hover {
        color: #A31D1D !important;
    }
    
    .author-fixed-border {
        border-color: #6D2323 !important;
    }
</style>

<div class="texture-overlay"></div>

<main class="relative z-10 w-full px-6 py-12 md:py-24 max-w-[1400px] mx-auto flex-grow">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-20">
        
        <aside class="lg:col-span-4 lg:sticky lg:top-32 h-fit">
            
            <div class="bg-[#EAD3AD] border-2 border-[var(--line-color)] shadow-[8px_8px_0px_#6D2323] p-8 md:p-10 flex flex-col items-center text-center author-sidebar-card author-fixed-border">
                
                <div class="mb-6 mx-auto">
                    <div class="w-36 h-36 md:w-48 md:h-48 rounded-full border-2 border-[var(--line-color)] p-1 shadow-[4px_4px_0px_#6D2323] bg-[var(--bg-paper)] group author-fixed-border">
                        <img src="<?php echo !empty($author['image_url']) ? SITE_URL . '/' . ltrim($author['image_url'], '/') : SITE_URL . '/assets/default-avatar.jpg'; ?>" 
                             class="w-full h-full object-cover rounded-full grayscale group-hover:grayscale-0 transition-all duration-500" 
                             alt="<?php echo htmlspecialchars($author['name']); ?>">
                    </div>
                </div>

                <h1 class="font-syne text-4xl md:text-5xl font-bold leading-none tracking-tight text-[var(--text-main)] uppercase mb-4">
                    <?php echo htmlspecialchars($author['name']); ?>
                </h1>

                <div class="font-body text-lg leading-relaxed text-[#1a1a1a] author-bio-text opacity-90 mb-8">
                    <?php echo nl2br(htmlspecialchars($author['bio'] ?: 'Henüz bir biyografi eklenmedi.')); ?>
                </div>

                <?php if (!empty($author['twitter']) || !empty($author['instagram']) || !empty($author['website'])): ?>
                    <div class="flex flex-col gap-4 font-mono text-sm uppercase tracking-widest border-t border-[var(--line-color)] border-opacity-30 pt-6 w-full items-center">
                        
                        <?php if (!empty($author['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($author['twitter']); ?>" target="_blank" class="flex items-center gap-3 text-[var(--text-main)] hover:text-[var(--text-accent)] transition-colors">
                                <img src="<?php echo SITE_URL; ?>/assets/uploads/twitter.png" alt="Twitter" class="w-5 h-5 object-contain"> 
                                Twitter (X)
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($author['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($author['instagram']); ?>" target="_blank" class="flex items-center gap-3 text-[var(--text-main)] hover:text-[var(--text-accent)] transition-colors">
                                <img src="<?php echo SITE_URL; ?>/assets/uploads/instagram.png" alt="Instagram" class="w-5 h-5 object-contain"> 
                                Instagram
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($author['website'])): ?>
                            <a href="<?php echo htmlspecialchars($author['website']); ?>" target="_blank" class="flex items-center gap-3 text-[var(--text-main)] hover:text-[var(--text-accent)] transition-colors">
                                <span class="text-[var(--text-accent)] text-lg leading-none">▣</span> 
                                Kişisel Websitesi
                            </a>
                        <?php endif; ?>
                        
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <div class="lg:col-span-8 space-y-20">
            
            <section>
                <div class="flex items-baseline justify-between border-b-2 border-[var(--text-main)] pb-4 mb-8">
                    <h2 class="font-syne text-3xl font-bold uppercase tracking-wide text-[var(--text-main)]">
                        Seçkiler
                    </h2>
                    <span class="font-mono text-xs tracking-widest opacity-50 uppercase">Öne Çıkanlar</span>
                </div>

                <?php if (!empty($featured_articles)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php foreach ($featured_articles as $article): ?>
                            <a href="/makale/<?php echo $article['slug']; ?>" class="brutalist-card bg-[var(--bg-paper)] group block">
                                <?php if (!empty($article['image_url'])): ?>
                                    <div class="aspect-video w-full overflow-hidden border-b-2 border-[var(--line-color)]">
                                        <img src="<?php echo SITE_URL . '/' . ltrim($article['image_url'], '/'); ?>" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500">
                                    </div>
                                <?php endif; ?>
                                <div class="p-6">
                                    <div class="font-mono text-[10px] text-[var(--text-accent)] tracking-widest uppercase mb-3 font-bold">
                                        <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                    </div>
                                    <h3 class="font-syne text-xl font-bold leading-tight mb-3 group-hover:text-[var(--text-accent)] transition-colors line-clamp-2">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </h3>
                                    <p class="font-body text-sm opacity-80 line-clamp-2">
                                        <?php echo htmlspecialchars($article['short_desc']); ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-8 border-2 border-dashed border-[var(--line-color)] opacity-50 font-mono text-sm text-center">
                        // Yazar henüz öne çıkan bir makale seçmedi.
                    </div>
                <?php endif; ?>
            </section>

            <section>
                <div class="flex items-baseline justify-between border-b border-[var(--line-color)] pb-4 mb-8">
                    <h2 class="font-syne text-2xl font-bold uppercase tracking-wide text-[var(--text-main)]">
                        Tüm Makaleler
                    </h2>
                    <span class="font-mono text-xs tracking-widest opacity-50 uppercase">Arşiv</span>
                </div>

                <?php if (!empty($all_articles)): ?>
                    <div class="flex flex-col gap-4">
                        <?php foreach ($all_articles as $article): ?>
                            <a href="/makale/<?php echo $article['slug']; ?>" class="group flex flex-col md:flex-row md:items-center justify-between p-4 border border-transparent hover:border-[var(--line-color)] hover:bg-[var(--bg-secondary)]/10 transition-all gap-4">
                                <div class="flex-grow">
                                    <h3 class="font-syne text-lg font-bold group-hover:text-[var(--text-accent)] transition-colors">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </h3>
                                    <p class="font-body text-sm opacity-70 mt-1 line-clamp-1">
                                        <?php echo htmlspecialchars($article['short_desc']); ?>
                                    </p>
                                </div>
                                <div class="font-mono text-xs text-[var(--text-accent)] font-bold shrink-0 md:text-right">
                                    <?php echo date('d.m.Y', strtotime($article['created_at'])); ?> <br>
                                    <span class="opacity-50 text-[10px]"><?php echo $article['reads']; ?> OKUNMA</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="font-mono text-sm opacity-50">
                        // Bu yazarın henüz yayında olan bir makalesi bulunmuyor.
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>