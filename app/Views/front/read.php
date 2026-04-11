<?php
$page_title = isset($article['title']) ? htmlspecialchars($article['title']) . " | FEZADAN" : 'Makale | FEZADAN';
require_once ROOT . '/app/Views/inc/header.php';

$word_count = str_word_count(strip_tags($article['content']));
$reading_time = ceil($word_count / 200);
if ($reading_time < 1)
    $reading_time = 1;

$total_seconds = $reading_time * 60;
$threshold_seconds = ceil($total_seconds * 0.20);

if ($threshold_seconds < 10)
    $threshold_seconds = 10;

require_once ROOT . '/app/Controllers/MakaleController.php';
$secretKey = getenv('SECRET_KEY');
?>

<style>
    .font-body {
        font-family: 'EB Garamond', serif;
    }

    .texture-overlay {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        opacity: 0.06;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
        mix-blend-mode: multiply;
    }

    #progress-bar {
        position: fixed;
        top: 0;
        left: 0;
        height: 4px;
        background: #A31D1D;
        width: 0%;
        z-index: 9999;
        transition: width 0.1s;
    }

    .journal-text {
        font-size: 1.25rem;
        line-height: 1.8;
        color: #1a1a1a;
    }

    [data-theme="dark"] .journal-text {
        color: var(--text-main);
    }

    .journal-text h1 {
        color: var(--text-main) !important;
    }

    .journal-text h2,
    .journal-text h3 {
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        color: var(--text-accent);
        letter-spacing: -0.02em;
        scroll-margin-top: 100px;
    }

    .main-article-title {
        color: #1a1a1a;
    }

    [data-theme="dark"] .main-article-title {
        color: var(--text-main);
    }

    ::selection {
        background: var(--text-accent);
        color: var(--bg-paper);
    }

    .journal-text ul {
        list-style-type: disc;
        padding-left: 1.5em;
        margin-bottom: 1.5em;
        marker: #A31D1D;
    }

    ::selection {
        background: #A31D1D;
        color: #FEF9E1;
    }

    .reference-highlight {
        background-color: rgba(163, 29, 29, 0.1) !important;
        border: 1px solid rgba(163, 29, 29, 0.3) !important;
        transition: all 0.5s ease;
    }

    [data-theme="dark"] .reference-highlight {
        background-color: rgba(255, 92, 92, 0.15) !important;
        border: 1px solid rgba(255, 92, 92, 0.3) !important;
    }

    /* ===== ToC ===== */

    .article-grid {
        display: grid;
        grid-template-columns: 1fr;
        max-width: 1920px;
        margin: 0 auto;
        position: relative;
        z-index: 10;
    }

    @media (min-width: 1280px) {
        .article-grid {
            grid-template-columns: 1fr 56rem 1fr;
        }
    }

    /* Masaüstü ToC */
    #toc-sidebar {
        display: none;
        position: sticky;
        top: 100px;
        justify-self: end;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        padding-right: 2rem;
        padding-top: 1.5rem;
        width: 15rem;
        /* Hide scrollbar */
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    #toc-sidebar::-webkit-scrollbar {
        display: none;
    }

    @media (min-width: 1280px) {
        #toc-sidebar {
            display: block;
        }
    }

    .toc-title {
        font-family: 'Syne', sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-accent);
        cursor: pointer;
        margin-bottom: 1.25rem;
        line-height: 1.3;
        transition: color 0.2s;
        text-decoration: none;
        display: block;
    }

    .toc-title:hover {
        opacity: 0.8;
    }

    .toc-list {
        position: relative;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .toc-track {
        position: absolute;
        right: -1rem;
        top: 0;
        width: 2px;
        background: var(--line-color);
        opacity: 0.4;
        border-radius: 1px;
    }

    .toc-progress {
        position: absolute;
        right: -1rem;
        top: 0;
        width: 2px;
        height: 0%;
        background: var(--text-accent);
        border-radius: 1px;
        transition: height 0.15s ease-out;
        z-index: 1;
    }

    .toc-item {
        position: relative;
        margin-bottom: 0.15rem;
    }

    .toc-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 1rem 0.35rem 0;
        text-decoration: none;
        color: var(--text-main);
        opacity: 0.25;
        font-size: 0.95rem;
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 600;
        line-height: 1.35;
        transition: all 0.2s ease;
        text-align: right;
        justify-content: flex-end;
    }

    [data-theme="dark"] .toc-link {
        opacity: 0.2;
    }

    .toc-link:hover {
        opacity: 0.7;
        color: var(--text-accent);
    }

    .toc-item[data-level="3"] .toc-link {
        font-size: 0.85rem;
        padding-right: 0.5rem;
        opacity: 0.2;
    }

    [data-theme="dark"] .toc-item[data-level="3"] .toc-link {
        opacity: 0.15;
    }

    .toc-dot {
        position: absolute;
        right: calc(-1rem - 3px);
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        box-sizing: border-box;
        border-radius: 50%;
        background: var(--line-color);
        border: 2px solid var(--bg-paper);
        z-index: 2;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .toc-item[data-level="3"] .toc-dot {
        width: 6px;
        height: 6px;
        right: calc(-1rem - 2px);
    }

    .toc-item.toc-active .toc-link {
        opacity: 1;
        color: var(--text-accent);
    }

    [data-theme="dark"] .toc-item.toc-active .toc-link {
        opacity: 1;
        color: var(--text-accent);
    }

    .toc-item.toc-active .toc-dot {
        background: var(--text-accent);
        box-shadow: 0 0 0 3px rgba(163, 29, 29, 0.2);
    }

    [data-theme="dark"] .toc-item.toc-active .toc-dot {
        box-shadow: 0 0 0 3px rgba(255, 92, 92, 0.3);
    }

    .toc-item.toc-passed .toc-dot {
        background: var(--text-accent);
    }

    .toc-item.toc-passed .toc-link {
        opacity: 0.6;
        color: var(--text-accent);
    }

    [data-theme="dark"] .toc-item.toc-passed .toc-link {
        opacity: 0.5;
        color: var(--text-accent);
    }

    .toc-item[data-level="1"] .toc-link {
        font-family: 'Syne', sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-accent);
        opacity: 1;
        line-height: 1.3;
        padding-right: 2rem;
        padding-bottom: 0.5rem;
    }

    [data-theme="dark"] .toc-item[data-level="1"] .toc-link {
        opacity: 1;
        color: var(--text-accent);
    }

    .toc-item[data-level="1"] .toc-dot {
        width: 10px;
        height: 10px;
        right: calc(-1rem - 4px);
        background: var(--text-accent);
        border: none;
    }

    .toc-refs-item .toc-dot,
    .toc-refs-item .toc-mobile-dot {
        width: 10px;
        height: 10px;
        background: var(--line-color);
        border: none;
    }

    .toc-refs-item .toc-dot {
        right: calc(-1rem - 4px);
    }

    .toc-refs-item .toc-mobile-dot {
        left: calc(0.5rem - 4px);
    }

    .toc-refs-item.toc-active .toc-dot,
    .toc-refs-item.toc-active .toc-mobile-dot {
        background: var(--text-accent);
    }

    /* ===== Mobil ToC ===== */
    #toc-mobile-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    #toc-mobile-overlay.toc-drawer-open {
        opacity: 1;
        pointer-events: auto;
    }

    #toc-mobile-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 85%;
        max-width: 320px;
        height: 100dvh;
        background: var(--bg-paper);
        border-left: 1px solid var(--line-color);
        z-index: 999;
        transform: translateX(100%);
        transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        overflow-y: auto;
        padding: 2rem 1.5rem 2rem 1.5rem;
        display: flex;
        flex-direction: column;
    }

    #toc-mobile-drawer.toc-drawer-open {
        transform: translateX(0);
    }

    .toc-drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--line-color);
    }

    .toc-drawer-label {
        font-family: 'Syne', sans-serif;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--text-accent);
    }

    .toc-drawer-close {
        background: none;
        border: none;
        color: var(--text-main);
        cursor: pointer;
        padding: 0.25rem;
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .toc-drawer-close:hover {
        opacity: 1;
    }

    /* Mobile ToC list styling */
    .toc-mobile-list {
        position: relative;
        list-style: none;
        padding: 0;
        margin: 0;
        flex: 1;
    }

    .toc-mobile-track {
        position: absolute;
        left: 0.5rem;
        top: 0;
        width: 2px;
        background: var(--line-color);
        opacity: 0.4;
        border-radius: 1px;
    }

    .toc-mobile-progress {
        position: absolute;
        left: 0.5rem;
        top: 0;
        width: 2px;
        height: 0%;
        background: var(--text-accent);
        border-radius: 1px;
        transition: height 0.15s ease-out;
        z-index: 1;
    }

    .toc-mobile-item {
        position: relative;
        margin-bottom: 0.1rem;
    }

    .toc-mobile-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.4rem 0 0.4rem 2rem;
        text-decoration: none;
        color: var(--text-main);
        opacity: 0.25;
        font-size: 1rem;
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 600;
        line-height: 1.35;
        transition: all 0.2s ease;
    }

    [data-theme="dark"] .toc-mobile-link {
        opacity: 0.2;
    }

    .toc-mobile-link:hover {
        opacity: 0.7;
        color: var(--text-accent);
    }

    .toc-mobile-item[data-level="3"] .toc-mobile-link {
        font-size: 0.88rem;
        padding-left: 2.75rem;
        opacity: 0.2;
    }

    [data-theme="dark"] .toc-mobile-item[data-level="3"] .toc-mobile-link {
        opacity: 0.15;
    }

    .toc-mobile-dot {
        position: absolute;
        left: calc(0.5rem - 3px);
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        box-sizing: border-box;
        border-radius: 50%;
        background: var(--line-color);
        border: 2px solid var(--bg-paper);
        z-index: 2;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .toc-mobile-item[data-level="3"] .toc-mobile-dot {
        width: 6px;
        height: 6px;
        left: calc(0.5rem - 2px);
    }

    .toc-mobile-item.toc-active .toc-mobile-link {
        opacity: 1;
        color: var(--text-accent);
    }

    [data-theme="dark"] .toc-mobile-item.toc-active .toc-mobile-link {
        opacity: 1;
        color: var(--text-accent);
    }

    .toc-mobile-item.toc-active .toc-mobile-dot {
        background: var(--text-accent);
        box-shadow: 0 0 0 3px rgba(163, 29, 29, 0.2);
    }

    [data-theme="dark"] .toc-mobile-item.toc-active .toc-mobile-dot {
        box-shadow: 0 0 0 3px rgba(255, 92, 92, 0.3);
    }

    .toc-mobile-item.toc-passed .toc-mobile-dot {
        background: var(--text-accent);
    }

    .toc-mobile-item.toc-passed .toc-mobile-link {
        opacity: 0.6;
        color: var(--text-accent);
    }

    [data-theme="dark"] .toc-mobile-item.toc-passed .toc-mobile-link {
        opacity: 0.5;
        color: var(--text-accent);
    }

    .toc-mobile-item[data-level="1"] .toc-mobile-link {
        font-family: 'Syne', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-accent);
        opacity: 1;
        line-height: 1.3;
        padding-left: 1.25rem;
        padding-bottom: 0.5rem;
    }

    .toc-mobile-item[data-level="1"] .toc-mobile-dot {
        width: 10px;
        height: 10px;
        left: calc(0.5rem - 4px);
        background: var(--text-accent);
        border: none;
    }

    .toc-mobile-title {
        font-family: 'Syne', sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-accent);
        cursor: pointer;
        margin-bottom: 1rem;
        line-height: 1.3;
        text-decoration: none;
        display: block;
        padding-left: 1.5rem;
    }

    .toc-mobile-title:hover {
        opacity: 0.8;
    }

    @media (min-width: 1280px) {
        #scrollTopBtn .toc-toggle-icon {
            display: none;
        }

        #scrollTopBtn .scroll-top-icon {
            display: block;
        }
    }

    @media (max-width: 1279px) {
        #scrollTopBtn .toc-toggle-icon {
            display: block;
        }

        #scrollTopBtn .scroll-top-icon {
            display: none;
        }
    }

    #toggle-refs {
        cursor: pointer;
    }
</style>

<div id="progress-bar"></div>
<div class="texture-overlay"></div>

<div id="toc-mobile-overlay"></div>

<div id="toc-mobile-drawer">
    <div class="toc-drawer-header">
        <span class="toc-drawer-label">İçindekiler</span>
        <button class="toc-drawer-close" id="toc-drawer-close-btn" aria-label="Kapat">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <a href="#" class="toc-mobile-title" id="toc-mobile-title"></a>
    <ul class="toc-mobile-list" id="toc-mobile-list">
        <div class="toc-mobile-track"></div>
        <div class="toc-mobile-progress" id="toc-mobile-progress"></div>
    </ul>
</div>

<div class="article-grid flex-grow">

    <aside id="toc-sidebar">
        <a href="#" class="toc-title" id="toc-title"></a>
        <ul class="toc-list" id="toc-list">
            <div class="toc-track"></div>
            <div class="toc-progress" id="toc-progress"></div>
        </ul>
    </aside>

    <main class="relative z-10 w-full px-6 py-12 md:py-20 min-w-0">
        <article>
            <header class="mb-12 text-center md:text-left border-b border-[var(--line-color)] pb-10">
                <div
                    class="flex flex-wrap justify-center md:justify-start items-center gap-2 md:gap-3 font-mono text-xs md:text-sm text-[var(--text-accent)] mb-6 uppercase tracking-wider font-bold">

                    <span>
                        <?php echo isset($article['created_at']) ? date('d F Y', strtotime($article['created_at'])) : date('d F Y'); ?>
                    </span>

                    <span class="text-[var(--text-accent)] opacity-60 font-light px-1">&mdash;</span>

                    <div class="flex gap-3">
                        <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                        <a href="/makaleler?cat=<?php echo $cat['id']; ?>"
                            class="hover:text-[var(--text-main)] hover:underline decoration-2 underline-offset-4 transition-all cursor-pointer">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <span class="opacity-50">GENEL</span>
                        <?php endif; ?>
                    </div>

                    <span class="text-[var(--text-accent)] opacity-60 font-light px-1">&mdash;</span>

                    <span class="flex items-center gap-2 text-[var(--text-accent)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo isset($reading_time) ? $reading_time : '1'; ?> DK OKUMA
                    </span>

                </div>

                <h1 id="article-top"
                    class="main-article-title font-syne text-5xl md:text-7xl font-bold leading-[0.9] tracking-tight mb-8">
                    <?php echo isset($article['title']) ? htmlspecialchars($article['title']) : 'Başlık Yok'; ?>
                </h1>

                <?php if (!empty($article['short_desc'])): ?>
                <p
                    class="font-body text-xl md:text-2xl italic leading-normal text-[var(--text-main)] opacity-90 pl-6 border-l-4 border-[var(--text-accent)]">
                    "
                    <?php echo htmlspecialchars($article['short_desc']); ?>"
                </p>
                <?php endif; ?>
            </header>
            <div class="journal-text font-body">
                <?php if (!empty($article['image_url'])): ?>
                <figure class="my-12">
                    <div class="p-2 border border-[#2B1B17] bg-[var(--bg-secondary)]/20">
                        <div
                            class="aspect-video w-full overflow-hidden relative">
                            <img src="<?php echo SITE_URL . '/' . ltrim($article['image_url'], '/'); ?>"
                                class="w-full h-full object-cover mix-blend-multiply contrast-110" alt="Kapak Görseli">
                        </div>
                    </div>
                </figure>
                <?php endif; ?>
                <?php
if (isset($article['content'])) {
    $processedContent = preg_replace_callback('/\[(\d+)\]/', function ($matches) {
        static $seenRefs = [];
        $refNum = $matches[1];
        $idAttr = '';
        if (!in_array($refNum, $seenRefs)) {
            $idAttr = 'id="ref-link-' . $refNum . '"';
            $seenRefs[] = $refNum;
        }
        return '<sup class="reference-sup"><a href="#ref-item-' . $refNum . '" ' . $idAttr . ' class="text-[var(--text-accent)] hover:underline" style="scroll-margin-top: 250px;">[' . $refNum . ']</a></sup>';
    }, $article['content']);
    echo $processedContent;
}
else {
    echo '<p>İçerik yükleniyor...</p>';
}
?>
            </div>

            <?php if (!empty($article['refs'])): ?>
            <?php
                $rawRefs = explode("\n", $article['refs']);
                $numberedRefs = [];
                $plainRefs = [];

                foreach ($rawRefs as $line) {
                    $line = trim($line);
                    if (empty($line))
                        continue;

                    if (strpos($line, '=') !== false) {
                        list($key, $val) = explode('=', $line, 2);
                        if (is_numeric(trim($key))) {
                            $numberedRefs[intval($key)] = trim($val);
                        }
                        else {
                            $plainRefs[] = $line;
                        }
                    }
                    elseif (preg_match('/^\[(\d+)\](.*)/', $line, $matches)) {
                        $numberedRefs[intval($matches[1])] = trim($matches[2]);
                    }
                    else {
                        $plainRefs[] = $line;
                    }
                }
                ksort($numberedRefs);
            ?>

            <div class="my-12 border border-[var(--line-color)] bg-[var(--bg-paper)]">
                <button id="toggle-refs"
                    class="w-full flex justify-between items-center p-4 hover:bg-[var(--bg-secondary)]/30 transition-colors group">
                    <span
                        class="font-syne font-bold uppercase text-sm tracking-widest text-[var(--text-accent)] flex items-center gap-2">
                        <span class="w-2 h-2 bg-[var(--text-accent)] rounded-full"></span>
                        KAYNAKÇA VE NOTLAR
                    </span>
                    <span id="ref-icon" class="font-mono text-xl block">+</span>
                </button>

                <div id="refs-content"
                    class="hidden border-t border-[var(--line-color)] bg-[var(--bg-secondary)]/10 p-6">
                    <ul class="space-y-3 font-mono text-xs md:text-sm text-[var(--text-main)]/80">

                        <?php foreach ($numberedRefs as $key => $val): ?>
                        <li id="ref-item-<?php echo $key; ?>"
                            class="flex gap-3 p-2 rounded border border-transparent transition-all duration-1000 scroll-mt-24">
                            <a href="#ref-link-<?php echo $key; ?>" class="font-bold text-[var(--text-accent)] flex-shrink-0 hover:underline">[<?php echo $key; ?>]
                            </a>

                            <?php if (filter_var($val, FILTER_VALIDATE_URL)): ?>
                            <a href="<?php echo htmlspecialchars($val); ?>" target="_blank" rel="nofollow"
                            class="underline decoration-[var(--text-accent)] hover:text-[var(--text-accent)] truncate">
                                <?php echo htmlspecialchars($val); ?> ↗
                            </a>
                            <?php else: ?>
                            <span>
                                <?php echo htmlspecialchars($val); ?>
                            </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>

                        <?php foreach ($plainRefs as $val): ?>
                        <li class="flex gap-3 p-2 opacity-80 border border-transparent">
                            <span class="text-[var(--text-accent)] flex-shrink-0">•</span>

                            <?php if (filter_var($val, FILTER_VALIDATE_URL)): ?>
                            <a href="<?php echo htmlspecialchars($val); ?>" target="_blank" rel="nofollow"
                            class="underline decoration-[var(--line-color)] hover:text-[var(--text-accent)] truncate">
                                <?php echo htmlspecialchars($val); ?> ↗
                            </a>
                            <?php else: ?>
                            <span>
                                <?php echo htmlspecialchars($val); ?>
                            </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>

                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-20 pt-10 border-t border-[var(--line-color)]">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-8 bg-[var(--bg-secondary)]/10 p-8 border border-[var(--line-color)]">

                    <a href="<?php echo SITE_URL; ?>/yazar/<?php echo $article['author_slug'] ?? $article['author_id']; ?>"
                        class="w-24 h-24 flex-shrink-0 border-2 border-[var(--text-accent)] rounded-full overflow-hidden p-1 group cursor-pointer block">
                        <img src="<?php echo !empty($article['author_img']) ? SITE_URL . '/' . ltrim($article['author_img'], '/') : SITE_URL . '/assets/default-avatar.jpg'; ?>"
                            class="w-full h-full object-cover rounded-full grayscale group-hover:grayscale-0 transition-all duration-500"
                            alt="<?php echo htmlspecialchars($article['author_name']); ?>">
                    </a>

                    <div class="text-center md:text-left flex-grow">
                        <span class="block font-syne text-xs uppercase tracking-widest text-[var(--text-accent)] mb-2 font-bold">MAKALE YAZARI</span>

                        <a href="<?php echo SITE_URL; ?>/yazar/<?php echo $article['author_slug'] ?? $article['author_id']; ?>"
                            class="font-syne text-2xl font-bold mb-2 text-[var(--text-main)] hover:text-[var(--text-accent)] hover:underline decoration-2 underline-offset-4 transition-colors inline-block">
                            <?php echo htmlspecialchars($article['author_name'] ?: 'Fezadan Editörü'); ?>
                        </a>

                        <p class="font-body text-lg text-[var(--text-main)]/80 leading-relaxed max-w-lg mx-auto md:mx-0">
                            <?php echo htmlspecialchars($article['author_bio'] ?: 'Veri ve estetik arasındaki sessiz çatışmayı inceleyen bir gözlemci.'); ?>
                        </p>

                        <a href="<?php echo SITE_URL; ?>/yazar/<?php echo $article['author_slug'] ?? $article['author_id']; ?>"
                            class="inline-flex items-center gap-2 mt-4 text-xs font-bold uppercase tracking-widest text-[var(--text-accent)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] px-3 py-1 border border-[var(--text-accent)] transition-all">
                            <span>Yazarın Profilini İncele</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>

                </div>
            </div>
        </article>
    </main>

    <div class="hidden xl:block"></div>
</div>

<button id="scrollTopBtn"
    class="fixed bottom-8 right-8 bg-[var(--text-accent)] text-[#FEF9E1] w-12 h-12 rounded-full flex items-center justify-center opacity-0 pointer-events-none cursor-pointer transition-all duration-500 z-50 hover:bg-[#6D2323] hover:scale-110 shadow-lg">
    <svg class="scroll-top-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
    <svg class="toc-toggle-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"></path>
    </svg>
</button>

<script>
    (function () {
        // ===== ToC =====

        const articleTitle = document.querySelector('#article-top');
        const contentDiv = document.querySelector('.journal-text');
        const tocList = document.getElementById('toc-list');
        const tocTitle = document.getElementById('toc-title');
        const tocProgress = document.getElementById('toc-progress');
        const tocMobileList = document.getElementById('toc-mobile-list');
        const tocMobileTitle = document.getElementById('toc-mobile-title');
        const tocMobileProgress = document.getElementById('toc-mobile-progress');
        const mobileDrawer = document.getElementById('toc-mobile-drawer');
        const mobileOverlay = document.getElementById('toc-mobile-overlay');
        const drawerCloseBtn = document.getElementById('toc-drawer-close-btn');
        const scrollTopBtn = document.getElementById('scrollTopBtn');

        let headings = [];
        let tocItems = [];
        let tocMobileItems = [];
        let activeIndex = -1;

        function buildToc() {
            if (!contentDiv || !tocList) return;

            const headingEls = contentDiv.querySelectorAll('h2, h3');
            if (headingEls.length === 0) return;

            const titleText = articleTitle ? articleTitle.textContent.trim() : '';

            if (tocTitle) tocTitle.style.display = 'none';
            if (tocMobileTitle) tocMobileTitle.style.display = 'none';

            const addItem = (level, text, targetId, isMobile) => {
                const isH1 = level === 1;
                const isRefs = targetId === 'refs-section';
                
                const li = document.createElement('li');
                li.className = isMobile ? 'toc-mobile-item' : 'toc-item';
                if (isRefs) li.classList.add('toc-refs-item');
                li.setAttribute('data-level', level);

                const a = document.createElement('a');
                a.className = isMobile ? 'toc-mobile-link' : 'toc-link';
                a.href = '#' + targetId;
                a.textContent = text;

                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (isMobile) closeMobileDrawer();
                    
                    const scrollAction = () => {
                        if (isH1) {
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        } else {
                            const target = document.getElementById(targetId);
                            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    };
                    isMobile ? setTimeout(scrollAction, 100) : scrollAction();
                });

                const dot = document.createElement('span');
                dot.className = isMobile ? 'toc-mobile-dot' : 'toc-dot';

                if (isMobile) {
                    li.appendChild(dot);
                    li.appendChild(a);
                    tocMobileList.appendChild(li);
                    tocMobileItems.push(li);
                } else {
                    li.appendChild(a);
                    li.appendChild(dot);
                    tocList.appendChild(li);
                    tocItems.push(li);
                }
            };

            if (titleText) {
                if (articleTitle) headings.push({ el: articleTitle, id: 'article-top', level: 1, text: titleText });
                addItem(1, titleText, 'article-top', false); // Masaüstü
                addItem(1, titleText, 'article-top', true);  // Mobil
            }

            headingEls.forEach((el, i) => {
                if (!el.id) el.id = 'heading-' + i;
                const level = parseInt(el.tagName.charAt(1));
                const text = el.textContent.trim();
                
                headings.push({ el: el, id: el.id, level: level, text: text });
                addItem(level, text, el.id, false);
                addItem(level, text, el.id, true);
            });

            const refsSection = document.getElementById('refs-section');
            if (refsSection) {
                const refsText = 'Kaynakça ve Notlar';
                headings.push({ el: refsSection, id: 'refs-section', level: 2, text: refsText });
                addItem(2, refsText, 'refs-section', false);
                addItem(2, refsText, 'refs-section', true);
            }
        }

        
        function updateScrollSpy() {
            if (headings.length === 0) return;

            const scrollY = window.scrollY;
            const offset = 120; 
            let currentIdx = -1;

            if ((window.innerHeight + Math.ceil(window.scrollY)) >= document.documentElement.scrollHeight - 10) {
                currentIdx = headings.length - 1;
            } else {
                for (let i = headings.length - 2; i >= 0; i--) {
                    const headingTop = headings[i].el.getBoundingClientRect().top + scrollY - offset;
                    if (scrollY >= headingTop) {
                        currentIdx = i;
                        break;
                    }
                }
            }

            if (currentIdx === activeIndex) return;
            activeIndex = currentIdx;

            [tocItems, tocMobileItems].forEach(list => {
                list.forEach((item, i) => {
                    item.classList.remove('toc-active', 'toc-passed');
                    if (i === activeIndex) item.classList.add('toc-active');
                    else if (i < activeIndex) item.classList.add('toc-passed');
                });
            });

            updateProgressLine();
        }

        function updateProgressLine() {
            if (headings.length === 0 || activeIndex < 0) {
                if (tocProgress) tocProgress.style.height = '0%';
                if (tocMobileProgress) tocMobileProgress.style.height = '0%';
                return;
            }

            const updateTrack = (listNode, itemsArr, progressNode) => {
                if (!listNode || !progressNode || itemsArr.length === 0) return;
                const activeItem = itemsArr[activeIndex];
                if (!activeItem) return;

                const listRect = listNode.getBoundingClientRect();
                const itemRect = activeItem.getBoundingClientRect();
                const dotCenter = itemRect.top + itemRect.height / 2 - listRect.top;

                const firstRect = itemsArr[0].getBoundingClientRect();
                const startTop = firstRect.top + firstRect.height / 2 - listRect.top;

                const height = Math.max(0, dotCenter - startTop);
                progressNode.style.top = startTop + 'px';
                progressNode.style.height = height + 'px';
            };

            updateTrack(tocList, tocItems, tocProgress);
            updateTrack(tocMobileList, tocMobileItems, tocMobileProgress);
        }

        function openMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.add('toc-drawer-open');
            if (mobileOverlay) mobileOverlay.classList.add('toc-drawer-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            setTimeout(updateProgressLine, 50);
        }

        function closeMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.remove('toc-drawer-open');
            if (mobileOverlay) mobileOverlay.classList.remove('toc-drawer-open');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }

        if (scrollTopBtn) {
            scrollTopBtn.addEventListener('click', function () {
                const isDesktop = window.innerWidth >= 1280;
                if (isDesktop || scrollTopBtn.getAttribute('data-no-toc') === 'true') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    // Toggle mobile drawer
                    if (mobileDrawer && mobileDrawer.classList.contains('toc-drawer-open')) {
                        closeMobileDrawer();
                    } else {
                        openMobileDrawer();
                    }
                }
            });
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeMobileDrawer);
        }

        if (drawerCloseBtn) {
            drawerCloseBtn.addEventListener('click', closeMobileDrawer);
        }

        let isScrolling = false;
        window.addEventListener('scroll', function () {
            if (!isScrolling) {
                window.requestAnimationFrame(function () {
                    const scrollTop = window.scrollY || document.documentElement.scrollTop;
                    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    const scrolled = (scrollTop / scrollHeight) * 100;
                    const progressBar = document.getElementById("progress-bar");
                    if (progressBar) progressBar.style.width = scrolled + "%";

                    if (scrollTopBtn) {
                        if (scrollTop > 300) {
                            scrollTopBtn.classList.remove('opacity-0', 'pointer-events-none');
                            scrollTopBtn.classList.add('opacity-100', 'translate-y-0');
                        } else {
                            scrollTopBtn.classList.add('opacity-0', 'pointer-events-none');
                            scrollTopBtn.classList.remove('opacity-100', 'translate-y-0');
                        }
                    }

                    updateScrollSpy();
                    
                    isScrolling = false;
                });
                isScrolling = true;
            }
        }, { passive: true });

        document.addEventListener("DOMContentLoaded", function () {
            const refsContent = document.getElementById('refs-content');
            const toggleBtn = document.getElementById('toggle-refs');
            const refIcon = document.getElementById('ref-icon');

            if (contentDiv && refsContent) {
                document.querySelectorAll('.reference-sup a').forEach(link => {
                    link.addEventListener('click', function (e) {
                        const refId = this.innerText.replace(/\[|\]/g, '');
                        const targetItem = document.getElementById('ref-item-' + refId);

                        if (refsContent.classList.contains('hidden')) {
                            toggleReferences(true);
                        }

                        if (targetItem) {
                            targetItem.classList.add('reference-highlight');
                            setTimeout(() => {
                                targetItem.classList.remove('reference-highlight');
                            }, 2000);
                        }
                    });
                });

                document.querySelectorAll('.ref-link').forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const refId = this.getAttribute('data-id');
                        const targetItem = document.getElementById('ref-item-' + refId);

                        if (refsContent.classList.contains('hidden')) {
                            toggleReferences(true);
                        }

                        if (targetItem) {
                            targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            targetItem.classList.add('reference-highlight');
                            setTimeout(() => {
                                targetItem.classList.remove('reference-highlight');
                            }, 2000);
                        }
                    });
                });

                function toggleReferences(forceOpen = false) {
                    const isHidden = refsContent.classList.contains('hidden');

                    if (forceOpen || isHidden) {
                        refsContent.classList.remove('hidden');
                        refIcon.innerText = "-";
                    } else {
                        refsContent.classList.add('hidden');
                        refIcon.innerText = "+";
                    }
                }

                if (toggleBtn) {
                    toggleBtn.addEventListener('click', () => toggleReferences());
                }
            }

            if (toggleBtn) {
                var refsWrapper = toggleBtn.closest('div');
                if (refsWrapper) {
                    refsWrapper.setAttribute('id', 'refs-section');
                    refsWrapper.style.scrollMarginTop = '100px';
                }
            }

            buildToc();

            if (headings.length === 0 && scrollTopBtn) {
                var tocIcon = scrollTopBtn.querySelector('.toc-toggle-icon');
                var scrollIcon = scrollTopBtn.querySelector('.scroll-top-icon');
                if (tocIcon) tocIcon.style.display = 'none';
                if (scrollIcon) scrollIcon.style.display = 'block';
                scrollTopBtn.setAttribute('data-no-toc', 'true');
            }

            requestAnimationFrame(function () {
                var desktopTrack = document.querySelector('.toc-track');
                if (desktopTrack && tocItems.length > 0 && tocList) {
                    var listRect = tocList.getBoundingClientRect();

                    var firstItem = tocItems[0];
                    var firstRect = firstItem.getBoundingClientRect();
                    var startTop = firstRect.top + firstRect.height / 2 - listRect.top;

                    var lastItem = tocItems[tocItems.length - 1];
                    var lastRect = lastItem.getBoundingClientRect();
                    var endTop = lastRect.top + lastRect.height / 2 - listRect.top;

                    var offset = 0;
                    if (lastItem.classList.contains('toc-refs-item')) {
                        offset = 5;
                    }

                    desktopTrack.style.top = startTop + 'px';
                    desktopTrack.style.height = Math.max(0, endTop - startTop - offset) + 'px';
                }

                var mobileTrack = document.querySelector('.toc-mobile-track');
                if (mobileTrack && tocMobileItems.length > 0 && tocMobileList) {
                    var origOpen = openMobileDrawer;
                    openMobileDrawer = function () {
                        origOpen();
                        setTimeout(function () {
                            requestAnimationFrame(function () {
                                var mListRect = tocMobileList.getBoundingClientRect();

                                var mFirstItem = tocMobileItems[0];
                                var mFirstRect = mFirstItem.getBoundingClientRect();
                                var mStartTop = mFirstRect.top + mFirstRect.height / 2 - mListRect.top;

                                var mLastItem = tocMobileItems[tocMobileItems.length - 1];
                                var mLastRect = mLastItem.getBoundingClientRect();
                                var mEndTop = mLastRect.top + mLastRect.height / 2 - mListRect.top;

                                var mOffset = 0;
                                if (mLastItem.classList.contains('toc-refs-item')) {
                                    mOffset = 5;
                                }

                                mobileTrack.style.top = mStartTop + 'px';
                                mobileTrack.style.height = Math.max(0, mEndTop - mStartTop - mOffset) + 'px';
                            });
                        }, 100);
                    };
                }
            });

            updateScrollSpy();
        });
    })();
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "<?php echo htmlspecialchars($article['title']); ?>",
  "image": "<?php echo !empty($article['image_url']) ? "https://fezadan.org" . $article['image_url'] : "https://fezadan.org/assets/default-cover.jpg"; ?>",
  "author": {
    "@type": "Person",
    "name": "<?php echo htmlspecialchars($article['author_name']); ?>",
    "url": "<?php echo "https://fezadan.org/yazar/" . $article['author_slug']; ?>"
  },
  "datePublished": "<?php echo date('c', strtotime($article['created_at'])); ?>",
  "description": "<?php echo htmlspecialchars($article['short_desc']); ?>",
  "publisher": {
    "@type": "Organization",
    "name": "FEZADAN",
    "logo": {
      "@type": "ImageObject",
      "url": "https://fezadan.org/assets/uploads/logo.jpg"
    }
  }
}
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const articleId = <?php echo $article['id']; ?>;
        const targetSeconds = <?php echo $threshold_seconds; ?>;

        const token = "<?php echo md5('okuma_' . $article['id'] . date('Y-m-d') . $secretKey); ?>";

        let timeSpent = 0; 
        let isRead = false;
        let timerInterval;

        function handleVisibility() {
            clearInterval(timerInterval);
            
            if (!document.hidden && !isRead) {
                timerInterval = setInterval(() => {
                    timeSpent++;

                    if (timeSpent >= targetSeconds) {
                        sendReadData();
                        clearInterval(timerInterval);
                    }
                }, 1000);
            }
        }

        function sendReadData() {
            if (isRead) return;
            
            fetch('/makale/count', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: articleId, token: token }),
                keepalive: true
            }).then(() => {
                isRead = true;
            }).catch(err => console.error(err));
        }

        document.addEventListener("visibilitychange", handleVisibility);
        
        handleVisibility();
    });
</script>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>