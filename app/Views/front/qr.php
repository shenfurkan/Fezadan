<?php
$siteBase  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$pageUrl   = articleUrl($article['author_slug'] ?? 'yazar', $article['slug'] ?? '', $article['lang'] ?? 'TR');
$pageTitle = $article['title'] ?? 'Makale';
$safeTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
$safeUrl   = htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $safeTitle; ?> — QR Kod | FEZADAN</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="/assets/css/fonts.css?v=<?= filemtime(ROOT . '/public_html/assets/css/fonts.css') ?>">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #FEF9E1;
            color: #6D2323;
            font-family: 'Space Grotesk', sans-serif;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            gap: 0;
        }

        .card {
            background: #FEF9E1;
            border: 2px solid #E5D0AC;
            max-width: 420px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
        }

        .card-header {
            width: 100%;
            border-bottom: 2px solid #E5D0AC;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #6D2323;
            text-decoration: none;
        }

        .scan-label {
            font-family: 'Syne', sans-serif;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #A31D1D;
            opacity: .7;
        }

        .card-body {
            padding: 2rem 2rem 1.5rem;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .qr-wrap {
            position: relative;
            display: inline-block;
        }

        /* Corner decorators */
        .qr-wrap::before,
        .qr-wrap::after,
        .qr-wrap .corner-bl,
        .qr-wrap .corner-br {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            border-color: #A31D1D;
            border-style: solid;
        }
        .qr-wrap::before  { top: -6px;    left: -6px;  border-width: 3px 0 0 3px; }
        .qr-wrap::after   { top: -6px;    right: -6px; border-width: 3px 3px 0 0; }
        .qr-wrap .corner-bl { bottom: -6px; left: -6px;  border-width: 0 0 3px 3px; }
        .qr-wrap .corner-br { bottom: -6px; right: -6px; border-width: 0 3px 3px 0; }

        #qr-canvas {
            display: block;
            width: 320px;
            height: 320px;
        }

        .article-info {
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .article-title {
            font-family: 'EB Garamond', serif;
            font-weight: 600;
            font-size: 1.05rem;
            line-height: 1.4;
            color: #6D2323;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .article-url {
            font-family: 'JetBrains Mono', monospace;
            font-size: .65rem;
            color: #A31D1D;
            opacity: .65;
            word-break: break-all;
        }

        .card-footer {
            width: 100%;
            border-top: 2px solid #E5D0AC;
            padding: 1rem 1.5rem;
            display: flex;
            gap: .75rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            font-family: 'Syne', sans-serif;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background .15s, color .15s;
        }

        .btn-primary {
            background: #6D2323;
            color: #FEF9E1;
        }
        .btn-primary:hover { background: #A31D1D; }

        .btn-secondary {
            background: transparent;
            color: #6D2323;
            border: 1.5px solid #E5D0AC;
        }
        .btn-secondary:hover { border-color: #6D2323; }

        #hidden-qr { display: none !important; }

        .spinner {
            width: 320px;
            height: 320px;
            box-sizing: content-box;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #A31D1D;
            font-family: 'Syne', sans-serif;
            font-size: .7rem;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <a href="<?php echo $siteBase; ?>" class="brand">Fezadan</a>
            <span class="scan-label">QR ile Tara</span>
        </div>

        <div class="card-body">
            <div class="qr-wrap">
                <div class="corner-bl"></div>
                <div class="corner-br"></div>
                <div class="spinner" id="qr-spinner">Oluşturuluyor…</div>
                <canvas id="qr-canvas" style="display:none;"></canvas>
            </div>

            <div class="article-info">
                <p class="article-title"><?php echo $safeTitle; ?></p>
                <p class="article-url"><?php echo $safeUrl; ?></p>
            </div>
        </div>

        <div class="card-footer">
            <a id="dl-btn" class="btn btn-primary" download="fezadan-qr.png">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12"/><polyline points="8 12 12 16 16 12"/><line x1="4" y1="20" x2="20" y2="20"/></svg>
                PNG İndir
            </a>
            <button id="qr-close-btn" class="btn btn-secondary">Kapat</button>
        </div>
    </div>

    <div id="hidden-qr"></div>

    <script src="/assets/js/qrcode.min.js?v=<?= filemtime(ROOT . '/public_html/assets/js/qrcode.min.js') ?>"></script>
    <script nonce="<?= CSP_NONCE ?>">
    (function () {
        var url       = <?php echo json_encode($pageUrl); ?>;
        var QR_SIZE   = 640;
        var LOGO_FRAC = 0.22;      // logo alanı QR boyutunun %22'si
        var LOGO_PAD  = 10;        // logo etrafında beyaz boşluk (px)

        var spinner   = document.getElementById('qr-spinner');
        var finalCanvas = document.getElementById('qr-canvas');
        var dlBtn     = document.getElementById('dl-btn');

        /* 1. Geçici div'e QR çiz */
        var tempDiv = document.getElementById('hidden-qr');
        var qrObj = new QRCode(tempDiv, {
            text:          url,
            width:         QR_SIZE,
            height:        QR_SIZE,
            colorDark:     '#6D2323',
            colorLight:    '#FEF9E1',
            correctLevel:  QRCode.CorrectLevel.H
        });

        /* 2. qrcodejs canvas'ını synchronous draw() sonrası yakala */
        var srcCanvas = tempDiv.querySelector('canvas');

        if (!srcCanvas) {
            spinner.textContent = 'QR oluşturulamadı.';
            return;
        }

        /* 3. Composite canvas'a QR kopyala */
        finalCanvas.width  = QR_SIZE;
        finalCanvas.height = QR_SIZE;
        var ctx = finalCanvas.getContext('2d');
        ctx.drawImage(srcCanvas, 0, 0);

        /* 4. Logo overlay */
        var logoSize = Math.round(QR_SIZE * LOGO_FRAC);
        var lx = Math.round((QR_SIZE - logoSize) / 2);
        var ly = Math.round((QR_SIZE - logoSize) / 2);

        /* beyaz arka plan dikdörtgeni */
        ctx.fillStyle = '#FEF9E1';
        ctx.fillRect(lx - LOGO_PAD, ly - LOGO_PAD,
                     logoSize + LOGO_PAD * 2,
                     logoSize + LOGO_PAD * 2);

        var logo = new Image();
        logo.crossOrigin = 'anonymous';

        logo.onload = function () {
            /* Aspect-ratio korunarak logoSize kutusuna sığdır */
            var nw = logo.naturalWidth  || logo.width  || logoSize;
            var nh = logo.naturalHeight || logo.height || logoSize;
            var scale = Math.min(logoSize / nw, logoSize / nh);
            var dw = Math.round(nw * scale);
            var dh = Math.round(nh * scale);
            var dx = Math.round((QR_SIZE - dw) / 2);
            var dy = Math.round((QR_SIZE - dh) / 2);
            ctx.drawImage(logo, dx, dy, dw, dh);
            showResult();
        };

        logo.onerror = function () {
            /* Logo yüklenemezse "FEZADAN" yazısı yaz */
            ctx.fillStyle = '#FEF9E1';
            ctx.fillRect(lx - LOGO_PAD, ly - LOGO_PAD,
                         logoSize + LOGO_PAD * 2,
                         logoSize + LOGO_PAD * 2);
            ctx.fillStyle = '#6D2323';
            ctx.font = 'bold ' + Math.round(logoSize * 0.28) + 'px Syne, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('FEZADAN', QR_SIZE / 2, QR_SIZE / 2);
            showResult();
        };

        logo.src = '/cdn/logo-light.png';

        function showResult() {
            spinner.style.display = 'none';
            finalCanvas.style.display = 'block';

            /* Download bağlantısı */
            try {
                dlBtn.href = finalCanvas.toDataURL('image/png');
            } catch (e) {
                dlBtn.textContent = 'Sağ Tık → Kaydet';
                dlBtn.removeAttribute('download');
                dlBtn.href = '#';
            }
        }
    })();
    </script>
    <script nonce="<?= CSP_NONCE ?>">
document.getElementById('qr-close-btn').addEventListener('click',function(){window.close()});
</script>
</body>
</html>
