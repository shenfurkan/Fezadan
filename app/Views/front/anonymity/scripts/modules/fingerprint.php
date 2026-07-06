<?php // Browser Fingerprinting test modules. ?>
    async function checkWebRtc() {
        if (!window.RTCPeerConnection) {
            applyFinding({
                module: 'WebRTC API',
            category: 'Browser Fingerprinting',
                status: 'UNSUPPORTED',
                severity: 'low',
                confidence: 1,
                penalty: 0,
                evidence: 'RTCPeerConnection constructor undefined',
                impactText: 'WebRTC interfaces are completely unavailable.',
                mitigation: 'No action required unless strict policy enforcement dictates disabling PeerConnection interfaces entirely.'
            });
            updateMatrixCell(els.mWebRtc, 'UNSUPPORTED');
            return;
        }

        const seen = new Set();
        const local = [];
        const mdns = [];
        const publicCandidates = [];
        let pc;

        try {
            pc = new RTCPeerConnection({ iceServers: [] });
            pc.createDataChannel('candidate-check');
            
            pc.onicecandidate = event => {
                if (!event.candidate || !event.candidate.candidate) return;
                const candidate = event.candidate.candidate;
                if (seen.has(candidate)) return;
                seen.add(candidate);
                
                const parts = candidate.split(' ');
                const addr = parts[4] || '';
                
                if (addr.endsWith('.local')) {
                    mdns.push(addr);
                } else if (/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.|127\.)/.test(addr)) {
                    local.push(addr);
                } else if (/^[0-9a-f:.]+$/i.test(addr)) {
                    publicCandidates.push(addr);
                }
            };
            
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            await sleep(950);
        } catch (error) {
            applyFinding({
                module: 'WebRTC API',
            category: 'Browser Fingerprinting',
                status: 'BLOCKED',
                severity: 'low',
                confidence: 0.9,
                penalty: 0,
                evidence: 'WebRTC ICE gathering blocked: ' + error.name,
                impactText: 'Execution policy or privacy extension prevented ICE candidate gathering.',
                mitigation: 'No action required unless strict policy enforcement dictates disabling PeerConnection interfaces entirely.'
            });
            updateMatrixCell(els.mWebRtc, 'BLOCKED');
            return;
        } finally {
            if (pc) pc.close();
        }

        if (local.length > 0 || publicCandidates.length > 0) {
            const status = 'EXPOSED';
            applyFinding({
                module: 'WebRTC Leak',
            category: 'Browser Fingerprinting',
                status: status,
                severity: 'high',
                confidence: 0.95,
                penalty: local.length > 0 ? 30 : 15,
                evidence: 'Exposed candidates: local=' + local.join(', ') + ' public=' + publicCandidates.join(', '),
                impactText: 'ICE gathering exposed local or direct routing candidates without using a third-party STUN server.',
                mitigation: 'Implement strict WebRTC IP handling policies or disable the PeerConnection API within your browser profile to prevent local candidate extraction.'
            });
            updateMatrixCell(els.mWebRtc, status);
        } else if (mdns.length > 0) {
            applyFinding({
                module: 'WebRTC Leak',
            category: 'Browser Fingerprinting',
                status: 'BLOCKED',
                severity: 'low',
                confidence: 0.9,
                penalty: 0,
                evidence: 'Masked hosts detected: mDNS=' + mdns.slice(0, 1).join(''),
                impactText: 'The browser uses mDNS hostnames instead of local IP addresses.',
                mitigation: 'Implement strict WebRTC IP handling policies or disable the PeerConnection API within your browser profile to prevent local candidate extraction.'
            });
            updateMatrixCell(els.mWebRtc, 'BLOCKED');
        } else {
            applyFinding({
                module: 'WebRTC Leak',
            category: 'Browser Fingerprinting',
                status: 'NO_SIGNAL',
                severity: 'low',
                confidence: 0.8,
                penalty: 0,
                evidence: 'No local candidates extracted from PeerConnection offer',
                impactText: 'No WebRTC network endpoint was visible to this page.',
                mitigation: 'Implement strict WebRTC IP handling policies or disable the PeerConnection API within your browser profile to prevent local candidate extraction.'
            });
            updateMatrixCell(els.mWebRtc, 'NO_SIGNAL');
        }
    }

    async function checkCanvas() {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = 360;
            canvas.height = 90;
            const ctx = canvas.getContext('2d');
            if (!ctx) throw new Error('Canvas 2D context unavailable');

            ctx.textBaseline = 'top';
            ctx.fillStyle = '#f60';
            ctx.fillRect(10, 10, 110, 36);
            ctx.fillStyle = '#069';
            ctx.font = "18px 'Space Grotesk', Arial";
            ctx.fillText('fezadan fingerprint sample', 14, 18);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('fezadan fingerprint sample', 16, 40);
            
            const canvasHash = await sha256(canvas.toDataURL());
            applyFinding({
                module: 'Canvas Fingerprint',
            category: 'Browser Fingerprinting',
                status: 'EXPOSED',
                severity: 'medium',
                confidence: 0.85,
            penalty: 8,
            evidence: 'hash=' + shortHash(canvasHash),
            impactText: 'Canvas output is stable enough to contribute to browser fingerprinting.',
            mitigation: 'Employ robust anti-fingerprinting browser extensions or profiles that intentionally introduce noise into Canvas API readbacks.'
            });
            updateMatrixCell(els.mCanvas, 'EXPOSED');
        } catch (error) {
            applyFinding({
                module: 'Canvas Fingerprint',
            category: 'Browser Fingerprinting',
                status: 'UNSUPPORTED',
                severity: 'low',
                confidence: 1,
                penalty: 0,
                evidence: 'Canvas rendering failed: ' + error.message,
                impactText: 'Browser environment does not support canvas element pixel analysis.',
                mitigation: 'Employ robust anti-fingerprinting browser extensions or profiles that intentionally introduce noise into Canvas API readbacks.'
            });
            updateMatrixCell(els.mCanvas, 'UNSUPPORTED');
        }
    }

    async function checkWebGL() {
        const glCanvas = document.createElement('canvas');
        const gl = glCanvas.getContext('webgl') || glCanvas.getContext('experimental-webgl');
        
        if (!gl) {
            applyFinding({
                module: 'WebGL Renderer',
            category: 'Browser Fingerprinting',
                status: 'UNSUPPORTED',
                severity: 'low',
                confidence: 1,
                penalty: 0,
                evidence: 'WebGL graphics context is not initialized',
                impactText: 'Hardware graphics APIs are unavailable or blocked.',
                mitigation: 'Configure browser settings to mask unprivileged WebGL rendering details, preventing hardware-level device identification.'
            });
            updateMatrixCell(els.mWebGL, 'UNSUPPORTED');
            return;
        }

        try {
            const ext = gl.getExtension('WEBGL_debug_renderer_info');
            const vendor = ext ? gl.getParameter(ext.UNMASKED_VENDOR_WEBGL) : gl.getParameter(gl.VENDOR);
            const renderer = ext ? gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER);
            const glHash = await sha256(vendor + '|' + renderer);
            
            const isEmulated = /swiftshader|llvmpipe|angle/i.test(renderer);
            const status = 'EXPOSED';

            applyFinding({
                module: 'WebGL Renderer',
            category: 'Browser Fingerprinting',
                status: status,
                severity: isEmulated ? 'high' : 'medium',
                confidence: 0.9,
            penalty: isEmulated ? 15 : 8,
            evidence: 'vendor=' + vendor + ' renderer=' + renderer + ' hash=' + shortHash(glHash),
            impactText: 'Graphics renderer details can identify the device class and driver stack.',
            mitigation: 'Configure browser settings to mask unprivileged WebGL rendering details, preventing hardware-level device identification.'
            });
            updateMatrixCell(els.mWebGL, status);
        } catch (error) {
            applyFinding({
                module: 'WebGL Renderer',
            category: 'Browser Fingerprinting',
                status: 'BLOCKED',
                severity: 'low',
                confidence: 0.9,
                penalty: 0,
                evidence: 'Error fetching extension arguments: ' + error.message,
                impactText: 'Access to unmasked hardware info is blocked by current configurations.',
                mitigation: 'Configure browser settings to mask unprivileged WebGL rendering details, preventing hardware-level device identification.'
            });
            updateMatrixCell(els.mWebGL, 'BLOCKED');
        }
    }

    async function checkAudio() {
        try {
            const OfflineCtx = window.OfflineAudioContext || window.webkitOfflineAudioContext;
            if (!OfflineCtx) throw new Error('OfflineAudioContext interface undefined');
            
            const ac = new OfflineCtx(1, 44100, 44100);
            const osc = ac.createOscillator();
            const compressor = ac.createDynamicsCompressor();
            
            osc.type = 'triangle';
            osc.frequency.value = 10000;
            compressor.threshold.value = -50;
            compressor.knee.value = 40;
            compressor.ratio.value = 12;
            
            osc.connect(compressor);
            compressor.connect(ac.destination);
            
            osc.start(0);
            const rendered = await ac.startRendering();
            const data = rendered.getChannelData(0).slice(0, 300);
            const audioHash = await sha256(Array.from(data).map(n => n.toFixed(6)).join(','));
            
            applyFinding({
                module: 'Audio Fingerprint',
            category: 'Browser Fingerprinting',
                status: 'EXPOSED',
                severity: 'medium',
                confidence: 0.75,
            penalty: 6,
            evidence: 'hash=' + shortHash(audioHash),
            impactText: 'Audio processing output can add a stable fingerprinting signal.',
            mitigation: 'Enable advanced browser fingerprint protection to restrict or randomize the output of the AudioContext interfaces.'
            });
            updateMatrixCell(els.mAudio, 'EXPOSED');
        } catch (error) {
            applyFinding({
                module: 'Audio Fingerprint',
            category: 'Browser Fingerprinting',
                status: 'BLOCKED',
                severity: 'low',
                confidence: 0.85,
                penalty: 0,
                evidence: error.message,
                impactText: 'Audio checks are restricted by policy or interface blocks.',
                mitigation: 'Enable advanced browser fingerprint protection to restrict or randomize the output of the AudioContext interfaces.'
            });
            updateMatrixCell(els.mAudio, 'BLOCKED');
        }
    }

    async function checkBrowserLocaleAndTime() {
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        els.endpointTimezone.textContent = tz;

        const screenDetails = `${screen.width}x${screen.height} (DPR: ${window.devicePixelRatio || 1})`;
        const cpuCores = navigator.hardwareConcurrency || 'hidden';
        const memory = navigator.deviceMemory || 'hidden';
        const lang = (navigator.languages || [navigator.language]).join(', ');

        const evidence = `tz=${tz} screen=${screenDetails} cores=${cpuCores} memory=${memory} lang=${lang}`;
        
        applyFinding({
            module: 'Hardware & Locales',
            category: 'Browser Fingerprinting',
            status: 'REVIEW',
            severity: 'low',
            confidence: 0.85,
            penalty: 4,
            evidence: evidence,
            impactText: 'Language, viewport, CPU count, and memory hints can make a browser profile more distinctive.',
            mitigation: 'Utilize generalized profiles that normalize language, CPU core counts, and viewport dimensions to blend in with common traffic.'
        });
        updateMatrixCell(els.mTimezone, 'REVIEW');

        const skew = Math.abs(Date.now() - serverSignals.serverTimeMs);
        const loopStart = performance.now();
        for (let i = 0; i < 150000; i++) Math.sin(i) * Math.cos(i);
        const loopDuration = Math.round(performance.now() - loopStart);

        applyFinding({
            module: 'Clock & Runtime',
            category: 'Browser Fingerprinting',
            status: skew > 4000 ? 'REVIEW' : 'NO_SIGNAL',
            severity: skew > 4000 ? 'medium' : 'low',
            confidence: 0.8,
            penalty: skew > 4000 ? 5 : 0,
            evidence: 'skew=' + skew + 'ms loopDuration=' + loopDuration + 'ms',
            impactText: skew > 4000 ? 'The browser clock differs noticeably from the server timestamp.' : 'No meaningful clock skew was observed.',
            mitigation: 'Ensure local system time is accurately synchronized via an NTP server to prevent temporal anomalies.'
        });

        const serverGeoTimezone = serverSignals.geoTimezone;
        if (serverGeoTimezone && tz && serverGeoTimezone !== tz && tz !== 'UTC' && serverGeoTimezone !== 'UTC') {
            applyFinding({
                module: 'Timezone Mismatch',
                category: 'Network & Infrastructure',
                status: 'REVIEW',
                severity: 'medium',
                confidence: 0.8,
                penalty: 8,
                evidence: 'browser_tz=' + tz + ' server_geo_tz=' + serverGeoTimezone,
                impactText: 'Browser timezone differs from the IP-based geographic timezone, suggesting VPN or proxy usage.',
                mitigation: 'If using a VPN, ensure browser timezone matches the VPN exit location, or disable timezone detection in browser privacy settings.'
            });
        }
    }

    async function checkUaClientHints() {
        if (!navigator.userAgentData) {
            applyFinding({
                module: 'UA Client Hints',
                category: 'Browser Fingerprinting',
                status: 'UNSUPPORTED',
                severity: 'low',
                confidence: 1,
                penalty: 0,
                evidence: 'navigator.userAgentData not available',
                impactText: 'User-Agent Client Hints API is not supported in this browser.',
                mitigation: 'Modern browsers use Client Hints; older ones rely on the User-Agent string alone.'
            });
            updateMatrixCell(els.mUach, 'UNSUPPORTED');
            return;
        }

        const b = navigator.userAgentData.brands || [];
        const brands = b.map(x => x.brand + '/' + x.version).join(', ');
        const mobile = navigator.userAgentData.mobile;
        const platform = navigator.userAgentData.platform;

        let evidence = 'brands=' + brands + ' mobile=' + mobile + ' platform=' + platform;
        let penalty = 4;

        try {
            const high = await navigator.userAgentData.getHighEntropyValues([
                'architecture', 'bitness', 'model', 'platformVersion', 'fullVersionList'
            ]);
            if (high) {
                evidence += ' arch=' + (high.architecture || '?') + ' bits=' + (high.bitness || '?') + ' model=' + (high.model || 'none') + ' platformVer=' + (high.platformVersion || '?');
                penalty = 10;
            }
        } catch (e) {
            // Yüksek entropi reddedildi
        }

        applyFinding({
            module: 'UA Client Hints',
            category: 'Browser Fingerprinting',
            status: 'EXPOSED',
            severity: penalty > 5 ? 'medium' : 'low',
            confidence: 0.85,
            penalty: penalty,
            evidence: evidence,
            impactText: 'Client Hints expose browser brand, platform, and optionally architecture/model, replacing the User-Agent header.',
            mitigation: 'Disable User-Agent Client Hints via browser flags or use a profile that restricts high-entropy hints.'
        });
        updateMatrixCell(els.mUach, 'EXPOSED');
    }

    async function checkScreenFingerprint() {
        const props = {
            colorDepth: screen.colorDepth,
            pixelDepth: screen.pixelDepth,
            availW: screen.availWidth,
            availH: screen.availHeight,
            innerW: window.innerWidth,
            innerH: window.innerHeight,
            outerW: window.outerWidth,
            outerH: window.outerHeight,
            orient: screen.orientation ? screen.orientation.type : 'unavailable',
            extended: screen.isExtended || false
        };

        const dimMismatch = Math.abs(props.outerH - props.innerH) > 120;
        const hasExtended = props.extended;
        const extraSignals = [dimMismatch, hasExtended].filter(Boolean).length;
        const penalty = 3 + extraSignals * 2;

        applyFinding({
            module: 'Screen Properties',
            category: 'Browser Fingerprinting',
            status: 'EXPOSED',
            severity: extraSignals > 0 ? 'medium' : 'low',
            confidence: 0.85,
            penalty: penalty,
            evidence: 'cd=' + props.colorDepth + ' pd=' + props.pixelDepth + ' viewport=' + props.innerW + 'x' + props.innerH + ' outer=' + props.outerW + 'x' + props.outerH + ' avail=' + props.availW + 'x' + props.availH + ' orient=' + props.orient,
            impactText: dimMismatch ? 'Browser chrome dimensions differ from viewport, adding toolbar fingerprint entropy.' : 'Screen properties contribute a modest amount of fingerprint entropy.',
            mitigation: 'Use a standard browser window size without developer toolbars or unusual aspect ratios.'
        });
        updateMatrixCell(els.mScreen, 'EXPOSED');
    }

    async function checkPluginsAndMime() {
        const plugins = Array.from(navigator.plugins || []);
        const mimeTypes = Array.from(navigator.mimeTypes || []);
        const names = plugins.map(p => p.name);
        const count = names.length;
        const penalty = Math.min(count, 10);

        applyFinding({
            module: 'Plugin & MIME Enumeration',
            category: 'Browser Fingerprinting',
            status: count > 0 ? 'EXPOSED' : 'NO_SIGNAL',
            severity: count > 3 ? 'medium' : 'low',
            confidence: 0.9,
            penalty: penalty,
            evidence: 'plugins=' + count + ' mimeTypes=' + mimeTypes.length + (count > 0 ? ' names=' + names.slice(0, 4).join(', ') + (count > 4 ? '...' : '') : ''),
            impactText: count > 3 ? 'A significant number of browser plugins are exposed, enabling strong device fingerprinting.' : 'Limited plugin surface is exposed.',
            mitigation: 'Disable unnecessary browser plugins or use a browser profile that blocks plugin enumeration.'
        });
        updateMatrixCell(els.mPlugins, count > 0 ? 'EXPOSED' : 'NO_SIGNAL');
    }

    async function checkConnectionFingerprint() {
        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (!conn) {
            applyFinding({
                module: 'Connection Info',
                category: 'Browser Fingerprinting',
                status: 'UNSUPPORTED',
                severity: 'low',
                confidence: 1,
                penalty: 0,
                evidence: 'NetworkInformation API unavailable',
                impactText: 'Network type and speed cannot be enumerated from this browser.',
                mitigation: 'N/A'
            });
            updateMatrixCell(els.mConnection, 'UNSUPPORTED');
            return;
        }

        const evidence = 'type=' + (conn.effectiveType || '?') + ' downlink=' + (conn.downlink != null ? conn.downlink + 'Mbps' : '?') + ' rtt=' + (conn.rtt != null ? conn.rtt + 'ms' : '?') + ' saveData=' + (conn.saveData || false);

        applyFinding({
            module: 'Connection Info',
            category: 'Browser Fingerprinting',
            status: 'EXPOSED',
            severity: 'low',
            confidence: 0.8,
            penalty: 4,
            evidence: evidence,
            impactText: 'Network bandwidth and latency estimates can distinguish connection types (4G, WiFi, Ethernet).',
            mitigation: 'Use browsers that restrict the NetworkInformation API or disable it via flags.'
        });
        updateMatrixCell(els.mConnection, 'EXPOSED');
    }

    async function checkSpeechVoices() {
        if (!window.speechSynthesis) {
            updateMatrixCell(els.mSpeech, 'UNSUPPORTED');
            return;
        }

        let voices = speechSynthesis.getVoices();
        if (!voices || !voices.length) {
            await new Promise(resolve => {
                speechSynthesis.onvoiceschanged = () => {
                    voices = speechSynthesis.getVoices();
                    resolve();
                };
                setTimeout(resolve, 800);
            });
        }

        if (!voices || !voices.length) {
            updateMatrixCell(els.mSpeech, 'UNSUPPORTED');
            return;
        }

        const langs = [...new Set(voices.map(v => v.lang))];
        const count = voices.length;

        applyFinding({
            module: 'Speech Voices',
            category: 'Browser Fingerprinting',
            status: 'EXPOSED',
            severity: 'low',
            confidence: 0.7,
            penalty: Math.min(Math.floor(count / 3), 5),
            evidence: 'voices=' + count + ' langs=' + langs.slice(0, 5).join(', ') + (langs.length > 5 ? '...' : ''),
            impactText: 'Installed speech synthesis voices correlate with OS language packs and can distinguish users.',
            mitigation: 'Disable speech synthesis in high-privacy browser profiles.'
        });
        updateMatrixCell(els.mSpeech, 'EXPOSED');
    }

    async function checkMathPrecision() {
        const vec = {};
        const val = Math.sin(1e100);
        vec.sin1e100 = isNaN(val) ? 'NaN' : Number.isFinite(val) ? val.toExponential(12) : 'overflow';
        const val2 = Math.tan(-1e100);
        vec.tanNeg1e100 = isNaN(val2) ? 'NaN' : Number.isFinite(val2) ? val2.toExponential(12) : 'overflow';
        const val3 = Math.exp(1000);
        vec.exp1000 = isNaN(val3) ? 'NaN' : Number.isFinite(val3) ? val3.toExponential(12) : 'overflow';

        applyFinding({
            module: 'Math Precision',
            category: 'Browser Fingerprinting',
            status: 'REVIEW',
            severity: 'low',
            confidence: 0.5,
            penalty: 2,
            evidence: 'sin=' + vec.sin1e100 + ' tan=' + vec.tanNeg1e100 + ' exp=' + vec.exp1000,
            impactText: 'Floating-point precision varies across CPU architectures and JS engines, adding subtle fingerprint entropy.',
            mitigation: 'Standard JS rounding makes this a weak signal; use consistent hardware profiles if concerned.'
        });
    }

    async function checkHeaderAnalysis() {
        const h = serverSignals.headers || {};
        const acceptLang = h['HTTP_ACCEPT_LANGUAGE'] || '';
        const primaryLang = (acceptLang.split(',')[0] || '').split(';')[0].trim().split('-')[0].toLowerCase();

        const langToCc = { tr: 'TR', en: 'US', de: 'DE', fr: 'FR', es: 'ES', it: 'IT', ru: 'RU', ja: 'JP', zh: 'CN', ar: 'SA', pt: 'BR', nl: 'NL', pl: 'PL', sv: 'SE' };

        const hasSecChUa = 'HTTP_SEC_CH_UA' in h;
        const hasSecChUaPlat = 'HTTP_SEC_CH_UA_PLATFORM' in h;
        const hasSecChUaMobile = 'HTTP_SEC_CH_UA_MOBILE' in h;
        const upgradeInsecure = h['HTTP_UPGRADE_INSECURE_REQUESTS'] === '1';
        const acceptEnc = h['HTTP_ACCEPT_ENCODING'] || '';
        const hasBrotli = acceptEnc.includes('br');
        const hasZstd = acceptEnc.includes('zstd');

        const signals = [];
        if (hasSecChUa) signals.push('Sec-CH-UA');
        if (upgradeInsecure) signals.push('Upgrade-Insecure');
        if (hasBrotli) signals.push('Brotli');
        if (hasZstd) signals.push('Zstd');

        let penalty = 0;
        const serverCountry = serverSignals.country;
        if (serverCountry && langToCc[primaryLang] && langToCc[primaryLang] !== serverCountry) {
            signals.push('Lang-Geo mismatch (' + primaryLang + ' vs ' + serverCountry + ')');
            penalty += 5;
        }
        if (hasSecChUa) penalty += hasSecChUaPlat ? 3 : 1;
        if (hasBrotli) penalty += 1;

        const status = (penalty > 0 || signals.length > 0) ? 'REVIEW' : 'NO_SIGNAL';

        applyFinding({
            module: 'HTTP Header Profile',
            category: 'Browser Fingerprinting',
            status: status,
            severity: penalty > 3 ? 'medium' : 'low',
            confidence: 0.8,
            penalty: penalty,
            evidence: signals.length > 0 ? signals.join(', ') : 'Standard header set, no mismatches',
            impactText: serverCountry && langToCc[primaryLang] && langToCc[primaryLang] !== serverCountry
                ? 'Accept-Language browser preference mismatches the IP location, which may indicate VPN/proxy usage.'
                : 'HTTP headers reveal browser and network configuration details.',
            mitigation: 'Standardize HTTP headers or use a privacy-focused browser profile.'
        });
        updateMatrixCell(els.mHeaders, status);
    }

    async function checkMediaQueries() {
        const tests = {
            'dark-mode': '(prefers-color-scheme: dark)',
            'reduced-motion': '(prefers-reduced-motion: reduce)',
            'high-contrast': '(prefers-contrast: more)',
            'reduced-transparency': '(prefers-reduced-transparency: reduce)',
            'forced-colors': '(forced-colors: active)',
            'inverted-colors': '(inverted-colors: inverted)',
            'p3-gamut': '(color-gamut: p3)',
            'rec2020-gamut': '(color-gamut: rec2020)',
            'high-dynamic-range': '(dynamic-range: high)',
        };

        const active = [];
        for (const [name, query] of Object.entries(tests)) {
            if (matchMedia(query).matches) active.push(name);
        }

        const status = active.length > 0 ? 'EXPOSED' : 'NO_SIGNAL';

        applyFinding({
            module: 'Media Query Profile',
            category: 'Browser Fingerprinting',
            status: status,
            severity: 'low',
            confidence: 0.75,
            penalty: Math.min(active.length, 6),
            evidence: active.length > 0 ? 'Active: ' + active.join(', ') : 'No special media features detected',
            impactText: active.length > 0 ? 'OS-level display and accessibility preferences are readable via CSS media queries.' : 'No detectable OS-level media preferences.',
            mitigation: 'Use browser privacy modes that normalize CSS media query responses.'
        });
        updateMatrixCell(els.mMedia, status);
    }

    async function checkTouchAndInput() {
        const Touch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const pts = navigator.maxTouchPoints || 0;
        const coarse = matchMedia('(pointer: coarse)').matches;
        const fine = matchMedia('(pointer: fine)').matches;
        const hover = matchMedia('(hover: hover)').matches;

        const hit = [Touch, coarse, hover].filter(Boolean).length;

        applyFinding({
            module: 'Touch & Input',
            category: 'Browser Fingerprinting',
            status: 'REVIEW',
            severity: 'low',
            confidence: 0.7,
            penalty: hit >= 2 ? 3 : 0,
            evidence: 'touch=' + Touch + ' points=' + pts + ' coarse=' + coarse + ' fine=' + fine + ' hover=' + hover,
            impactText: 'Input modality (touch, pointer, hover) can indicate mobile vs desktop class.',
            mitigation: 'Spoof input modality if strong fingerprint masking is required.'
        });
        updateMatrixCell(els.mInput, 'REVIEW');
    }

    async function checkFonts() {
        const testFonts = [
            'Consolas', 'Segoe UI', 'Calibri', 'Cambria', 'MS Trebuchet',
            'Helvetica Neue', 'Monaco', 'Geneva', 'Chalkboard',
            'Liberation Mono', 'Ubuntu',
            'Impact', 'Comic Sans MS', 'Arial Black'
        ];

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            updateMatrixCell(els.mFonts, 'UNSUPPORTED');
            return;
        }

        const testString = "mmmmmmmmmmlli";
        ctx.font = "72px serif";
        const baseWidth = ctx.measureText(testString).width;

        const detectedFonts = [];

        testFonts.forEach(font => {
            ctx.font = `72px "${font}", serif`;
            const width = ctx.measureText(testString).width;
            if (width !== baseWidth) {
                detectedFonts.push(font);
            }
        });

        const status = detectedFonts.length > 0 ? 'EXPOSED' : 'NO_SIGNAL';
        applyFinding({
            module: 'Installed Fonts',
            status: status,
            severity: 'low',
            confidence: 0.95,
            penalty: detectedFonts.length > 5 ? 8 : 0,
            evidence: detectedFonts.length > 0 ? 'Detected ' + detectedFonts.length + ' system fonts: ' + detectedFonts.slice(0, 5).join(', ') + (detectedFonts.length > 5 ? '...' : '') : 'No custom operating system fonts detected',
            impactText: 'System fonts reveal typography packages, identifying target operating system environment.',
            mitigation: 'Use generic system font replacement in privacy browser profiles.'
        });
        updateMatrixCell(els.mFonts, status);
    }

    async function updateGeoDom() {
        const countryDisplay = serverSignals.countryName
            ? serverSignals.countryName + (serverSignals.country && serverSignals.country !== serverSignals.countryName ? ' (' + serverSignals.country + ')' : '')
            : (serverSignals.country || 'Unavailable');
        const cityDisplay = serverSignals.city || 'Unavailable';
        const networkDisplay = serverSignals.networkOrg
            ? serverSignals.networkOrg + (serverSignals.networkAsn ? ' / ' + serverSignals.networkAsn : '')
            : (serverSignals.networkAsn || 'Not exposed');

        const countryEls = document.querySelectorAll('.geo-details-item .param-value');
        for (const el of countryEls) {
            const label = el.parentElement.querySelector('.param-label');
            if (label && label.textContent.trim() === 'Country') el.textContent = countryDisplay;
        }

        const cityEls = document.querySelectorAll('.geo-details-item .param-value');
        for (const el of cityEls) {
            const label = el.parentElement.querySelector('.param-label');
            if (label && label.textContent.trim() === 'City') el.textContent = cityDisplay;
        }

        const ispEls = document.querySelectorAll('.param-list .param-item');
        for (const row of ispEls) {
            const label = row.querySelector('.param-label');
            if (label && label.textContent.trim() === 'ISP / ASN') {
                const valEl = row.querySelector('.param-value');
                if (valEl) valEl.textContent = networkDisplay;
                break;
            }
        }

        const sourceTag = document.getElementById('geo-source-tag');
        if (sourceTag && serverSignals.geoSource
                && serverSignals.geoSource !== 'Cloudflare'
                && serverSignals.geoSource !== 'Unavailable') {
            sourceTag.textContent = 'Source: ' + serverSignals.geoSource;
            sourceTag.style.display = 'block';
        } else if (sourceTag) {
            sourceTag.style.display = 'none';
        }
    }
