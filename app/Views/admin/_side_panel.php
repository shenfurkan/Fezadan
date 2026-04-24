<?php
// Aktif sayfayı belirlemek için mevcut URI'yi alıyoruz
$current_uri = $_SERVER['REQUEST_URI'];

// Dinamik Sayfa Başlıkları ve Sistem Durumu Algılayıcı
$page_title = "KONTROL PANELİ";
$system_status = "SİSTEM ÇEVRİMİÇİ";
$status_color = "bg-[#00ff00]"; // Dashboard için yeşil
$blink = "blink";

// DİKKAT: /admin/create-patch kontrolü, /admin/create'den ÖNCE olmalıdır
// Aksi halde sistem içinde 'create' kelimesi geçtiği için makale yazma sayfası sanar.
if (strpos($current_uri, '/admin/create-patch') !== false) {
    $page_title = "YAMA SİSTEMİ";
    $system_status = "MODE: INSERT";
    $status_color = "bg-[var(--text-accent)]";
} elseif (strpos($current_uri, '/admin/create') !== false) {
    $page_title = "YENİ MAKALE YAZ";
    $system_status = "EDİTÖR AKTİF";
    $status_color = "bg-yellow-400";
} elseif (strpos($current_uri, '/admin/categories') !== false) {
    $page_title = "KATEGORİ YÖNETİMİ";
    $system_status = "VERİTABANI BAĞLANTISI STABİL";
    $status_color = "bg-[var(--text-accent)]";
} elseif (strpos($current_uri, '/admin/authors') !== false) {
    $page_title = "YAZAR HAVUZU";
    $system_status = "PROFİL VERİLERİ ÇEKİLİYOR...";
    $status_color = "bg-[var(--text-accent)]";
} elseif (strpos($current_uri, 'addNote') !== false || strpos($current_uri, 'storeNote') !== false) {
    $page_title = "PDF NOT YÖNETİMİ";
    $system_status = "R2 DEPOLAMA BAĞLANTISI AKTİF";
    $status_color = "bg-[var(--text-accent)]";
} elseif (strpos($current_uri, '/admin/profile') !== false) {
    $page_title = "PROFİL GÜVENLİĞİ";
    $system_status = "GÜVENLİK PROTOKOLLERİ AKTİF";
    $status_color = "bg-[var(--text-accent)]";
} elseif (strpos($current_uri, '/admin/edit') !== false) {
    $page_title = "MAKALE DÜZENLEME";
    $system_status = "EDİTÖR KİLİDİ AÇILDI";
    $status_color = "bg-yellow-400";
}
?>

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

<style>
    /* Admin Paneli Karanlık Tema Renk Değişkenleri */
    [data-theme="dark"] {
        --bg-paper: #120A0A;
        --bg-secondary: #1F1212;
        --text-main: #E5D0AC;
        --text-accent: #FF5C5C;
        --line-color: #E5D0AC;
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
            <p class="font-mono text-[10px] text-[var(--text-accent)] tracking-[0.2em]">TERMINAL v4.0</p>
        </div>
        <div class="md:hidden font-syne text-2xl font-bold text-[var(--text-main)]">FZD</div>
    </div>

    <nav class="flex-1 overflow-y-auto py-8 flex flex-col gap-2">
        <a href="/admin/dashboard" class="nav-item <?php echo strpos($current_uri, '/admin/dashboard') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <span class="text-lg">▣</span> <span class="hidden md:inline">Genel Bakış</span>
        </a>
        <a href="/admin/create" class="nav-item <?php echo (strpos($current_uri, '/admin/create') !== false && strpos($current_uri, '/admin/create-patch') === false) ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <span class="text-lg">✎</span> <span class="hidden md:inline">Yeni Makale Yaz</span>
        </a>
        <a href="/admin/categories" class="nav-item <?php echo strpos($current_uri, '/admin/categories') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <span class="text-lg">❖</span> <span class="hidden md:inline">Kategoriler</span>
        </a>
        <a href="/admin/authors" class="nav-item <?php echo strpos($current_uri, '/admin/authors') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <span class="text-lg">♟</span> <span class="hidden md:inline">Yazarlar</span>
        </a>
        <a href="/admin/addNote" class="nav-item <?php echo (stripos($_SERVER['REQUEST_URI'], 'addNote') !== false) ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <span class="text-lg">🗄</span> <span class="hidden md:inline">Notlar</span>
        </a>
        <a href="/admin/profile" class="nav-item <?php echo strpos($current_uri, '/admin/profile') !== false ? 'active' : ''; ?> py-4 px-8 font-bold uppercase tracking-widest text-sm flex items-center gap-3">
            <span class="text-lg">⚙</span> <span class="hidden md:inline">Profil Ayarları</span>
        </a>
    </nav>

    <div class="p-6 border-t-2 border-[var(--text-main)] flex-shrink-0">
        <a href="/admin/logout" class="block border-2 border-[var(--text-accent)] text-[var(--text-accent)] text-center py-3 font-bold uppercase hover:bg-[var(--text-accent)] hover:text-[var(--bg-paper)] transition-colors text-xs tracking-widest">
            Güvenli Çıkış
        </a>
    </div>
</aside>

<main class="flex-1 flex flex-col relative z-10 overflow-hidden h-screen">
    
    <header class="h-24 border-b-2 border-[var(--text-main)] bg-[var(--bg-paper)]/90 backdrop-blur-sm flex justify-between items-center px-6 md:px-12 flex-shrink-0 z-20 transition-colors">
        <div>
            <h2 class="font-syne text-xl md:text-3xl font-bold uppercase text-[var(--text-main)]"><?php echo $page_title; ?></h2>
            <div class="flex items-center gap-2 text-xs font-mono text-[var(--text-accent)] mt-1">
                <span class="w-2 h-2 <?php echo $status_color; ?> rounded-full <?php echo $blink; ?>"></span>
                <?php echo $system_status; ?>
            </div>
        </div>
        
        <div class="flex items-center gap-6 md:gap-10">
            <div class="theme-switch-wrapper group" role="button" tabindex="0" aria-label="Temayı Değiştir">
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

            <div class="text-right hidden md:block">
                <p class="text-[10px] uppercase opacity-60 tracking-widest">YEREL ZAMAN</p>
                <p class="font-mono text-xl font-bold" id="shared-clock">00:00:00</p>
            </div>
        </div>
    </header>

    <script>
        // Saat Fonksiyonu
        function updateSharedClock() {
            const now = new Date();
            const clock = document.getElementById('shared-clock');
            if(clock) clock.textContent = now.toLocaleTimeString('tr-TR', { hour12: false });
        }
        setInterval(updateSharedClock, 1000); updateSharedClock();

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