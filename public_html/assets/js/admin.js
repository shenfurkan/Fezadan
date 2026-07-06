(function (window, document) {
    'use strict';

    var toastRoot = null;
    var toastId = 0;
    var handlingGlobalError = false;

    function ensureStyles() {
        if (document.getElementById('fezadan-toast-style')) return;
        var style = document.createElement('style');
        style.id = 'fezadan-toast-style';
        style.textContent = [
            '.fezadan-toast-root{position:fixed;right:1rem;bottom:1rem;z-index:2147483000;display:flex;flex-direction:column;gap:.65rem;max-width:min(26rem,calc(100vw - 2rem));pointer-events:none;}',
            '.fezadan-toast{pointer-events:auto;border:2px solid var(--line-color,#6D2323);background:var(--bg-paper,#FEF9E1);color:var(--text-main,#6D2323);box-shadow:6px 6px 0 rgba(0,0,0,.16);padding:.85rem 1rem;font-family:JetBrains Mono,ui-monospace,monospace;font-size:.78rem;line-height:1.45;display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:start;opacity:0;transform:translateY(8px);transition:opacity .16s ease,transform .16s ease;}',
            '.fezadan-toast.is-visible{opacity:1;transform:translateY(0);}',
            '.fezadan-toast[data-kind="success"]{border-color:#276749;background:#edf7ed;color:#1f5135;}',
            '.fezadan-toast[data-kind="error"]{border-color:var(--text-accent,#A31D1D);background:#fff1f1;color:#8a1717;}',
            '.fezadan-toast[data-kind="warning"]{border-color:#b7791f;background:#fff8db;color:#6f4b11;}',
            '.fezadan-toast[data-kind="info"]{border-color:var(--line-color,#6D2323);}',
            '.fezadan-toast strong{display:block;font-family:Syne,system-ui,sans-serif;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;}',
            '.fezadan-toast button{appearance:none;border:0;background:transparent;color:inherit;font:inherit;font-weight:800;cursor:pointer;padding:0 .1rem;line-height:1;}',
            '.fezadan-import-error{border:2px dashed var(--text-accent,#A31D1D);padding:1rem;background:rgba(163,29,29,.08);color:var(--text-accent,#A31D1D);font-family:JetBrains Mono,ui-monospace,monospace;font-size:.78rem;margin:1rem 0;}',
            '.fezadan-import-error strong{display:block;font-family:Syne,system-ui,sans-serif;text-transform:uppercase;margin-bottom:.35rem;}',
            '@media (max-width:640px){.fezadan-toast-root{left:1rem;right:1rem;bottom:1rem;max-width:none}.fezadan-toast{box-shadow:4px 4px 0 rgba(0,0,0,.16);}}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function ensureRoot() {
        if (toastRoot) return toastRoot;
        ensureStyles();
        toastRoot = document.getElementById('fezadan-toast-root');
        if (!toastRoot) {
            toastRoot = document.createElement('div');
            toastRoot.id = 'fezadan-toast-root';
            toastRoot.className = 'fezadan-toast-root';
            toastRoot.setAttribute('aria-live', 'polite');
            toastRoot.setAttribute('aria-atomic', 'true');
            document.body.appendChild(toastRoot);
        }
        return toastRoot;
    }

    function titleFor(kind) {
        return ({ success: 'Başarılı', error: 'Hata', warning: 'Uyarı', info: 'Bilgi' })[kind] || 'Bilgi';
    }

    function showToast(kind, message, options) {
        options = options || {};
        if (!message) return null;
        var root = ensureRoot();
        var toast = document.createElement('div');
        toast.className = 'fezadan-toast';
        toast.dataset.kind = kind || 'info';
        toast.id = 'fezadan-toast-' + (++toastId);
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');

        var text = document.createElement('div');
        var title = document.createElement('strong');
        title.textContent = options.title || titleFor(kind);
        var body = document.createElement('span');
        body.textContent = String(message);
        text.appendChild(title);
        text.appendChild(body);

        var close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', 'Bildirimi kapat');
        close.textContent = 'x';
        close.addEventListener('click', function () { removeToast(toast); });

        toast.appendChild(text);
        toast.appendChild(close);
        root.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('is-visible'); });

        var ttl = typeof options.ttl === 'number' ? options.ttl : (kind === 'error' ? 8000 : 4500);
        if (ttl > 0) setTimeout(function () { removeToast(toast); }, ttl);
        return toast;
    }

    function removeToast(toast) {
        if (!toast || !toast.parentNode) return;
        toast.classList.remove('is-visible');
        setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 180);
    }

    function parseErrorMessage(payload, status) {
        if (payload && typeof payload === 'object') {
            return payload.error || payload.message || payload.detail || ('Sunucu hatası: HTTP ' + status);
        }
        return 'Sunucu hatası: HTTP ' + status;
    }

    function readResponse(response) {
        return response.text().then(function (text) {
            var payload = null;
            if (text) {
                try { payload = JSON.parse(text); }
                catch (err) {
                    if ((response.headers.get('content-type') || '').indexOf('application/json') !== -1) {
                        var parseError = new Error('Sunucu yanıtı JSON olarak okunamadı.');
                        parseError.cause = err;
                        throw parseError;
                    }
                }
            }
            if (!response.ok) {
                var httpError = new Error(parseErrorMessage(payload, response.status));
                httpError.status = response.status;
                httpError.payload = payload;
                httpError.requestId = payload && payload.request_id ? payload.request_id : '';
                throw httpError;
            }
            return payload !== null ? payload : text;
        });
    }

    function setButtonBusy(button, busy, busyText) {
        if (!button) return;
        if (busy) {
            button.dataset.fezadanOriginalText = button.textContent;
            button.disabled = true;
            if (busyText) button.textContent = busyText;
        } else {
            button.disabled = false;
            if (button.dataset.fezadanOriginalText) {
                button.textContent = button.dataset.fezadanOriginalText;
                delete button.dataset.fezadanOriginalText;
            }
        }
    }

    function fezadanFetch(input, init, options) {
        options = options || {};
        var button = options.button || null;
        var toastOnError = options.toastOnError !== false;
        setButtonBusy(button, true, options.busyText);

        var requestInit = Object.assign({ credentials: 'same-origin' }, init || {});
        return window.fetch(input, requestInit)
            .then(readResponse)
            .catch(function (err) {
                var requestId = err && err.requestId ? ' (Hata kodu: ' + err.requestId + ')' : '';
                var message = (options.errorMessage || (err && err.message) || 'İşlem tamamlanamadı.') + requestId;
                if (toastOnError && window.FezadanToast) window.FezadanToast.error(message);
                console.error('[FezadanFetch]', err);
                throw err;
            })
            .finally(function () {
                setButtonBusy(button, false);
                if (typeof options.finally === 'function') options.finally();
            });
    }

    window.FezadanToast = {
        show: showToast,
        success: function (message, options) { return showToast('success', message, options); },
        error: function (message, options) { return showToast('error', message, options); },
        warning: function (message, options) { return showToast('warning', message, options); },
        info: function (message, options) { return showToast('info', message, options); }
    };
    window.FezadanFetch = fezadanFetch;

    window.addEventListener('error', function (event) {
        if (handlingGlobalError) return;
        handlingGlobalError = true;
        try {
            var message = event.message || 'Beklenmeyen bir arayüz hatası oluştu.';
            console.error('[FezadanGlobalError]', event.error || message, event.filename, event.lineno, event.colno);
            if (window.FezadanToast) window.FezadanToast.error('Arayüz hatası: ' + message);
        } finally {
            setTimeout(function () { handlingGlobalError = false; }, 250);
        }
    });

    window.addEventListener('unhandledrejection', function (event) {
        if (handlingGlobalError) return;
        handlingGlobalError = true;
        try {
            var reason = event.reason;
            var message = reason && reason.message ? reason.message : 'Beklenmeyen bir arka plan işlemi hatası oluştu.';
            console.error('[FezadanUnhandledRejection]', reason);
            if (window.FezadanToast) window.FezadanToast.error('İşlem hatası: ' + message);
        } finally {
            setTimeout(function () { handlingGlobalError = false; }, 250);
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form');
        if (!form) return;
        var msg = form.getAttribute('data-confirm');
        if (!msg) return;
        if (!confirm(msg)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
})(window, document);
