<?php
$nonce = $nonce ?? '';
$proxyCount = is_array($proxy_headers ?? null) ? count($proxy_headers) : 0;

$latRaw = trim((string)($latitude ?? ''));
$lonRaw = trim((string)($longitude ?? ''));
$hasMap = $latRaw !== '' && $lonRaw !== '' && is_numeric($latRaw) && is_numeric($lonRaw);
$lat = $hasMap ? (float)$latRaw : null;
$lon = $hasMap ? (float)$lonRaw : null;
$countryCodeDisplay = trim((string)($country ?? ''));
$countryNameDisplay = trim((string)($country_name ?? ''));
$countryDisplay = $countryNameDisplay !== ''
    ? $countryNameDisplay . ($countryCodeDisplay !== '' && $countryCodeDisplay !== $countryNameDisplay ? ' (' . $countryCodeDisplay . ')' : '')
    : ($countryCodeDisplay ?: 'Unavailable');
$countryFlagUrl = $countryCodeDisplay !== ''
    ? 'https://flagcdn.com/' . strtolower($countryCodeDisplay) . '.svg'
    : '';
$cityDisplay = trim((string)($city ?? '')) ?: 'Unavailable';
$regionDisplay = trim((string)($region ?? ''));
$geoSourceDisplay = trim((string)($geo_source ?? '')) ?: 'Unavailable';
$geoAccuracyDisplay = trim((string)($geo_accuracy ?? ''));
$geoTimezoneDisplay = trim((string)($geo_timezone ?? ''));
$networkAsnDisplay = trim((string)($network_asn ?? ''));
$networkOrgDisplay = trim((string)($network_org ?? ''));
$networkOwnerDisplay = $networkOrgDisplay !== ''
    ? $networkOrgDisplay . ($networkAsnDisplay !== '' ? ' / AS' . ltrim($networkAsnDisplay, 'AS') : '')
    : ($networkAsnDisplay !== '' ? 'AS' . ltrim($networkAsnDisplay, 'AS') : 'Not exposed');
$coordinateDisplay = $hasMap
    ? number_format($lat, 4, '.', '') . ', ' . number_format($lon, 4, '.', '')
    : 'Coordinates unavailable';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN - Anonymity Report</title>
    <link rel="preload" href="/assets/fonts/league-gothic/LeagueGothic-Condensed.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/eb-garamond-v32-latin-ext-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/jetbrains-mono-v24-latin-ext-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/plugins/leaflet/leaflet.css" />
    <script src="/assets/plugins/leaflet/leaflet.js" nonce="<?= htmlspecialchars($nonce) ?>"></script>

    <?php require __DIR__ . '/anonymity/style.php'; ?>
</head>
<body>

    <a class="brand-mark" href="https://fezadan.org" aria-label="Created by Fezadan">
        <img src="/cdn/logo-dark.png" alt="" width="30" height="30">
        <span class="brand-mark-text">
            <span class="brand-kicker">Created by</span>
            <span class="brand-name">Fezadan</span>
        </span>
    </a>

    <div id="progress-container">
        <div id="progress-bar"></div>
    </div>

    <div class="container">

        <!-- Start gate -->
        <section class="start-screen" id="start-screen" style="display: none;">
            <h1>Anonymity Report</h1>
            <p class="subtitle"><span>Connection details, browser surface, and local privacy checks in one report.</span></p>
            <p class="scan-note">After you start, the report may use a public STUN server for WebRTC leak detection and an external IP lookup only if local geolocation data is unavailable.</p>

            <button class="btn-scan" id="btn-start-scan">Run report</button>
        </section>

        <!-- PROGRESS SCREEN -->
        <section class="progress-screen-container" id="progress-screen" style="display: none;">
            <div class="aperture-stage" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; border: none; background: transparent; overflow: visible;">
                <img src="/assets/img/close-popups.webp" alt="Scanning" style="max-width: 280px; height: auto; display: block; filter: hue-rotate(0deg);">
                <div class="aperture-label" id="progress-log" style="position: relative; bottom: auto; left: auto; transform: none;">Ready</div>
            </div>
            <div class="progress-text" id="progress-header">Ready</div>
        </section>

        <!-- DASHBOARD RESULTS WRAPPER -->
        <div id="dashboard-wrapper" style="display: none;">
            <!-- Cached Alert Header -->
            <div id="cached-alert">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>Saved report loaded.</span>
            </div>

            <!-- Dashboard main grid -->
            <div class="dashboard-grid">
                
                <!-- Left Side Columns -->
                <div class="left-col">
                    
                    <!-- Score Card -->
                    <div class="card score-card">
                        <div class="score-circle-wrapper">
                            <svg class="score-svg" viewBox="0 0 160 160">
                                <circle class="score-bg-circle" cx="80" cy="80" r="70"></circle>
                                <circle class="score-progress-circle" id="score-ring" cx="80" cy="80" r="70"></circle>
                            </svg>
                            <div class="score-text-overlay">
                                <span class="score-number" id="score-display">--</span>
                                <span class="score-max">/ 100</span>
                            </div>
                        </div>
                        <div class="score-info">
                            <div class="card-title">Privacy score</div>
                            <div class="score-verdict" id="score-verdict-text">Pending</div>
                            <div class="score-desc" id="score-desc-text">
                                Result is based on network signals, tracker blocking, WebRTC behavior, and fingerprinting surface.
                            </div>
                        </div>
                    </div>

                    <!-- Parameters Card -->
                    <div class="card parameters-card">
                        <div class="card-header">
                            <div class="card-title">Network profile</div>
                        </div>
                        <div class="param-list">
                            <div class="param-item">
                                <div class="param-label">IP Address</div>
                                <div class="param-value"><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="param-item">
                                <div class="param-label">ISP / ASN</div>
                                <div class="param-value"><?= htmlspecialchars($networkOwnerDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="param-item">
                                <div class="param-label">Browser</div>
                                <div class="param-value" id="endpoint-browser">Pending</div>
                            </div>
                            <div class="param-item">
                                <div class="param-label">Timezone</div>
                                <div class="param-value" id="endpoint-timezone">Pending</div>
                                <?php if ($geoTimezoneDisplay !== ''): ?>
                                    <div class="param-desc">Server-Side: <?= htmlspecialchars($geoTimezoneDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Side Column (Geo & Map) -->
                <div class="card geo-card">
                    <div class="card-header">
                        <div class="card-title">Location Estimate</div>
                    </div>
                    
                    <div class="geo-content">
                        <div class="geo-details-list">
                            <div class="geo-details-item">
                                <div class="param-label">Country</div>
                                <div class="param-value" style="font-size: 1.15rem;"><?= htmlspecialchars($countryDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="geo-details-item" style="padding: 0; overflow: hidden; display: flex; align-items: center; justify-content: center; min-height: 70px;">
                                <?php if ($countryFlagUrl !== ''): ?>
                                    <img class="geo-flag-card-img" src="<?= htmlspecialchars($countryFlagUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                <?php else: ?>
                                    <span class="param-label" style="opacity: 0.5;">No Flag</span>
                                <?php endif; ?>
                            </div>
                            <div class="geo-details-item" style="grid-column: span 2; align-items: center; text-align: center;">
                                <div class="param-label">City</div>
                                <div class="param-value"><?= htmlspecialchars($cityDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="param-desc" id="geo-source-tag" style="margin-top: 4px;<?= ($geoSourceDisplay !== '' && $geoSourceDisplay !== 'Unavailable' && $geoSourceDisplay !== 'Cloudflare') ? '' : ' display: none;' ?>"><?= ($geoSourceDisplay !== '' && $geoSourceDisplay !== 'Unavailable' && $geoSourceDisplay !== 'Cloudflare') ? 'Source: ' . htmlspecialchars($geoSourceDisplay, ENT_QUOTES, 'UTF-8') : '' ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Local location estimate panel -->
                    <div id="map-wrapper">
                        <div id="map"></div>
                    </div>
                </div>

            </div>

            <div class="summary-strip">
                <div class="summary-item">
                    <div class="summary-label">Priority findings</div>
                    <div class="summary-value" id="summary-priority">0</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Fingerprint signals</div>
                    <div class="summary-value" id="summary-fingerprint">0</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Network masking</div>
                    <div class="summary-value" id="summary-network">Direct</div>
                </div>
            </div>

            <!-- Indicators Matrix Section -->
            <section class="matrix-section">
                <div class="card-title">Checks</div>
                <div class="matrix-grid">
                    <div class="matrix-cell">
                        <span class="matrix-name">WebRTC</span>
                        <span class="badge testing" id="m-webrtc">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Canvas</span>
                        <span class="badge testing" id="m-canvas">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">WebGL</span>
                        <span class="badge testing" id="m-webgl">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Audio</span>
                        <span class="badge testing" id="m-audio">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Timezone</span>
                        <span class="badge testing" id="m-timezone">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Proxy</span>
                        <span class="badge testing" id="m-proxy">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Tor</span>
                        <span class="badge testing" id="m-tor">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">ISP / ASN</span>
                        <span class="badge testing" id="m-isp">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">DNS Resolver</span>
                        <span class="badge testing" id="m-dns">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Local services</span>
                        <span class="badge testing" id="m-localhost">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">WebGPU</span>
                        <span class="badge testing" id="m-webgpu">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Content filter</span>
                        <span class="badge testing" id="m-adblock">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Account isolation</span>
                        <span class="badge testing" id="m-social">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">App hints</span>
                        <span class="badge testing" id="m-localapps">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Fonts</span>
                        <span class="badge testing" id="m-fonts">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">UA Hints</span>
                        <span class="badge testing" id="m-uach">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Screen</span>
                        <span class="badge testing" id="m-screen">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Plugins</span>
                        <span class="badge testing" id="m-plugins">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Connection</span>
                        <span class="badge testing" id="m-connection">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Input</span>
                        <span class="badge testing" id="m-input">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Media Q.</span>
                        <span class="badge testing" id="m-media">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Headers</span>
                        <span class="badge testing" id="m-headers">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Privacy</span>
                        <span class="badge testing" id="m-privacy">Pending</span>
                    </div>
                    <div class="matrix-cell">
                        <span class="matrix-name">Speech</span>
                        <span class="badge testing" id="m-speech">Pending</span>
                    </div>
                </div>
            </section>

            <!-- Detailed Findings Section -->
            <section class="findings-section" id="findings-container" style="display: none;">
                <div class="card-title">Findings</div>
                <div class="findings-list" id="findings-tbody">
                    <!-- Javascript populated findings cards -->
                </div>
            </section>
        </div>

    </div>

<?php require __DIR__ . '/anonymity/script.php'; ?>
</body>
</html>
