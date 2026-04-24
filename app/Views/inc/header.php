<?php
$current_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive($uri, $target)
{
    if ($uri == $target || ($target !== '/' && strpos($uri, '/makale') === 0 && $target == '/makaleler')) {
        return 'border-b-2 border-[var(--text-accent)] !text-[var(--text-accent)]';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo isset($page_title) ? $page_title : 'FEZADAN'; ?>
    </title>
    
    <link rel="icon" type="image/x-icon" href="/cdn/light-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/light-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/light-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/light-apple-touch-icon.png">
    
    <meta name="description"
        content="<?php echo isset($article['short_desc']) ? htmlspecialchars($article['short_desc']) : 'Veri ve estetik arasındaki sessiz çatışma.'; ?>">
    <meta name="robots" content="index, follow">
    <?php
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $canonical_url = $protocol . "://" . $host . $path;
    ?>
    <link rel="canonical" href="<?php echo $canonical_url; ?>">

    <meta property="og:site_name" content="FEZADAN">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : 'FEZADAN'; ?>">
    <meta property="og:description" content="<?php echo $og_desc ?? ''; ?>">
    <meta property="og:image" content="<?php echo $og_image ?? ''; ?>">
    <meta property="og:url" content="<?php echo $og_url ?? ''; ?>">
    <meta name="twitter:card" content="summary_large_image">
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="/assets/css/fonts.css">

    <style>
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
            --text-main: #E5D0AC;
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

        [data-theme="dark"] .theme-switch::after {
            transform: translateX(20px);
            background-color: var(--text-main);
        }

        [data-theme="dark"] .theme-switch {
            background-color: var(--bg-secondary);
        }

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

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--text-accent); 
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: var(--text-accent); 
        }

        .nav-link:hover::after {
            width: 100%;
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
    <script>
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

    <nav id="main-navbar" class="flex justify-between items-center px-3 py-5 md:px-6 h-[85px]">

        <div class="relative z-50">
            <a href="/" class="flex items-center gap-2" aria-label="Anasayfa">
                <img src="/cdn/logo-light.png" 
                    alt="Fezadan Logo" 
                    width="150" height="40"
                    style="height: 40px; width: auto;" 
                    class="object-contain rounded-sm logo-light">
                    
                <img src="/cdn/logo-dark.png" 
                    alt="Fezadan Logo" 
                    width="150" height="40"
                    style="height: 40px; width: auto;" 
                    class="object-contain rounded-sm logo-dark">
            </a>
        </div>

        <div class="hidden md:flex gap-8 items-center pt-1">
            <a href="<?php echo SITE_URL; ?>/makaleler"
                class="nav-link <?php echo isActive($current_uri, '/makaleler'); ?>">Makaleler</a>
            
            <?php 
                $host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
                $notlar_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . (strpos($host, 'notlar.') === 0 ? $host : "notlar." . $host);
            ?>
            <a href="<?php echo $notlar_url; ?>" class="nav-link">Notlar</a>
            
            <a href="<?php echo SITE_URL; ?>/hakkinda"
                class="nav-link <?php echo isActive($current_uri, '/hakkinda'); ?>">Hakkında</a>
                
            <a href="<?php echo SITE_URL; ?>/manifesto"
                class="nav-link <?php echo isActive($current_uri, '/manifesto'); ?>">Manifesto</a>

            <div id="theme-toggle" class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Temayı Değiştir">
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
            <a href="/"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">ANASAYFA</a>
            <a href="/makaleler"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">MAKALELER</a>
            <a href="<?php echo $notlar_url; ?>"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">NOTLAR</a>
            <a href="/hakkinda"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">HAKKINDA</a>
            <a href="/manifesto"
                class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">MANİFESTO</a>
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
            Bilim ve Estetik
        </div>
    </div>
    <script>
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
            });
        });
    </script>