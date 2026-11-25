// @ts-check
// `self` is the ServiceWorkerGlobalScope here; the alias lets TypeScript (jsconfig.sw.json) type it.
const sw = /** @type {ServiceWorkerGlobalScope} */ (/** @type {unknown} */ (self));

const CACHE_VERSION = 'fezadan-2026-10-30-analytics';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGE_CACHE = `${CACHE_VERSION}-pages`;

const CORE_ASSETS = [
  '/site.webmanifest',
  '/assets/css/admin.css',
  '/assets/css/fonts.css',
  '/cdn/logo-light-320.webp',
  '/cdn/logo-dark-320.webp',
  '/cdn/light-android-chrome-192x192.png',
  '/cdn/light-android-chrome-512x512.png',
  '/assets/fonts/space-grotesk-v22-latin-ext-regular.woff2',
  '/assets/fonts/syne-v24-latin-ext-700.woff2'
];

const STATIC_DESTINATIONS = new Set(['style', 'script', 'font', 'image', 'manifest']);
const BYPASS_PREFIXES = ['/yonetim', '/git-deploy.php', '/health.php', '/anonymitycheck', '/not/view', '/not/download'];

function shouldBypass(url) {
  let path;
  try { path = decodeURIComponent(url.pathname).replace(/^\/(?:tr|en)(?=\/|$)/i, ''); }
  catch (_) { return true; }
  return /^(?:anonymitycheck|notlar)\./i.test(url.hostname)
    || BYPASS_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
}

async function storeResponse(cache, request, response) {
  if (response.status !== 200 || response.redirected || request.cache === 'no-store'
    || /\b(?:no-store|private|no-cache)\b/i.test(response.headers.get('Cache-Control') || '')) return;
  // Cache quota/storage failure must not turn a successful network request into an error.
  try { await cache.put(request, response.clone()); } catch (_) {}
}

async function warmCache() {
  const cache = await caches.open(STATIC_CACHE);
  await Promise.allSettled(
    CORE_ASSETS.map(async (asset) => {
      const request = new Request(asset, { cache: 'reload' });
      await storeResponse(cache, request, await fetch(request));
    })
  );
}

async function cacheFirst(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request);
  if (cached) return cached;

  const response = await fetch(request);
  await storeResponse(cache, request, response);
  return response;
}

async function networkFirst(request) {
  const cache = await caches.open(PAGE_CACHE);

  try {
    const response = await fetch(request);
    await storeResponse(cache, request, response);
    return response;
  } catch (error) {
    const cached = await cache.match(request);
    if (cached) return cached;

    // Never substitute the Turkish home page for a different requested page.
    return Response.error();
  }
}

sw.addEventListener('install', (event) => {
  event.waitUntil(warmCache().then(() => sw.skipWaiting()));
});

sw.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((key) => key.startsWith('fezadan-') && !key.startsWith(CACHE_VERSION))
          .map((key) => caches.delete(key))
      ))
      .then(() => sw.clients.claim())
  );
});

sw.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== sw.location.origin || request.headers.has('Range')
    || request.cache === 'no-store' || shouldBypass(url)) return;

  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request));
    return;
  }

  if (
    STATIC_DESTINATIONS.has(request.destination) ||
    url.pathname.startsWith('/assets/') ||
    url.pathname.startsWith('/cdn/')
  ) {
    event.respondWith(cacheFirst(request));
  }
});
