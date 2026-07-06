<?php
$current_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive($uri, $target)
{
    $path = preg_replace('#^/(tr|en)(/|$)#i', '/', $uri);
    $path = rtrim($path, '/');
    if ($path === '') {
        $path = '/';
    }

    if ($target === '/makaleler') {
        return (in_array($path, ['/makaleler', '/articles'], true) || strpos($path, '/makale/') === 0 || strpos($path, '/article/') === 0)
            ? 'border-b-2 border-[var(--text-accent)] !text-[var(--text-accent)]'
            : '';
    }

    if ($target === '/manifesto') {
        return ($path === '/manifesto')
            ? 'border-b-2 border-[var(--text-accent)] !text-[var(--text-accent)]'
            : '';
    }

    if ($target === '/hakkinda') {
        return (in_array($path, ['/hakkinda', '/about'], true))
            ? 'border-b-2 border-[var(--text-accent)] !text-[var(--text-accent)]'
            : '';
    }

    if ($target === '/') {
        return ($path === '/')
            ? 'border-b-2 border-[var(--text-accent)] !text-[var(--text-accent)]'
            : '';
    }

    return '';
}
function ariaCurrent($uri, $target)
{
    $path = preg_replace('#^/(tr|en)(/|$)#i', '/', $uri);
    $path = rtrim($path, '/');
    if ($path === '') {
        $path = '/';
    }
    return $path === $target ? 'aria-current="page"' : '';
}
function getLanguageSwitchUrl($lang) {
    global $page_alternates;
    $targetLang = strtolower($lang);
    
    if (!empty($page_alternates) && isset($page_alternates[$targetLang])) {
        return $page_alternates[$targetLang];
    }
    
    $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Query string'i ayrıştır ve dahili apache rewrite 'url' parametresini kaldır
    $queryParams = [];
    parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
    unset($queryParams['url']);
    
    $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
    
    $segments = explode('/', ltrim($currentUri, '/'));
    $pathSegments = $segments;
    if (isset($segments[0]) && in_array(strtolower($segments[0]), ['tr', 'en'])) {
        $pathSegments = array_slice($segments, 1);
        $segments[0] = $targetLang;
    } else {
        array_unshift($segments, $targetLang);
    }

    $staticPaths = [
        '' => ['tr' => '', 'en' => ''],
        'hakkinda' => ['tr' => 'hakkinda', 'en' => 'about'],
        'about' => ['tr' => 'hakkinda', 'en' => 'about'],
        'manifesto' => ['tr' => 'manifesto', 'en' => 'manifesto'],
        'gizlilik-politikasi' => ['tr' => 'gizlilik-politikasi', 'en' => 'privacy'],
        'privacy' => ['tr' => 'gizlilik-politikasi', 'en' => 'privacy'],
        'bagis' => ['tr' => 'bagis', 'en' => 'donate'],
        'donate' => ['tr' => 'bagis', 'en' => 'donate'],
        'teyit' => ['tr' => 'teyit', 'en' => 'verification'],
        'verification' => ['tr' => 'teyit', 'en' => 'verification'],
        'makaleler' => ['tr' => 'makaleler', 'en' => 'articles'],
        'articles' => ['tr' => 'makaleler', 'en' => 'articles'],
    ];

    if (count($pathSegments) <= 1) {
        $key = strtolower($pathSegments[0] ?? '');
        if (isset($staticPaths[$key])) {
            $localized = $staticPaths[$key][$targetLang];
            return '/' . $targetLang . ($localized !== '' ? '/' . $localized : '') . $queryString;
        }
    }
    
    return '/' . implode('/', $segments) . $queryString;
}

// ============================================================
// META / SEO Varsayılanları — view'lar geçersiz kılabilir
// ============================================================
$siteBase        = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$isEnHead        = (App::getLang() === 'EN');
$page_title      = $page_title      ?? ($isEnHead ? 'FEZADAN — Science, Aesthetics, and Independent Thought' : 'FEZADAN — Bilim, Estetik ve Bağımsız Düşünce');
$page_description = $page_description ?? ($isEnHead 
    ? 'The silent conflict between data and aesthetics. FEZADAN — an independent publication on science, aesthetics, and thought.'
    : 'Veri ve estetik arasındaki sessiz çatışma. FEZADAN — bilim, estetik ve fikir üzerine bağımsız bir yayın.');
$og_type         = $og_type         ?? 'website';
$og_image        = $og_image        ?? ($siteBase . '/cdn/notlar-social-preview.png');
// Kanonik: controller/view sabit slug-bazlı URL belirlemediyse mevcut path'e düş
$page_canonical  = $page_canonical  ?? ($siteBase . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$og_url          = $og_url          ?? $page_canonical;
$page_robots     = $page_robots     ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1, noai, noimageai';
$extra_jsonld    = $extra_jsonld    ?? [];
?>

<!DOCTYPE html>
<html lang="<?= strtolower(App::getLang()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="yandex-verification" content="b6e1d10c17e7147c">
    <meta name="theme-color" content="#6D2323" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#120A0A" media="(prefers-color-scheme: dark)">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/x-icon" href="/cdn/light-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/light-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/light-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/light-apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="/cdn/light-android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/cdn/light-android-chrome-512x512.png">

    <meta name="description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($page_keywords)): ?>
        <meta name="keywords" content="<?= htmlspecialchars($page_keywords, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= htmlspecialchars($page_robots, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($page_canonical, ENT_QUOTES, 'UTF-8') ?>">
    <?php
    if (empty($page_alternates)) {
        $page_alternates = [];
        $siteBaseUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
        $trSwitch = getLanguageSwitchUrl('tr');
        $enSwitch = getLanguageSwitchUrl('en');
        $page_alternates['tr'] = preg_match('#^https?://#i', $trSwitch) ? $trSwitch : rtrim($siteBaseUrl . $trSwitch, '/');
        $page_alternates['en'] = preg_match('#^https?://#i', $enSwitch) ? $enSwitch : rtrim($siteBaseUrl . $enSwitch, '/');
    }
    if (!empty($page_alternates) && empty($page_alternates['x-default'])) {
        $page_alternates['x-default'] = $page_alternates['tr'] ?? $page_alternates['en'] ?? $page_canonical;
    }
    ?>
    <?php if (!empty($page_alternates)): ?>
        <?php foreach ($page_alternates as $lang => $url): ?>
            <link rel="alternate" hreflang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="alternate" type="application/rss+xml" title="FEZADAN RSS" href="/rss">

    <meta property="og:site_name" content="FEZADAN">
    <meta property="og:locale" content="<?= $isEnHead ? 'en_US' : 'tr_TR' ?>">
    <meta property="og:type" content="<?= htmlspecialchars($og_type, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?= htmlspecialchars($og_url, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8') ?>">

    <?php if ($og_type === 'article'): ?>
        <?php if (!empty($article_published_time)): ?>
            <meta property="article:published_time" content="<?= htmlspecialchars($article_published_time, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <?php if (!empty($article_modified_time)): ?>
            <meta property="article:modified_time" content="<?= htmlspecialchars($article_modified_time, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <?php if (!empty($article_author_name)): ?>
            <meta property="article:author" content="<?= htmlspecialchars($article_author_name, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <?php if (!empty($article_section)): ?>
            <meta property="article:section" content="<?= htmlspecialchars($article_section, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <?php foreach (($article_tags ?? []) as $tag): ?>
            <meta property="article:tag" content="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <?php /* En sık kullanılan font dosyalarını preload et — FOIT/CLS azalır */ ?>
    <link rel="preload" href="/assets/fonts/space-grotesk-v22-latin-ext-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/syne-v24-latin-ext-700.woff2" as="font" type="font/woff2" crossorigin>

    <?php if (!empty($preload_image)): ?>
        <link rel="preload" as="image" href="<?= htmlspecialchars($preload_image, ENT_QUOTES, 'UTF-8') ?>" fetchpriority="high">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= $siteBase ?>/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?: time() ?>">
    <link rel="preload" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>"></noscript>
    <?php
    $cdnOrigin = (defined('CDN_URL') && CDN_URL !== '' && CDN_URL !== $siteBase) ? rtrim(CDN_URL, '/') : '';
    if ($cdnOrigin !== ''): ?>
        <link rel="preconnect" href="<?= htmlspecialchars($cdnOrigin, ENT_QUOTES, 'UTF-8') ?>">
        <link rel="dns-prefetch" href="<?= htmlspecialchars($cdnOrigin, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <?php
    // Varsayılan JSON-LD: WebSite + Breadcrumb ($extra_jsonld ile geçersiz kılınabilir)
    if (empty($extra_jsonld)) {
        $defaultBreadcrumb = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => (App::getLang() === 'EN' ? 'Home' : 'Anasayfa'), 'item' => langUrl('/')],
        ];
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', ltrim($currentPath, '/'))));
        if (isset($segments[1])) {
            $seg1 = strtolower($segments[1]);
            if ($seg1 === 'makaleler' || $seg1 === 'articles') {
                $defaultBreadcrumb[] = [
                    '@type' => 'ListItem', 
                    'position' => 2, 
                    'name' => (App::getLang() === 'EN' ? 'Articles' : 'Makaleler'), 
                    'item' => langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler')
                ];
            } elseif ($seg1 === 'yazar' || $seg1 === 'author') {
                $authorNameVal = (App::getLang() === 'EN' ? 'Author' : 'Yazar');
                $defaultBreadcrumb[] = [
                    '@type' => 'ListItem', 
                    'position' => 2, 
                    'name' => $authorNameVal, 
                    'item' => authorUrl($segments[2] ?? '')
                ];
            } else {
                $defaultBreadcrumb[] = ['@type' => 'ListItem', 'position' => 2, 'name' => ucfirst($segments[1]), 'item' => $page_canonical];
            }
        }
        $extra_jsonld = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'FEZADAN',
                'url' => $siteBase,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $siteBase . '/tr/makaleler?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $defaultBreadcrumb,
            ],
        ];
    }
    foreach ($extra_jsonld as $jsonld): ?>
        <script type="application/ld+json" nonce="<?= CSP_NONCE ?>"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endforeach; ?>

    <style>
        /* === A11y: Skip-to-content === */
        .skip-to-content {
            position: absolute;
            left: -9999px;
            top: 0;
            z-index: 100;
            padding: 12px 20px;
            background: #6D2323;
            color: #FEF9E1;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .skip-to-content:focus {
            left: 12px;
            top: 12px;
            outline: 3px solid #FEF9E1;
            outline-offset: 2px;
        }

        /* === A11y: Focus visible === */
        :focus-visible {
            outline: 2px solid var(--text-accent);
            outline-offset: 2px;
        }
        :focus:not(:focus-visible) {
            outline: none;
        }
        html:focus, body:focus, html:focus-visible, body:focus-visible {
            outline: none !important;
        }

        :root {
            --bg-paper: #FEF9E1;
            --bg-secondary: #E5D0AC;
            --text-main: #6D2323;
            --text-accent: #A31D1D;
            --line-color: #E5D0AC;
            --img-blend: multiply;
            --img-opacity: 0.5;
            --img-filter: grayscale(50%) contrast(110%);
        }

        [data-theme="dark"] {
            --bg-paper: #120A0A;
            --bg-secondary: #1F1212;
            --text-main: #E1C89E;
            --text-accent: #FF5C5C;
            --line-color: #3D1F1F;
            --img-blend: normal;
            --img-opacity: 0.4;
            --img-filter: grayscale(40%) contrast(100%);
        }

        .logo-dark { display: none; }
        [data-theme="dark"] .logo-light { display: none; }
        [data-theme="dark"] .logo-dark { display: block; }

        .theme-switch-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .theme-switch {
            width: 48px; /* Genişletildi */
            height: 24px;
            background-color: var(--text-main);
            border: 2px solid var(--text-main); /* Çerçeve eklendi */
            border-radius: 999px;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        /* Karanlık tema için kontrast ayarı */
        [data-theme="dark"] .theme-switch {
            background-color: var(--bg-secondary);
            border-color: var(--text-main);
        }

        .theme-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px; /* Çerçeveden dolayı ufaltıldı */
            height: 16px; /* Çerçeveden dolayı ufaltıldı */
            background-color: var(--bg-paper);
            border-radius: 50%;
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        [data-theme="dark"] .theme-switch::after {
            transform: translateX(24px); /* Genişliğe göre uyarlandı */
            background-color: var(--text-main);
        }

        .theme-icon {
            width: 14px;
            height: 14px;
            color: var(--text-main);
        }

        .sun-icon { opacity: 1; color: var(--text-main); }
        .moon-icon { opacity: 0.3; color: var(--text-main); }

        [data-theme="dark"] .sun-icon { opacity: 0.3; }
        [data-theme="dark"] .moon-icon { opacity: 1; }

        ::target-text {
            background-color: transparent;
        }

        html {
            width: 100%;
            overflow-x: clip;
        }

        body {
            background-color: var(--bg-paper);
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            overflow-x: clip;
            position: relative;
            padding-top: 85px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, .font-syne {
            font-family: 'Syne', sans-serif;
        }

        .nav-link {
            position: relative;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            color: var(--text-main);
            text-decoration: none;
            padding-bottom: 2px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--text-accent); 
        }

        #main-navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 50;
            background-color: var(--bg-paper);
            border-bottom: 1px solid var(--line-color);
            transform: translateY(0);
            transition: transform 0.3s ease-in-out, border-color 0.3s ease;
            will-change: transform;
        }

        [data-theme="dark"] #main-navbar {
            border-bottom-color: var(--text-main);
        }

        .nav-hidden {
            transform: translateY(-100%) !important;
        }

        #mobile-menu {
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            height: 100dvh;
            background-color: var(--bg-paper);
            z-index: 40;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        #mobile-menu.menu-open {
            transform: translateX(0);
        }

        .hamburger line {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
            transform-origin: center;
            transform-box: fill-box;
        }

        .menu-active .line1 {
            transform: translateY(6px) rotate(45deg);
        }

        .menu-active .line2 {
            opacity: 0;
            transform: scaleX(0);
        }

        .menu-active .line3 {
            transform: translateY(-6px) rotate(-45deg);
        }
    </style>
    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            const userTheme = localStorage.getItem('theme');
            const htmlElement = document.documentElement;

            if (userTheme === 'dark') {
                htmlElement.setAttribute('data-theme', 'dark');
            } else {
                htmlElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>

<body>

    <a href="#main-content" class="skip-to-content">Ana içeriğe atla</a>

    <nav id="main-navbar" class="flex justify-between items-center px-3 py-5 md:px-6 h-[85px]" aria-label="Ana gezinme">

        <div class="relative z-50">
            <a href="<?= langUrl() ?>" class="flex items-center gap-2" aria-label="Anasayfa">
                <img src="/cdn/logo-light.png" 
                    alt="Fezadan Logo" 
                    width="150" height="40"
                    style="height: 40px; width: auto;" 
                    class="object-contain rounded-sm logo-light">
                    
                <img src="/cdn/logo-dark.png"
                    alt=""
                    aria-hidden="true"
                    width="150" height="40"
                    style="height: 40px; width: auto;"
                    class="object-contain rounded-sm logo-dark">
            </a>
        </div>

        <div class="hidden md:flex gap-8 items-center pt-1">
            <a href="<?= langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler') ?>"
                class="nav-link <?php echo isActive($current_uri, '/makaleler'); ?>"
                <?php echo ariaCurrent($current_uri, '/makaleler'); ?>><?= App::getLang() === 'EN' ? 'Articles' : 'Makaleler' ?></a>
            
            <?php 
                $host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
                $notlar_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . (strpos($host, 'notlar.') === 0 ? $host : "notlar." . $host);
            ?>
            <a href="<?php echo $notlar_url; ?>" class="nav-link"><?= App::getLang() === 'EN' ? 'Notes' : 'Notlar' ?></a>
            
            <a href="<?= pageUrl('manifesto') ?>"
                class="nav-link <?php echo isActive($current_uri, '/manifesto'); ?>"
                <?php echo ariaCurrent($current_uri, '/manifesto'); ?>><?= App::getLang() === 'EN' ? 'Manifesto' : 'Manifesto' ?></a>
                
            <a href="<?= pageUrl('about') ?>"
                class="nav-link <?php echo isActive($current_uri, '/hakkinda'); ?>"
                <?php echo ariaCurrent($current_uri, '/hakkinda'); ?>><?= App::getLang() === 'EN' ? 'About' : 'Hakkında' ?></a>

            <div id="theme-toggle" class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Temayı Değiştir" aria-pressed="false">
                <svg class="theme-icon sun-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <div class="theme-switch"></div>
                <svg class="theme-icon moon-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                    </path>
                </svg>
            </div>
        </div>

        <button id="hamburger-btn" class="md:hidden relative z-50 focus:outline-none p-2" aria-label="Menüyü Aç" aria-expanded="false">
            <svg class="hamburger w-8 h-8 text-[var(--text-main)]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <line x1="5" y1="6" x2="19" y2="6" class="line1"></line>
                <line x1="5" y1="12" x2="19" y2="12" class="line2"></line>
                <line x1="5" y1="18" x2="19" y2="18" class="line3"></line>
            </svg>
        </button>
    </nav>

    <div id="mobile-menu">
        <div class="flex flex-col gap-8 text-center items-center"> 
            <a href="<?= langUrl() ?>"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]"><?= App::getLang() === 'EN' ? 'HOME' : 'ANASAYFA' ?></a>
            <a href="<?= langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler') ?>"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]"><?= App::getLang() === 'EN' ? 'ARTICLES' : 'MAKALELER' ?></a>
            <a href="<?php echo $notlar_url; ?>"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]"><?= App::getLang() === 'EN' ? 'NOTES' : 'NOTLAR' ?></a>

            <a href="<?= pageUrl('manifesto') ?>"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]"><?= App::getLang() === 'EN' ? 'MANIFESTO' : 'MANİFESTO' ?></a>
            <a href="<?= pageUrl('about') ?>"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]"><?= App::getLang() === 'EN' ? 'ABOUT' : 'HAKKINDA' ?></a>
            <div class="theme-switch-wrapper group scale-125 mt-4" role="button" tabindex="0" aria-label="Temayı Değiştir">
                <svg class="theme-icon sun-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <div class="theme-switch"></div>
                <svg class="theme-icon moon-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                    </path>
                </svg>
            </div>

        </div>

        <div class="mt-12 opacity-50 text-xs tracking-widest uppercase">
            <?= App::getLang() === 'EN' ? 'Science and Aesthetics' : 'Bilim ve Estetik' ?>
        </div>
    </div>
    <script nonce="<?= CSP_NONCE ?>">
        const navbar = document.getElementById('main-navbar');
        let lastScrollY = window.scrollY;

        const hamburgerBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const html = document.documentElement;
        const body = document.body;

        let isMenuOpen = false;

        function toggleScrollLock(isOpen) {
            if (isOpen) {
                html.style.overflow = 'hidden';
                body.style.overflow = 'hidden';
                body.style.touchAction = 'none';
                hamburgerBtn.setAttribute('aria-expanded', 'true');
            } else {
                html.style.overflow = '';
                body.style.overflow = '';
                body.style.touchAction = '';
                hamburgerBtn.setAttribute('aria-expanded', 'false');
            }
        }

        hamburgerBtn.addEventListener('click', () => {
            isMenuOpen = !isMenuOpen;
            hamburgerBtn.classList.toggle('menu-active');
            mobileMenu.classList.toggle('menu-open');
            toggleScrollLock(isMenuOpen);
        });

        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                isMenuOpen = false;
                hamburgerBtn.classList.remove('menu-active');
                mobileMenu.classList.remove('menu-open');
                toggleScrollLock(false);
            });
        });

        window.addEventListener('scroll', () => {
            if (isMenuOpen) return;

            const currentScrollY = window.scrollY;

            if (currentScrollY <= 0) {
                navbar.classList.remove('nav-hidden');
                lastScrollY = 0;
                return;
            }

            if (currentScrollY > lastScrollY && currentScrollY > 50) {
                if (!navbar.classList.contains('nav-hidden')) {
                    navbar.classList.add('nav-hidden');
                }
            } else if (currentScrollY < lastScrollY) {
                if (navbar.classList.contains('nav-hidden')) {
                    navbar.classList.remove('nav-hidden');
                }
            }
            lastScrollY = currentScrollY;
        }, { passive: true });

        const themeToggleBtns = document.querySelectorAll('.theme-switch-wrapper');

        themeToggleBtns.forEach(btn => {
            btn.addEventListener('keydown', (e) => {
                if(e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
            
            btn.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                themeToggleBtns.forEach(b => b.setAttribute('aria-pressed', String(newTheme === 'dark')));
            });
        });

        // Mount sırasında aria-pressed senkronize et
        (function () {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            themeToggleBtns.forEach(b => b.setAttribute('aria-pressed', String(isDark)));
        })();

        // Mobil menü: Escape ile kapat (a11y)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isMenuOpen) {
                isMenuOpen = false;
                hamburgerBtn.classList.remove('menu-active');
                mobileMenu.classList.remove('menu-open');
                toggleScrollLock(false);
                hamburgerBtn.focus();
            }
        });
    </script>
