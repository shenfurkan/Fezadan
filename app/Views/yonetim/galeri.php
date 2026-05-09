<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | GALERİ YÖNETİMİ</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="stylesheet" href="/assets/css/yonetim.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">

    <style>
        :root {
            --bg-paper: #FEF9E1;
            --bg-secondary: #E5D0AC;
            --text-main: #6D2323;
            --text-accent: #A31D1D;
            --line-color: #6D2323;
        }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; overflow-x: hidden; }
        .font-syne { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-paper); border-left: 1px solid var(--line-color); }
        ::-webkit-scrollbar-thumb { background: var(--line-color); }
        .grid-bg { background-image: linear-gradient(var(--line-color) 1px, transparent 1px), linear-gradient(90deg, var(--line-color) 1px, transparent 1px); background-size: 40px 40px; opacity: 0.05; pointer-events: none; }
        .blink { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0; } }
        .nav-item { position: relative; transition: all 0.3s; z-index: 1; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0; background: var(--text-main); transition: width 0.3s; z-index: -1; }
        .nav-item:hover::before { width: 100%; }
        .nav-item:hover { color: var(--bg-paper); padding-left: 1.5rem; }
        .nav-item.active { background: var(--text-main); color: var(--bg-paper); }
        .scanline { width: 100%; height: 100px; z-index: 9999; background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(109,35,35,0.1) 50%, rgba(0,0,0,0) 100%); opacity: 0.1; position: absolute; bottom: 100%; animation: scanline 10s linear infinite; pointer-events: none; }
        @keyframes scanline { 0% { bottom: 100%; } 100% { bottom: -100%; } }
        .brutalist-card { transition: all 0.2s; box-shadow: 6px 6px 0px var(--line-color); border: 2px solid var(--line-color); }

        /* Modal */
        .art-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 1rem; }
        .art-modal.open { display: flex; }
        .art-modal-inner { background: var(--bg-paper); border: 2px solid var(--text-main); box-shadow: 8px 8px 0 var(--text-main); width: 100%; max-width: 620px; max-height: 92vh; overflow-y: auto; padding: 2rem; }

        /* Table */
        .art-table { width: 100%; border-collapse: collapse; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; }
        .art-table th { background: var(--text-main); color: var(--bg-paper); padding: 0.6rem 0.8rem; text-align: left; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .art-table td { padding: 0.6rem 0.8rem; border-bottom: 1px solid rgba(109,35,35,0.12); vertical-align: middle; }
        .art-table tr:hover td { background: rgba(109,35,35,0.04); }

        textarea.field { width: 100%; border: 2px solid var(--text-main); background: transparent; padding: 0.6rem; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; outline: none; resize: vertical; color: var(--text-main); }
        textarea.field:focus { border-color: var(--text-accent); }
        .btn-primary { background: var(--text-main); color: var(--bg-paper); border: 2px solid var(--text-main); padding: 0.5rem 1.2rem; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { background: var(--text-accent); border-color: var(--text-accent); }
        .btn-ghost { background: transparent; color: var(--text-main); border: 2px solid var(--text-main); padding: 0.5rem 1.2rem; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: all 0.2s; }
        .btn-ghost:hover { background: var(--bg-secondary); }
        .badge { display: inline-block; font-size: 0.6rem; font-family: 'JetBrains Mono', monospace; padding: 2px 6px; border: 1px solid; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-ok { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }
        .badge-warn { border-color: #ca8a04; color: #92400e; background: #fffbeb; }
        .badge-auto { border-color: rgba(109,35,35,0.4); color: var(--text-main); background: var(--bg-secondary); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden relative">
    <div class="grid-bg fixed inset-0 z-0"></div>
    <div class="scanline fixed"></div>

    <?php include __DIR__ . '/_side_panel.php'; ?>

    <main class="flex-1 flex flex-col relative z-10 overflow-hidden h-screen">
        <!-- Header is already rendered by _side_panel.php -->

        <div class="flex-1 overflow-y-auto p-6 md:p-12 relative">

            <!-- Bildirimler -->
            <?php if (isset($_GET['status'])): ?>
            <div style="background:var(--text-main);color:var(--bg-paper);" class="mb-6 p-4 border-2 border-[var(--text-main)] flex items-center justify-between">
                <span class="font-mono text-sm font-bold">
                    <?php
                        if ($_GET['status'] === 'refreshed') echo '✓ Günün sanat eseri başarıyla yenilendi.';
                        elseif ($_GET['status'] === 'updated') echo '✓ Eser açıklaması güncellendi.';
                        else echo '✓ İşlem başarılı.';
                    ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div style="background:#A31D1D;color:#FEF9E1;" class="mb-6 p-4 border-2 flex items-center justify-between">
                <span class="font-mono text-sm font-bold">
                    <?php
                        if ($_GET['error'] === 'refresh_failed') echo '✗ Eser yenilenirken hata oluştu. API kotası dolmuş olabilir — Admin Logs kontrol edin.';
                        elseif ($_GET['error'] === 'update_failed') echo '✗ Açıklama kaydedilemedi. Admin Logs kontrol edin.';
                        elseif ($_GET['error'] === 'invalid_id') echo '✗ Geçersiz eser ID.';
                        else echo '✗ Bir hata oluştu.';
                    ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Günün Eseri -->
            <div class="mb-10">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-syne text-2xl font-bold uppercase text-[var(--text-main)] flex items-center gap-3">
                        <span class="w-4 h-4 bg-[var(--text-main)] inline-block"></span>
                        Günün Eseri — <?= htmlspecialchars(date('d.m.Y', strtotime($today ?? date('Y-m-d')))) ?>
                    </h2>
                    <form action="/yonetim/refreshDailyArt" method="POST"
                          onsubmit="return confirm('Mevcut eseri silip API\'den yenisini çekmek istiyor musun?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-primary">⟳ Yenile</button>
                    </form>
                </div>

                <?php if ($todayArt): ?>
                <div class="brutalist-card bg-[var(--bg-paper)] p-6 flex gap-6 flex-wrap">
                    <div style="flex:0 0 160px;">
                        <img src="<?= htmlspecialchars($todayArt['thumbnail_url'] ?: $todayArt['image_url']) ?>"
                             style="width:100%;height:auto;border:2px solid var(--text-main);"
                             alt="Artwork"
                             onerror="this.onerror=null;this.src='/cdn/notlar-social-preview.png'"
                             referrerpolicy="no-referrer">
                    </div>
                    <div style="flex:1;min-width:260px;">
                        <h3 class="font-syne text-xl font-bold text-[var(--text-main)] mb-1"><?= htmlspecialchars($todayArt['title']) ?></h3>
                        <p class="font-mono text-sm opacity-70 mb-3"><?= htmlspecialchars($todayArt['artist']) ?></p>
                        <div class="font-mono text-xs mb-4 space-y-1">
                            <div><span class="opacity-50">MÜZE:</span> <?= htmlspecialchars($todayArt['provider']) ?></div>
                            <div><span class="opacity-50">KAYNAK:</span>
                                <span class="badge badge-auto"><?= htmlspecialchars($todayArt['description_source']) ?></span>
                            </div>
                        </div>
                        <?php if (!empty($todayArt['description_tr'])): ?>
                        <p class="text-sm leading-relaxed opacity-80 mb-4 border-l-2 border-[var(--text-accent)] pl-3">
                            <?= htmlspecialchars(mb_substr($todayArt['description_tr'], 0, 200)) ?>…
                        </p>
                        <?php endif; ?>
                        <button onclick="openModal('modal-<?= (int)$todayArt['id'] ?>')" class="btn-primary">
                            ✎ Açıklamayı Düzenle
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="brutalist-card bg-[var(--bg-paper)] p-8 text-center">
                    <p class="font-mono text-sm opacity-60">
                        Bugün için henüz eser çekilmedi.<br>
                        Siteye ziyaretçi girdiğinde otomatik çekilir veya "Yenile" butonunu kullanabilirsin.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Arşiv Tablosu -->
            <div>
                <h2 class="font-syne text-2xl font-bold uppercase text-[var(--text-main)] flex items-center gap-3 mb-6">
                    <span class="w-4 h-4 bg-[var(--text-main)] inline-block"></span>
                    Geçmiş Arşiv
                    <span class="font-mono text-sm font-normal opacity-50">(Son <?= count($allArtworks) ?> kayıt)</span>
                </h2>

                <div class="border-2 border-[var(--text-main)] overflow-x-auto" style="box-shadow:6px 6px 0 var(--line-color);">
                    <table class="art-table">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Resim</th>
                                <th>Eser Adı</th>
                                <th>Sanatçı</th>
                                <th>Kaynak</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($allArtworks)): ?>
                                <?php foreach ($allArtworks as $art): ?>
                                <tr>
                                    <td style="white-space:nowrap;font-weight:bold;">
                                        <?= date('d.m.Y', strtotime($art['date'])) ?>
                                    </td>
                                    <td>
                                        <img src="<?= htmlspecialchars($art['thumbnail_url'] ?: $art['image_url']) ?>"
                                             style="height:38px;width:auto;border:1px solid var(--text-main);display:block;"
                                             alt=""
                                             onerror="this.onerror=null;this.src='/cdn/notlar-social-preview.png'"
                                             referrerpolicy="no-referrer">
                                    </td>
                                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($art['title']) ?>">
                                        <?= htmlspecialchars($art['title']) ?>
                                    </td>
                                    <td style="opacity:0.75;"><?= htmlspecialchars($art['artist']) ?></td>
                                    <td>
                                        <?php
                                            $src = $art['description_source'] ?? '';
                                            $cls = match(true) {
                                                $src === 'manual'  => 'badge-ok',
                                                $src === 'gemini'  => 'badge-warn',
                                                default            => 'badge-auto',
                                            };
                                        ?>
                                        <span class="badge <?= $cls ?>"><?= htmlspecialchars($src) ?></span>
                                    </td>
                                    <td>
                                        <button onclick="openModal('modal-<?= (int)$art['id'] ?>')" class="btn-ghost" style="padding:3px 10px;font-size:0.65rem;">
                                            Düzenle
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center;padding:2rem;opacity:0.5;">Henüz kayıt yok.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /flex-1 -->
    </main>

    <!-- Modaller (tüm eserler için) -->
    <?php if ($todayArt): ?>
    <?php $allForModal = array_merge([$todayArt], array_filter($allArtworks, fn($a) => $a['id'] !== $todayArt['id'])); ?>
    <?php else: ?>
    <?php $allForModal = $allArtworks; ?>
    <?php endif; ?>

    <?php foreach ($allForModal as $art): ?>
    <div id="modal-<?= (int)$art['id'] ?>" class="art-modal" onclick="if(event.target===this)closeModal('modal-<?= (int)$art['id'] ?>')">
        <div class="art-modal-inner">
            <h3 class="font-syne text-lg font-bold text-[var(--text-main)] mb-4 uppercase">
                Açıklamayı Düzenle
            </h3>
            <p class="font-mono text-xs opacity-60 mb-4"><?= htmlspecialchars($art['title']) ?> — <?= htmlspecialchars($art['artist']) ?></p>

            <?php if (!empty($art['description_en'])): ?>
            <div class="mb-4">
                <label class="font-mono text-xs uppercase opacity-60 block mb-1">Orijinal (İngilizce)</label>
                <textarea class="field" rows="4" readonly style="opacity:0.6;"><?= htmlspecialchars($art['description_en']) ?></textarea>
            </div>
            <?php endif; ?>

            <form action="/yonetim/updateArtDescription" method="POST">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$art['id'] ?>">
                <div class="mb-5">
                    <label class="font-mono text-xs uppercase opacity-60 block mb-1">Türkçe Açıklama (Sitede Görünür)</label>
                    <textarea name="description_tr" class="field" rows="7" required><?= htmlspecialchars($art['description_tr'] ?? '') ?></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                    <button type="button" class="btn-ghost" onclick="closeModal('modal-<?= (int)$art['id'] ?>')">İptal</button>
                    <button type="submit" class="btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <script>
        function openModal(id) {
            var m = document.getElementById(id);
            if (m) m.classList.add('open');
        }
        function closeModal(id) {
            var m = document.getElementById(id);
            if (m) m.classList.remove('open');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.art-modal.open').forEach(function(m) {
                    m.classList.remove('open');
                });
            }
        });
    </script>
</body>
</html>
