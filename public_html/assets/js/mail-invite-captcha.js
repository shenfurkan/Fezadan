// @ts-check
// LiteCaptcha's native embedded bridge. Proof acceptance is server-side only.
(() => {
    const row = document.getElementById('mail-invite-captcha');
    const check = document.getElementById('litecaptcha-check');
    const status = document.getElementById('litecaptcha-status');
    const detail = document.getElementById('litecaptcha-detail');
    const frame = document.querySelector('.litecaptcha-bridge');
    const submit = document.getElementById('mail-invite-submit');
    const form = document.getElementById('mail-invite-form');
    const rtInput = document.getElementById('lc_rt');
    const sigInput = document.getElementById('lc_sig');
    const expInput = document.getElementById('lc_exp');
    if (!row || !(check instanceof HTMLInputElement) || !status || !detail
        || !(frame instanceof HTMLIFrameElement) || !(submit instanceof HTMLButtonElement)
        || !(form instanceof HTMLFormElement) || !(rtInput instanceof HTMLInputElement)
        || !(sigInput instanceof HTMLInputElement) || !(expInput instanceof HTMLInputElement)) return;

    const bridgeUrl = new URL(frame.dataset.src || '', location.href);
    const expected = new URL(row.dataset.return || '', location.href);
    if (!['https:', 'http:'].includes(bridgeUrl.protocol) || expected.origin !== location.origin) return;
    const origin = bridgeUrl.origin;
    /** @param {string} key */
    const label = key => row.dataset[key] || '';
    let ready = false, active = false, reload = false, escapePending = false, submitting = false;
    let required = 3, escapes = 0, lastEscapeAt = 0, startedAt = 0, lastRafAt = 0;
    let readyTimer = 0, expiryTimer = 0, rafId = 0, lastPointerAt = 0;
    /** @type {number[]} */
    let rafDeltas = [];
    /** @type {Array<{left: number, top: number, width: number, height: number, time: number}>} */
    let domRects = [];
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'litecaptcha-page-btn';
    button.hidden = true;
    document.body.appendChild(button);

    /** @param {Record<string, unknown>} message */
    const post = message => frame.contentWindow?.postMessage(message, origin);
    const viewport = () => ({ width: innerWidth, height: innerHeight });
    /** @param {string} text @param {string} hint @param {string} state */
    const setStatus = (text, hint, state) => {
        status.textContent = text;
        detail.textContent = hint;
        detail.hidden = !hint;
        row.classList.remove('is-active', 'is-done', 'is-error');
        row.classList.add(state);
    };
    /** @param {string} [hint] */
    const fail = (hint = label('error')) => {
        clearTimeout(readyTimer);
        clearTimeout(expiryTimer);
        cancelAnimationFrame(rafId);
        rtInput.value = sigInput.value = expInput.value = '';
        submit.disabled = true;
        check.checked = false;
        check.disabled = false;
        button.hidden = true;
        active = ready = escapePending = false;
        reload = true;
        setStatus('', hint, 'is-error');
    };
    function sampleRect() {
        if (button.hidden) return;
        const rect = button.getBoundingClientRect();
        domRects.push({ left: Math.round(rect.left), top: Math.round(rect.top),
            width: Math.round(rect.width), height: Math.round(rect.height),
            time: Math.round(performance.now() - startedAt) });
        domRects = domRects.slice(-24);
    }
    /** @param {number} now */
    function collectRaf(now) {
        if (!active) return;
        if (rafDeltas.length < 80) rafDeltas.push(Number(Math.max(0.1, now - lastRafAt).toFixed(2)));
        lastRafAt = now;
        rafId = requestAnimationFrame(collectRaf);
    }
    /** @param {number} x @param {number} y */
    function clamp(x, y) {
        return { x: Math.min(Math.max(0, innerWidth - button.offsetWidth), Math.max(0, x)),
            y: Math.min(Math.max(0, innerHeight - button.offsetHeight), Math.max(0, y)) };
    }
    /** @param {unknown} leftPct @param {unknown} topPct */
    function place(leftPct, topPct) {
        if (typeof leftPct !== 'number' || typeof topPct !== 'number'
            || !Number.isFinite(leftPct) || !Number.isFinite(topPct)) return;
        button.hidden = false;
        const width = button.offsetWidth, height = button.offsetHeight;
        // Match LiteCaptcha's robot_button_target() geometry on narrow screens.
        const margin = Math.max(16, Math.min(32, Math.round(Math.min(innerWidth, innerHeight) * 0.04)));
        const position = clamp(margin + leftPct / 100 * Math.max(0, innerWidth - width - margin * 2) - width / 2,
            margin + topPct / 100 * Math.max(0, innerHeight - height - margin * 2) - height / 2);
        button.style.left = position.x + 'px';
        button.style.top = position.y + 'px';
        sampleRect();
    }
    /** @param {number} x @param {number} y @param {number} stamp */
    function pointer(x, y, stamp) {
        if (!active || button.hidden) return;
        if (performance.now() - lastPointerAt < 16) return;
        lastPointerAt = performance.now();
        post({ type: 'lc:pointer', x, y, viewport: viewport(), timeStamp: stamp });
        if (escapes >= required || escapePending || performance.now() - lastEscapeAt < 260) return;
        const rect = button.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2, centerY = rect.top + rect.height / 2;
        const distance = Math.hypot(x - centerX, y - centerY);
        if (distance < 1 || distance >= 130) return;
        sampleRect();
        escapePending = true;
        lastEscapeAt = performance.now();
        post({ type: 'lc:escape', mouse: { x, y },
            button: { x: centerX, y: centerY, left: rect.left, top: rect.top, width: rect.width, height: rect.height },
            viewport: viewport(), distance, domRects });
    }
    /** @param {number} x @param {number} y @param {boolean} trusted @param {number} clickDetail */
    function finalClick(x, y, trusted, clickDetail) {
        if (!active || escapePending || escapes < required || button.disabled) return;
        sampleRect();
        const rect = button.getBoundingClientRect();
        button.disabled = true;
        post({ type: 'lc:final-click', viewport: viewport(),
            buttonRect: { left: Math.round(rect.left), top: Math.round(rect.top),
                width: Math.round(rect.width), height: Math.round(rect.height) },
            click: { x, y, detail: clickDetail || 1, isTrusted: trusted }, rafDeltas, domRects });
        setStatus(label('checking'), '', 'is-active');
    }
    button.addEventListener('click', event => {
        const rect = button.getBoundingClientRect();
        finalClick(event.detail ? event.clientX : rect.left + rect.width / 2,
            event.detail ? event.clientY : rect.top + rect.height / 2, event.isTrusted, event.detail);
    });
    button.addEventListener('touchend', event => {
        event.preventDefault();
        const touch = event.changedTouches[0];
        if (touch) finalClick(touch.clientX, touch.clientY, event.isTrusted, 1);
    }, { passive: false });
    document.addEventListener('mousemove', event => pointer(event.clientX, event.clientY, event.timeStamp), { passive: true });
    document.addEventListener('touchmove', event => {
        const touch = event.touches[0];
        if (touch) pointer(touch.clientX, touch.clientY, event.timeStamp);
    }, { passive: true });
    window.addEventListener('resize', () => {
        if (button.hidden) return;
        const rect = button.getBoundingClientRect(), position = clamp(rect.left, rect.top);
        button.style.left = position.x + 'px';
        button.style.top = position.y + 'px';
        sampleRect();
    }, { passive: true });

    /** @param {unknown} redirectUrl */
    const success = redirectUrl => {
        if (typeof redirectUrl !== 'string') return fail();
        let target;
        try { target = new URL(redirectUrl); } catch (_) { return fail(); }
        const rt = target.searchParams.get('rt') || '', sig = target.searchParams.get('sig') || '';
        const exp = target.searchParams.get('exp') || '';
        if (target.origin !== expected.origin || target.pathname !== expected.pathname
            || target.searchParams.get('lc_state') !== expected.searchParams.get('lc_state')
            || !/^[a-f0-9]{32}$/.test(rt) || !/^[a-f0-9]{64}$/.test(sig) || !/^[0-9]{10}$/.test(exp)
            || Number(exp) * 1000 <= Date.now() || Number(exp) * 1000 > Date.now() + 90000) return fail();
        rtInput.value = rt;
        sigInput.value = sig;
        expInput.value = exp;
        active = false;
        clearTimeout(readyTimer);
        clearTimeout(expiryTimer);
        cancelAnimationFrame(rafId);
        button.hidden = true;
        check.checked = true;
        check.disabled = true;
        submit.disabled = false;
        setStatus(label('verified'), '', 'is-done');
        expiryTimer = window.setTimeout(() => fail(label('expired')), Number(exp) * 1000 - Date.now());
    };
    check.addEventListener('change', () => {
        if (!check.checked || active) return;
        active = true;
        check.disabled = true;
        submit.disabled = true;
        escapes = 0;
        rafDeltas = [];
        domRects = [];
        startedAt = lastRafAt = performance.now();
        rafId = requestAnimationFrame(collectRaf);
        setStatus(ready ? label('checking') : label('preparing'), '', 'is-active');
        if (reload) {
            reload = false;
            frame.src = bridgeUrl.href;
        }
        readyTimer = window.setTimeout(() => fail(), 15000);
        if (ready) post({ type: 'lc:start' });
    });
    frame.addEventListener('error', () => fail());
    frame.addEventListener('load', () => post({ type: 'lc:diagnose' }));
    document.addEventListener('securitypolicyviolation', event => {
        if (event.disposition === 'enforce' && ['frame-src', 'child-src'].includes(event.effectiveDirective)
            && event.blockedURI.startsWith(origin)) fail();
    });
    window.addEventListener('message', event => {
        if (event.source !== frame.contentWindow || event.origin !== origin
            || !event.data || typeof event.data !== 'object' || Array.isArray(event.data)) return;
        /** @type {Record<string, unknown>} */
        const data = event.data;
        if (data.type === 'lc:ready') {
            ready = true;
            if (typeof data.requiredEscapes === 'number' && Number.isInteger(data.requiredEscapes)
                && data.requiredEscapes > 0 && data.requiredEscapes <= 10) required = data.requiredEscapes;
            if (active) post({ type: 'lc:start' });
        } else if (data.type === 'lc:failure') {
            fail();
        } else if (active && data.type === 'lc:started') {
            clearTimeout(readyTimer);
            button.textContent = label('catch');
            button.disabled = false;
            escapePending = false;
            setStatus('0/' + required, label('follow'), 'is-active');
            place(data.initLeftPct, data.initTopPct);
        } else if (active && data.type === 'lc:status') {
            if (typeof data.escapeCount !== 'number' || !Number.isInteger(data.escapeCount)
                || data.escapeCount < 0 || data.escapeCount > required) return;
            escapes = data.escapeCount;
            escapePending = false;
            button.textContent = label(escapes >= required ? 'verify' : 'catch');
            setStatus(escapes + '/' + required, label(escapes >= required ? 'final' : 'follow'), 'is-active');
            place(data.nextLeftPct, data.nextTopPct);
        } else if (active && data.type === 'lc:success') {
            success(data.redirectUrl);
        }
    });
    form.addEventListener('submit', event => {
        if (submitting || submit.disabled || Number(expInput.value) * 1000 <= Date.now()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!submitting) fail(label('expired'));
        } else {
            submitting = true;
            submit.disabled = true;
            clearTimeout(expiryTimer);
        }
    }, true);
    window.addEventListener('pageshow', event => {
        if (event.persisted) { submitting = false; fail(label('expired')); }
    });
    frame.src = bridgeUrl.href;
})();
