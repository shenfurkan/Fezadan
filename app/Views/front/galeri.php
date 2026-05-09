<?php require_once ROOT . '/app/Views/inc/header.php'; ?>

<main id="main-content" class="min-h-screen bg-[var(--bg-paper)] text-[var(--text-main)]">
    <div class="max-w-7xl mx-auto px-4 py-12 md:py-20">
        
        <!-- Header -->
        <div class="text-center mb-16 border-b-2 border-[var(--line-color)] pb-8">
            <h1 class="font-syne text-4xl md:text-6xl font-bold mb-4 tracking-wider text-[var(--text-main)]">Günün Sanat Eseri</h1>
            <p class="text-lg md:text-xl opacity-80 uppercase tracking-widest text-[var(--text-accent)]">Dünya müzelerinden seçilmiş günlük bir eser</p>
        </div>

        <?php if ($todayArt): ?>
            <!-- Today's Artwork -->
            <div class="flex flex-col lg:flex-row gap-12 items-center bg-[var(--bg-secondary)] p-6 md:p-12 border-2 border-[var(--line-color)] shadow-[8px_8px_0px_var(--line-color)]">
                
                <!-- Image -->
                <div class="w-full lg:w-1/2">
                    <img src="<?= htmlspecialchars($todayArt['image_url']) ?>" 
                         alt="<?= htmlspecialchars($todayArt['title']) ?>" 
                         class="w-full h-auto object-contain max-h-[70vh] border-2 border-[var(--line-color)] shadow-[4px_4px_0px_var(--text-accent)]"
                         onerror="this.onerror=null;this.src='/cdn/notlar-social-preview.png'"
                         loading="eager"
                         decoding="async"
                         referrerpolicy="no-referrer">
                </div>
                
                <!-- Details -->
                <div class="w-full lg:w-1/2 flex flex-col justify-center">
                    <div class="mb-6">
                        <span class="inline-block px-3 py-1 bg-[var(--text-accent)] text-[var(--bg-paper)] text-xs font-bold uppercase tracking-widest mb-4">BUGÜN</span>
                        <h2 class="font-syne text-3xl md:text-5xl font-bold text-[var(--text-main)] mb-2 leading-tight">
                            <?= htmlspecialchars($todayArt['title']) ?>
                        </h2>
                        <h3 class="text-xl md:text-2xl text-[var(--text-main)] opacity-80 font-semibold">
                            <?= htmlspecialchars($todayArt['artist']) ?>
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-8 text-sm opacity-80 border-y border-[var(--line-color)] py-6 font-mono">
                        <div><strong class="text-[var(--text-main)] block uppercase text-xs mb-1">TARİH</strong> <?= htmlspecialchars($todayArt['date_display'] ?: 'Bilinmiyor') ?></div>
                        <div><strong class="text-[var(--text-main)] block uppercase text-xs mb-1">TEKNİK</strong> <?= htmlspecialchars($todayArt['medium'] ?: 'Bilinmiyor') ?></div>
                        <div><strong class="text-[var(--text-main)] block uppercase text-xs mb-1">BOYUT</strong> <?= htmlspecialchars($todayArt['dimensions'] ?: 'Bilinmiyor') ?></div>
                        <div>
                            <strong class="text-[var(--text-main)] block uppercase text-xs mb-1">MÜZE</strong> 
                            <?php if ($todayArt['external_url']): ?>
                                <a href="<?= htmlspecialchars($todayArt['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-[var(--text-accent)] underline decoration-[var(--text-accent)] transition-colors">
                                    <?= htmlspecialchars($todayArt['provider']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($todayArt['provider']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="prose prose-invert max-w-none text-lg leading-relaxed opacity-90 mb-6 text-[var(--text-main)]">
                        <p><?= nl2br(htmlspecialchars($todayArt['description_tr'] ?: $todayArt['description_en'] ?: 'Açıklama bulunmuyor.')) ?></p>
                    </div>

                    <?php if (!empty($todayArt['wikipedia_url']) && !empty($todayArt['artist_bio']) && !in_array(mb_strtolower(trim($todayArt['artist']), 'UTF-8'), ['bilinmeyen sanatçı', 'unknown artist', 'unknown', 'anonymous', 'anonim'], true)): ?>
                        <div class="mt-2 text-sm font-bold uppercase tracking-widest">
                            <a href="<?= htmlspecialchars($todayArt['wikipedia_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-[var(--text-accent)] hover:text-[var(--text-main)] transition-colors flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                SANATÇIYI WIKIPEDIA'DA İNCELE
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-20 bg-[var(--bg-secondary)] border-2 border-[var(--line-color)]">
                <p class="text-xl opacity-60 font-mono">Bugün için eser bulunamadı. Lütfen daha sonra tekrar deneyin.</p>
            </div>
        <?php endif; ?>

        <!-- Grid of past artworks -->
        <?php if (!empty($gridArtworks)): ?>
            <div class="mt-32">
                <div class="flex justify-between items-end border-b-2 border-[var(--line-color)] pb-4 mb-12">
                    <h3 class="font-syne text-3xl text-[var(--text-main)] font-bold">GEÇMİŞ ESERLER</h3>
                    <a href="/galeri/arsiv" class="text-[var(--text-accent)] hover:text-[var(--text-main)] uppercase tracking-widest text-sm font-bold transition-colors">TÜM ARŞİV →</a>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8">
                    <?php foreach ($gridArtworks as $art): ?>
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
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
