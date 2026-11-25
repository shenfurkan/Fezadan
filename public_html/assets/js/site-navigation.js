// @ts-check
(function () {
    const navbar = document.getElementById('main-navbar');
    const button = document.getElementById('hamburger-btn');
    const menu = document.getElementById('mobile-menu');
    if (!navbar || !button || !menu) return;

    const themes = document.querySelectorAll('.theme-switch-wrapper');
    const background = new Map();
    let isOpen = false;
    let lastScrollY = window.scrollY;
    let previousOverflow = ['', ''];

    function visibleControls() {
        return [button, ...menu.querySelectorAll('a, button, [tabindex="0"]')]
            .filter((element) => element instanceof HTMLElement && element.offsetParent !== null);
    }

    function setOpen(open, restoreFocus = false) {
        if (open === isOpen) return;
        isOpen = open;
        button.classList.toggle('menu-active', open);
        menu.classList.toggle('menu-open', open);
        button.setAttribute('aria-expanded', String(open));
        button.setAttribute('aria-label', open ? button.dataset.closeLabel : button.dataset.openLabel);
        menu.setAttribute('aria-hidden', String(!open));
        menu.inert = !open;
        if (open) {
            navbar.classList.remove('nav-hidden');
            previousOverflow = [document.documentElement.style.overflow, document.body.style.overflow];
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            for (const element of document.body.children) {
                if (element instanceof HTMLElement && element !== navbar && element !== menu
                    && !['SCRIPT', 'STYLE'].includes(element.tagName)) {
                    background.set(element, element.inert);
                    element.inert = true;
                }
            }
            const first = visibleControls()[1];
            if (first instanceof HTMLElement) first.focus();
        } else {
            document.documentElement.style.overflow = previousOverflow[0];
            document.body.style.overflow = previousOverflow[1];
            background.forEach((wasInert, element) => { element.inert = wasInert; });
            background.clear();
            if (restoreFocus) {
                const target = button.offsetParent !== null ? button : navbar.querySelector('a');
                if (target instanceof HTMLElement) target.focus();
            }
        }
    }

    menu.inert = true;
    menu.setAttribute('aria-hidden', 'true');
    button.addEventListener('click', () => setOpen(!isOpen, true));
    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
    window.matchMedia('(min-width: 768px)').addEventListener('change', (event) => {
        if (event.matches) setOpen(false, true);
    });
    document.addEventListener('keydown', (event) => {
        if (!isOpen) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false, true);
        } else if (event.key === 'Tab') {
            const controls = visibleControls();
            const index = controls.indexOf(document.activeElement);
            const next = controls[(index + (event.shiftKey ? -1 : 1) + controls.length) % controls.length];
            if (next instanceof HTMLElement) {
                event.preventDefault();
                next.focus();
            }
        }
    });
    navbar.addEventListener('focusin', () => navbar.classList.remove('nav-hidden'));
    window.addEventListener('scroll', () => {
        const current = window.scrollY;
        if (!isOpen && !navbar.contains(document.activeElement)) {
            navbar.classList.toggle('nav-hidden', current > lastScrollY && current > 50);
        }
        lastScrollY = current;
    }, { passive: true });

    function syncThemeButtons() {
        const dark = document.documentElement.getAttribute('data-theme') === 'dark';
        themes.forEach((theme) => theme.setAttribute('aria-pressed', String(dark)));
    }
    themes.forEach((theme) => {
        theme.addEventListener('keydown', (event) => {
            const key = /** @type {KeyboardEvent} */ (event).key;
            if (key === 'Enter' || key === ' ') {
                event.preventDefault();
                if (theme instanceof HTMLElement) theme.click();
            }
        });
        theme.addEventListener('click', () => {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('theme', next); } catch (_) {}
            syncThemeButtons();
        });
    });
    syncThemeButtons();
})();
