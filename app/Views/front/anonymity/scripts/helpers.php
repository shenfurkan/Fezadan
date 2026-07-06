<?php // Paylaşılan yardımcı, render, skor ve özet fonksiyonları. ?>
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function sha256(value) {
        const buffer = new TextEncoder().encode(value);
        const hash = await crypto.subtle.digest('SHA-256', buffer);
        return Array.from(new Uint8Array(hash)).map(b => b.toString(16).padStart(2, '0')).join('');
    }

    function shortHash(hash) {
        return hash ? hash.slice(0, 16) + '...' : 'unavailable';
    }

    function anonymityEndpoint(path) {
        const prefix = window.location.pathname.indexOf('/anonymitycheck') === 0 ? '/anonymitycheck' : '';
        return prefix + path;
    }

    async function fetchJsonWithTimeout(url, timeoutMs) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        try {
            const response = await fetch(url, {
                cache: 'no-store',
                credentials: 'omit',
                signal: controller.signal
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        } finally {
            clearTimeout(timeoutId);
        }
    }

    let geoLookupPromise = null;
    let geoLookupFailed = false;
    async function getGeoLookup() {
        // geo-lookup sunucu tarafında hız sınırlıdır; başarıyı paylaş, ancak başarısızlıktan sonra bir kez daha dene.
        if (!geoLookupPromise || geoLookupFailed) {
            geoLookupFailed = false;
            geoLookupPromise = fetchJsonWithTimeout(anonymityEndpoint('/geo-lookup'), 5000)
                .catch(error => {
                    geoLookupFailed = true;
                    throw error;
                });
        }
        return geoLookupPromise;
    }

    function getBrowserDetails() {
        const ua = navigator.userAgent;
        let browserName = "Unknown Browser";
        let osName = "Unknown OS";

        if (ua.indexOf("Win") !== -1) osName = "Windows";
        else if (ua.indexOf("Mac") !== -1) osName = "macOS";
        else if (ua.indexOf("Linux") !== -1) osName = "Linux";
        else if (ua.indexOf("Android") !== -1) osName = "Android";
        else if (ua.indexOf("like Mac") !== -1) osName = "iOS";

        if (ua.indexOf("Firefox") !== -1) browserName = "Firefox";
        else if (ua.indexOf("Chrome") !== -1) browserName = "Chrome";
        else if (ua.indexOf("Safari") !== -1) browserName = "Safari";
        else if (ua.indexOf("Edge") !== -1) browserName = "Edge";
        else if (ua.indexOf("OPR") !== -1 || ua.indexOf("Opera") !== -1) browserName = "Opera";

        return `${browserName} / ${osName}`;
    }

    function activateAperture() {
        if (!els.progressScreen) return;
        document.body.classList.add('progress-active');
        els.progressScreen.classList.remove('is-opening', 'is-active');
        els.progressScreen.style.display = 'block';
        requestAnimationFrame(() => {
            els.progressScreen.classList.add('is-opening');
            requestAnimationFrame(() => {
                els.progressScreen.classList.add('is-active');
            });
        });
    }

    function closeAperture() {
        if (!els.progressScreen) return;
        document.body.classList.remove('progress-active');
        els.progressScreen.classList.remove('is-opening', 'is-active');
        els.progressScreen.style.display = 'none';
    }

    function updateMatrixCell(el, status) {
        if (!el) return;
        el.dataset.status = String(status || '').toUpperCase();
        el.className = 'badge ' + statusClass(status);
        el.textContent = statusLabel(status);
    }

    function applyFinding(finding) {
        findings.push(finding);

        if (finding.penalty && String(finding.status).toUpperCase() !== 'THEORY') {
            score = Math.max(0, Math.round(score - (finding.penalty * (finding.confidence || 1))));
        }

        renderFindings();
        updateReportSummary();
    }

    function statusClass(status) {
        const key = String(status || '').toLowerCase();
        if (key === 'masked' || key === 'protected') return 'safe';
        if (key === 'review') return 'warning';
        return key || 'unsupported';
    }

    function statusLabel(status) {
        const labels = {
            NO_SIGNAL: 'Clear',
            SAFE: 'Clear',
            BLOCKED: 'Protected',
            PROTECTED: 'Protected',
            MASKED: 'Masked',
            EXPOSED: 'Exposed',
            WARNING: 'Review',
            REVIEW: 'Review',
            UNSUPPORTED: 'Not checked'
        };
        const key = String(status || '').toUpperCase();
        return labels[key] || key.replace(/_/g, ' ');
    }

    function severityRank(severity) {
        return { critical: 0, high: 1, medium: 2, low: 3 }[String(severity || '').toLowerCase()] ?? 4;
    }

    function renderFindings() {
        if (!els.findingsTbody) return;
        els.findingsTbody.innerHTML = '';

        const grouped = findings.reduce((acc, finding) => {
            const cat = finding.category || 'Other Findings';
            if (!acc[cat]) acc[cat] = [];
            acc[cat].push(finding);
            return acc;
        }, {});

        const order = ['Network & Infrastructure', 'Browser Fingerprinting', 'Local Network Probing', 'Security & Anti-Tracking', 'Other Findings'];

        order.forEach(cat => {
            if (!grouped[cat] || !grouped[cat].length) return;

            const header = document.createElement('div');
            header.className = 'findings-category-header';
            header.textContent = cat;
            els.findingsTbody.appendChild(header);

            grouped[cat]
                .sort((a, b) => severityRank(a.severity) - severityRank(b.severity))
                .forEach(finding => {
                    const card = document.createElement('div');
                    card.className = `finding-card severity-${String(finding.severity || 'low').toLowerCase()}`;
                    card.innerHTML = `
                        <div class="finding-meta">
                            <span class="finding-name">${escapeHtml(finding.module)}</span>
                            <span class="badge ${statusClass(finding.status)}">${escapeHtml(statusLabel(finding.status))}</span>
                        </div>
                        <div class="finding-impact-block">
                            <div class="finding-title-small">Observed evidence</div>
                            <div class="finding-evidence">${escapeHtml(finding.evidence || 'No signal')}</div>
                            <div class="finding-title-small" style="margin-top: 10px;">Meaning</div>
                            <div class="finding-text">${escapeHtml(finding.impactText || 'None')}</div>
                        </div>
                        <div class="finding-mitigation-block">
                            <div class="finding-title-small">Action</div>
                            <div class="finding-mitigation-text">${escapeHtml(finding.mitigation || 'No action required.')}</div>
                        </div>
                    `;
                    els.findingsTbody.appendChild(card);
                });
        });

        document.getElementById('findings-container').style.display = findings.length ? 'block' : 'none';
    }

    function hasTrustedEdgeHeaders() {
        const h = serverSignals.headers || {};
        return Boolean(
            h.HTTP_CF_RAY
            || h.HTTP_CF_CONNECTING_IP
            || h.HTTP_CF_VISITOR
            || h.HTTP_CF_IPCOUNTRY
            || String(serverSignals.geoSource || '').indexOf('Cloudflare') !== -1
        );
    }

    function updateReportSummary() {
        const priority = findings.filter(item => {
            const severity = String(item.severity || '').toLowerCase();
            const status = String(item.status || '').toUpperCase();
            return (severity === 'critical' || severity === 'high') && (status === 'EXPOSED' || status === 'REVIEW');
        }).length;
        const fingerprint = findings.filter(item => {
            const name = String(item.module || '');
            return /(Fingerprint|Canvas|WebGL|Audio|Fonts|Hardware|Locales)/i.test(name) && (item.penalty || 0) > 0;
        }).length;
        const edgeHeaders = hasTrustedEdgeHeaders();
        const network = serverSignals.isTor
            ? 'Tor'
            : (edgeHeaders ? 'CDN edge' : (serverSignals.proxyCount > 0 ? 'Proxy header' : 'Direct'));

        if (els.summaryPriority) els.summaryPriority.textContent = String(priority);
        if (els.summaryFingerprint) els.summaryFingerprint.textContent = String(fingerprint);
        if (els.summaryNetwork) els.summaryNetwork.textContent = network;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, ch => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[ch]));
    }
