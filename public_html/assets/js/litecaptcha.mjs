export class LiteCaptchaWidget {
    constructor(options) {
        this.containerSelector = options.containerSelector || '#litecaptcha-check-row';
        this.statusSelector = options.statusSelector || '#litecaptcha-status';
        this.detailSelector = options.detailSelector || '#litecaptcha-detail';
        this.checkboxSelector = options.checkboxSelector || '#litecaptcha-check';
        this.iframeSelector = options.iframeSelector || '.litecaptcha-bridge';
        this.onSuccess = options.onSuccess || (() => {});
        this.onFailure = options.onFailure || (() => {});

        this.rowEl = document.querySelector(this.containerSelector);
        this.statusEl = document.querySelector(this.statusSelector);
        this.detailEl = document.querySelector(this.detailSelector);
        this.checkEl = document.querySelector(this.checkboxSelector);
        this.frameEl = document.querySelector(this.iframeSelector);

        this.origin = '*';
        if (this.frameEl) {
            try {
                this.origin = new URL(this.frameEl.getAttribute('src') || '', window.location.href).origin;
            } catch (e) {
                this.origin = '*';
            }
        }

        this.ready = false;
        this.active = false;
        this.startRequested = false;
        this.readyTimer = null;
        this.requiredEscapes = 3;
        this.escapes = 0;
        this.lastEscapeAt = 0;
        this.catchBtn = null;
        this.rafDeltas = [];
        this.domRects = [];
        this.lastRafAt = performance.now();
        this.startedAt = performance.now();

        this._collectRaf = this._collectRaf.bind(this);
        this._handleMessage = this._handleMessage.bind(this);
        this._handleMouseMove = this._handleMouseMove.bind(this);
        this._handleTouchMove = this._handleTouchMove.bind(this);
        this._handleResize = this._handleResize.bind(this);
        this._handleCheckbox = this._handleCheckbox.bind(this);

        this.init();
    }

    init() {
        if (!this.rowEl || !this.checkEl || !this.frameEl) return;

        requestAnimationFrame(this._collectRaf);

        window.addEventListener('message', this._handleMessage);
        window.addEventListener('resize', this._handleResize, { passive: true });
        document.addEventListener('mousemove', this._handleMouseMove, { passive: true });
        document.addEventListener('touchmove', this._handleTouchMove, { passive: true });

        this.checkEl.addEventListener('change', this._handleCheckbox);

        this.frameEl.addEventListener('error', () => {
            this.fail('LITECAPTCHA_IFRAME_LOAD_ERROR', 'Iframe failed to load. Check domain and CSP settings.');
        });
    }

    _collectRaf(now) {
        if (this.active && this.rafDeltas.length < 80) {
            this.rafDeltas.push(Number(Math.max(0.1, now - this.lastRafAt).toFixed(2)));
        }
        this.lastRafAt = now;
        requestAnimationFrame(this._collectRaf);
    }

    setStatus(label, detailText, stateClass) {
        if (this.statusEl) this.statusEl.textContent = label;
        if (this.detailEl && detailText) this.detailEl.textContent = detailText;
        if (this.rowEl && stateClass) {
            this.rowEl.classList.remove('is-active', 'is-done', 'is-error');
            this.rowEl.classList.add(stateClass);
        }
    }

    post(message) {
        if (this.frameEl && this.frameEl.contentWindow) {
            this.frameEl.contentWindow.postMessage(message, this.origin);
        }
    }

    sampleBtnRect() {
        if (!this.catchBtn || this.catchBtn.style.display === 'none') return;
        const rect = this.catchBtn.getBoundingClientRect();
        this.domRects.push({
            left: Math.round(rect.left),
            top: Math.round(rect.top),
            width: Math.round(rect.width),
            height: Math.round(rect.height),
            time: Math.round(performance.now() - this.startedAt)
        });
        this.domRects = this.domRects.slice(-24);
    }

    fail(reason, detail) {
        if (!this.active || this.ready) return;
        if (this.readyTimer) {
            clearTimeout(this.readyTimer);
            this.readyTimer = null;
        }
        this.setStatus('Failed', detail || ('Reason: ' + reason), 'is-error');
        if (this.checkEl) {
            this.checkEl.checked = false;
            this.checkEl.disabled = false;
        }
        this.active = false;
        this.startRequested = false;
        if (this.catchBtn) this.catchBtn.style.display = 'none';
        this.onFailure(reason);
    }

    handleSuccess(data) {
        if (this.catchBtn) this.catchBtn.style.display = 'none';
        if (!data.redirectUrl) {
            this.fail('REDIRECT_MISSING', 'Verification completed but no token URL received.');
            return;
        }

        let target;
        try {
            target = new URL(data.redirectUrl, window.location.href);
        } catch (error) {
            this.fail('BAD_REDIRECT_URL', 'Verification URL format invalid.');
            return;
        }

        const rt  = target.searchParams.get('rt')  || '';
        const sig = target.searchParams.get('sig') || '';
        const exp = target.searchParams.get('exp') || '';

        if (!rt || !sig || !exp) {
            this.fail('MISSING_TOKEN_PARAMS', 'Token parameters not found in redirect URL.');
            return;
        }

        this.setStatus('Verified', 'Verification complete.', 'is-done');
        this.onSuccess({ rt, sig, exp });
    }

    requestStart() {
        this.startRequested = true;
        if (!this.readyTimer) {
            this.readyTimer = setTimeout(() => {
                this.fail('LITECAPTCHA_IFRAME_NOT_READY', 'LiteCaptcha did not respond in time.');
            }, 8000);
        }
        if (this.ready) {
            this.post({ type: 'lc:start' });
        }
    }

    createCatchBtn() {
        if (this.catchBtn) return this.catchBtn;
        this.catchBtn = document.createElement('button');
        this.catchBtn.type = 'button';
        this.catchBtn.className = 'litecaptcha-page-btn';
        this.catchBtn.textContent = 'Catch';
        this.catchBtn.style.display = 'none';
        document.body.appendChild(this.catchBtn);

        const fireClick = (clientX, clientY) => {
            const rect = this.catchBtn.getBoundingClientRect();
            this.sampleBtnRect();
            this.post({
                type: 'lc:final-click',
                viewport: { width: window.innerWidth, height: window.innerHeight },
                buttonRect: {
                    left: Math.round(rect.left),
                    top: Math.round(rect.top),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                },
                click: { x: clientX, y: clientY, detail: 1, isTrusted: true },
                rafDeltas: this.rafDeltas.slice(-80),
                domRects: this.domRects.slice(-24)
            });
        };

        this.catchBtn.addEventListener('click', e => fireClick(e.clientX, e.clientY));
        this.catchBtn.addEventListener('touchend', e => {
            e.preventDefault();
            const touch = e.changedTouches[0];
            if (touch) fireClick(touch.clientX, touch.clientY);
        }, { passive: false });

        return this.catchBtn;
    }

    getBtnBounds(btn) {
        const margin = Math.max(16, Math.min(32, Math.round(Math.min(window.innerWidth, window.innerHeight) * 0.04)));
        const width  = Math.max(1, btn.offsetWidth  || 112);
        const height = Math.max(1, btn.offsetHeight ||  52);
        return {
            minX: margin,
            minY: margin,
            maxX: Math.max(margin, window.innerWidth  - width  - margin),
            maxY: Math.max(margin, window.innerHeight - height - margin),
            width, height
        };
    }

    clampBtnPos(btn, x, y) {
        const bounds = this.getBtnBounds(btn);
        return {
            x: Math.min(bounds.maxX, Math.max(bounds.minX, x)),
            y: Math.min(bounds.maxY, Math.max(bounds.minY, y))
        };
    }

    getExpectedCoords(leftPct, topPct) {
        const btn = this.createCatchBtn();
        const bounds = this.getBtnBounds(btn);
        const expectedX = bounds.minX + (leftPct / 100.0) * (bounds.maxX - bounds.minX);
        const expectedY = bounds.minY + (topPct / 100.0) * (bounds.maxY - bounds.minY);
        const left = expectedX - bounds.width / 2;
        const top = expectedY - bounds.height / 2;
        return this.clampBtnPos(btn, left, top);
    }

    placeBtn(leftPct, topPct) {
        const btn = this.createCatchBtn();
        btn.style.display = 'block';
        if (typeof leftPct !== 'number' || typeof topPct !== 'number') {
            const bounds = this.getBtnBounds(btn);
            const x = bounds.minX + Math.random() * Math.max(1, bounds.maxX - bounds.minX);
            const y = bounds.minY + Math.random() * Math.max(1, bounds.maxY - bounds.minY);
            const pos = this.clampBtnPos(btn, x, y);
            btn.style.left = pos.x + 'px';
            btn.style.top  = pos.y + 'px';
        } else {
            const pos = this.getExpectedCoords(leftPct, topPct);
            btn.style.left = pos.x + 'px';
            btn.style.top  = pos.y + 'px';
        }
        this.sampleBtnRect();
    }

    moveBtn(pointerX, pointerY) {
        if (!this.catchBtn || this.escapes >= this.requiredEscapes) return;
        const now = performance.now();
        if (now - this.lastEscapeAt < 260) return;
        const rect = this.catchBtn.getBoundingClientRect();
        this.sampleBtnRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top  + rect.height / 2;
        const distance = Math.hypot(pointerX - centerX, pointerY - centerY);
        const triggerRadius = window.matchMedia('(pointer: coarse)').matches ? 160 : 130;
        if (distance >= triggerRadius) return;

        this.lastEscapeAt = now;
        this.escapes += 1;
        this.setStatus(this.escapes + '/' + this.requiredEscapes, 'Tracking movement, follow the button.', 'is-active');

        this.post({
            type: 'lc:escape',
            mouse: { x: pointerX, y: pointerY },
            button: { x: centerX, y: centerY, left: rect.left, top: rect.top, width: rect.width, height: rect.height },
            viewport: { width: window.innerWidth, height: window.innerHeight },
            distance,
            domRects: this.domRects.slice(-24)
        });

        if (this.escapes >= this.requiredEscapes) {
            this.setStatus('Checking', 'Server is verifying escape signals.', 'is-active');
        }
    }

    _handleMouseMove(e) {
        if (!this.active) return;
        this.post({ type: 'lc:pointer', x: e.clientX, y: e.clientY, viewport: { width: window.innerWidth, height: window.innerHeight } });
        this.moveBtn(e.clientX, e.clientY);
    }

    _handleTouchMove(e) {
        if (!this.active) return;
        const touch = e.touches[0];
        if (!touch) return;
        this.post({ type: 'lc:pointer', x: touch.clientX, y: touch.clientY, viewport: { width: window.innerWidth, height: window.innerHeight } });
        this.moveBtn(touch.clientX, touch.clientY);
    }

    _handleResize() {
        if (!this.catchBtn || this.catchBtn.style.display === 'none') return;
        const rect = this.catchBtn.getBoundingClientRect();
        const pos = this.clampBtnPos(this.catchBtn, rect.left, rect.top);
        this.catchBtn.style.left = pos.x + 'px';
        this.catchBtn.style.top  = pos.y + 'px';
        this.sampleBtnRect();
    }

    _handleCheckbox() {
        if (!this.checkEl.checked || this.active) return;
        this.active = true;
        this.checkEl.disabled = true;
        this.rafDeltas = [];
        this.domRects = [];
        this.lastRafAt = performance.now();
        this.startedAt = performance.now();
        this.setStatus(
            this.ready ? 'Checking' : 'Preparing',
            'Browser and behavior signals are being verified.',
            'is-active'
        );
        this.requestStart();
    }

    _handleMessage(e) {
        if (this.frameEl && e.source !== this.frameEl.contentWindow) return;
        if (this.origin !== '*' && e.origin !== this.origin) return;

        const data = e.data || {};
        if (typeof data.type !== 'string' || !data.type.startsWith('lc:')) return;

        if (data.type === 'lc:ready') {
            this.ready = true;
            if (this.readyTimer) {
                clearTimeout(this.readyTimer);
                this.readyTimer = null;
            }
            this.requiredEscapes = Number(data.requiredEscapes || 3);
            if (this.startRequested) {
                this.setStatus('Checking', 'Button will appear on the page shortly.', 'is-active');
                this.post({ type: 'lc:start' });
            }
        } else if (data.type === 'lc:started') {
            this.requiredEscapes = Number(data.requiredEscapes || this.requiredEscapes);
            this.setStatus('0/' + this.requiredEscapes, 'Tap or move near the button to chase it, then tap it when it stops.', 'is-active');
            this.placeBtn(data.initLeftPct, data.initTopPct);
        } else if (data.type === 'lc:status') {
            const escCount = Number(data.escapeCount || 0);
            if (escCount > 0) this.escapes = escCount;
            const label  = data.label || (escCount + '/' + this.requiredEscapes);
            const detail = escCount >= this.requiredEscapes
                ? 'Final step: tap the button to complete verification.'
                : 'Keep chasing the button — tap or swipe near it.';
            this.setStatus(label, detail, 'is-active');
            if (escCount >= this.requiredEscapes && this.catchBtn) {
                this.catchBtn.textContent = 'Verify ✓';
            }
            if (this.catchBtn && typeof data.nextLeftPct === 'number' && typeof data.nextTopPct === 'number') {
                const pos = this.getExpectedCoords(data.nextLeftPct, data.nextTopPct);
                this.catchBtn.style.left = pos.x + 'px';
                this.catchBtn.style.top  = pos.y + 'px';
                window.setTimeout(() => this.sampleBtnRect(), 90);
            }
        } else if (data.type === 'lc:success') {
            this.handleSuccess(data);
        } else if (data.type === 'lc:failure') {
            this.fail(data.reason || 'REJECTED', 'Refresh the page and try again.');
        }
    }
}
