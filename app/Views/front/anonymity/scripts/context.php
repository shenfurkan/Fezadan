<?php // Server-provided state and DOM references for the anonymity report script. ?>
    const isCachedReport = <?= $cached_result ? 'true' : 'false' ?>;
    const cachedData = <?= $cached_result ? json_encode($cached_result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null' ?>;

    const serverSignals = {
        isTor: <?= $is_tor ? 'true' : 'false' ?>,
        proxyCount: <?= (int)$proxyCount ?>,
        proxyHeaders: <?= json_encode(array_keys($proxy_headers ?? [])) ?>,
        country: <?= json_encode($country ?? '', JSON_UNESCAPED_UNICODE) ?>,
        city: <?= json_encode($city ?? '', JSON_UNESCAPED_UNICODE) ?>,
        networkAsn: <?= json_encode($network_asn ?? '', JSON_UNESCAPED_UNICODE) ?>,
        networkOrg: <?= json_encode($network_org ?? '', JSON_UNESCAPED_UNICODE) ?>,
        serverTimeMs: <?= (int)(time() * 1000) ?>,
        latitude: <?= (isset($latitude) && $latitude !== '') ? $latitude : 'null' ?>,
        longitude: <?= (isset($longitude) && $longitude !== '') ? $longitude : 'null' ?>,
        ip: <?= json_encode($ip, JSON_UNESCAPED_UNICODE) ?>,
        hostname: <?= json_encode($hostname, JSON_UNESCAPED_UNICODE) ?>,
        countryName: <?= json_encode($countryName ?? '', JSON_UNESCAPED_UNICODE) ?>,
        geoSource: <?= json_encode($geoSourceDisplay, JSON_UNESCAPED_UNICODE) ?>,
        geoAccuracy: <?= json_encode($geoAccuracyDisplay, JSON_UNESCAPED_UNICODE) ?>,
        geoTimezone: <?= json_encode($geoTimezoneDisplay, JSON_UNESCAPED_UNICODE) ?>,
        headers: <?= json_encode($headers ?? []) ?>,
        serverProtocol: <?= json_encode($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') ?>
    };

    let score = 100;
    const findings = [];

    const els = {
        progressBar: document.getElementById('progress-bar'),
        progressScreen: document.getElementById('progress-screen'),
        progressHeader: document.getElementById('progress-header'),
        progressLog: document.getElementById('progress-log'),
        scoreDisplay: document.getElementById('score-display'),
        scoreRing: document.getElementById('score-ring'),
        scoreVerdictText: document.getElementById('score-verdict-text'),
        scoreDescText: document.getElementById('score-desc-text'),
        findingsTbody: document.getElementById('findings-tbody'),
        findingsContainer: document.getElementById('findings-container'),
        endpointTimezone: document.getElementById('endpoint-timezone'),
        endpointBrowser: document.getElementById('endpoint-browser'),
        cachedAlert: document.getElementById('cached-alert'),
        startScreen: document.getElementById('start-screen'),
        dashboardWrapper: document.getElementById('dashboard-wrapper'),
        btnStartScan: document.getElementById('btn-start-scan'),
        summaryPriority: document.getElementById('summary-priority'),
        summaryFingerprint: document.getElementById('summary-fingerprint'),
        summaryNetwork: document.getElementById('summary-network'),
        mWebRtc: document.getElementById('m-webrtc'),
        mCanvas: document.getElementById('m-canvas'),
        mWebGL: document.getElementById('m-webgl'),
        mAudio: document.getElementById('m-audio'),
        mTimezone: document.getElementById('m-timezone'),
        mProxy: document.getElementById('m-proxy'),
        mTor: document.getElementById('m-tor'),
        mIsp: document.getElementById('m-isp'),
        mDns: document.getElementById('m-dns'),
        mLocalhost: document.getElementById('m-localhost'),
        mWebGpu: document.getElementById('m-webgpu'),
        mAdBlock: document.getElementById('m-adblock'),
        mSocial: document.getElementById('m-social'),
        mLocalApps: document.getElementById('m-localapps'),
        mFonts: document.getElementById('m-fonts'),
        mUach: document.getElementById('m-uach'),
        mScreen: document.getElementById('m-screen'),
        mPlugins: document.getElementById('m-plugins'),
        mConnection: document.getElementById('m-connection'),
        mInput: document.getElementById('m-input'),
        mMedia: document.getElementById('m-media'),
        mHeaders: document.getElementById('m-headers'),
        mPrivacy: document.getElementById('m-privacy'),
        mSpeech: document.getElementById('m-speech')
    };
