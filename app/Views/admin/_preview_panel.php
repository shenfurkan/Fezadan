<?php
$preview_date = $preview_date ?? date('d F Y');
$preview_title = isset($article['title']) ? (string)$article['title'] : 'Başlık buraya gelecek...';
$preview_desc = isset($article['short_desc']) ? (string)$article['short_desc'] : '';
$preview_image = isset($article['image_url']) ? (string)$article['image_url'] : '';
?>
<section id="previewPanel" class="hidden fixed inset-x-0 top-20 bottom-0 z-40 overflow-y-auto bg-[var(--bg-paper)] text-[var(--text-main)] p-6 md:p-10">
    <div class="max-w-5xl mx-auto grid grid-cols-1 xl:grid-cols-[1fr_18rem] gap-8">
        <article class="bg-[var(--bg-paper)] border-2 border-[var(--text-main)] shadow-[8px_8px_0px_var(--text-main)] p-6 md:p-10">
            <div class="flex flex-wrap items-center gap-3 font-mono text-[10px] uppercase tracking-wider opacity-70 mb-5">
                <span id="prev-cats">KATEGORİ SEÇİLMEDİ</span>
                <span aria-hidden="true">/</span>
                <span><?php echo htmlspecialchars($preview_date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                <span aria-hidden="true">/</span>
                <span><span id="prev-readtime">1</span> dk okuma</span>
            </div>

            <h1 id="prev-title" class="font-syne text-4xl md:text-6xl font-black uppercase leading-none mb-6">
                <?php echo htmlspecialchars($preview_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </h1>

            <p id="prev-desc" class="<?php echo $preview_desc === '' ? 'hidden ' : ''; ?>font-mono text-sm md:text-base border-l-4 border-[var(--text-accent)] pl-4 mb-8 opacity-80">
                <?php echo htmlspecialchars($preview_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </p>

            <figure id="prev-cover-wrap" class="<?php echo $preview_image === '' ? 'hidden ' : ''; ?>mb-8 border-2 border-[var(--text-main)] bg-[var(--bg-secondary)]/20">
                <img id="prev-cover" src="<?php echo htmlspecialchars($preview_image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="" class="w-full aspect-video object-cover">
            </figure>

            <div id="prev-content" class="prose max-w-none font-serif text-lg leading-8 text-[var(--text-main)]">
                <p class="opacity-40 italic">İçerik editörden yansıyacak...</p>
            </div>

            <section id="prev-refs-wrap" class="hidden mt-10 pt-6 border-t-2 border-[var(--line-color)]">
                <h2 class="font-syne text-xl font-black uppercase mb-4">Kaynakça</h2>
                <ol id="prev-refs" class="space-y-2 font-mono text-xs leading-6"></ol>
            </section>
        </article>

        <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
            <div class="border-2 border-[var(--text-main)] bg-[var(--bg-secondary)]/20 p-4">
                <h2 class="font-syne text-sm font-black uppercase mb-3">İçindekiler</h2>
                <nav id="prev-toc" class="font-mono text-xs space-y-2 opacity-80">
                    <p class="opacity-50">Başlıklar eklendikçe burada görünür.</p>
                </nav>
            </div>
        </aside>
    </div>
</section>
