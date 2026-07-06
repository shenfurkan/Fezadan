<?php // Security & Anti-Tracking test modules. ?>
    async function checkAutomation() {
        const isWebdriver = navigator.webdriver === true;
        const isSelenium = Boolean(window.cdc_adoQpoasnfa || document.documentElement.getAttribute('webdriver'));
        const hasAutomation = isWebdriver || isSelenium;

        applyFinding({
            module: 'Automation Indicators',
            category: 'Security & Anti-Tracking',
            status: hasAutomation ? 'EXPOSED' : 'NO_SIGNAL',
            severity: hasAutomation ? 'critical' : 'low',
            confidence: 1,
            penalty: hasAutomation ? 25 : 0,
            evidence: `webdriver=${isWebdriver} seleniumSignature=${isSelenium}`,
            impactText: hasAutomation ? 'Exposes browser automation profiles, confirming execution via automated driver tools.' : 'No active driver flags detected.',
            mitigation: 'For automated flows, utilize stealth driver variants. In standard environments, ensure testing frameworks are fully disabled.'
        });
    }

    async function checkDeviceAPIs() {
        let batteryVal = 'Blocked or unavailable';
        let batteryStatus = 'BLOCKED';

        if (navigator.getBattery) {
            try {
                const b = await navigator.getBattery();
                batteryVal = `level=${Math.round(b.level * 100)}% charging=${b.charging}`;
                batteryStatus = 'EXPOSED';
            } catch (err) {
                batteryVal = 'Battery API error: ' + err.name;
            }
        } else {
            batteryStatus = 'UNSUPPORTED';
        }

        applyFinding({
            module: 'Battery API',
            status: batteryStatus,
            severity: batteryStatus === 'EXPOSED' ? 'medium' : 'low',
            confidence: 0.8,
            penalty: batteryStatus === 'EXPOSED' ? 4 : 0,
            evidence: batteryVal,
            impactText: batteryStatus === 'EXPOSED' ? 'Battery state can add a small amount of fingerprinting entropy.' : 'Battery state is blocked or unavailable.',
            mitigation: batteryStatus === 'EXPOSED' ? 'Restrict hardware APIs in high-privacy profiles.' : 'No action required.'
        });

        let gpuStatus = 'UNSUPPORTED';
        let gpuVal = 'GPU controller interface missing';
        if ('gpu' in navigator) {
            gpuStatus = 'REVIEW';
            gpuVal = 'navigator.gpu interface exposed';
        }
        applyFinding({
            module: 'WebGPU API',
            status: gpuStatus,
            severity: 'low',
            confidence: 0.9,
            penalty: gpuStatus === 'REVIEW' ? 2 : 0,
            evidence: gpuVal,
            impactText: gpuStatus === 'REVIEW' ? 'WebGPU support is visible and may add graphics fingerprinting surface.' : 'WebGPU is unavailable or restricted.',
            mitigation: gpuStatus === 'REVIEW' ? 'Restrict experimental graphics APIs in high-privacy profiles.' : 'No action required.'
        });
        updateMatrixCell(els.mWebGpu, gpuStatus);
    }

    async function checkTheoreticalRisks() {
        const authStatus = window.PublicKeyCredential ? 'REVIEW' : 'UNSUPPORTED';
        applyFinding({
            module: 'WebAuthn API',
            status: authStatus,
            severity: 'low',
            confidence: 0.6,
            penalty: 0,
            evidence: window.PublicKeyCredential ? 'PublicKeyCredential is available' : 'PublicKeyCredential not supported',
            impactText: 'Passkey support is visible, which is normal in modern browsers.',
            mitigation: 'No action required unless you use strict compartmentalized browser profiles.'
        });

        applyFinding({
            module: 'XS-Leaks',
            status: 'UNSUPPORTED',
            severity: 'low',
            evidence: 'Cross-site timing checks were not executed.',
            impactText: 'This report does not infer account state through external timing probes.',
            mitigation: 'Keep third-party cookies restricted and isolate sensitive accounts.'
        });
    }

    async function checkAdBlocker() {
        let isBlocked = false;
        
        const adTest = document.createElement('div');
        adTest.className = 'adsbox ad-placement banner-ads doubleclick-ad';
        adTest.setAttribute('style', 'position: absolute; left: -9999px; width: 1px; height: 1px;');
        document.body.appendChild(adTest);
        
        await sleep(20);
        
        if (adTest.offsetHeight === 0 || window.getComputedStyle(adTest).display === 'none') {
            isBlocked = true;
        }
        document.body.removeChild(adTest);

        const status = isBlocked ? 'PROTECTED' : 'EXPOSED';
        applyFinding({
            module: 'Content Filter',
            status: status,
            severity: 'low',
            confidence: 0.8,
            penalty: isBlocked ? 0 : 5,
            evidence: isBlocked ? 'Local ad bait element was hidden by browser or extension rules' : 'Local ad bait element remained visible',
            impactText: isBlocked ? 'Basic ad/tracker bait appears to be filtered.' : 'Common ad/tracker patterns are not filtered in this profile.',
            mitigation: isBlocked ? 'No action required.' : 'Enable a trusted content blocker or browser tracking protection.'
        });
        updateMatrixCell(els.mAdBlock, status);
    }

    async function checkTrackers() {
        const cookieString = document.cookie;
        const targetCookies = ['_ga', '_gid', '_fbp', '_fbc', '_ym_uid'];
        const foundCookies = [];
        
        targetCookies.forEach(key => {
            if (cookieString.includes(key + '=')) {
                foundCookies.push(key);
            }
        });

        const foundStorage = [];
        const storageKeys = ['_ga', '_gid', '_fbp', '_ym_uid', 'amplitude', 'mixpanel'];
        try {
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                storageKeys.forEach(s => {
                    if (key.includes(s)) foundStorage.push(key);
                });
            }
        } catch (e) {}

        const trackerCount = foundCookies.length + foundStorage.length;
        const cookiesEnabled = navigator.cookieEnabled;
        
        let writeOk = false;
        try {
            document.cookie = "fezadan_test_cookie=1; path=/";
            if (document.cookie.includes("fezadan_test_cookie=1")) {
                writeOk = true;
                document.cookie = "fezadan_test_cookie=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
            }
        } catch (e) {}

        const status = trackerCount > 0 ? 'EXPOSED' : 'NO_SIGNAL';
        let evidence = `cookiesEnabled=${cookiesEnabled} writeVerified=${writeOk}`;
        if (trackerCount > 0) {
            evidence += ` foundCookies=[${foundCookies.join(',')}] foundStorage=[${foundStorage.join(',')}]`;
        }

        applyFinding({
            module: 'Ad Tracker Cookies',
            status: status,
            severity: trackerCount > 0 ? 'medium' : 'low',
            confidence: 0.9,
            penalty: trackerCount > 0 ? 12 : 0,
            evidence: evidence,
            impactText: trackerCount > 0 ? 'Tracking cookies or localStorage keys are present, enabling session reconstruction.' : 'No active tracker cookies detected on the current origin.',
            mitigation: trackerCount > 0 ? 'Clear local cookies or block third-party trackers.' : 'Configure browser to restrict third-party tracking cookies.'
        });
    }

    async function checkPrivacySignals() {
        const dnt = navigator.doNotTrack || window.doNotTrack || navigator.msDoNotTrack;
        const gpc = navigator.globalPrivacyControl;
        const secure = window.isSecureContext;
        const isolated = window.crossOriginIsolated;

        const signals = [];
        if (dnt === '1') signals.push('DNT=enabled');
        else if (dnt === '0') signals.push('DNT=disabled');
        else signals.push('DNT=unset');
        if (gpc) signals.push('GPC=true');
        if (secure) signals.push('SecureContext');
        if (isolated) signals.push('CrossOriginIsolated');

        const status = (dnt === '1' || gpc) ? 'PROTECTED' : 'REVIEW';

        applyFinding({
            module: 'Privacy Signals',
            category: 'Security & Anti-Tracking',
            status: status,
            severity: 'low',
            confidence: 0.9,
            penalty: (dnt === '1' || gpc) ? 0 : 2,
            evidence: signals.join(', ') || 'No privacy signals detected',
            impactText: (dnt === '1' || gpc) ? 'Browser is sending Do Not Track or Global Privacy Control signals.' : 'No DNS/GPC privacy signals are being sent.',
            mitigation: 'Enable Do Not Track or Global Privacy Control in browser privacy settings.'
        });
        updateMatrixCell(els.mPrivacy, status);
    }

    async function checkSocialSessions() {
        applyFinding({
            module: 'Cross-site Sessions',
            status: 'UNSUPPORTED',
            severity: 'low',
            confidence: 1,
            penalty: 0,
            evidence: 'No third-party session probes were sent.',
            impactText: 'Timing probes against external login providers are noisy and create unnecessary third-party traffic.',
            mitigation: 'Use private browsing or browser profile separation for account isolation.'
        });
        updateMatrixCell(els.mSocial, 'UNSUPPORTED');
    }
