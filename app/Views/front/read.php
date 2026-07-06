<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$slug     = $article['slug'] ?? '';
$isEn     = (App::getLang() === 'EN');

// --- Meta / OG (header.php'nin standart değişkenleri) ---
$page_title       = !empty($article['seo_title']) ? $article['seo_title'] : (($article['title'] ?? 'Makale') . ' | FEZADAN');
$page_description = !empty($article['seo_description'])
    ? $article['seo_description']
    : (!empty($article['short_desc'])
        ? $article['short_desc']
        : mb_substr(trim(strip_tags($article['content'] ?? '')), 0, 160));
$page_canonical   = $page_canonical ?? articleUrl($article['author_slug'] ?? 'yazar', $slug, $contentLang);
$og_url           = $page_canonical;
$og_type          = 'article';
$hero_image_url = !empty($article['image_url'])
    ? Upload::assetUrl($article['image_url'])
    : '';

$og_image = !empty($article['og_image'])
    ? Upload::assetUrl($article['og_image'])
    : ($hero_image_url !== '' && !preg_match('/\.webp(\?|$)/i', $hero_image_url)
        ? $hero_image_url
        : $siteBase . '/cdn/notlar-social-preview.png');

$preload_image = $hero_image_url !== '' ? $hero_image_url : $og_image;

$article_published_time = !empty($article['created_at']) ? date('c', strtotime($article['created_at'])) : null;
$article_modified_time  = !empty($article['updated_at']) ? date('c', strtotime($article['updated_at'])) : $article_published_time;
$article_author_name    = $article['author_name'] ?? null;
$article_section        = !empty($categories[0]['name']) ? $categories[0]['name'] : null;
$article_tags           = array_column($categories ?? [], 'name');

if (!function_exists('fezadan_is_own_upload_image')) {
    function fezadan_is_own_upload_image(string $src): bool
    {
        $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($src === '' || strpos($src, 'data:') === 0 || strpos($src, 'blob:') === 0) {
            return false;
        }

        $path = (string)(parse_url($src, PHP_URL_PATH) ?: $src);
        $path = '/' . ltrim($path, '/');
        if (strpos($path, '/uploads/') !== 0) {
            return false;
        }

        if (!preg_match('#^https?://#i', $src)) {
            return true;
        }

        $host = (string)parse_url($src, PHP_URL_HOST);
        $siteHost = defined('SITE_URL') ? (string)parse_url(SITE_URL, PHP_URL_HOST) : '';
        $cdnHost = defined('CDN_URL') ? (string)parse_url(CDN_URL, PHP_URL_HOST) : '';
        return $host !== '' && (
            ($siteHost !== '' && strcasecmp($host, $siteHost) === 0)
            || ($cdnHost !== '' && strcasecmp($host, $cdnHost) === 0)
        );
    }
}

if (!function_exists('fezadan_sanitize_article_html')) {
    function fezadan_sanitize_article_html(string $html): string
    {
        $dangerousTags = '<(script|iframe|object|embed|form|input|select|textarea|button|applet|audio|video|source|track|link|style|meta|base|frame|frameset)\b[^>]*>.*?</\1>|<(script|iframe|object|embed|form|input|select|textarea|button|applet|audio|video|source|track|link|style|meta|base|frame|frameset)\b[^>]*/?\s*>';
        $html = preg_replace('@' . $dangerousTags . '@is', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*(["\'])(?:(?!\1).)*\1/is', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/is', '', $html);
        $html = preg_replace('/\s+href\s*=\s*(["\'])javascript:/is', ' href=$1#', $html);
        return $html;
    }
}

if (!function_exists('fezadan_normalize_article_images')) {
    function fezadan_normalize_article_images(string $html): string
    {
        return (string)preg_replace_callback('/<img\b[^>]*>/i', function ($matches) {
            $tag = $matches[0];
            if (preg_match('/\ssrc=(["\'])(.*?)\1/i', $tag, $srcMatch)) {
                $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (fezadan_is_own_upload_image($src)) {
                    $newSrc = htmlspecialchars(Upload::assetUrl($src), ENT_QUOTES, 'UTF-8');
                    $tag = preg_replace('/\ssrc=(["\']).*?\1/i', ' src="' . $newSrc . '"', $tag, 1);
                }
            }

            if (stripos($tag, ' loading=') === false) {
                $tag = preg_replace('/(\s*\/?>)$/', ' loading="lazy"$1', $tag, 1);
            }
            if (stripos($tag, ' decoding=') === false) {
                $tag = preg_replace('/(\s*\/?>)$/', ' decoding="async"$1', $tag, 1);
            }
            return $tag;
        }, $html);
    }
}

// --- JSON-LD: BlogPosting + BreadcrumbList ---
$wordCount = str_word_count(strip_tags($article['content'] ?? ''));
$blogPosting = [
    '@context' => 'https://schema.org',
    '@type'    => 'BlogPosting',
    'headline' => $article['title'] ?? '',
    'description' => $page_description,
    'image'    => [$og_image],
    'datePublished' => $article_published_time,
    'dateModified'  => $article_modified_time,
    'author' => [
        '@type' => 'Person',
        'name'  => $article['author_name'] ?? 'FEZADAN',
        'url'   => !empty($article['author_slug']) ? authorUrl($article['author_slug']) : langUrl('/'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name'  => 'FEZADAN',
        'logo'  => [
            '@type' => 'ImageObject',
            'url'   => $siteBase . '/cdn/logo-light.png',
        ],
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id'   => $page_canonical,
    ],
    'inLanguage' => $isEn ? 'en-US' : 'tr-TR',
    'wordCount'  => $wordCount,
];
if (!empty($article_section))      $blogPosting['articleSection'] = $article_section;
if (!empty($article_tags))         $blogPosting['keywords']       = implode(', ', $article_tags);

$breadcrumbItems = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => (App::getLang() === 'EN' ? 'Home' : 'Anasayfa'),  'item' => langUrl('/')],
    ['@type' => 'ListItem', 'position' => 2, 'name' => (App::getLang() === 'EN' ? 'Articles' : 'Makaleler'), 'item' => langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler')],
];
if (!empty($categories[0]['id'])) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem', 'position' => 3,
        'name'  => $categories[0]['name'],
        'item'  => langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler') . '?cat=' . (int)$categories[0]['id'],
    ];
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $article['title'] ?? '', 'item' => $page_canonical];
} else {
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title'] ?? '', 'item' => $page_canonical];
}
$breadcrumb = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];

$extra_jsonld = [$blogPosting, $breadcrumb];

require_once ROOT . '/app/Views/inc/header.php';

$word_count = $wordCount;
$reading_time = max(1, (int)ceil($word_count / 200));
$total_seconds = $reading_time * 60;
$threshold_seconds = max(10, (int)ceil($total_seconds * 0.20));
$previousArticleUrl = !empty($previousArticle['slug'])
    ? articleUrl($previousArticle['author_slug'] ?? 'yazar', $previousArticle['slug'], $contentLang)
    : null;
$nextArticleUrl = !empty($nextArticle['slug'])
    ? articleUrl($nextArticle['author_slug'] ?? 'yazar', $nextArticle['slug'], $contentLang)
    : null;

?>


<div id="progress-bar"></div>
<div class="texture-overlay"></div>

<div id="toc-mobile-overlay"></div>

<div id="toc-mobile-drawer">
    <div class="toc-drawer-header">
        <span class="toc-drawer-label"><?= $isEn ? 'Table of Contents' : 'İçindekiler' ?></span>
        <button class="toc-drawer-close" id="toc-drawer-close-btn" aria-label="<?= $isEn ? 'Close' : 'Kapat' ?>">
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

    <main id="main-content" class="relative z-10 w-full px-6 py-12 md:py-20 min-w-0">
        <article>
            <header class="mb-12 text-center md:text-left border-b border-[var(--line-color)] pb-10">
                <div class="flex flex-wrap justify-center md:justify-start items-center gap-2 md:gap-3 font-mono text-xs md:text-sm text-[var(--text-accent)] mb-6 uppercase tracking-wider font-bold">

                    <span>
                        <?php echo isset($article['created_at']) ? date('d F Y', strtotime($article['created_at'])) : date('d F Y'); ?>
                    </span>

                    <span class="text-[var(--text-accent)] opacity-60 font-light px-1">&mdash;</span>

                    <div class="flex gap-3">
                        <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                        <a href="<?php echo langUrl((App::getLang() === 'EN' ? 'articles' : 'makaleler') . '?cat=' . $cat['id']); ?>"
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
                        <?php echo isset($reading_time) ? $reading_time : '1'; ?> <?= $isEn ? 'MIN READ' : 'DK OKUMA' ?>
                    </span>

                </div>

                <h1 id="article-top"
                    class="main-article-title font-syne text-5xl md:text-7xl font-bold leading-[0.9] tracking-tight mb-8">
                    <?php echo isset($article['title']) ? htmlspecialchars($article['title']) : ($isEn ? 'No Title' : 'Başlık Yok'); ?>
                </h1>

                <?php if (!empty($article['short_desc'])): ?>
                <p
                    class="font-body text-xl md:text-2xl italic leading-normal text-[var(--text-main)] opacity-90 pl-6 border-l-4 border-[var(--text-accent)]">
                    "
                    <?php echo htmlspecialchars($article['short_desc']); ?>"
                </p>
                <?php endif; ?>

                <?php if (!empty($articleAuthors)): ?>
                <div class="flex flex-wrap items-center gap-2 font-mono text-[11px] uppercase tracking-wider mt-4">
                    <span class="opacity-60"><?= $isEn ? 'AUTHORS:' : 'YAZARLAR:' ?></span>
                    <?php foreach ($articleAuthors as $i => $aut): ?>
                        <?php if ($i > 0): ?><span class="opacity-40">&</span><?php endif; ?>
                        <a href="<?php echo authorUrl($aut['slug'] ?? $aut['id']); ?>" class="font-bold hover:text-[var(--text-accent)] hover:underline">
                            <?php echo htmlspecialchars($aut['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </header>

            <div class="reader-tools" aria-label="<?= $isEn ? 'Reading preferences' : 'Okuma tercihleri' ?>">
                <button type="button" class="reader-tool" data-reader-size="small" aria-label="<?= $isEn ? 'Decrease font size' : 'Yazi boyutunu kucult' ?>" title="<?= $isEn ? 'Decrease font size' : 'Yazi boyutunu kucult' ?>">A-</button>
                <button type="button" class="reader-tool" data-reader-size="medium" aria-label="<?= $isEn ? 'Default font size' : 'Varsayilan yazi boyutu' ?>" title="<?= $isEn ? 'Default font size' : 'Varsayilan yazi boyutu' ?>">A</button>
                <button type="button" class="reader-tool" data-reader-size="large" aria-label="<?= $isEn ? 'Increase font size' : 'Yazi boyutunu buyut' ?>" title="<?= $isEn ? 'Increase font size' : 'Yazi boyutunu buyut' ?>">A+</button>
                <button type="button" class="reader-tool" id="print-article-btn" aria-label="<?= $isEn ? 'Print' : 'Yazdir' ?>" title="<?= $isEn ? 'Print' : 'Yazdir' ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6z"></path>
                    </svg>
                </button>
            </div>

            <?php if (!empty($article['audio_url'])): ?>
            <div class="tts-player-wrapper mb-8 p-4 flex flex-col sm:flex-row items-center gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🎙️</span>
                    <div class="text-left">
                        <span class="block font-syne text-[10px] font-bold uppercase tracking-widest text-[var(--text-accent)]"><?= $isEn ? 'AUDIO ARTICLE' : 'SESLİ MAKALE' ?></span>
                        <span class="block font-mono text-[9px] opacity-65"><?= $isEn ? 'AI voice narration' : 'Yapay zeka seslendirmesi' ?></span>
                    </div>
                </div>
                <audio src="<?php echo \App\Core\Upload::assetUrl($article['audio_url']); ?>" controls class="flex-grow w-full"></audio>
            </div>
            <?php endif; ?>

            <div class="journal-text font-body">
                <?php if (!empty($article['image_url'])):
                    $coverRel  = '/' . ltrim($article['image_url'], '/');
                    $coverUrl  = Upload::assetUrl($coverRel);
                    $coverWebp = Upload::webpUrl($coverRel);
                    $coverAlt  = !empty($article['title']) ? htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') . ($isEn ? ' — cover image' : ' — kapak görseli') : ($isEn ? 'Cover image' : 'Kapak görseli');
                ?>
                <figure class="my-12">
                    <div class="p-2 border border-[#2B1B17] bg-[var(--bg-secondary)]/20">
                        <div class="aspect-video w-full overflow-hidden relative">
                            <picture>
                                <?php if ($coverWebp): ?>
                                    <source type="image/webp" srcset="<?= htmlspecialchars($coverWebp, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    class="w-full h-full object-cover mix-blend-multiply contrast-110"
                                    alt="<?= $coverAlt ?>"
                                    width="1200" height="675"
                                    fetchpriority="high">
                            </picture>
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
    $processedContent = preg_replace('/(<figcaption)\s+contenteditable="true"/', '$1', $processedContent);
    $processedContent = preg_replace('/(<figcaption)\s+data-placeholder="[^"]*"/', '$1', $processedContent);
    $processedContent = fezadan_normalize_article_images($processedContent);
    $processedContent = fezadan_sanitize_article_html($processedContent);
    $processedContent = preg_replace('/<img\s(?!.*\bloading\b)/i', '<img loading="lazy" decoding="async" ', $processedContent);
    echo $processedContent;
}
else {
    echo $isEn ? '<p>Content loading...</p>' : '<p>İçerik yükleniyor...</p>';
}
?>
            </div>

            <?php if (!empty($corrections)): ?>
            <div class="my-8 p-6 border-2 border-dashed border-[var(--text-accent)]/40 bg-[var(--bg-secondary)]/5">
                <span class="block font-syne font-bold uppercase text-xs text-[var(--text-accent)] tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-[var(--text-accent)] rounded-full"></span>
                    <?= $isEn ? 'CORRECTIONS & UPDATE HISTORY' : 'DÜZELTME VE GÜNCELLEME GEÇMİŞİ' ?>
                </span>
                <ul class="space-y-3 font-mono text-xs text-[var(--text-main)]/90">
                    <?php foreach ($corrections as $corr): ?>
                    <li class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-3 border-b border-[var(--line-color)]/10 pb-2">
                        <span class="text-[var(--text-accent)] font-bold flex-shrink-0">
                            [<?php echo date('d.m.Y H:i', strtotime($corr['created_at'])); ?>]
                        </span>
                        <span><?php echo htmlspecialchars($corr['correction_text']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

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
                        <?= $isEn ? 'REFERENCES & NOTES' : 'KAYNAKÇA VE NOTLAR' ?>
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
            
            <div class="article-share mt-12 mb-8 flex items-center justify-center md:justify-start gap-4 border-y border-[var(--line-color)] py-6">
                <span class="font-syne text-xs font-bold uppercase tracking-widest text-[var(--text-accent)]"><?= $isEn ? 'Share:' : 'Paylaş:' ?></span>
                
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($article['title']); ?>&url=<?php echo urlencode($og_url); ?>" 
                target="_blank" 
                class="p-2 border border-[var(--line-color)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all group">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
                </a>

                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($og_url); ?>" 
                target="_blank" 
                class="p-2 border border-[var(--line-color)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path></svg>
                </a>

                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($article['title'] . ' ' . $og_url); ?>" 
                target="_blank" 
                class="p-2 border border-[var(--line-color)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.94 3.659 1.437 5.634 1.437h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path></svg>
                </a>

                <a href="<?php echo langUrl((App::getLang() === 'EN' ? 'article' : 'makale') . '/qr/' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')); ?>"
                target="_blank"
                title="<?= $isEn ? 'Share via QR Code' : 'QR Kod ile Paylaş' ?>"
                class="p-2 border border-[var(--line-color)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="square" stroke-linejoin="miter"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/><rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/><rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/><line x1="14" y1="14" x2="14" y2="14"/><line x1="17" y1="14" x2="17" y2="14"/><line x1="20" y1="14" x2="20" y2="14"/><line x1="14" y1="17" x2="14" y2="17"/><line x1="20" y1="17" x2="20" y2="17"/><line x1="14" y1="20" x2="14" y2="20"/><line x1="17" y1="20" x2="20" y2="20"/><line x1="17" y1="17" x2="17" y2="17"/></svg>
                </a>

                <button id="native-share-btn"
                title="<?= $isEn ? 'Share' : 'Paylaş' ?>"
                class="p-2 border border-[var(--line-color)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all"
                style="display:none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                </button>

                <button id="copy-link-btn"
                title="<?= $isEn ? 'Copy Link' : 'Bağlantıyı Kopyala' ?>"
                class="p-2 border border-[var(--line-color)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all">
                    <svg class="w-5 h-5 copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                    </svg>
                    <svg class="w-5 h-5 check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </div>                                    

            <?php if (!empty($articleAuthors)): ?>
            <div class="article-author-box mt-12 space-y-6">
                <?php foreach ($articleAuthors as $aut): ?>
                <div class="flex flex-col md:flex-row items-center md:items-start gap-8 bg-[var(--bg-secondary)]/10 p-8 border border-[var(--line-color)]">

                    <a href="<?php echo authorUrl($aut['slug'] ?? $aut['id']); ?>"
                        class="w-24 h-24 flex-shrink-0 border-2 border-[var(--text-accent)] rounded-full overflow-hidden p-1 group cursor-pointer block">
                        <img src="<?php echo !empty($aut['image_url']) ? SITE_URL . '/' . ltrim($aut['image_url'], '/') : SITE_URL . '/assets/default-avatar.jpg'; ?>"
                            class="w-full h-full object-cover rounded-full grayscale group-hover:grayscale-0 transition-all duration-500"
                            alt="<?php echo htmlspecialchars($aut['name']); ?>">
                    </a>

                    <div class="text-center md:text-left flex-grow">
                        <span class="block font-syne text-xs uppercase tracking-widest text-[var(--text-accent)] mb-2 font-bold"><?= $isEn ? 'ARTICLE AUTHOR' : 'MAKALE YAZARI' ?></span>

                        <a href="<?php echo authorUrl($aut['slug'] ?? $aut['id']); ?>"
                            class="font-syne text-2xl font-bold mb-2 text-[var(--text-main)] hover:text-[var(--text-accent)] hover:underline decoration-2 underline-offset-4 transition-colors inline-block">
                            <?php echo htmlspecialchars($aut['name'] ?: ($isEn ? 'Fezadan Editor' : 'Fezadan Editörü')); ?>
                        </a>

                        <p class="font-body text-lg text-[var(--text-main)]/80 leading-relaxed max-w-lg mx-auto md:mx-0">
                            <?php echo htmlspecialchars($aut['bio'] ?: ($isEn ? 'An observer examining the quiet conflict between data and aesthetics.' : 'Veri ve estetik arasındaki sessiz çatışmayı inceleyen bir gözlemci.')); ?>
                        </p>

                        <a href="<?php echo authorUrl($aut['slug'] ?? $aut['id']); ?>"
                            class="inline-flex items-center gap-2 mt-4 text-xs font-bold uppercase tracking-widest text-[var(--text-accent)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] px-3 py-1 border border-[var(--text-accent)] transition-all">
                            <span><?= $isEn ? 'View Author Profile' : 'Yazarın Profilini İncele' ?></span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($previousArticleUrl || $nextArticleUrl): ?>
            <nav class="article-neighbors" aria-label="<?= $isEn ? 'Article navigation' : 'Makale gezinme' ?>">
                <?php if ($previousArticleUrl): ?>
                    <a class="article-neighbor previous" rel="prev" href="<?= htmlspecialchars($previousArticleUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="article-neighbor-label"><?= $isEn ? 'Previous article' : 'Onceki makale' ?></span>
                        <span class="article-neighbor-title"><?= htmlspecialchars($previousArticle['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($nextArticleUrl): ?>
                    <a class="article-neighbor next" rel="next" href="<?= htmlspecialchars($nextArticleUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="article-neighbor-label"><?= $isEn ? 'Next article' : 'Sonraki makale' ?></span>
                        <span class="article-neighbor-title"><?= htmlspecialchars($nextArticle['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </article>

        <?php if (!empty($related)): ?>
        <section aria-labelledby="related-heading" class="related-articles max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 mb-12">
            <h2 id="related-heading" class="text-xs uppercase tracking-[0.3em] font-bold text-[var(--text-accent)] border-b border-[var(--line-color)] pb-3 mb-8">
                <?= $isEn ? 'Related Articles' : 'İlgili Makaleler' ?>
            </h2>
            <ul class="grid grid-cols-1 md:grid-cols-3 gap-8 list-none p-0">
                <?php foreach ($related as $rel): ?>
                    <?php
                        $relUrl   = articleUrl($rel['author_slug'] ?? 'yazar', $rel['slug'], $contentLang);
                        $relImg   = !empty($rel['image_url'])
                            ? Upload::assetUrl($rel['image_url'])
                            : $siteBase . '/cdn/notlar-social-preview.png';
                        $relDesc  = !empty($rel['short_desc'])
                            ? mb_substr($rel['short_desc'], 0, 120)
                            : '';
                    ?>
                    <li>
                        <article class="group">
                            <a href="<?= htmlspecialchars($relUrl, ENT_QUOTES, 'UTF-8') ?>" class="block">
                                <div class="aspect-[16/10] overflow-hidden border border-[var(--line-color)] mb-3 bg-[var(--bg-secondary)]">
                                    <img src="<?= htmlspecialchars($relImg, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="<?= htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy" decoding="async"
                                         width="800" height="500"
                                         class="w-full h-full object-cover transition-transform duration-500"
                                         style="filter: var(--img-filter); mix-blend-mode: var(--img-blend); opacity: var(--img-opacity);">
                                </div>
                                <h3 class="font-syne text-lg font-bold leading-tight text-[var(--text-main)] group-hover:text-[var(--text-accent)] transition-colors">
                                    <?= htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <?php if ($relDesc): ?>
                                    <p class="text-sm mt-2 opacity-80 leading-relaxed">
                                        <?= htmlspecialchars($relDesc, ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($rel['short_desc'] ?? '') > 120 ? '…' : '' ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($rel['author_name'])): ?>
                                    <div class="text-xs uppercase tracking-widest mt-3 opacity-60">
                                        <?= htmlspecialchars($rel['author_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
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

<script nonce="<?= CSP_NONCE ?>">
    window.FezadanArticleData = {
        articleId: <?= (int)($article['id'] ?? 0) ?>,
        shareUrl: <?= json_encode($og_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        shareTitle: <?= json_encode($article['title'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        previousArticleUrl: <?= json_encode($previousArticleUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        nextArticleUrl: <?= json_encode($nextArticleUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="/assets/js/article-reader.js?v=<?= filemtime(ROOT . '/public_html/assets/js/article-reader.js') ?>" nonce="<?= CSP_NONCE ?>"></script>
<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
