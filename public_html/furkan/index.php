<?php
define('ROOT', dirname(dirname(__DIR__)));

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Core/Upload.php';

$photography = [];
$visualArt = [];
$dbError = false;

try {
    $pdo = Db::pdo();
    $items = $pdo->query("SELECT * FROM portfolio_images ORDER BY display_order ASC, id DESC")->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $url = Upload::assetUrl($item['image_url']);
        $title = !empty($item['title_en']) ? $item['title_en'] : ($item['title_tr'] ?? '');
        $entry = ['src' => $url, 'title' => $title, 'alt' => $title];
        if ($item['type'] === 'photo') {
            $photography[] = $entry;
        } else {
            $visualArt[] = $entry;
        }
    }
} catch (\Exception $e) {
    $dbError = true;
    error_log("Portfolio DB Fetch Error: " . $e->getMessage());
}

$siteUrl = rtrim(SITE_URL, '/');
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$imageId = null;
if (preg_match('#^/furkan/image/(\d+)#', $requestUri, $m)) {
    $imageId = (int)$m[1];
}

$single = null;
$prevId = null;
$nextId = null;

if ($imageId && !$dbError) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $single = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($single) {
            $stmtPrev = $pdo->prepare("SELECT id FROM portfolio_images WHERE type = ? AND (display_order < ? OR (display_order = ? AND id < ?)) ORDER BY display_order DESC, id DESC LIMIT 1");
            $stmtPrev->execute([$single['type'], $single['display_order'], $single['display_order'], $single['id']]);
            $prev = $stmtPrev->fetch(\PDO::FETCH_ASSOC);
            $prevId = $prev ? (int)$prev['id'] : null;

            $stmtNext = $pdo->prepare("SELECT id FROM portfolio_images WHERE type = ? AND (display_order > ? OR (display_order = ? AND id > ?)) ORDER BY display_order ASC, id ASC LIMIT 1");
            $stmtNext->execute([$single['type'], $single['display_order'], $single['display_order'], $single['id']]);
            $next = $stmtNext->fetch(\PDO::FETCH_ASSOC);
            $nextId = $next ? (int)$next['id'] : null;
        }
    } catch (\Exception $e) {
        error_log("Portfolio Single Fetch Error: " . $e->getMessage());
    }
}

$csp_nonce = bin2hex(random_bytes(16));
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'nonce-{$csp_nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; media-src 'self'; frame-ancestors 'self'; object-src 'none';");

if ($single):
    $imgUrl = Upload::assetUrl($single['image_url']);
    $imgTitle = !empty($single['title_en']) ? $single['title_en'] : ($single['title_tr'] ?? 'Furkan Shen');
    $imgDesc = !empty($single['description_en']) ? $single['description_en'] : ($single['description_tr'] ?? '');
    $pageType = ($single['type'] === 'photo') ? 'Photography' : 'Visual Art';
    $canonicalUrl = $siteUrl . '/furkan/image/' . $single['id'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($imgTitle, ENT_QUOTES, 'UTF-8') ?> &mdash; Furkan Shen</title>
    <meta name="description" content="<?= htmlspecialchars($imgDesc ?: $imgTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($imgTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($imgDesc ?: $imgTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="article">
    <meta property="og:image" content="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/png" sizes="32x32" href="../cdn/light-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../cdn/light-favicon-16x16.png">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="noise"></div>

    <header class="site-header" data-header>
        <a class="brand-mark" href="/furkan/" aria-label="Furkan Shen home">
            <span>furkan</span> shen
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
            <span></span>
            <span></span>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="Primary navigation">
            <a href="/furkan/" class="nav-link">Home</a>
            <a href="/furkan/#photography" class="nav-link">Photography</a>
            <a href="/furkan/#art" class="nav-link">Visual Art</a>
            <a href="/furkan/about.html" class="nav-link">About</a>
        </nav>
    </header>

    <main class="single-image-page">
        <nav class="single-nav">
            <?php if ($prevId): ?>
                <a href="/furkan/image/<?= $prevId ?>" class="single-nav-link single-nav-prev" rel="prev">&larr; Previous</a>
            <?php else: ?>
                <span class="single-nav-link single-nav-prev is-disabled">&larr; Previous</span>
            <?php endif; ?>
            <span class="single-nav-label"><?= htmlspecialchars($pageType, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($nextId): ?>
                <a href="/furkan/image/<?= $nextId ?>" class="single-nav-link single-nav-next" rel="next">Next &rarr;</a>
            <?php else: ?>
                <span class="single-nav-link single-nav-next is-disabled">Next &rarr;</span>
            <?php endif; ?>
        </nav>

        <div class="single-image-wrap">
            <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($imgTitle, ENT_QUOTES, 'UTF-8') ?>" class="single-image">
        </div>

        <div class="single-meta">
            <h1 class="single-title"><?= htmlspecialchars($imgTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($imgDesc): ?>
                <p class="single-description"><?= htmlspecialchars($imgDesc, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <a href="/furkan/" class="single-back" rel="index">Back to Portfolio</a>
    </main>

    <footer class="site-footer"></footer>

    <svg style="display:none" aria-hidden="true">
        <filter id="ripple" x="-20%" y="-20%" width="140%" height="140%">
            <feTurbulence id="ripple-turb" type="fractalNoise" baseFrequency="0.015 0.015" numOctaves="3" seed="2" result="noise" />
            <feDisplacementMap id="ripple-disp" in="SourceGraphic" in2="noise" scale="0" xChannelSelector="R" yChannelSelector="G" />
        </filter>
    </svg>

    <script nonce="<?= $csp_nonce ?>">
        document.addEventListener("keydown", function (e) {
            if (e.key === "ArrowLeft") {
                var prev = document.querySelector(".single-nav-prev[rel='prev']");
                if (prev) prev.click();
            } else if (e.key === "ArrowRight") {
                var next = document.querySelector(".single-nav-next[rel='next']");
                if (next) next.click();
            }
        });
    </script>
    <script src="../assets/js/main.js"></script>

</body>
</html>
<?php
else:
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Furkan Shen &mdash; Photographer &amp; Visual Artist</title>
    <meta name="description" content="Furkan Shen is a photographer and visual artist based in Ankara, specializing in creative direction, digital imagery, editorial design, and identity systems.">
    <meta property="og:title" content="Furkan Shen | Portfolio">
    <meta property="og:description" content="Selected photography and visual art portfolio by Furkan Shen, exploring the intersection of composition, visual storytelling, and narrative.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= rtrim(SITE_URL, '/') ?>/furkan/result_5991.jpg">
    <link rel="canonical" href="<?= rtrim(SITE_URL, '/') ?>/furkan/">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/png" sizes="32x32" href="../cdn/light-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../cdn/light-favicon-16x16.png">
    <link rel="preload" href="result_5991.jpg" as="image">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="noise"></div>

    <header class="site-header" data-header>
        <a class="brand-mark" href="/furkan/" aria-label="Furkan Shen home">
            <span>furkan</span> shen
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
            <span></span>
            <span></span>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="Primary navigation">
            <a href="/furkan/" class="nav-link is-active">Home</a>
            <a href="#photography" class="nav-link">Photography</a>
            <a href="#art" class="nav-link">Visual Art</a>
            <a href="/furkan/about.html" class="nav-link">About</a>
        </nav>
    </header>

    <main>
        <section class="hero-section" id="home">
            <div class="hero-content">
                <h1 class="hero-title" aria-label="Furkan Shen">
                    <svg class="hero-title-svg" viewBox="0 0 420 230" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <clipPath id="text-clip">
                                <text x="0" y="90" font-weight="600" font-size="100">Furkan</text>
                                <text x="0" y="200" font-weight="600" font-size="106">Shen</text>
                            </clipPath>
                        </defs>
                        <g class="static-text">
                            <text x="0" y="90" font-weight="600" font-size="100">Furkan</text>
                            <text x="0" y="200" font-weight="600" font-size="106">Shen</text>
                        </g>
                        <!-- Hover video text overlay -->
                        <foreignObject x="0" y="0" width="100%" height="100%" clip-path="url(#text-clip)" class="runner-fo">
                            <video class="runner-video" src="assets/runner.mp4" loop muted autoplay playsinline></video>
                        </foreignObject>
                    </svg>
                </h1>
                <div class="hero-meta">
                    <span class="hero-kicker">Photographer &amp; Visual Artist</span>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-frame">
                    <div class="hero-image-wrap">
                        <img src="result_5991.jpg" alt="">
                    </div>
                    <span class="hero-image-credit">Art Institute of Chicago</span>
                </div>
            </div>
        </section>

        <section class="work-section" id="photography">
            <div class="work-header">
                <div class="work-number reveal">01</div>
                <h2 class="section-title reveal">Selected Photography</h2>
            </div>
            <div class="work-gallery" id="photography-gallery">
                <div class="gallery-skeleton">
                    <div class="skele-row" data-cols="1"><div class="skele-box"></div></div>
                    <div class="skele-row" data-cols="2"><div class="skele-box"></div><div class="skele-box tall"></div></div>
                    <div class="skele-row" data-cols="1"><div class="skele-box short"></div></div>
                    <div class="skele-row" data-cols="2"><div class="skele-box tall"></div><div class="skele-box"></div></div>
                </div>
            </div>
        </section>

        <section class="work-section" id="art">
            <div class="work-header">
                <div class="work-number reveal">02</div>
                <h2 class="section-title reveal">Selected Visual Art</h2>
            </div>
            <div class="work-gallery" id="visual-art-gallery">
                <div class="gallery-skeleton">
                    <div class="skele-row" data-cols="1"><div class="skele-box"></div></div>
                    <div class="skele-row" data-cols="2"><div class="skele-box"></div><div class="skele-box tall"></div></div>
                    <div class="skele-row" data-cols="1"><div class="skele-box short"></div></div>
                    <div class="skele-row" data-cols="2"><div class="skele-box tall"></div><div class="skele-box"></div></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer"></footer>

    <div class="lightbox" id="lightbox" aria-hidden="true">
        <button class="lightbox-close" aria-label="Close">&times;</button>
        <img class="lightbox-img" src="" alt="">
    </div>

    <svg style="display:none" aria-hidden="true">
        <filter id="ripple" x="-20%" y="-20%" width="140%" height="140%">
            <feTurbulence id="ripple-turb" type="fractalNoise" baseFrequency="0.015 0.015" numOctaves="3" seed="2" result="noise" />
            <feDisplacementMap id="ripple-disp" in="SourceGraphic" in2="noise" scale="0" xChannelSelector="R" yChannelSelector="G" />
        </filter>
    </svg>

    <?php if (!$dbError && (!empty($photography) || !empty($visualArt))): ?>
    <script nonce="<?= $csp_nonce ?>">
        var PHOTOGRAPHY_IMAGES = <?php echo json_encode($photography); ?>;
        var VISUAL_ART_IMAGES = <?php echo json_encode($visualArt); ?>;
    </script>
    <?php else: ?>
    <script src="assets/js/gallery-data.js"></script>
    <?php endif; ?>
    <script src="assets/js/main.js"></script>

</body>
</html>
<?php endif; ?>
