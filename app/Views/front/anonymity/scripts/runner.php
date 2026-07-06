<?php // Skor sunumu, kayıtlı rapor render, tarama yürütme ve başlatma. ?>
    function animateScore(targetScore) {
        let strokeColor = 'var(--exposed)';
        let verdict = 'Low exposure';
        let desc = 'No high-priority issue was observed. Some ordinary browser attributes can still be fingerprinted.';

        if (targetScore < 35) {
            strokeColor = 'var(--safe)';
            verdict = 'Critical exposure';
            desc = 'Several high-confidence signals are visible and the browser is easy to distinguish.';
        } else if (targetScore < 55) {
            strokeColor = 'var(--warning)';
            verdict = 'High exposure';
            desc = 'Network or browser details expose enough surface to make tracking easier.';
        } else if (targetScore < 75) {
            strokeColor = 'var(--warning)';
            verdict = 'Noticeable fingerprint';
            desc = 'Fingerprinting APIs are returning stable details that can be combined with network data.';
        }

        els.scoreRing.style.stroke = strokeColor;
        els.scoreVerdictText.textContent = verdict;
        els.scoreVerdictText.style.color = strokeColor;
        els.scoreDescText.textContent = desc;
        els.scoreDisplay.textContent = String(targetScore);
        els.scoreRing.style.strokeDashoffset = 440 - (targetScore / 100) * 440;
    }

    function loadCachedReport(data) {
        els.cachedAlert.style.display = 'flex';

        els.endpointBrowser.textContent = data.browser || "Unknown";
        els.endpointTimezone.textContent = data.timezone || "Europe/Istanbul";

        score = Number(data.score) || 0;
        findings.length = 0;
        (Array.isArray(data.findings) ? data.findings : []).forEach(f => findings.push(f));
        renderFindings();
        updateReportSummary();

        if (data.matrix) {
            Object.keys(data.matrix).forEach(key => {
                const badgeEl = document.getElementById('m-' + key.toLowerCase());
                if (badgeEl) {
                    updateMatrixCell(badgeEl, data.matrix[key]);
                }
            });
        }

        animateScore(score);

        const lat = data.latitude !== undefined ? data.latitude : serverSignals.latitude;
        const lon = data.longitude !== undefined ? data.longitude : serverSignals.longitude;
        initMap(lat, lon, (lat !== null ? 11 : 4));
    }

    function getMatrixStates() {
        const matrixKeys = [
            'WebRtc', 'Canvas', 'WebGL', 'Audio', 'Timezone',
            'Proxy', 'Tor', 'Isp', 'Dns', 'Localhost', 'WebGpu',
            'AdBlock', 'Social', 'LocalApps', 'Fonts',
            'Uach', 'Screen', 'Plugins', 'Connection', 'Input',
            'Media', 'Headers', 'Privacy', 'Speech'
        ];
        const state = {};
        matrixKeys.forEach(k => {
            const el = els['m' + k];
            if (el) {
                let s = el.dataset.status || 'NO_SIGNAL';
                if (el.className.includes('exposed')) s = 'EXPOSED';
                else if (el.className.includes('safe') && !el.dataset.status) s = 'SAFE';
                else if (el.className.includes('warning')) s = 'REVIEW';
                else if (el.className.includes('blocked')) s = 'BLOCKED';
                else if (el.className.includes('unsupported')) s = 'UNSUPPORTED';
                state[k] = s;
            }
        });
        return state;
    }

    async function runScan() {
        els.progressBar.style.display = 'block';
        els.progressBar.style.width = '0%';

        const steps = [
            { weight: 5, name: "Network", fn: checkServerNetwork, matrixEls: [els.mProxy, els.mTor] },
            { weight: 10, name: "Geo Locate", fn: checkGeoLocation },
            { weight: 15, name: "Provider", fn: checkNetworkOwner, matrixEls: [els.mIsp] },
            { weight: 20, name: "STUN Leak", fn: () => checkWebRtcStunLeak(serverSignals.ip) },
            { weight: 24, name: "WebRTC", fn: checkWebRtc, matrixEls: [els.mWebRtc] },
            { weight: 28, name: "DNS", fn: checkDnsResolver, matrixEls: [els.mDns] },
            { weight: 32, name: "Canvas", fn: checkCanvas, matrixEls: [els.mCanvas] },
            { weight: 36, name: "WebGL", fn: checkWebGL, matrixEls: [els.mWebGL] },
            { weight: 40, name: "Audio", fn: checkAudio, matrixEls: [els.mAudio] },
            { weight: 44, name: "Locale/Time", fn: checkBrowserLocaleAndTime, matrixEls: [els.mTimezone] },
            { weight: 48, name: "UA Hints", fn: checkUaClientHints, matrixEls: [els.mUach] },
            { weight: 52, name: "Automation", fn: checkAutomation },
            { weight: 56, name: "Screen", fn: checkScreenFingerprint, matrixEls: [els.mScreen] },
            { weight: 60, name: "Media Q.", fn: checkMediaQueries, matrixEls: [els.mMedia] },
            { weight: 64, name: "Plugins", fn: checkPluginsAndMime, matrixEls: [els.mPlugins] },
            { weight: 68, name: "Speech", fn: checkSpeechVoices, matrixEls: [els.mSpeech] },
            { weight: 72, name: "Headers", fn: checkHeaderAnalysis, matrixEls: [els.mHeaders] },
            { weight: 76, name: "Device APIs", fn: checkDeviceAPIs, matrixEls: [els.mWebGpu] },
            { weight: 80, name: "Connection", fn: checkConnectionFingerprint, matrixEls: [els.mConnection] },
            { weight: 83, name: "Extensions", fn: checkLocalhostAndExtensions, matrixEls: [els.mLocalhost] },
            { weight: 86, name: "Privacy", fn: checkPrivacySignals, matrixEls: [els.mPrivacy] },
            { weight: 89, name: "Touch", fn: checkTouchAndInput, matrixEls: [els.mInput] },
            { weight: 92, name: "Math", fn: checkMathPrecision },
            { weight: 95, name: "Content Filter", fn: checkAdBlocker, matrixEls: [els.mAdBlock] },
            { weight: 97, name: "Tracker Data", fn: checkTrackers },
            { weight: 99, name: "Local Apps", fn: checkLocalApps, matrixEls: [els.mLocalApps] },
            { weight: 100,name: "Account Scope", fn: () => Promise.all([checkFonts(), checkSocialSessions(), checkTheoreticalRisks()]), matrixEls: [els.mFonts, els.mSocial] }
        ];

        let completedWeight = 0;
        for (const step of steps) {
            els.progressHeader.textContent = step.name;
            els.progressLog.textContent = step.name;
            try {
                await step.fn();
            } catch (error) {
                console.error('Anonymity check step failed:', step.name, error);
                (step.matrixEls || []).forEach(el => updateMatrixCell(el, 'UNSUPPORTED'));
                applyFinding({
                    module: step.name + ' Check',
                    category: 'Other Findings',
                    status: 'UNSUPPORTED',
                    severity: 'low',
                    confidence: 1,
                    penalty: 0,
                    evidence: 'Check failed: ' + (error && error.message ? error.message : 'unknown error'),
                    impactText: 'This module failed, but the remaining report continued.',
                    mitigation: 'Reload and run the report again. If this repeats, inspect the browser console for the failed module.'
                });
            }
            completedWeight = step.weight;
            els.progressBar.style.width = completedWeight + '%';
        }

        els.progressBar.style.width = '100%';
        els.progressLog.textContent = "Results";

        closeAperture();
        els.startScreen.style.display = 'none';
        els.dashboardWrapper.style.display = 'block';
        els.progressBar.style.display = 'none';

        els.endpointBrowser.textContent = getBrowserDetails();
        animateScore(score);
        initMap(serverSignals.latitude, serverSignals.longitude);

        const report = {
            ip: serverSignals.ip,
            hostname: serverSignals.hostname,
            country: serverSignals.country,
            country_name: serverSignals.countryName,
            network_asn: serverSignals.networkAsn,
            network_org: serverSignals.networkOrg,
            city: serverSignals.city,
            region: serverSignals.region,
            geo_timezone: serverSignals.geoTimezone,
            geo_source: serverSignals.geoSource,
            geo_accuracy: serverSignals.geoAccuracy,
            latitude: serverSignals.latitude,
            longitude: serverSignals.longitude,
            is_tor: serverSignals.isTor,
            proxy_headers: serverSignals.proxyHeaders,
            server_protocol: serverSignals.serverProtocol,
            headers: serverSignals.headers,
            score: score,
            findings: findings,
            matrix: getMatrixStates(),
            browser: getBrowserDetails(),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };

        try {
            const response = await fetch(anonymityEndpoint('/save'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8'
                },
                body: JSON.stringify(report)
            });
            if (response.ok) {
                const res = await response.json();
                if (res.ok && res.id) {
                    const targetUrl = anonymityEndpoint('/results' + res.id);
                    history.pushState({ id: res.id }, '', targetUrl);
                }
            }
        } catch (e) {
            console.error('Failed to save report cache:', e);
        }
    }

    async function runSimulatedScan() {
        els.startScreen.style.display = 'none';
        closeAperture();
        els.dashboardWrapper.style.display = 'block';
        els.progressBar.style.display = 'none';

        loadCachedReport(cachedData);
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (isCachedReport && cachedData) {
            runSimulatedScan();
        } else {
            els.startScreen.style.display = 'block';
            els.dashboardWrapper.style.display = 'none';
            document.body.classList.add('start-screen-active');

            els.btnStartScan.addEventListener('click', () => {
                els.startScreen.classList.add('is-leaving');
                document.body.classList.remove('start-screen-active');
                activateAperture();
                runScan();
            });
        }
    });
