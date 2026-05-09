<?php require_once ROOT . '/app/Views/inc/header.php'; ?>

<main id="main-content" class="min-h-screen bg-[var(--bg-paper)] text-[var(--text-main)]">
    <div class="max-w-7xl mx-auto px-4 py-12 md:py-20">
        
        <div class="text-center mb-16 border-b-2 border-[var(--line-color)] pb-12">
            <h1 class="font-syne text-4xl md:text-5xl font-bold mb-4 tracking-wider text-[var(--text-main)]">Galeri Arşivi</h1>
            <p class="text-lg opacity-80 uppercase tracking-widest text-[var(--text-accent)]">Geçmiş günlerin tüm eserleri</p>
        </div>

        <?php if (!empty($artworks)): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8 mb-16">
                <?php foreach ($artworks as $art): ?>
                    <a href="/galeri/<?= htmlspecialchars($art['slug']) ?>" class="group block bg-[var(--bg-secondary)] border-2 border-[var(--line-color)] hover:border-[var(--text-accent)] hover:shadow-[4px_4px_0px_var(--text-accent)] transition-all overflow-hidden flex flex-col h-full relative">
                        <div class="aspect-square w-full overflow-hidden bg-[var(--bg-paper)] flex items-center justify-center p-4 border-b-2 border-[var(--line-color)]">
                            <img src="<?= htmlspecialchars($art['thumbnail_url'] ?: $art['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($art['title']) ?>" 
                                 class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-700 ease-in-out grayscale group-hover:grayscale-0"
                                 onerror="this.onerror=null;this.src='/cdn/notlar-social-preview.png'"
                                 loading="lazy"
                                 decoding="async"
                                 referrerpolicy="no-referrer">
                        </div>
                        <div class="p-4 flex-grow flex flex-col">
                            <span class="text-[11px] bg-[#5d1818] text-[#f8ecd1] px-2.5 py-1 inline-block w-fit font-bold uppercase mb-2 font-mono tracking-wider border border-[#7e2a2a]">
                                <?= htmlspecialchars(date('d M Y', strtotime($art['date']))) ?>
                            </span>
                            <h4 class="font-syne text-lg text-[var(--text-main)] font-bold mb-1 line-clamp-2 leading-tight group-hover:text-[var(--text-accent)] transition-colors"><?= htmlspecialchars($art['title']) ?></h4>
                            <p class="text-sm text-[var(--text-main)]/85 line-clamp-1 mt-auto pt-2 font-mono"><?= htmlspecialchars($art['artist']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center gap-4 border-t-2 border-[var(--line-color)] pt-8 font-mono font-bold text-sm">
                    <?php if ($page > 1): ?>
                        <a href="/galeri/arsiv?p=<?= $page - 1 ?>" class="px-4 py-2 border-2 border-[var(--line-color)] hover:border-[var(--text-accent)] hover:text-[var(--text-accent)] hover:shadow-[2px_2px_0px_var(--text-accent)] transition-all">&larr; ÖNCEKİ</a>
                    <?php endif; ?>
                    
                    <span class="text-[var(--text-main)] opacity-60">SAYFA <?= $page ?> / <?= $totalPages ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="/galeri/arsiv?p=<?= $page + 1 ?>" class="px-4 py-2 border-2 border-[var(--line-color)] hover:border-[var(--text-accent)] hover:text-[var(--text-accent)] hover:shadow-[2px_2px_0px_var(--text-accent)] transition-all">SONRAKİ &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20">
                <p class="text-xl opacity-60">Arşivde henüz eser bulunmuyor.</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
