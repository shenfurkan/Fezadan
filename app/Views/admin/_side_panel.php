<?php
// Aktif sayfayı belirlemek için mevcut URI'yi alıyoruz
$current_uri = $_SERVER['REQUEST_URI'];

$page_title = "PANEL";
$system_status = "";
$status_color = "";
$blink = "";

if (strpos($current_uri, '/yonetim/create-patch') !== false) {
    $page_title = "NOT DEGISIKLIGI";
} elseif (strpos($current_uri, '/yonetim/create') !== false) {
    $page_title = "MAKALE YAZ";
} elseif (strpos($current_uri, '/yonetim/categories') !== false) {
    $page_title = "KATEGORILER";
} elseif (strpos($current_uri, '/yonetim/authors') !== false) {
    $page_title = "YAZARLAR";
} elseif (strpos($current_uri, 'add-note') !== false || strpos($current_uri, 'store-note') !== false || strpos($current_uri, 'edit-note') !== false) {
    $page_title = "NOTLAR";
} elseif (strpos($current_uri, '/yonetim/logs') !== false) {
    $page_title = "KAYITLAR";
} elseif (strpos($current_uri, '/yonetim/admins') !== false) {
    $page_title = "KULLANICILAR";
} elseif (strpos($current_uri, '/yonetim/profile') !== false) {
    $page_title = "PROFIL";
} elseif (strpos($current_uri, '/yonetim/edit') !== false) {
    $page_title = "MAKALE DUZENLE";
} elseif (strpos($current_uri, '/yonetim/portfolio') !== false) {
    $page_title = "PORTFOLYO";
}
?>

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

<style>
    /* Admin Paneli Karanlık Tema Renk Değişkenleri */
    [data-theme="dark"] {
        --bg-paper: #120A0A;
        --bg-secondary: #1F1212;
        --text-main: #E1C89E;
        --text-accent: #FF5C5C;
        --line-color: #E1C89E;
    }

    /* Tema Değiştirme Butonu Tasarımı */
    .theme-switch-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .theme-switch {
        width: 48px;
        height: 24px;
        background-color: var(--text-main);
        border: 2px solid var(--text-main);
        border-radius: 999px;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    }

    [data-theme="dark"] .theme-switch {
        background-color: var(--bg-secondary);
        border-color: var(--text-main);
    }

    .theme-switch::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background-color: var(--bg-paper);
        border-radius: 50%;
        transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    [data-theme="dark"] .theme-switch::after {
        transform: translateX(24px);
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
</style>

<aside class="w-20 md:w-72 bg-[var(--bg-paper)] border-r-2 border-[var(--text-main)] flex flex-col z-20 flex-shrink-0 transition-all duration-300 h-screen">
    <div class="h-24 flex items-center justify-center md:justify-start md:px-8 border-b-2 border-[var(--text-main)] flex-shrink-0">
        <div class="hidden md:block">
            <h1 class="font-syne text-3xl font-bold tracking-tighter text-[var(--text-main)]">FEZADAN</h1>
            <p class="font-mono text-[10px] text-[var(--text-accent)] tracking-[0.2em]">Yönetim Paneli</p>
        </div>
        <div class="md:hidden font-syne text-2xl font-bold text-[var(--text-main)]">FZD</div>
    </div>

    <nav class="flex-1 overflow-y-auto py-8 flex flex-col gap-2">
        <?php $adminRole = $_SESSION['admin_role'] ?? 'superadmin'; ?>
        <a href="/yonetim/dashboard" class="nav-item <?php echo strpos($current_uri, '/yonetim/dashboard') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg>
            <span class="hidden md:inline">Genel Bakış</span>
        </a>
        <?php if ($adminRole !== 'viewer'): ?>
        <a href="/yonetim/create" class="nav-item <?php echo (strpos($current_uri, '/yonetim/create') !== false && strpos($current_uri, '/yonetim/create-patch') === false) ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M160-400v-80h280v80H160Zm0-160v-80h440v80H160Zm0-160v-80h440v80H160Zm360 560v-123l221-220q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T863-380L643-160H520Zm300-263-37-37 37 37ZM580-220h38l121-122-18-19-19-18-122 121v38Zm141-141-19-18 37 37-18-19Z"/></svg>
            <span class="hidden md:inline">Makale Yaz</span>
        </a>
        <a href="/yonetim/categories" class="nav-item <?php echo strpos($current_uri, '/yonetim/categories') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640H447l-80-80H160v480l96-320h684L837-217q-8 26-29.5 41.5T760-160H160Zm84-80h516l72-240H316l-72 240Zm0 0 72-240-72 240Zm-84-400v-80 80Z"/></svg>
            <span class="hidden md:inline">Kategoriler</span>
        </a>
        <?php if ($adminRole === 'superadmin'): ?>
            <a href="/yonetim/authors" class="nav-item <?php echo strpos($current_uri, '/yonetim/authors') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T541-790q14-5 28-7.5t29-2.5q66 0 113 47t47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0 320Zm0-400Z"/></svg>
                <span class="hidden md:inline">Yazarlar</span>
            </a>
        <?php endif; ?>
        <a href="/yonetim/add-note" class="nav-item <?php echo (stripos($_SERVER['REQUEST_URI'], 'add-note') !== false || stripos($_SERVER['REQUEST_URI'], 'edit-note') !== false) ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
            <span class="hidden md:inline">Notlar</span>
        </a>

        <?php if ($adminRole === 'superadmin'): ?>
        <a href="/yonetim/portfolio" class="nav-item <?php echo strpos($current_uri, '/yonetim/portfolio') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M360-400h400L622-580l-92 120-62-80-108 140Zm-40 160q-33 0-56.5-23.5T240-320v-480q0-33 23.5-56.5T320-880h480q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H320Zm0-80h480v-480H320v480ZM160-80q-33 0-56.5-23.5T80-160v-560h80v560h560v80H160Zm160-720v480-480Z"/></svg>
            <span class="hidden md:inline">Portfolyo</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <a href="/yonetim/logs" class="nav-item <?php echo strpos($current_uri, '/yonetim/logs') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M480-200q66 0 113-47t47-113v-160q0-66-47-113t-113-47q-66 0-113 47t-47 113v160q0 66 47 113t113 47Zm-80-120h160v-80H400v80Zm0-160h160v-80H400v80Zm80 40Zm0 320q-65 0-120.5-32T272-240H160v-80h84q-3-20-3.5-40t-.5-40h-80v-80h80q0-20 .5-40t3.5-40h-84v-80h112q27-56 82.5-88T480-680q65 0 120.5 32T683-560h117v80h-84q3 20 3.5 40t.5 40h80v80h-80q0 20-.5 40t-3.5 40h84v80H688q-27 56-82.5 88T480-120Z"/></svg>
            <span class="hidden md:inline">Hata Kayıtları</span>
        </a>
        <?php if ($adminRole === 'superadmin'): ?>
        <a href="/yonetim/admins" class="nav-item <?php echo strpos($current_uri, '/yonetim/admins') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M287-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18h14q6 0 12 2-8 18-13.5 37.5T404-360h-4q-71 0-127.5 18T180-306q-9 5-14.5 14t-5.5 20v32h252q6 21 16 41.5t22 38.5H80Zm560 40-12-60q-12-5-22.5-10.5T584-204l-58 18-40-68 46-40q-2-14-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T628-460l12-60h80l12 60q12 5 22.5 10.5T776-436l58-18 40 68-46 40q2 14 2 26t-2 26l46 40-40 68-58-18q-11 8-21.5 13.5T732-180l-12 60h-80Zm40-120q33 0 56.5-23.5T760-320q0-33-23.5-56.5T680-400q-33 0-56.5 23.5T600-320q0 33 23.5 56.5T680-240ZM400-640q0 33 23.5 56.5T480-560q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640Zm80 80Zm-8 320Z"/></svg>
            <span class="hidden md:inline">Kullanıcılar</span>
        </a>
        <?php endif; ?>
        <a href="/yonetim/profile" class="nav-item <?php echo strpos($current_uri, '/yonetim/profile') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 65q-5 14-7 29.5t-2 31.5q0 16 2 31.5t7 29.5l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg>
            <span class="hidden md:inline">Profil Ayarları</span>
        </a>
    </nav>

    <div class="p-6 border-t-2 border-[var(--text-main)] flex-shrink-0">
        <form method="POST" action="/yonetim/logout">
            <?= Csrf::field() ?>
            <button type="submit" class="w-full block border-2 border-[var(--text-accent)] text-[var(--text-accent)] text-center py-3 font-bold uppercase hover:bg-[var(--text-accent)] hover:text-[var(--bg-paper)] transition-colors text-xs tracking-widest">
                Güvenli Çıkış
            </button>
        </form>
    </div>
</aside>

<main class="flex-1 flex flex-col relative z-10 overflow-hidden h-screen">
    
    <header class="h-24 border-b-2 border-[var(--text-main)] bg-[var(--bg-paper)]/90 backdrop-blur-sm flex justify-between items-center px-6 md:px-12 flex-shrink-0 z-20 transition-colors">
        <div>
            <h2 class="font-syne text-xl md:text-3xl font-bold uppercase text-[var(--text-main)]"><?php echo $page_title; ?></h2>
            <?php if ($system_status !== ''): ?>
            <div class="flex items-center gap-2 text-xs font-mono text-[var(--text-accent)] mt-1">
                <span class="w-2 h-2 <?php echo $status_color; ?> rounded-full <?php echo $blink; ?>"></span>
                <?php echo $system_status; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="flex items-center gap-6 md:gap-10">
            <div class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Tema Değiştir">
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
    </header>

    <script nonce="<?= CSP_NONCE ?>">
        // Tema Switch Fonksiyonu
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

    <div class="flex-1 overflow-y-auto p-6 md:p-12 relative">

