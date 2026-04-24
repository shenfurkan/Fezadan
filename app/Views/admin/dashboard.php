<?php
// Sorting link
function sortLink($col, $currentSort) {
    $params = $_GET;
    unset($params['page']);
    
    // Sorting order
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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | KONTROL MERKEZİ</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="icon" type="image/jpeg" href="<?php echo SITE_URL ?? ''; ?>/assets/uploads/logo.jpg">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">

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
    
    <?php include __DIR__ . '/_side_panel.php'; ?>

    <div class="scroll-smooth h-full"> <?php if (isset($_GET['status'])): ?>
        <div class="mb-8 p-6 bg-[#6D2323] text-[#FEF9E1] border-2 border-[var(--text-main)] shadow-[8px_8px_0px_#A31D1D] flex items-center justify-between">
            <div>
                <h3 class="font-syne text-xl font-bold uppercase">
                    <?php 
                        if($_GET['status'] == 'deleted') echo '🗑️ MAKALE SİLİNDİ';
                        elseif($_GET['status'] == 'patch_deleted') echo '🗑️ YAMA NOTU SİLİNDİ';
                        elseif($_GET['status'] == 'patch_added') echo '✅ YAMA SİSTEME İŞLENDİ';
                        elseif($_GET['status'] == 'draft_saved') echo '📝 TASLAK KAYDEDİLDİ';
                        elseif($_GET['status'] == 'published') echo '🚀 MAKALE YAYINLANDI';
                        else echo '✅ İŞLEM BAŞARILI';
                    ?>
                </h3>
                <p class="font-mono text-xs opacity-80 mt-1">Sistem kayıtları başarıyla güncellendi.</p>
            </div>
            <a href="/admin/dashboard" class="px-4 py-2 border border-[#FEF9E1] hover:bg-[var(--bg-paper)] hover:text-[var(--text-main)] font-mono text-xs uppercase transition-colors">TAMAM</a>
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
                    </div>
                </div>

                <div class="relative z-10">
                    <h3 class="font-mono text-[10px] text-[var(--text-accent)] mb-1 uppercase tracking-widest">
                        Toplam Okunma
                    </h3>
                    
                    <div class="font-syne text-4xl font-bold text-[var(--text-main)] flex items-center gap-3">
                        
                        <span class="text-3xl opacity-85 text-[var(--text-accent)] relative -top-[-5px]">👁</span>
                        
                        <?php echo number_format($stats['total_reads'] ?? 0); ?>
                        
                    </div>
                </div>
            </div>

            <div class="brutalist-card bg-[#6D2323] text-[#FEF9E1] p-6 relative group border-[var(--text-main)]">
                <h3 class="font-mono text-xs text-[#E5D0AC] mb-2 uppercase tracking-widest">Sunucu Durumu</h3>
                <div class="font-syne text-4xl font-bold mb-2">STABIL</div>
                <p class="text-xs opacity-80 font-mono leading-relaxed">CPU: %12 | RAM: 2.4GB | PING: 24ms</p>
                <div class="absolute bottom-4 right-4 w-3 h-3 bg-[#00ff00] rounded-full blink"></div>
            </div>

            <div class="brutalist-card bg-[var(--bg-paper)] p-6 relative group">
                <h3 class="font-mono text-xs text-[var(--text-accent)] mb-2 uppercase tracking-widest">Son Erişim</h3>
                <div class="font-syne text-2xl font-bold text-[var(--text-main)] mb-2"><?php echo $stats['last_login'] ?? date('H:i'); ?></div>
                <p class="text-xs opacity-60 font-mono">USER: <?php echo $_SESSION['admin_user'] ?? 'ADMIN'; ?></p>
            </div>
        </div>

        <div class="mb-12">
            <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
                <h3 class="font-syne text-xl font-bold uppercase flex items-center gap-3 text-[var(--text-main)]">
                    <span class="w-4 h-4 bg-[#6D2323]"></span>
                    VERİ HAVUZU
                </h3>
                
                <form action="/admin/dashboard" method="GET" class="flex w-full md:w-auto">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($pagination['search'] ?? ''); ?>" 
                           placeholder="Makale ara... veya id=5" 
                           class="bg-transparent border-2 border-[var(--text-main)] px-4 py-2 font-mono text-xs outline-none placeholder-[#6D2323]/50 w-full md:w-64">
                    <button type="submit" class="bg-[#6D2323] text-[#FEF9E1] px-4 py-2 font-bold uppercase text-xs hover:bg-[var(--text-accent)]">ARA</button>
                    <?php if(!empty($pagination['search'])): ?>
                        <a href="/admin/dashboard" class="bg-[var(--text-accent)] text-white px-3 py-2 font-bold text-xs flex items-center justify-center">X</a>
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
                                            <img src="<?php echo $article['image_url']; ?>" 
                                                class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-300 transform hover:scale-105">
                                        </div>
                                    <?php else: ?>
                                        <div class="w-28 h-16 bg-[#6D2323]/10 border border-[var(--text-main)]/20 flex items-center justify-center opacity-50">NO IMG</div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 font-bold font-sans text-sm italic group relative">
                                    <div class="truncate w-full absolute inset-0 p-4 flex items-center">
                                        <?php if(($article['status'] ?? 'published') === 'draft'): ?>
                                            <span class="text-[9px] font-bold font-mono bg-yellow-100 text-yellow-800 border border-yellow-500 px-1.5 py-0.5 uppercase mr-1 flex-shrink-0">TASLAK</span>
                                        <?php endif; ?>
                                        <a href="/makale/<?php echo $article['slug']; ?>" target="_blank"
                                        class="hover:text-[var(--text-accent)] transition-colors truncate"
                                        title="<?php echo htmlspecialchars($article['title']); ?>">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </div>
                                </td>
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 hidden md:table-cell truncate">
                                    <?php echo htmlspecialchars($article['author_name'] ?: 'Admin'); ?>
                                </td>
                                
                                <td class="p-4 border-r border-[var(--text-main)]/10 uppercase opacity-70 text-[var(--text-accent)] font-bold text-xs truncate">
                                    <?php echo !empty($article['category_names']) ? $article['category_names'] : '-'; ?>
                                </td>

                                <td class="p-2 border-r border-[var(--text-main)]/10 hidden md:table-cell font-mono text-xs text-center">
                                    <div class="inline-flex items-center gap-1 text-[var(--text-accent)] font-bold">
                                        <span class="text-sm">👁</span>
                                        <span><?php echo number_format($article['reads']); ?></span>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2 flex-col md:flex-row items-center">
                                        <a href="/admin/edit?id=<?php echo $article['id']; ?>"
                                        class="w-full md:w-auto px-2 py-1 bg-[#6D2323] text-[#FEF9E1] hover:bg-[var(--bg-paper)] hover:text-[var(--text-main)] border border-[var(--text-main)] text-[10px] font-bold text-center">
                                            DÜZENLE
                                        </a>
                                        <?php if(($article['status'] ?? 'published') === 'draft'): ?>
                                        <a href="/admin/publish?id=<?php echo $article['id']; ?>"
                                        onclick="return confirm('Makaleyi yayınlamak istiyor musun?');"
                                        class="w-full md:w-auto px-2 py-1 bg-yellow-600 text-white hover:bg-yellow-800 text-[10px] font-bold text-center">
                                            YAYINLA
                                        </a>
                                        <?php endif; ?>
                                        <a href="/admin/delete?id=<?php echo $article['id']; ?>"
                                        onclick="return confirm('Silmek istediğine emin misin?');"
                                        class="w-full md:w-auto px-2 py-1 bg-[var(--text-accent)] text-white hover:bg-black text-[10px] font-bold text-center">
                                            SİL
                                        </a>
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
                        $baseUrl = "/admin/dashboard?" . ($baseQuery ? $baseQuery . '&' : '');
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
            <div class="flex justify-between items-end mb-6">
                <h3 class="font-syne text-xl font-bold uppercase flex items-center gap-3 text-[var(--text-main)]">
                    <span class="w-4 h-4 bg-[#6D2323]"></span>
                    SİSTEM GÜNCELLEMELERİ (v<?php echo $patches[0]['version'] ?? '1.0'; ?>)
                </h3>
                <div class="text-xs font-mono text-[var(--text-accent)] opacity-70 hidden md:block">
                    // LATEST_PATCH: <?php echo $patches[0]['created_at'] ?? date('Y-m-d'); ?>
                </div>
            </div>

            <div class="border-2 border-[var(--text-main)] bg-[var(--bg-paper)] p-1 shadow-[8px_8px_0px_rgba(109,35,35,0.1)]">
                <div class="bg-[var(--bg-secondary)]/20 p-6 max-h-80 overflow-y-auto custom-scrollbar">
                    
                    <?php if(!empty($patches)): ?>
                        <?php foreach($patches as $index => $patch): ?>
                        <div class="mb-6 last:mb-0 relative pl-6 border-l-2 <?php echo $index === 0 ? 'border-[var(--text-accent)]' : 'border-[var(--text-main)]/30'; ?>">
                            <div class="absolute -left-[5px] top-0 w-2 h-2 rounded-full <?php echo $index === 0 ? 'bg-[var(--text-accent)] blink' : 'bg-[#6D2323]/30'; ?>"></div>
                            
                            <div class="flex justify-between items-start mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-[var(--text-accent)] bg-[var(--text-accent)]/10 px-2 py-1">
                                        v<?php echo htmlspecialchars($patch['version']); ?>
                                    </span>
                                    
                                    <a href="/admin/patch-delete?id=<?php echo $patch['id']; ?>" 
                                    onclick="return confirm('Bu yama notunu silmek istediğinize emin misiniz?');"
                                    class="text-[10px] text-[var(--text-accent)] hover:text-black font-bold transition-colors">
                                        [SİL]
                                    </a>
                                </div>
                                <span class="font-mono text-[10px] opacity-60">
                                    <?php echo date('d.m.Y H:i', strtotime($patch['created_at'])); ?>
                                </span>
                            </div>
                            
                            <h4 class="font-bold text-[var(--text-main)] text-sm mb-2 uppercase tracking-wide">
                                <?php echo htmlspecialchars($patch['title']); ?>
                            </h4>
                            
                            <p class="font-mono text-xs opacity-80 leading-relaxed whitespace-pre-line text-[var(--text-main)]">
                                <?php echo htmlspecialchars($patch['content']); ?>
                            </p>
                            
                            <div class="mt-2 text-[9px] font-mono uppercase opacity-50 text-right">
                                > Patch by: <?php echo htmlspecialchars($patch['author']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 opacity-50 font-mono text-xs">
                            <p>// HENÜZ SİSTEM KAYDI BULUNAMADI</p>
                            <p class="mt-2">Veritabanı bağlantısını kontrol edin veya ilk yamayı oluşturun.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <div class="mb-12">
            <h3 class="font-syne text-xl font-bold uppercase mb-6 flex items-center gap-3">
                <span class="w-4 h-4 bg-[#6D2323]"></span>
                KOMUT MERKEZİ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <a href="/admin/create" class="group border-2 border-[var(--text-main)] hover:bg-[#6D2323] hover:text-[#FEF9E1] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden">
                    <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">01</div>
                    <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">YENİ DOSYA<br>OLUŞTUR</h4>
                    <div class="flex justify-between items-end">
                        <p class="font-mono text-[10px] opacity-70 group-hover:opacity-100">/create-new</p>
                        <span class="font-syne text-2xl group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <a href="/admin/create-patch" class="group border-2 border-[var(--text-main)] hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] hover:border-[var(--text-accent)] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden">
                    <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">02</div>
                    <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">YAMA NOTU<br>GİRİŞİ</h4>
                    <div class="flex justify-between items-end">
                        <p class="font-mono text-[10px] opacity-70 group-hover:opacity-100">/add-patch</p>
                        <span class="font-syne text-2xl group-hover:rotate-90 transition-transform">+</span>
                    </div>
                </a>

                <a href="/" target="_blank" class="group border-2 border-[var(--text-main)] hover:bg-[var(--bg-secondary)] transition-all p-6 flex flex-col justify-between cursor-pointer h-32 relative overflow-hidden text-[var(--text-main)]">
                    <div class="absolute top-2 right-2 opacity-20 font-syne text-4xl group-hover:opacity-10 font-bold">03</div>
                    <h4 class="font-syne text-lg font-bold uppercase mb-1 leading-tight">SİTEYİ<br>GÖRÜNTÜLE</h4>
                    <div class="flex justify-between items-end">
                        <p class="font-mono text-[10px] opacity-70">/view-front</p>
                        <span class="font-syne text-2xl group-hover:scale-125 transition-transform">↗</span>
                    </div>
                </a>

            </div>
        </div>

        <div class="border-2 border-[var(--text-main)] bg-[#1a1a1a] p-4 font-mono text-xs text-[#00ff00] h-48 overflow-hidden relative">
            <div class="absolute top-2 right-2 text-[10px] opacity-50 border border-[#00ff00] px-1">SYSTEM_LOG</div>
            <div id="terminal-content" class="space-y-1 opacity-90"></div>
            <div class="mt-2 animate-pulse">_</div>
        </div>

        <footer class="mt-12 pt-6 border-t border-[var(--line-color)] text-center md:text-left flex justify-between items-center opacity-60 pb-8">
            <p class="text-[10px] uppercase font-bold tracking-widest">FEZADAN COLLECTIVE © 2026</p>
            <p class="text-[10px] font-mono hidden md:block">SECURE CONNECTION ESTABLISHED</p>
        </footer>
    
    </div> </div> </main> <script>
    const logs = [
        "Bağlantı kuruldu: <?php echo $_SERVER['REMOTE_ADDR']; ?>",
        "Veritabanı bağlantısı: BAŞARILI",
        "Kullanıcı yetkileri doğrulandı: ADMIN",
        "Veri havuzu senkronize ediliyor...",
        "Güvenlik protokolleri aktif.",
        "Son güncelleme: <?php echo date('H:i'); ?>",
        "Sistem stabil."
    ];
    const terminal = document.getElementById('terminal-content');
    let logIndex = 0;
    function addLog() {
        if (logIndex < logs.length) {
            const p = document.createElement('div');
            p.textContent = "> " + logs[logIndex];
            terminal.appendChild(p);
            terminal.parentElement.scrollTop = terminal.parentElement.scrollHeight;
            logIndex++; setTimeout(addLog, Math.random() * 800 + 200);
        }
    }
    setTimeout(addLog, 500);
</script>
</body>
</html>