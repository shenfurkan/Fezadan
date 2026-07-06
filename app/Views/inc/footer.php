<?php
$footerLangTrUrl = function_exists('getLanguageSwitchUrl')
    ? getLanguageSwitchUrl('TR')
    : (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/tr' : '/tr');
$footerLangEnUrl = function_exists('getLanguageSwitchUrl')
    ? getLanguageSwitchUrl('EN')
    : (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/en' : '/en');

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8080', '127.0.0.1', '127.0.0.1:8080', '::1']);
$portfolioUrl = $isLocal ? '/furkan' : 'https://furkan.fezadan.org';
?>

<style>
    [data-theme="dark"] .dynamic-footer-border {
        border-top-color: var(--text-main) !important;
    }

    .pwa-install-banner {
        position: fixed;
        left: 1rem;
        bottom: 1rem;
        z-index: 70;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        max-width: 34rem;
        margin: 0;
        padding: 0.85rem 0.9rem;
        border: 2px solid var(--text-main);
        background: var(--bg-paper);
        color: var(--text-main);
        box-shadow: 6px 6px 0 var(--line-color);
        font-family: 'Space Grotesk', sans-serif;
    }

    .pwa-install-banner.is-visible {
        display: flex;
    }

    .pwa-install-text {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        line-height: 1.35;
    }

    .pwa-install-actions {
        display: inline-flex;
        flex-shrink: 0;
        gap: 0.5rem;
    }

    .pwa-install-action {
        min-width: 3.4rem;
        height: 2.1rem;
        border: 1px solid var(--text-main);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .pwa-install-action.primary {
        background: var(--text-main);
        color: var(--bg-paper);
    }

    .pwa-install-action.secondary {
        background: transparent;
        color: var(--text-main);
    }

    .pwa-install-action:hover {
        background: var(--text-accent);
        color: #FEF9E1;
        border-color: var(--text-accent);
    }

    @media (max-width: 520px) {
        .pwa-install-banner {
            align-items: stretch;
            flex-direction: column;
            right: 1rem;
            max-width: none;
        }

        .pwa-install-actions {
            justify-content: flex-end;
        }
    }
</style>

<footer class="py-10 px-6 md:px-12 flex flex-col md:flex-row justify-between items-center text-[10px] uppercase tracking-widest opacity-60 text-[var(--text-main)] border-t border-[var(--line-color)] dynamic-footer-border mt-auto">
    <div class="mb-4 md:mb-0 flex flex-col md:flex-row items-center gap-2 md:gap-4">
        <span>FEZADAN</span>

        <span class="hidden md:inline opacity-50">|</span>
        <a href="<?= pageUrl('privacy') ?>" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300"><?= __('nav.privacy', 'Privacy Policy') ?></a>
        <span class="hidden md:inline opacity-50">|</span>
        <a href="/sitemap.xml" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300"><?= __('nav.sitemap', 'Sitemap') ?></a>
        <span class="hidden md:inline opacity-50">|</span>
        <a href="https://github.com/shenfurkan/Fezadan" rel="noopener" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300"><?= __('nav.source_code', 'Source Code') ?></a>
        <span class="hidden md:inline opacity-50">|</span>
        <a href="<?= htmlspecialchars($portfolioUrl) ?>" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300"><?= __('nav.portfolios', 'My Portfolios') ?></a>
        <span class="hidden md:inline opacity-50">|</span>
        <?php 
            $host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
            $anonymity_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . (strpos($host, 'anonymitycheck.') === 0 ? $host : "anonymitycheck." . $host);
        ?>
        <a href="<?= htmlspecialchars($anonymity_url) ?>" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300"><?= __('nav.anonymity', 'Anonymity Check') ?></a>
        <span class="hidden md:inline opacity-50">|</span>
        <a href="/rss" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300">RSS</a>
    </div>
    <div class="flex gap-6 items-center">
        <a href="https://www.instagram.com/fezadanorg" target="_blank" rel="noopener noreferrer" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300">Instagram</a>
        <a href="https://www.x.com/fezadanorg" target="_blank" rel="noopener noreferrer" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300">X</a>
        <a href="mailto:info@fezadan.org" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300">info@fezadan.org</a>
        <span class="opacity-50">|</span>
        <div class="inline-flex gap-1">
            <a href="<?= htmlspecialchars($footerLangTrUrl, ENT_QUOTES, 'UTF-8') ?>" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300 <?= App::getLang() === 'TR' ? 'text-[var(--text-accent)] font-bold' : 'opacity-70' ?>">TR</a>
            <span class="opacity-30">/</span>
            <a href="<?= htmlspecialchars($footerLangEnUrl, ENT_QUOTES, 'UTF-8') ?>" class="hover:text-[#E1C89E] hover:opacity-100 transition-all duration-300 <?= App::getLang() === 'EN' ? 'text-[var(--text-accent)] font-bold' : 'opacity-70' ?>">EN</a>
        </div>
    </div>
</footer>

<div id="pwa-install-banner" class="pwa-install-banner" role="status" aria-live="polite">
    <span class="pwa-install-text"><?= __('pwa.install', 'FEZADAN\'i ana ekrana ekle') ?></span>
    <div class="pwa-install-actions">
        <button type="button" id="pwa-install-dismiss" class="pwa-install-action secondary"><?= __('pwa.later', 'Sonra') ?></button>
        <button type="button" id="pwa-install-accept" class="pwa-install-action primary"><?= __('pwa.add', 'Ekle') ?></button>
    </div>
</div>

<script nonce="<?= CSP_NONCE ?>">
    (function () {

        var articleSearchUrl = <?= json_encode(langUrl(App::getLang() === 'EN' ? '/articles' : '/makaleler'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        function isEditableTarget(target) {
            if (!target) return false;
            var tagName = (target.tagName || '').toLowerCase();
            return target.isContentEditable || tagName === 'input' || tagName === 'textarea' || tagName === 'select';
        }

        function focusSearchInput() {
            var input = document.querySelector('input[name="q"], input[name="search"], input[type="search"]');
            if (!input) return false;
            input.focus();
            if (typeof input.select === 'function') input.select();
            return true;
        }

        document.addEventListener('keydown', function (event) {
            if (event.defaultPrevented || isEditableTarget(event.target)) return;

            if ((event.ctrlKey || event.metaKey) && event.key && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (!focusSearchInput()) {
                    window.location.href = articleSearchUrl + '?focus=search';
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            try {
                if (new URLSearchParams(window.location.search).get('focus') === 'search') {
                    focusSearchInput();
                }
            } catch (error) {}
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }

        var deferredPrompt = null;
        var banner = document.getElementById('pwa-install-banner');
        var acceptButton = document.getElementById('pwa-install-accept');
        var dismissButton = document.getElementById('pwa-install-dismiss');
        var dismissedKey = 'fezadan-pwa-install-dismissed';

        function hideBanner(remember) {
            if (banner) banner.classList.remove('is-visible');
            if (remember) {
                try { localStorage.setItem(dismissedKey, '1'); } catch (error) {}
            }
        }

        function showBanner() {
            if (!banner || !deferredPrompt) return;
            try {
                if (localStorage.getItem(dismissedKey) === '1') return;
            } catch (error) {}
            banner.classList.add('is-visible');
        }

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredPrompt = event;
            window.setTimeout(showBanner, 1200);
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            hideBanner(true);
        });

        if (acceptButton) {
            acceptButton.addEventListener('click', function () {
                if (!deferredPrompt) {
                    hideBanner(false);
                    return;
                }

                deferredPrompt.prompt();
                deferredPrompt.userChoice.finally(function () {
                    deferredPrompt = null;
                    hideBanner(false);
                });
            });
        }

        if (dismissButton) {
            dismissButton.addEventListener('click', function () {
                hideBanner(true);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideBanner(false);
            }
        });
    })();
</script>

<script nonce="<?= CSP_NONCE ?>">
    (function () {
        var handling = false;
        window.addEventListener('error', function (event) {
            if (handling) return;
            handling = true;
            try { console.error('[FezadanPublicError]', event.error || event.message); } catch (_) {}
            setTimeout(function () { handling = false; }, 250);
        });
        window.addEventListener('unhandledrejection', function (event) {
            if (handling) return;
            handling = true;
            try { console.error('[FezadanPublicRejection]', event.reason); } catch (_) {}
            setTimeout(function () { handling = false; }, 250);
        });
    })();
</script>

</body>
</html>
