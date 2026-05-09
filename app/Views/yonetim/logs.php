<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | HATA KAYITLARI</title>
    <link rel="stylesheet" href="/assets/css/yonetim.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        :root { --bg-paper:#FEF9E1; --bg-secondary:#E5D0AC; --text-main:#6D2323; --text-accent:#A31D1D; }
        body { background:var(--bg-paper); color:var(--text-main); font-family:'Space Grotesk',sans-serif; }
        .font-syne { font-family:'Syne',sans-serif; }
        .font-mono { font-family:'JetBrains Mono',monospace; }
        .log-card { border:2px solid var(--text-main); background:rgba(254,249,225,.92); }
        .level-error { background:var(--text-accent); color:var(--bg-paper); }
        .level-warning { background:#B7791F; color:var(--bg-paper); }
        .level-info { background:var(--text-main); color:var(--bg-paper); }
        .log-input { border:2px solid var(--text-main); background:var(--bg-paper); color:var(--text-main); padding:.75rem; font-family:'JetBrains Mono',monospace; font-size:.75rem; width:100%; }
        .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:.5rem; margin:.75rem 0; }
        .meta-pill { border:1px solid var(--text-main); padding:.55rem .7rem; font-family:'JetBrains Mono',monospace; font-size:.72rem; overflow-wrap:anywhere; background:rgba(229,208,172,.35); }
    </style>
</head>
<body class="flex h-screen w-full overflow-hidden">
    <?php require_once __DIR__ . '/_side_panel.php'; ?>
    <main class="flex-1 overflow-y-auto p-6 md:p-10">
        <?php
            $filters = $filters ?? [];
            $activeLevel = strtoupper((string)($filters['level'] ?? ''));
            $levelLabels = ['ERROR' => 'HATA', 'WARNING' => 'UYARI', 'INFO' => 'BİLGİ'];
        ?>
        <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <p class="font-mono text-xs uppercase tracking-widest opacity-70">Admin Panel</p>
                <h1 class="font-syne text-3xl md:text-5xl font-bold uppercase">Hata Kayıtları</h1>
            </div>
            <a href="/yonetim/logs" class="border-2 border-[var(--text-main)] px-4 py-3 font-bold uppercase text-xs hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)]">Yenile</a>
        </header>

        <form method="GET" action="/yonetim/logs" class="log-card p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <label class="font-mono text-xs uppercase font-bold">
                Seviye
                <select name="level" class="log-input mt-2">
                    <option value="">Tümü</option>
                    <?php foreach ($levelLabels as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $activeLevel === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="font-mono text-xs uppercase font-bold">
                Endpoint
                <input class="log-input mt-2" name="endpoint" value="<?php echo htmlspecialchars((string)($filters['endpoint'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="uploadContentImage">
            </label>
            <label class="font-mono text-xs uppercase font-bold md:col-span-1">
                Ara
                <input class="log-input mt-2" name="q" value="<?php echo htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="request_id, dosya, import">
            </label>
            <button class="border-2 border-[var(--text-main)] bg-[var(--text-main)] text-[var(--bg-paper)] px-4 py-3 font-bold uppercase text-xs hover:bg-transparent hover:text-[var(--text-main)]">Filtrele</button>
        </form>

        <?php if (empty($logs)): ?>
            <section class="log-card p-8">
                <h2 class="font-syne text-2xl font-bold uppercase mb-2">Kayıt yok</h2>
                <p class="font-mono text-sm opacity-80">Seçili filtrelere göre admin kaydı bulunmuyor.</p>
            </section>
        <?php else: ?>
            <section class="space-y-4">
                <?php foreach ($logs as $row): ?>
                    <?php
                        $level = strtoupper((string)($row['level'] ?? 'INFO'));
                        $levelClass = $level === 'ERROR' ? 'level-error' : ($level === 'WARNING' ? 'level-warning' : 'level-info');
                        $context = $row['context'] ?? [];
                        $metaItems = [
                            'Hata kodu' => $context['request_id'] ?? '',
                            'Endpoint' => $context['endpoint'] ?? '',
                            'Import' => $context['import_id'] ?? '',
                            'Görsel sıra' => isset($context['image_index']) ? (string)$context['image_index'] : '',
                            'Dosya' => $context['name'] ?? ($context['client_name'] ?? ''),
                            'Boyut' => isset($context['size']) ? number_format((int)$context['size'] / 1024, 1, ',', '.') . ' KB' : '',
                            'Süre' => isset($context['total_ms']) ? (int)$context['total_ms'] . ' ms' : '',
                            'Yol' => $context['path'] ?? '',
                        ];
                    ?>
                    <article class="log-card p-5">
                        <div class="flex flex-col md:flex-row md:items-center gap-3 md:justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <span class="<?php echo $levelClass; ?> font-mono text-xs font-bold px-3 py-1"><?php echo htmlspecialchars($levelLabels[$level] ?? $level, ENT_QUOTES, 'UTF-8'); ?></span>
                                <time class="font-mono text-xs opacity-70"><?php echo htmlspecialchars((string)($row['time'] ?? '')); ?></time>
                            </div>
                            <div class="font-mono text-xs opacity-70">
                                <?php echo htmlspecialchars((string)($row['user'] ?? '')); ?>
                                <?php if (!empty($row['ip'])): ?> / <?php echo htmlspecialchars((string)$row['ip']); ?><?php endif; ?>
                            </div>
                        </div>
                        <p class="font-bold text-lg mb-3"><?php echo htmlspecialchars((string)($row['message'] ?? '')); ?></p>
                        <?php if (!empty($row['uri'])): ?>
                            <p class="font-mono text-xs mb-3 opacity-80"><?php echo htmlspecialchars((string)$row['uri']); ?></p>
                        <?php endif; ?>
                        <div class="meta-grid">
                            <?php foreach ($metaItems as $label => $value): ?>
                                <?php if ($value !== '' && $value !== null): ?>
                                    <div class="meta-pill"><strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($context['visibility_warning'])): ?>
                            <p class="font-mono text-xs p-3 mb-3 border border-[#B7791F] bg-[#FFF4BF] text-[#6D2323]">
                                <?php echo htmlspecialchars((string)$context['visibility_warning'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($context)): ?>
                            <pre class="font-mono text-xs overflow-x-auto bg-[#1a1a1a] text-[#FEF9E1] p-4"><?php echo htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
    </div>
</body>
</html>
