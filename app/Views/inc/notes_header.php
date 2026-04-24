<?php 
    $host = $_SERVER['HTTP_HOST'];
    $main_site_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . str_replace('notlar.', '', $host);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <script>
        (function () {
            const userTheme = localStorage.getItem('theme');
            if (userTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN NOTLAR | <?php echo $page_title ?? 'Akademik Veri Havuzu'; ?></title>
    
    <link rel="icon" type="image/x-icon" href="/cdn/light-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/light-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/light-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/light-apple-touch-icon.png">
    
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? 'Fezadan PDF Veri Havuzu. Astronomi, bilim ve teknoloji üzerine akademik notlar, makaleler ve araştırmalar.'); ?>">
    <meta name="robots" content="index, follow">
    
    <?php $current_url = "https://notlar.fezadan.org" . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
    <link rel="canonical" href="<?php echo $current_url; ?>">

    <meta property="og:site_name" content="FEZADAN NOTLAR">
    <meta property="og:type" content="<?php echo isset($is_note) ? 'article' : 'website'; ?>">
    <meta property="og:title" content="FEZADAN NOTLAR | <?php echo htmlspecialchars($page_title ?? 'Akademik Veri Havuzu'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description ?? 'Fezadan PDF Veri Havuzu.'); ?>">
    <meta property="og:url" content="<?php echo $current_url; ?>">
    <meta property="og:image" content="https://fezadan.org/cdn/notlar-social-preview.png">
    <meta name="twitter:image" content="https://fezadan.org/cdn/notlar-social-preview.png">
    <meta name="twitter:card" content="summary_large_image">
    
    <link rel="stylesheet" href="/assets/css/admin.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    
    <style>
        :root { 
            --bg-paper: #FEF9E1; 
            --bg-secondary: #E5D0AC; 
            --text-main: #6D2323; 
            --text-accent: #A31D1D; 
            --line-color: #6D2323; 
        }

        [data-theme="dark"] {
            --bg-paper: #120A0A;
            --bg-secondary: #1F1212;
            --text-main: #E5D0AC;
            --text-accent: #FF5C5C;
            --line-color: #3D1F1F;
        }

        [data-theme="dark"] .theme-switch {
            background-color: var(--bg-secondary);
            border-color: var(--text-main);
        }

        body { 
            background-color: var(--bg-paper); 
            color: var(--text-main); 
            font-family: 'Space Grotesk', sans-serif; 
            padding-top: 85px;
            transition: background-color 0.3s ease, color 0.3s ease;
            overflow-x: hidden;
        }

        .logo-dark { display: none; }
        [data-theme="dark"] .logo-light { display: none; }
        [data-theme="dark"] .logo-dark { display: block; }

        .theme-switch-wrapper { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .theme-switch { width: 48px; height: 24px; background-color: var(--text-main); border: 2px solid var(--text-main); border-radius: 999px; position: relative; transition: background-color 0.3s; }
        .theme-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background-color: var(--bg-paper); border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1); }
        [data-theme="dark"] .theme-switch::after { transform: translateX(24px); background-color: var(--text-main); }
        [data-theme="dark"] .theme-switch { background-color: var(--bg-secondary); }
        .theme-icon { width: 14px; height: 14px; color: var(--text-main); }

        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        .grid-bg { 
            background-image: linear-gradient(var(--line-color) 1px, transparent 1px), 
                            linear-gradient(90deg, var(--line-color) 1px, transparent 1px); 
            background-size: 40px 40px; 
            opacity: 0.05; 
            pointer-events: none; 
        }
        
        .sun-icon { opacity: 1; color: var(--text-main); }
        .moon-icon { opacity: 0.3; color: var(--text-main); }
        [data-theme="dark"] .sun-icon { opacity: 0.3; }
        [data-theme="dark"] .moon-icon { opacity: 1; }

        /* --- MOBİL MENÜ VE NAVBAR ANİMASYONLARI --- */
        #main-navbar {
            transform: translateY(0);
            transition: transform 0.3s ease-in-out, background-color 0.3s, border-color 0.3s;
            will-change: transform;
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
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.3s;
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

        .menu-active .line1 { transform: translateY(6px) rotate(45deg); }
        .menu-active .line2 { opacity: 0; transform: scaleX(0); }
        .menu-active .line3 { transform: translateY(-6px) rotate(-45deg); }
    </style>
</head>
<body class="relative min-h-screen flex flex-col">
    <div class="grid-bg fixed inset-0 z-0"></div>

    <nav id="main-navbar" class="fixed top-0 left-0 w-full z-50 bg-[var(--bg-paper)] border-b-2 border-[var(--text-main)] flex justify-between items-center px-4 md:px-8 h-[85px] shadow-[0_4px_0px_rgba(109,35,35,0.05)]">
    
        <div class="flex items-center gap-4 relative z-50">
            <a href="/" class="flex items-center gap-2">
                <img src="/cdn/logo-light.png" alt="Logo" class="h-10 w-auto object-contain logo-light mix-blend-multiply opacity-90">
                <img src="/cdn/logo-dark.png" alt="Logo" class="h-10 w-auto object-contain logo-dark">
            </a>
            <div class="hidden md:flex flex-col justify-center border-l-2 border-[var(--text-main)] pl-4 ml-2">
                <span class="font-syne font-bold text-[var(--text-main)] leading-none text-3xl uppercase"><a href="/">NOTLAR</a></span>
            </div>
        </div>

        <div class="hidden md:flex items-center gap-6">
            <a href="<?php echo $main_site_url; ?>" class="group flex items-center gap-2 border-2 border-[var(--text-main)] px-4 py-2 hover:bg-[var(--text-main)] transition-all">
                <span class="font-bold text-[18px] text-[var(--text-main)] group-hover:text-[var(--bg-paper)] group-hover:-translate-x-1 transition-transform leading-none mb-1">←</span>
                <span class="font-syne font-bold uppercase text-[10px] md:text-xs text-[var(--text-main)] group-hover:text-[var(--bg-paper)] tracking-widest">
                    MERKEZE DÖN
                </span>
            </a>

            <div class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Temayı Değiştir">
                <svg class="theme-icon sun-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <div class="theme-switch"></div>
                <svg class="theme-icon moon-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </div>
        </div>

        <button id="hamburger-btn" class="md:hidden relative z-50 focus:outline-none p-2" aria-label="Menüyü Aç" aria-expanded="false">
            <svg class="hamburger w-8 h-8 text-[var(--text-main)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="5" y1="6" x2="19" y2="6" class="line1"></line>
                <line x1="5" y1="12" x2="19" y2="12" class="line2"></line>
                <line x1="5" y1="18" x2="19" y2="18" class="line3"></line>
            </svg>
        </button>
    </nav>

    <div id="mobile-menu">
        <div class="flex flex-col gap-8 text-center items-center"> 
            <a href="/" class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">NOTLAR</a>
            <a href="<?php echo $main_site_url; ?>" class="text-2xl font-syne font-bold text-[var(--text-main)] hover:text-[var(--text-accent)]">MERKEZ</a>
            
            <div class="theme-switch-wrapper group scale-125 mt-4" role="button" tabindex="0" aria-label="Temayı Değiştir">
                <svg class="theme-icon sun-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <div class="theme-switch"></div>
                <svg class="theme-icon moon-icon opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </div>
        </div>

        <div class="mt-12 opacity-50 text-xs tracking-widest uppercase font-mono">
            Fezadan Notlar
        </div>
    </div>

    <script>
        const navbar = document.getElementById('main-navbar');
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const html = document.documentElement;
        const body = document.body;
        
        let lastScrollY = window.scrollY;
        let isMenuOpen = false;

        // Arka planın kaymasını engelle
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

        // Hamburger Tıklama Olayı
        hamburgerBtn.addEventListener('click', () => {
            isMenuOpen = !isMenuOpen;
            hamburgerBtn.classList.toggle('menu-active');
            mobileMenu.classList.toggle('menu-open');
            toggleScrollLock(isMenuOpen);
        });

        // Mobil menüdeki linklere tıklanınca menüyü kapat
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                isMenuOpen = false;
                hamburgerBtn.classList.remove('menu-active');
                mobileMenu.classList.remove('menu-open');
                toggleScrollLock(false);
            });
        });

        // Aşağı Kaydırınca Navbar'ı Gizle (Ana sitedeki özellik)
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

        // Tema Değiştirme Mantığı (Hem masaüstü hem mobil butonlar için)
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