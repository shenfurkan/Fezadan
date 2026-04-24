<?php 
    $page_title = "Arşiv";
    include __DIR__ . '/../inc/notes_header.php'; 
?>

    <main class="flex-1 relative z-10 max-w-7xl mx-auto px-6 py-12 w-full flex flex-col min-h-screen">
        
        <form action="/" method="GET" class="mb-12 flex flex-col lg:flex-row gap-4 bg-[var(--bg-secondary)] p-6 border-2 border-[var(--text-main)] shadow-[8px_8px_0px_var(--text-main)]">
            
            <input type="text" name="search" value="<?php echo htmlspecialchars($current_search ?? ''); ?>" placeholder="Notlarda ara..." class="w-full lg:flex-1 px-4 py-3 bg-[var(--bg-paper)] border-2 border-[var(--text-main)] font-mono text-lg outline-none focus:shadow-[4px_4px_0px_var(--text-accent)] transition-all placeholder:opacity-50 text-[var(--text-main)]">
            
            <select name="cat" class="w-full lg:w-auto px-4 py-3 bg-[var(--bg-paper)] border-2 border-[var(--text-main)] font-mono text-sm font-bold uppercase cursor-pointer outline-none focus:shadow-[4px_4px_0px_var(--text-accent)] transition-all text-[var(--text-main)]">
                <option value="0">TÜM KATEGORİLER</option>
                <?php foreach($categories as $cat): 
                    // Eğer controller'dan note_count geliyorsa ve 0'dan büyükse VEYA controller sadece dolu kategorileri gönderdiyse listele
                    if(!isset($cat['note_count']) || $cat['note_count'] > 0):
                ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($current_cat) && $current_cat == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php 
                    endif;
                endforeach; ?>
            </select>

            <select name="lang" class="w-full lg:w-auto px-4 py-3 bg-[var(--bg-paper)] border-2 border-[var(--text-main)] font-mono text-sm font-bold uppercase cursor-pointer outline-none focus:shadow-[4px_4px_0px_var(--text-accent)] transition-all text-[var(--text-main)]">
                <option value="">TÜM DİLLER</option>
                <option value="tr" <?php echo (isset($current_lang) && $current_lang == 'tr') ? 'selected' : ''; ?>>TÜRKÇE</option>
                <option value="en" <?php echo (isset($current_lang) && $current_lang == 'en') ? 'selected' : ''; ?>>İNGİLİZCE</option>
            </select>
            
            <button type="submit" class="bg-[#6D2323] text-[#FEF9E1] px-8 py-3 font-bold uppercase font-syne hover:bg-black transition-colors border-2 border-[#6D2323] hover:border-black">FİLTRELE</button>
            
            <?php if(!empty($current_search) || (isset($current_cat) && $current_cat > 0) || !empty($current_lang)): ?>
                <a href="/" class="flex items-center justify-center px-6 py-3 border-2 border-[var(--text-accent)] text-[var(--text-accent)] font-bold hover:bg-[var(--text-accent)] hover:text-white transition-colors bg-[var(--bg-paper)]">SIFIRLA</a>
            <?php endif; ?>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 flex-1 content-start">
            <?php if(!empty($notes)): foreach($notes as $note): ?>
                
                <a href="/not/<?php echo $note['slug']; ?>" class="bg-[var(--bg-paper)] border-2 border-[var(--text-main)] p-6 flex flex-col h-full group shadow-[4px_4px_0px_var(--text-main)] hover:-translate-y-1 hover:shadow-[8px_8px_0px_var(--text-main)] transition-all">
                    
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex flex-wrap gap-1 flex-1 pr-2">
                            <?php 
                            if(!empty($note['category_names'])):
                                $cats = explode(', ', $note['category_names']);
                                foreach($cats as $c): 
                            ?>
                                <span class="text-[10px] font-mono bg-[var(--text-main)] text-[var(--bg-paper)] px-2 py-1 uppercase font-bold">
                                    <?php echo htmlspecialchars($c); ?>
                                </span>
                            <?php endforeach; endif; ?>
                        </div>
                        
                        <span class="text-[10px] font-mono border-2 border-[var(--text-accent)] text-[var(--text-accent)] px-2 py-0.5 font-bold uppercase flex-shrink-0">
                            <?php echo strtoupper(htmlspecialchars($note['lang'] ?? 'TR')); ?>
                        </span>
                    </div>
                    
                    <h3 class="font-syne text-xl font-bold mb-3 group-hover:text-[var(--text-accent)] transition-colors line-clamp-2 leading-tight">
                        <?php echo htmlspecialchars($note['title']); ?>
                    </h3>
                    
                    <p class="font-sans text-sm opacity-80 mb-6 line-clamp-3 flex-1">
                        <?php echo htmlspecialchars($note['description']); ?>
                    </p>
                    
                    <div class="border-t-2 border-dashed border-[var(--text-main)]/30 pt-4 flex justify-between items-center font-mono text-[10px] mt-auto">
                        <span class="opacity-60 uppercase font-bold">YÜKLEYEN: <?php echo htmlspecialchars($note['uploader_name']); ?></span>
                        <span class="font-bold text-[var(--text-accent)] group-hover:translate-x-2 transition-transform">OKU →</span>
                    </div>
                </a>
            <?php endforeach; else: ?>
                <div class="col-span-full border-2 border-dashed border-[var(--text-main)]/50 p-12 text-center bg-[var(--bg-paper)]">
                    <p class="font-mono opacity-50 uppercase text-lg">// Arama kriterlerine uygun not bulunamadı.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php 
        $current_page = $current_page ?? 1;
        $total_pages = $total_pages ?? 1;
        
        if($total_pages > 1): 
        ?>
        <div class="mt-16 flex justify-center gap-2 font-mono text-sm mb-8">
            <?php if($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($current_search ?? ''); ?>&cat=<?php echo $current_cat ?? 0; ?>&lang=<?php echo urlencode($current_lang ?? ''); ?>" class="px-4 py-2 border-2 border-[var(--text-main)] text-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors font-bold bg-[var(--bg-paper)]">← ÖNCEKİ</a>
            <?php endif; ?>

            <span class="px-6 py-2 border-2 border-[var(--text-main)] bg-[var(--text-main)] text-[var(--bg-paper)] font-bold">
                <?php echo $current_page; ?> / <?php echo $total_pages; ?>
            </span>

            <?php if($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($current_search ?? ''); ?>&cat=<?php echo $current_cat ?? 0; ?>&lang=<?php echo urlencode($current_lang ?? ''); ?>" class="px-4 py-2 border-2 border-[var(--text-main)] text-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)] transition-colors font-bold bg-[var(--bg-paper)]">SONRAKİ →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>
<?php 
require_once ROOT . '/app/Views/inc/footer.php'; 
?>