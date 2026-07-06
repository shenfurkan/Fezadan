<?php // Network & Infrastructure test modules. ?>
    async function checkServerNetwork() {
        const edgeHeaders = hasTrustedEdgeHeaders();
        const hasProxy = serverSignals.proxyCount > 0 && !edgeHeaders;
        const proxyStatus = hasProxy ? 'REVIEW' : 'NO_SIGNAL';
        applyFinding({
            module: 'Proxy Headers',
            category: 'Network & Infrastructure',
            status: proxyStatus,
            severity: hasProxy ? 'medium' : 'low',
            confidence: 1,
            penalty: hasProxy ? 3 : 0,
            evidence: hasProxy
                ? 'proxyHeaders=' + serverSignals.proxyHeaders.join(', ')
                : (edgeHeaders ? 'Trusted CDN edge headers present; not treated as a user proxy signal' : 'No HTTP proxy headers matching routing intermediates'),
            impactText: hasProxy
                ? 'The request contains proxy routing headers that may indicate an intermediary before the origin.'
                : (edgeHeaders ? 'The request reached the origin through a CDN edge. This is normal site infrastructure, not proof that the visitor is using a proxy.' : 'No proxy headers were observed in the request.'),
            mitigation: hasProxy ? 'Audit edge configurations to ensure origin IP forwarding is properly stripped before reaching the client.' : 'No action required.'
        });
        updateMatrixCell(els.mProxy, proxyStatus);

        let torDetected = serverSignals.isTor;
        let torEvidence = serverSignals.isTor
            ? 'Cloudflare country code T1 indicates Tor traffic'
            : 'Cloudflare country code does not indicate Tor';
        let torConfidence = serverSignals.isTor ? 1 : 0.4;

        try {
            const torProject = await fetchJsonWithTimeout(anonymityEndpoint('/tor-check'), 2500);
            if (torProject.ok && typeof torProject.is_tor === 'boolean') {
                torDetected = torProject.is_tor;
                torConfidence = 1;
                torEvidence = 'check.torproject.org/api/ip IsTor=' + torProject.is_tor + ' IP=' + (torProject.ip || 'unreported');
            }
        } catch (error) {
            // Arka plan kontrol zaman aşımlarını yoksay
        }

        const torStatus = torDetected ? 'MASKED' : 'NO_SIGNAL';
        applyFinding({
            module: 'Tor Infrastructure',
            category: 'Network & Infrastructure',
            status: torStatus,
            severity: torDetected ? 'medium' : 'low',
            confidence: torConfidence,
            penalty: 0,
            evidence: torEvidence,
            impactText: torDetected ? 'The visible network endpoint is a Tor exit node, so the direct ISP address is masked.' : 'No public Tor exit infrastructure was detected.',
            mitigation: 'If masking is required, maintain current configuration. Note that exit nodes may face elevated CAPTCHA friction or edge blocking.'
        });
        updateMatrixCell(els.mTor, torStatus);
    }

    async function checkNetworkOwner() {
        let hasNetworkOwner = Boolean(serverSignals.networkAsn || serverSignals.networkOrg);
        let evidence = '';
        let org = serverSignals.networkOrg || '';
        let asn = serverSignals.networkAsn || '';

        if (!hasNetworkOwner) {
            try {
                const res = await getGeoLookup();
                if (res && res.ok && (res.asn || res.org)) {
                    org = res.org || '';
                    asn = res.asn || '';
                    hasNetworkOwner = true;
                    
                    serverSignals.networkAsn = asn;
                    serverSignals.networkOrg = org;
                    
                    // DOM'daki ağ profili tablosunu güncelle
                    const tableRows = document.querySelectorAll('.param-list .param-item');
                    for (const row of tableRows) {
                        const label = row.querySelector('.param-label');
                        if (label && label.textContent.trim() === 'ISP / ASN') {
                            const valEl = row.querySelector('.param-value');
                            if (valEl) {
                                const display = [org, asn].filter(Boolean).join(' / ');
                                valEl.textContent = display || 'Not exposed';
                            }
                            break;
                        }
                    }
                }
            } catch (e) {
                console.error('Failed to query local ISP lookup:', e);
            }
        }

        evidence = hasNetworkOwner
            ? 'org=' + (org || 'unreported') + ' asn=' + (asn || 'unreported')
            : 'No ISP/ASN data in request headers.';

        applyFinding({
            module: 'ISP / ASN',
            category: 'Network & Infrastructure',
            status: hasNetworkOwner ? 'EXPOSED' : 'UNSUPPORTED',
            severity: hasNetworkOwner ? 'medium' : 'low',
            confidence: hasNetworkOwner ? 0.95 : 1,
            penalty: hasNetworkOwner ? 4 : 0,
            evidence: evidence,
            impactText: hasNetworkOwner ? 'Network owner and autonomous system can identify the access provider or hosting network.' : 'HTTP does not expose ISP/ASN unless the edge adds it as a trusted header.',
            mitigation: 'Consider employing a trusted commercial VPN or Tor network to obfuscate direct provider-level attribution if required.'
        });
        updateMatrixCell(els.mIsp, hasNetworkOwner ? 'EXPOSED' : 'UNSUPPORTED');
    }

    async function checkDnsResolver() {
        applyFinding({
            module: 'DNS Resolver',
            category: 'Network & Infrastructure',
            status: 'UNSUPPORTED',
            severity: 'low',
            confidence: 1,
            penalty: 0,
            evidence: 'Resolver IP is not available from a normal HTTP request.',
            impactText: 'DNS leak testing needs a first-party DNS probe. This page does not call an external DNS test service.',
            mitigation: 'Utilize specialized DNS leak testing frameworks or strict DNS-over-HTTPS (DoH) to secure name resolution traffic.'
        });
        updateMatrixCell(els.mDns, 'UNSUPPORTED');
    }

    async function checkWebRtcStunLeak(httpIp) {
        if (!window.RTCPeerConnection) return;

        const stunServers = [{ urls: 'stun:stun.l.google.com:19302' }];
        let pc;
        let leakedIp = null;

        try {
            pc = new RTCPeerConnection({ iceServers: stunServers });
            pc.createDataChannel('stun-leak');
            pc.onicecandidate = event => {
                if (!event.candidate || !event.candidate.candidate) return;
                const parts = event.candidate.candidate.split(' ');
                const addr = parts[4] || '';
                if (!/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.|127\.|0\.)/.test(addr)
                    && !addr.endsWith('.local')
                    && /^[0-9.]+$/.test(addr)
                    && addr !== httpIp
                    && addr !== '') {
                    leakedIp = addr;
                }
            };
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            await sleep(2200);
        } catch (e) {
            // STUN engellenmiş veya kullanılamıyor
        } finally {
            if (pc) pc.close();
        }

        if (leakedIp) {
            applyFinding({
                module: 'WebRTC STUN Leak',
                category: 'Network & Infrastructure',
                status: 'EXPOSED',
                severity: 'critical',
                confidence: 0.95,
                penalty: 25,
                evidence: 'STUN revealed candidate ' + leakedIp + ' (different from HTTP IP ' + httpIp + ')',
                impactText: 'Real IP is leaking through WebRTC ICE candidates despite network-layer masking. Third parties can discover the underlying ISP-assigned address.',
                mitigation: 'Disable WebRTC entirely in browser settings, or use a VPN client with full WebRTC leak protection (kill-switch + filter rules).'
            });
        } else {
            applyFinding({
                module: 'WebRTC STUN Leak',
                category: 'Network & Infrastructure',
                status: 'PROTECTED',
                severity: 'low',
                confidence: 0.7,
                penalty: 0,
                evidence: 'STUN query did not reveal additional public IP candidates beyond the HTTP-visible address.',
                impactText: 'WebRTC is not leaking an additional public IP.',
                mitigation: 'No action required.'
            });
        }
    }

    async function checkGeoLocation() {
        const needsGeo = serverSignals.latitude === null || serverSignals.latitude === '';
        const needsIsp = !serverSignals.networkAsn && !serverSignals.networkOrg;

        if (needsGeo || needsIsp) {
            try {
                const res = await getGeoLookup();
                if (res && res.ok) {
                    if (needsGeo && res.country_code) {
                        serverSignals.country = res.country_code || serverSignals.country;
                        serverSignals.countryName = res.country || serverSignals.countryName;
                        serverSignals.city = res.city || serverSignals.city;
                        serverSignals.latitude = res.latitude !== undefined ? res.latitude : serverSignals.latitude;
                        serverSignals.longitude = res.longitude !== undefined ? res.longitude : serverSignals.longitude;
                        serverSignals.geoTimezone = res.timezone || serverSignals.geoTimezone;
                        serverSignals.geoAccuracy = res.city ? 'City-level' : 'Country-level (IP-based)';
                    }
                    if (needsIsp && (res.asn || res.org)) {
                        serverSignals.networkAsn = res.asn || '';
                        serverSignals.networkOrg = res.org || '';
                    }
                    serverSignals.geoSource = (res.sources && res.sources.length ? res.sources.join(' + ') : serverSignals.geoSource);
                    updateGeoDom();
                }
            } catch (e) {
                console.error('Geo lookup failed:', e);
            }
        }
    }

    async function checkLocalhostAndExtensions() {
        let mmStatus = 'NO_SIGNAL';
        let mmVal = 'MetaMask web resource inaccessible';
        try {
            await fetch('chrome-extension://nkbihfbeogaeaoehlefnkodbefgpgknn/img/metamask-fox.svg', { mode: 'no-cors' });
            mmStatus = 'EXPOSED';
            mmVal = 'Resource chrome-extension://... returned load success';
        } catch (e) {
            // Inaccessible
        }

        applyFinding({
            module: 'Extension Resource',
            status: mmStatus,
            severity: mmStatus === 'EXPOSED' ? 'medium' : 'low',
            confidence: 0.7,
            penalty: mmStatus === 'EXPOSED' ? 6 : 0,
            evidence: mmVal,
            impactText: mmStatus === 'EXPOSED' ? 'A browser extension resource was reachable from the page context.' : 'No target extension resource was reachable.',
            mitigation: mmStatus === 'EXPOSED' ? 'Keep sensitive extensions in a separate browser profile.' : 'No action required.'
        });

        const ports = [80, 443, 3000];
        const activePorts = [];
        await Promise.all(ports.map(port => new Promise(resolve => {
            const controller = new AbortController();
            const timer = setTimeout(() => {
                controller.abort();
                resolve();
            }, 150);
            
            fetch('http://127.0.0.1:' + port, { mode: 'no-cors', signal: controller.signal })
                .then(() => activePorts.push(port))
                .catch(() => {})
                .finally(() => {
                    clearTimeout(timer);
                    resolve();
                });
        })));

        const localStatus = activePorts.length > 0 ? 'EXPOSED' : 'NO_SIGNAL';
        applyFinding({
            module: 'Local Services',
            status: localStatus,
            severity: activePorts.length > 0 ? 'medium' : 'low',
            confidence: 0.8,
            penalty: activePorts.length > 0 ? 7 : 0,
            evidence: activePorts.length > 0 ? 'Responsive local ports=' + activePorts.join(', ') : 'No response on local ports 80, 443, or 3000',
            impactText: activePorts.length > 0 ? 'A page can infer that local development or desktop services are running.' : 'No common local service endpoint responded.',
            mitigation: activePorts.length > 0 ? 'Stop unused local services or keep sensitive tooling in a separate browser profile.' : 'No action required.'
        });
        updateMatrixCell(els.mLocalhost, localStatus);
    }

    async function checkLocalApps() {
        const apps = [
            { name: 'Discord', url: 'http://127.0.0.1:6463/', penalty: 10 },
            { name: 'Steam Client', url: 'http://127.0.0.1:57343/', penalty: 10 },
            { name: 'Tor SOCKS Relay', url: 'http://127.0.0.1:9050/', penalty: 10 }
        ];

        const detectedApps = [];

        await Promise.all(apps.map(async app => {
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), 150);
            try {
                await fetch(app.url, { mode: 'no-cors', signal: controller.signal });
                detectedApps.push(app.name);
            } catch (err) {
                if (err.name !== 'AbortError' && !err.message.includes('Failed to fetch')) {
                    detectedApps.push(app.name);
                }
            } finally {
                clearTimeout(id);
            }
        }));

        const status = detectedApps.length > 0 ? 'EXPOSED' : 'NO_SIGNAL';
        applyFinding({
            module: 'App Service Hints',
            status: status,
            severity: detectedApps.length > 0 ? 'medium' : 'low',
            confidence: 0.9,
            penalty: detectedApps.length > 0 ? 7 : 0,
            evidence: detectedApps.length > 0 ? 'Responsive app endpoints: ' + detectedApps.join(', ') : 'No response on Discord, Steam, or Tor local ports',
            impactText: detectedApps.length > 0 ? 'A page can infer selected desktop apps or local proxy services.' : 'No tested desktop app endpoint responded.',
            mitigation: detectedApps.length > 0 ? 'Close unused local app services before sensitive browsing.' : 'No action required.'
        });
        updateMatrixCell(els.mLocalApps, status);
    }
