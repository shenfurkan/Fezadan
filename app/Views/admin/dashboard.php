<?php
// Sıralama bağlantısı
function sortLink($col, $currentSort) {
    $params = $_GET;
    unset($params['page']);
    unset($params['url']);
    
    // Sıralama yönü
    $newOrder = 'DESC';
    if ($currentSort['column'] == $col && $currentSort['order'] == 'DESC') {
        $newOrder = 'ASC';
    }
    
    $params['sort'] = $col;
    $params['order'] = $newOrder;
    
    return '?' . http_build_query($params);
}

function sortIndicator($col, $currentSort) {
    if ($currentSort['column'] != $col) return '<span class="opacity-50 ml-1 text-[10px]">↕</span>';
    
    return $currentSort['order'] == 'ASC' 
        ? ' <span class="text-[#FEF9E1] font-bold text-lg">↑</span>' 
        : ' <span class="text-[#FEF9E1] font-bold text-lg">↓</span>';
}

$showSystemScan = isset($_GET['scan']) && $_GET['scan'] == '1';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | Dashboard</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="icon" type="image/jpeg" href="<?php echo SITE_URL ?? ''; ?>/assets/uploads/logo.jpg">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <script src="/assets/js/admin.js?v=<?php echo filemtime(ROOT . '/public_html/assets/js/admin.js'); ?>"></script>

    <style>
        :root {
            --bg-paper: #FEF9E1;
            --bg-secondary: #E5D0AC;
            --text-main: #6D2323;
            --text-accent: #A31D1D;
            --line-color: #6D2323;
            --terminal-bg: #1a1a1a;
            --terminal-text: #00ff00;
        }

        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-paper); border-left: 1px solid var(--line-color); }
        ::-webkit-scrollbar-thumb { background: var(--line-color); }

        .grid-bg {
            background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px);
            background-size: 40px 40px; opacity: 0.05; pointer-events: none;
        }

        .blink { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0; } }

        .brutalist-card { transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94); box-shadow: 8px 8px 0px var(--line-color); border: 2px solid var(--line-color); }
        .brutalist-card:hover { transform: translate(-2px, -2px); box-shadow: 12px 12px 0px var(--text-accent); border-color: var(--text-accent); }

        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }

        .scanline { width: 100%; height: 100px; z-index: 9999; background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(109, 35, 35, 0.1) 50%, rgba(0,0,0,0) 100%); opacity: 0.1; position: absolute; bottom: 100%; animation: scanline 10s linear infinite; pointer-events: none; }
        @keyframes scanline { 0% { bottom: 100%; } 100% { bottom: -100%; } }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">

    <div class="grid-bg fixed inset-0 z-0"></div>
    <div class="scanline fixed"></div>
    <?php if (isset($_GET['status'])): ?>
    <script nonce="<?= CSP_NONCE ?>">
        (function () {
            try {
                var raw = sessionStorage.getItem('fezadan_pending_draft_clear');
                if (!raw) return;
                var keys = JSON.parse(raw);
                Object.keys(keys || {}).forEach(function (name) {
                    localStorage.removeItem(keys[name]);
                });
                sessionStorage.removeItem('fezadan_pending_draft_clear');
            } catch (e) {}
        })();
    </script>
    <?php endif; ?>
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="scroll-smooth h-full"> <?php if ($flashMessage = Flash::pull()): ?>
        <div class="mb-8 p-6 bg-[#6D2323] text-[#FEF9E1] border-2 border-[var(--text-main)] shadow-[8px_8px_0px_#A31D1D] font-mono text-xs uppercase">
            <?= htmlspecialchars($flashMessage) ?>
        </div>
    <?php endif; ?><?php if (isset($_GET['status'])): ?>
        <div class="mb-8 p-6 bg-[#6D2323] text-[#FEF9E1] border-2 border-[var(--text-main)] shadow-[8px_8px_0px_#A31D1D] flex items-center justify-between">
            <div>
                <h3 class="font-syne text-xl font-bold uppercase">
                    <?php
                        $svgDelete = '<svg class="inline w-5 h-5 mr-1 -mt-0.5" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>';
                        $svgCheck  = '<svg class="inline w-5 h-5 mr-1 -mt-0.5" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>';
                        $svgSave   = '<svg class="inline w-5 h-5 mr-1 -mt-0.5" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM565-275q35-35 35-85t-35-85q-35-35-85-35t-85 35q-35 35-35 85t35 85q35 35 85 35t85-35ZM255-560h310v-160H255v160Zm-55-86v446-560 114Z"/></svg>';
                        $svgRocket = '<svg class="inline w-5 h-5 mr-1 -mt-0.5" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="m226-559 78 33q14-28 29-54t33-52l-56-11-84 84Zm142 83 114 113q42-16 90-49t90-75q70-70 109.5-155.5T806-800q-72-5-158 34.5T492-656q-42 42-75 90t-49 90Zm155-121.5q0-33.5 23-56.5t57-23q34 0 57 23t23 56.5q0 33.5-23 56.5t-57 23q-34 0-57-23t-23-56.5ZM565-220l84-84-11-56q-26 18-52 32.5T532-299l33 79Zm313-653q19 121-23.5 235.5T708-419l20 99q4 20-2 39t-20 33L538-80l-84-197-171-171-197-84 168-168q14-14 33-20t39-2l99 20q57-56 171.5-98.5T878-873ZM157-321q35-35 85-35.5t85 34.5q35 35 34.5 85T326-152q-25 25-57.5 43T175-80q-1-32 17-64.5t43-58.5q-18 2-38 5t-40 9q17-22 33.5-41t33.5-33q17-17 17.5-41.5T224-346l-67 25Z"/></svg>';
                        $svgMap    = '<svg class="inline w-5 h-5 mr-1 -mt-0.5" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="m600-120-240-84-186 72q-20 8-37-4.5T120-170v-560q0-13 7.5-23t20.5-15l212-72 240 84 186-72q20-8 37 4.5t17 33.5v560q0 13-7.5 23T812-192l-212 72Zm-40-98v-468l-160-56v468l160 56Zm80 0 120-40v-474l-120 46v468Zm-440-10 120-46v-468l-120 40v474Zm440-458v468-468Zm-320-56v468-468Z"/></svg>';

                        if($_GET['status'] == 'deleted') echo $svgDelete . ' MAKALE SİLİNDİ';
                        elseif($_GET['status'] == 'patch_deleted') echo $svgDelete . ' YAMA NOTU SİLİNDİ';
                        elseif($_GET['status'] == 'patch_added') echo $svgCheck . ' NOT KAYDEDILDI';
                        elseif($_GET['status'] == 'draft_saved') echo $svgSave . ' TASLAK KAYDEDILDI';
                        elseif($_GET['status'] == 'published') echo $svgRocket . ' MAKALE YAYINLANDI';
                        elseif($_GET['status'] == 'sitemap') echo $svgMap . ' SITE HARITASI YENILENDI';
                        else echo $svgCheck . ' ISLEM BASARILI';
                    ?>
                </h3>
                <p class="font-mono text-xs opacity-80 mt-1">Islem basariyla tamamlandi.</p>
            </div>
            <a href="/yonetim/dashboard" class="px-4 py-2 border border-[#FEF9E1] hover:bg-[var(--bg-paper)] hover:text-[var(--text-main)] font-mono text-xs uppercase transition-colors">OK</a>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="brutalist-card bg-[var(--bg-paper)] p-6 relative group overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 right-0 p-4 opacity-10 font-syne text-9xl leading-none -mt-4 -mr-4 group-hover:scale-110 transition-transform pointer-events-none">01</div>
                
                <div class="relative z-10 border-b border-[var(--text-main)]/10 pb-3 mb-3">
                    <h3 class="font-mono text-[10px] text-[var(--text-accent)] mb-1 uppercase tracking-widest">
                        Toplam Makale
                    </h3>
                    <div class="font-syne text-4xl font-bold text-[var(--text-main)] flex items-baseline gap-3">
                        <?php echo $stats['total_articles'] ?? '0'; ?>
                        <?php if(($stats['total_drafts'] ?? 0) > 0): ?>
                            <span class="text-sm font-mono text-yellow-700 bg-yellow-100 border border-yellow-400 px-2 py-0.5">
                                <?php echo $stats['total_drafts']; ?> TASLAK
                            </span>
                        <?php endif; ?>
                        <?php if(($stats['total_scheduled'] ?? 0) > 0): ?>
                            <span class="text-sm font-mono text-blue-700 bg-blue-100 border border-blue-400 px-2 py-0.5">
                                <?php echo $stats['total_scheduled']; ?> PLANLI
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="relative z-10">
                    <h3 class="font-mono text-[10px] text-[var(--text-accent)] mb-1 uppercase tracking-widest">
                        Toplam Okunma
                    </h3>
                    
                    <div class="font-syne text-4xl font-bold text-[var(--text-main)] flex items-center gap-3">
                        
                        <svg class="w-8 h-8 opacity-85 text-[var(--text-accent)]" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M607.5-372.5Q660-425 660-500t-52.5-127.5Q555-680 480-680t-127.5 52.5Q300-575 300-500t52.5 127.5Q405-320 480-320t127.5-52.5Zm-204-51Q372-455 372-500t31.5-76.5Q435-608 480-608t76.5 31.5Q588-545 588-500t-31.5 76.5Q525-392 480-392t-76.5-31.5ZM214-281.5Q94-363 40-500q54-137 174-218.5T480-800q136 0 256 81.5T910-500Q856-363 736-281.5T480-200q-136 0-266-81.5ZM480-500Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z"/></svg>
                        
                        <?php echo number_format($stats['total_reads'] ?? 0); ?>
                        
                    </div>
                </div>
            </div>

            <div class="brutalist-card bg-[#6D2323] text-[#FEF9E1] p-6 relative group border-[var(--text-main)]">
                <h3 class="font-mono text-xs text-[#E1C89E] mb-2 uppercase tracking-widest">Sunucu Durumu</h3>
                <div class="font-syne text-2xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($stats['overall_status'] ?? 'Normal'); ?></div>
                <p class="text-xs opacity-80 font-mono leading-relaxed">PHP <?php echo PHP_VERSION; ?> | Mem: <?php echo ini_get('memory_limit'); ?></p>
            </div>

            <div class="brutalist-card bg-[var(--bg-paper)] p-6 relative group">
                <h3 class="font-mono text-xs text-[var(--text-accent)] mb-2 uppercase tracking-widest">Son Erişim</h3>
                <div class="font-syne text-2xl font-bold text-[var(--text-main)] mb-2"><?php echo $stats['last_login_full'] ?? date('d.m.Y H:i'); ?></div>
                <p class="text-xs opacity-60 font-mono">USER: <?php echo $_SESSION['admin_user'] ?? 'yonetim'; ?></p>
            </div>
        </div>

        <div class="mb-12">
            <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
                <h3 class="font-syne text-xl font-bold uppercase flex items-center gap-3 text-[var(--text-main)]">
                    <span class="w-4 h-4 bg-[#6D2323]"></span>
                    MAKALELER
                </h3>
                
                <form action="/yonetim/dashboard" method="GET" class="flex w-full md:w-auto">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($pagination['search'] ?? ''); ?>" 
                           placeholder="Makale ara... veya id=5" 
                           class="bg-transparent border-2 border-[var(--text-main)] px-4 py-2 font-mono text-xs outline-none placeholder-[#6D2323]/50 w-full md:w-64">
                    <button type="submit" class="bg-[#6D2323] text-[#FEF9E1] px-4 py-2 font-bold uppercase text-xs hover:bg-[var(--text-accent)]">ARA</button>
                    <?php if(!empty($pagination['search'])): ?>
                        <a href="/yonetim/dashboard" class="bg-[var(--text-accent)] text-white px-3 py-2 font-bold text-xs flex items-center justify-center">X</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] overflow-hidden shadow-[8px_8px_0px_rgba(109,35,35,0.1)]">
                <table class="w-full text-left font-mono text-xs table-fixed">
                    <thead class="bg-[#6D2323] text-[#FEF9E1] uppercase">
                        <tr>
                            <th class="bg-[#6D2323] w-20 border-r border-[#FEF9E1]/20 cursor-pointer hover:bg-black transition-colors p-0">
                                <a href="<?php echo sortLink('id', $sort); ?>" class="flex items-center justify-between w-full h-full p-4 text-[#FEF9E1] outline-none select-none [-webkit-tap-highlight-color:transparent]">
                                    ID <?php echo sortIndicator('id', $sort); ?>
                                </a>
                            </th>
                            
                            <th class="bg-[#6D2323] w-32 p-4 border-r border-[#FEF9E1]/20 hidden md:table-cell">Kapak</th>
                            
                            <th class="bg-[#6D2323] w-[35%] border-r border-[#FEF9E1]/20 cursor-pointer hover:bg-black transition-colors p-0">
                                <a href="<?php echo sortLink('title', $sort); ?>" class="flex items-center justify-between w-full h-full p-4 text-[#FEF9E1] outline-none select-none [-webkit-tap-highlight-color:transparent]">
                                    Başlık <?php echo sortIndicator('title', $sort); ?>
                                </a>
                            </th>

                            <th class="bg-[#6D2323] w-[15%] border-r border-[#FEF9E1]/20 hidden md:table-cell cursor-pointer hover:bg-black transition-colors p-0">
                                <a href="<?php echo sortLink('author', $sort); ?>" class="flex items-center justify-between w-full h-full p-4 text-[#FEF9E1] outline-none select-none [-webkit-tap-highlight-color:transparent]">
                                    Yazar <?php echo sortIndicator('author', $sort); ?>
                                </a>
                            </th>

                            <th class="bg-[#6D2323] w-[15%] border-r border-[#FEF9E1]/20 cursor-pointer hover:bg-black transition-colors p-0">
                                <a href="<?php echo sortLink('category', $sort); ?>" class="flex items-center justify-between w-full h-full p-4 text-[#FEF9E1] outline-none select-none [-webkit-tap-highlight-color:transparent]">
                                    Kategori <?php echo sortIndicator('category', $sort); ?>
                                </a>
                            </th>

                            <th class="bg-[#6D2323] w-[7%] border-r border-[#FEF9E1]/20 hidden md:table-cell cursor-pointer hover:bg-black transition-colors p-0">
                                <a href="<?php echo sortLink('reads', $sort); ?>" class="flex items-center justify-between w-full h-full p-4 text-[#FEF9E1] outline-none select-none [-webkit-tap-highlight-color:transparent]">
                                    Okunma <?php echo sortIndicator('reads', $sort); ?>
                                </a>
                            </th>

                            <th class="bg-[#6D2323] w-[18%] p-4 text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($articles)): ?>
                            <?php foreach($articles as $article): ?>
                            <tr class="border-b border-[var(--text-main)]/10 hover:bg-[var(--bg-secondary)]/20 transition-colors h-20">
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 font-bold text-center truncate">
                                    #<?php echo $article['id']; ?>
                                </td>
                                
                                <td class="p-2 border-r border-[var(--text-main)]/10 hidden md:table-cell">
                                    <?php if($article['image_url']): ?>
                                        <div class="w-28 h-16 overflow-hidden border border-[var(--text-main)]">
                                            <img src="<?php echo htmlspecialchars(Upload::assetUrl($article['image_url']), ENT_QUOTES, 'UTF-8'); ?>" 
                                                class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-300 transform hover:scale-105">
                                        </div>
                                    <?php else: ?>
                                        <div class="w-28 h-16 bg-[#6D2323]/10 border border-[var(--text-main)]/20 flex items-center justify-center opacity-50">GÖRSEL YOK</div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 font-bold font-sans text-sm italic group relative">
                                    <div class="truncate w-full absolute inset-0 p-4 flex items-center">
                                        <?php if(($article['status'] ?? 'published') === 'draft'): ?>
                                            <span class="text-[9px] font-bold font-mono bg-yellow-100 text-yellow-800 border border-yellow-500 px-1.5 py-0.5 uppercase mr-1 flex-shrink-0">TASLAK</span>
                                        <?php endif; ?>
                                        <a href="<?php echo articleUrl($article['author_slug'] ?? 'yazar', $article['slug'], $article['lang'] ?? 'TR'); ?>" target="_blank"
                                        class="hover:text-[var(--text-accent)] transition-colors truncate"
                                        title="<?php echo htmlspecialchars($article['title']); ?>">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </div>
                                </td>
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 hidden md:table-cell truncate">
                                    <?php echo htmlspecialchars($article['author_name'] ?: 'yonetim'); ?>
                                </td>
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 uppercase opacity-70 text-[var(--text-accent)] font-bold text-xs truncate">
                                    <?php echo !empty($article['category_names']) ? $article['category_names'] : '-'; ?>
                                </td>

                                <td class="p-2 border-r border-[var(--text-main)]/10 hidden md:table-cell font-mono text-xs text-center">
                                    <div class="inline-flex items-center gap-1 text-[var(--text-accent)] font-bold">
                                        <svg class="w-4 h-4" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M607.5-372.5Q660-425 660-500t-52.5-127.5Q555-680 480-680t-127.5 52.5Q300-575 300-500t52.5 127.5Q405-320 480-320t127.5-52.5Zm-204-51Q372-455 372-500t31.5-76.5Q435-608 480-608t76.5 31.5Q588-545 588-500t-31.5 76.5Q525-392 480-392t-76.5-31.5ZM214-281.5Q94-363 40-500q54-137 174-218.5T480-800q136 0 256 81.5T910-500Q856-363 736-281.5T480-200q-136 0-266-81.5ZM480-500Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z"/></svg>
                                        <span><?php echo number_format($article['reads']); ?></span>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2 flex-col md:flex-row items-center">
                                        <a href="/yonetim/edit?id=<?php echo $article['id']; ?>"
                                        class="w-full md:w-auto px-2 py-1 bg-[#6D2323] text-[#FEF9E1] hover:bg-[var(--bg-paper)] hover:text-[var(--text-main)] border border-[var(--text-main)] text-[10px] font-bold text-center">
                                            DÜZENLE
                                        </a>
                                        <?php if(($article['status'] ?? 'published') === 'draft'): ?>
                                        <form method="POST" action="/yonetim/publish" data-confirm="Bu makaleyi yayınlamak istediğinize emin misiniz?" class="contents">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$article['id']; ?>">
                                            <button type="submit" class="w-full md:w-auto px-2 py-1 bg-yellow-600 text-white hover:bg-yellow-800 text-[10px] font-bold text-center">YAYINLA</button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" action="/yonetim/delete" data-confirm="Bunu silmek istediğinize emin misiniz?" class="contents">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$article['id']; ?>">
                                            <button type="submit" class="w-full md:w-auto px-2 py-1 bg-[var(--text-accent)] text-white hover:bg-black text-[10px] font-bold text-center">SİL</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="p-8 text-center opacity-50 uppercase">// Kayıt bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if($pagination['total'] > 1): ?>
                <div class="bg-[var(--bg-paper)] p-4 border-t border-[var(--text-main)] flex justify-center gap-2">
                    <?php 
                        $qs = $_GET;
                        unset($qs['page']);
                        $baseQuery = http_build_query($qs);
                        $baseUrl = "/yonetim/dashboard?" . ($baseQuery ? $baseQuery . '&' : '');
                    ?>
                    
                    <?php if($pagination['current'] > 1): ?>
                        <a href="<?php echo $baseUrl . 'page=' . ($pagination['current'] - 1); ?>" class="px-3 py-1 border border-[var(--text-main)] text-xs font-bold hover:bg-[#6D2323] hover:text-[#FEF9E1]">←</a>
                    <?php endif; ?>

                    <span class="px-3 py-1 text-xs font-mono font-bold opacity-60">
                        SAYFA <?php echo $pagination['current']; ?> / <?php echo $pagination['total']; ?>
                    </span>

                    <?php if($pagination['current'] < $pagination['total']): ?>
                        <a href="<?php echo $baseUrl . 'page=' . ($pagination['current'] + 1); ?>" class="px-3 py-1 border border-[var(--text-main)] text-xs font-bold hover:bg-[#6D2323] hover:text-[#FEF9E1]">→</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mb-12">
            <h3 class="font-syne text-xl font-bold uppercase mb-6 flex items-center gap-3">
                <span class="w-4 h-4 bg-[#6D2323]"></span>
                ISLEMLER
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <a href="/yonetim/create" class="group border-2 border-[var(--text-main)] hover:bg-[#6D2323] hover:text-[#FEF9E1] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden">
                    <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">01</div>
                    <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">YENİ MAKALE<br>OLUŞTUR</h4>
                    <div class="flex justify-between items-end">
                        <p class="font-mono text-[10px] opacity-70 group-hover:opacity-100">/create-new</p>
                        <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M647-440H160v-80h487L423-744l57-56 320 320-320 320-57-56 224-224Z"/></svg>
                    </div>
                </a>

                <a href="/" target="_blank" class="group border-2 border-[var(--text-main)] hover:bg-[var(--bg-secondary)] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden text-[var(--text-main)]">
                    <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">03</div>
                    <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">SİTEYİ<br>GÖRÜNTÜLE</h4>
                    <div class="flex justify-between items-end">
                        <p class="font-mono text-[10px] opacity-70">/view-front</p>
                        <svg class="w-6 h-6 group-hover:scale-125 transition-transform" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h560v-280h80v280q0 33-23.5 56.5T760-120H200Zm188-212-56-56 372-372H560v-80h280v280h-80v-144L388-332Z"/></svg>
                    </div>
                </a>

                <form method="POST" action="/yonetim/generateSitemap" data-confirm="Site haritasını yeniden oluşturmak istiyor musunuz?" class="contents">
                    <?= Csrf::field() ?>
                    <button type="submit" class="group border-2 border-[var(--text-accent)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden text-[var(--text-main)] text-left w-full">
                        <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">04</div>
                        <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">SİTE HARİTASINI<br>YENİLE</h4>
                        <div class="flex justify-between items-end">
                            <p class="font-mono text-[10px] opacity-70 group-hover:opacity-100">/generateSitemap</p>
                            <svg class="w-6 h-6 group-hover:rotate-180 transition-transform" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M204-318q-22-38-33-78t-11-82q0-134 93-228t227-94h7l-64-64 56-56 160 160-160 160-56-56 64-64h-7q-100 0-170 70.5T240-478q0 26 6 51t18 49l-60 60ZM481-40 321-200l160-160 56 56-64 64h7q100 0 170-70.5T720-482q0-26-6-51t-18-49l60-60q22 38 33 78t11 82q0 134-93 228t-227 94h-7l64 64-56 56Z"/></svg>
                        </div>
                    </button>
                </form>

                <a href="?scan=1" class="group border-2 border-[var(--text-main)] hover:bg-[var(--text-main)] hover:text-[#FEF9E1] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden">
                    <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">06</div>
                    <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">SISTEMI<br>KONTROL ET</h4>
                    <div class="flex justify-between items-end">
                        <p class="font-mono text-[10px] opacity-70 group-hover:opacity-100">?scan=1</p>
                        <svg class="w-6 h-6 group-hover:scale-125 transition-transform" viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm40-82q117-19 198.5-104T820-480H520v318Zm-80 0v-318H140q17 117 98.5 202T440-162Zm-280-398h280v-238Q322-779 240-694t-80 134Zm360 0h280q-22-110-103-195t-177-104v299Z"/></svg>
                    </div>
                </a>

            </div>
        </div>

        <?php if ($showSystemScan): ?>
        <div class="border-2 border-[var(--text-main)] bg-[#1a1a1a] p-4 font-mono text-xs text-[#00ff00] h-96 overflow-hidden relative">
            <div class="absolute top-2 right-2 text-[10px] opacity-50 border border-[#00ff00] px-1">DURUM</div>
            <div id="terminal-content" class="space-y-1 opacity-90"></div>
            <div class="mt-2 animate-pulse">_</div>
        </div>
        <?php endif; ?>

        <footer class="mt-12 pt-6 border-t border-[var(--line-color)] pb-8"></footer>
    
    </div> </div> </main> <?php if ($showSystemScan): ?><script nonce="<?= CSP_NONCE ?>">
    const healthChecks = <?= json_encode($healthChecks ?? [], JSON_UNESCAPED_UNICODE) ?>;
    
    const logs = [
        "Oturum: <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        "Kullanici: <?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'yonetim', ENT_QUOTES, 'UTF-8'); ?> [<?php echo strtoupper($_SESSION['admin_role'] ?? 'admin'); ?>]",
        "=== Sistem Kontrolu Baslatiliyor ===",
        ""
    ];
    
    healthChecks.forEach(function(check) {
        const icon = check.status === 'ok' ? '[✓]' : (check.status === 'warn' ? '[!]' : (check.status === 'fail' ? '[✗]' : '[i]'));
        logs.push(icon + ' ' + check.label + ': ' + check.message);
        if (check.detail) {
            logs.push('     → ' + check.detail);
        }
        logs.push('');
    });
    
    logs.push('=== Kontrol Tamamlandi ===');
    logs.push('_');
    
    const terminal = document.getElementById('terminal-content');
    
    function addLog(line) {
        const p = document.createElement('div');

        if (line.includes('[✓]')) {
            p.style.color = '#00ff00';
        } else if (line.includes('[!]')) {
            p.style.color = '#ffff00';
        } else if (line.includes('[✗]')) {
            p.style.color = '#ff4444';
        } else if (line.includes('[i]')) {
            p.style.color = '#00aaff';
        } else if (line.includes('===')) {
            p.style.color = '#00ff00';
            p.style.fontWeight = 'bold';
        } else if (line.startsWith('     →')) {
            p.style.color = '#aaaaaa';
            p.style.fontSize = '0.85em';
        }

        p.textContent = "> " + line;
        terminal.appendChild(p);
        terminal.parentElement.scrollTop = terminal.parentElement.scrollHeight;
    }
    logs.forEach(addLog);
</script><?php endif; ?>
</body>
</html>
