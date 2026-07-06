<?php
$lang = App::getLang();
$isEn = ($lang === 'EN');

$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$page_title       = $isEn ? 'Support FEZADAN — Donate Monero' : 'Destek Ol — Monero Bağışı | FEZADAN';
$page_description = $isEn 
    ? 'Support FEZADAN — Support independent science, aesthetics and critical thought by donating Monero (XMR).'
    : 'FEZADAN\'a destek olun — Monero (XMR) bağışı yaparak bağımsız bilim, estetik ve fikir yayımcılığını destekleyin.';
$page_canonical   = langUrl($isEn ? '/donate' : '/bagis');
$og_url           = $page_canonical;

require_once ROOT . '/app/Views/inc/header.php';
?>

<style>
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 80vh;
        border-bottom: 1px solid var(--line-color);
    }
    @media (min-width: 1024px) {
        .grid-layout { grid-template-columns: 40% 60%; }
    }
    
    .sidebar {
        position: relative;
        overflow: hidden;
        background-color: var(--bg-secondary);
        border-right: 1px solid var(--text-main);
        padding: 4rem 2rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    @media (max-width: 1023px) { 
        .sidebar { border-right: none; border-bottom: 1px solid var(--text-main); } 
    }

    .content-area {
        padding: 4rem 2rem;
        max-width: 800px;
    }

    /* Gece modu için sadece ana ızgara çizgisi kalibrasyonu */
    [data-theme="dark"] .grid-layout {
        border-color: var(--text-main) !important;
    }

    /* Brutalist Kopyalama Kutusu */
    .address-box {
        background-color: rgba(109, 35, 35, 0.05);
        border: 2px solid var(--text-main);
        padding: 1rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.9rem;
        word-break: break-all;
        position: relative;
        color: var(--text-main);
        margin: 1.5rem 0;
    }

    [data-theme="dark"] .address-box {
        background-color: rgba(225, 200, 158, 0.05);
    }

    .brutalist-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: var(--text-accent);
        color: #FEF9E1;
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.1em;
        border: 2px solid var(--text-main);
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        transition: transform 0.1s, box-shadow 0.1s;
        box-shadow: 4px 4px 0px var(--text-main);
    }

    .brutalist-btn:active {
        transform: translate(2px, 2px);
        box-shadow: 2px 2px 0px var(--text-main);
    }

    .xmr-warning {
        border: 2px dashed var(--text-accent);
        background-color: rgba(163, 29, 29, 0.05);
        padding: 1rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.8rem;
        color: var(--text-accent);
        margin-top: 1.5rem;
    }
</style>

<main id="main-content" class="flex-grow w-full max-w-[1920px] mx-auto">
    <div class="grid-layout">
        <aside class="sidebar">
            <div class="relative z-10 flex flex-col justify-between h-full w-full">
                <div>
                    <h1 class="font-syne text-5xl md:text-7xl font-bold uppercase leading-[0.9] text-[var(--text-main)] mb-6">
                        <?= $isEn ? 'Support<br>Us' : 'Destek<br>Ol' ?>
                    </h1>
                    <div class="w-16 h-1 bg-[var(--text-main)] opacity-50 mb-6"></div>
                </div>
                
                <div class="mt-12 hidden lg:block">
                    <!-- Brutalist XMR Sign decoration -->
                    <div class="font-syne text-8xl font-black text-[var(--text-main)] opacity-10 leading-none">
                        XMR
                    </div>
                </div>
            </div>
        </aside>

        <article class="content-area">
            <div class="space-y-6 text-lg leading-relaxed opacity-90 text-[var(--text-main)]">
                <?php if ($isEn): ?>
                    <h2 class="font-syne text-2xl font-bold uppercase text-[var(--text-accent)]">Independent and Anonymous</h2>
                    <p>
                        FEZADAN is an autonomous digital resistance space built on the principles of open source philosophy and the freedom of knowledge, standing against surveillance capitalism. We reject advertisements and tracking networks.
                    </p>
                    <p>
                        To cover our server costs and maintain complete editorial independence, we accept anonymous donations via **Monero (XMR)**.
                    </p>
                    <h3 class="font-syne text-xl font-bold uppercase mt-8">Why Monero?</h3>
                    <p class="text-sm">
                        Monero (XMR) is a privacy-centric, untraceable, and decentralized cryptocurrency. Unlike Bitcoin, transactions do not reveal sending/receiving addresses or amounts. We believe in Cypherpunk values: **privacy for the weak, transparency for the strong**.
                    </p>
                <?php else: ?>
                    <h2 class="font-syne text-2xl font-bold uppercase text-[var(--text-accent)]">Bağımsız ve Anonim</h2>
                    <p>
                        FEZADAN, gözetim kapitalizmine karşı duran, reklam ve izleyici barındırmayan özerk bir dijital direniş uzayıdır. Çalışmalarımızı sürdürmek ve tamamen bağımsız kalabilmek adına üçüncü şahıs fonları yerine okurlarımızın katkılarını alıyoruz.
                    </p>
                    <p>
                        Sunucu giderlerimizi karşılamamıza destek olmak ve bağımsız kalmamızı sağlamak için bağışlarınızı **Monero (XMR)** ile yapabilirsiniz.
                    </p>
                    <h3 class="font-syne text-xl font-bold uppercase mt-8">Neden Monero?</h3>
                    <p class="text-sm">
                        Monero (XMR), göndericiyi, alıcıyı ve işlem miktarını tamamen gizli tutan, takip edilemez ve merkeziyetsiz bir kripto para birimidir. Cypherpunk ilkelerine inanıyor ve finansal mahremiyetinizi bizzat koruyoruz.
                    </p>
                <?php endif; ?>

                <div class="mt-8 border-t-2 border-[var(--line-color)] pt-8">
                    <h3 class="font-syne text-lg font-bold uppercase mb-4"><?= $isEn ? 'MONERO WALLET ADDRESS' : 'MONERO CÜZDAN ADRESİ' ?></h3>
                    
                    <div id="xmr-address" class="address-box"><?= MONERO_ADDRESS ?></div>

                    <div class="flex flex-wrap gap-4 items-center mt-4">
                        <button id="copy-xmr-btn" class="brutalist-btn">
                            <span class="btn-text"><?= $isEn ? 'COPY ADDRESS' : 'ADRESİ KOPYALA' ?></span>
                        </button>

                        <div id="xmr-qr-wrapper" class="hidden sm:block">
                            <!-- QR Code target element -->
                            <div id="xmr-qr" class="bg-white p-2 inline-block border-2 border-[var(--text-main)] shadow-[4px_4px_0px_var(--text-main)]"></div>
                        </div>
                    </div>

                    <div class="xmr-warning font-mono">
                        <?= $isEn ? '// WARNING: Please send Monero (XMR) only. Sending other assets will result in permanent loss.' : '// UYARI: Lütfen yalnızca Monero (XMR) gönderdiğinizden emin olun. Başka bir kripto varlık göndermek kalıcı kayıpla sonuçlanır.' ?>
                    </div>
                </div>
            </div>
        </article>
    </div>
</main>

<script src="/assets/js/qrcode.min.js?v=<?= filemtime(ROOT . '/public_html/assets/js/qrcode.min.js') ?>"></script>
<script nonce="<?= CSP_NONCE ?>">
    (function () {
        var xmrAddress = <?= json_encode(MONERO_ADDRESS) ?>;
        var qrContainer = document.getElementById('xmr-qr');
        var copyBtn = document.getElementById('copy-xmr-btn');

        // Dinamik QR Kod Çizimi (monero: address URI)
        if (qrContainer && typeof QRCode !== 'undefined') {
            new QRCode(qrContainer, {
                text: 'monero:' + xmrAddress,
                width: 140,
                height: 140,
                colorDark: '#6D2323',
                colorLight: '#FEF9E1',
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        // Adres Kopyalama Fonksiyonu
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                var btnText = copyBtn.querySelector('.btn-text');
                var textToCopy = xmrAddress;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textToCopy).then(showSuccess).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }

                function fallbackCopy() {
                    var textarea = document.createElement('textarea');
                    textarea.value = textToCopy;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        showSuccess();
                    } catch (err) {}
                    document.body.removeChild(textarea);
                }

                function showSuccess() {
                    var trSuccess = 'KOPYALANDI!';
                    var enSuccess = 'COPIED!';
                    btnText.textContent = <?= json_encode($isEn) ?> ? enSuccess : trSuccess;
                    copyBtn.style.backgroundColor = '#6D2323';
                    setTimeout(function() {
                        btnText.textContent = <?= json_encode($isEn) ?> ? 'COPY ADDRESS' : 'ADRESİ KOPYALA';
                        copyBtn.style.backgroundColor = '';
                    }, 2000);
                }
            });
        }
    })();
</script>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
