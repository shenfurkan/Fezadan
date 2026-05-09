<?php 
// Prepare JSON-LD for VisualArtwork
$jsonld = [
    "@context" => "https://schema.org",
    "@type" => "VisualArtwork",
    "name" => $art['title'],
    "image" => $art['image_url'],
    "description" => $art['description_tr'] ?: $art['description_en'],
    "creator" => [
        "@type" => "Person",
        "name" => $art['artist']
    ],
    "dateCreated" => $art['date_display'],
    "artMedium" => $art['medium'],
    "artDimensions" => $art['dimensions']
];
$extra_jsonld = [$jsonld];
$page_canonical = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org') . '/galeri/' . $art['slug'];

require_once ROOT . '/app/Views/inc/header.php'; 
?>

<main id="main-content" class="min-h-screen bg-[var(--bg-paper)] text-[var(--text-main)]">
    <div class="max-w-6xl mx-auto px-4 py-8 md:py-16">
        
        <!-- Back and Navigation -->
        <div class="flex justify-between items-center mb-10 border-b-2 border-[var(--line-color)] pb-4">
            <a href="/galeri" class="text-sm uppercase tracking-widest text-[var(--text-main)] hover:text-[var(--text-accent)] transition-colors flex items-center gap-2 font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                GALERİYE DÖN
            </a>
            
            <div class="flex gap-4 text-sm font-bold uppercase tracking-widest">
                <?php if ($prevDate): ?>
                    <a href="/galeri/<?= htmlspecialchars($prevDate) ?>" class="text-[var(--text-main)] hover:text-[var(--text-accent)] transition-colors" title="Önceki Gün">← ÖNCEKİ</a>
                <?php else: ?>
                    <span class="opacity-30 cursor-not-allowed">← ÖNCEKİ</span>
                <?php endif; ?>
                
                <span class="opacity-30">|</span>
                
                <?php if ($nextDate): ?>
                    <a href="/galeri/<?= htmlspecialchars($nextDate) ?>" class="text-[var(--text-main)] hover:text-[var(--text-accent)] transition-colors" title="Sonraki Gün">SONRAKİ →</a>
                <?php else: ?>
                    <span class="opacity-30 cursor-not-allowed">SONRAKİ →</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Artwork Detail -->
        <article class="bg-[var(--bg-secondary)] p-6 md:p-16 border-2 border-[var(--line-color)] shadow-[8px_8px_0px_var(--line-color)]">
            <div class="flex justify-center mb-16">
                <img src="<?= htmlspecialchars($art['image_url']) ?>" 
                     alt="<?= htmlspecialchars($art['title']) ?>" 
                     class="max-w-full h-auto object-contain max-h-[85vh] border-2 border-[var(--line-color)] shadow-[4px_4px_0px_var(--text-accent)]"
                     onerror="this.onerror=null;this.src='/cdn/notlar-social-preview.png'"
                     loading="eager"
                     decoding="async"
                     referrerpolicy="no-referrer">
            </div>
            
            <div class="max-w-3xl mx-auto text-center">
                <span class="text-[var(--text-accent)] font-mono text-sm tracking-widest mb-4 block">ARŞİV: <?= htmlspecialchars(date('d F Y', strtotime($art['date']))) ?></span>
                <h1 class="font-syne text-4xl md:text-6xl font-bold text-[var(--text-main)] mb-4"><?= htmlspecialchars($art['title']) ?></h1>
                <h2 class="text-2xl md:text-3xl text-[var(--text-main)] mb-12 opacity-80"><?= htmlspecialchars($art['artist']) ?></h2>
            </div>
            
            <div class="max-w-3xl mx-auto border-t-2 border-[var(--line-color)] pt-10 mt-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12 text-lg font-mono">
                    <div class="space-y-4">
                        <div><strong class="text-[var(--text-main)] uppercase text-xs tracking-widest block mb-1">TARİH</strong> <?= htmlspecialchars($art['date_display'] ?: 'Bilinmiyor') ?></div>
                        <div><strong class="text-[var(--text-main)] uppercase text-xs tracking-widest block mb-1">TEKNİK</strong> <?= htmlspecialchars($art['medium'] ?: 'Bilinmiyor') ?></div>
                        <div><strong class="text-[var(--text-main)] uppercase text-xs tracking-widest block mb-1">BOYUT</strong> <?= htmlspecialchars($art['dimensions'] ?: 'Bilinmiyor') ?></div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <strong class="text-[var(--text-main)] uppercase text-xs tracking-widest block mb-1">KOLEKSİYON</strong> 
                            <?php if ($art['external_url']): ?>
                                <a href="<?= htmlspecialchars($art['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-[var(--text-accent)] underline decoration-dotted transition-colors">
                                    <?= htmlspecialchars($art['provider']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($art['provider']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($art['is_public_domain']): ?>
                            <div><strong class="text-[var(--text-main)] uppercase text-xs tracking-widest block mb-1">LİSANS</strong> Public Domain (CC0)</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="prose prose-invert prose-lg max-w-none prose-p:leading-relaxed prose-p:mb-6 text-[var(--text-main)]">
                    <p><?= nl2br(htmlspecialchars($art['description_tr'] ?: $art['description_en'] ?: 'Açıklama bulunmuyor.')) ?></p>
                </div>

                <?php if ($art['artist_bio']): ?>
                    <div class="mt-12 p-6 bg-[var(--bg-paper)] border-l-4 border-[var(--text-accent)]">
                        <h3 class="text-[var(--text-main)] uppercase text-sm tracking-widest font-bold mb-3">SANATÇI HAKKINDA</h3>
                        <p class="text-sm opacity-80"><?= htmlspecialchars(strip_tags($art['artist_bio'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($art['wikipedia_url']) && !empty($art['artist_bio']) && !in_array(mb_strtolower(trim($art['artist']), 'UTF-8'), ['bilinmeyen sanatçı', 'unknown artist', 'unknown', 'anonymous', 'anonim'], true)): ?>
                    <div class="mt-8 text-center">
                        <a href="<?= htmlspecialchars($art['wikipedia_url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 border-2 border-[var(--line-color)] text-sm uppercase tracking-widest hover:bg-[var(--text-accent)] hover:border-[var(--text-accent)] hover:text-[var(--bg-paper)] transition-all font-bold">
                            SANATÇIYI WIKIPEDIA'DA İNCELE
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </article>

    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
