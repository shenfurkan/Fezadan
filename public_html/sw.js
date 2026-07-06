const CACHE_VERSION = 'fezadan-2026-06-12';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGE_CACHE = `${CACHE_VERSION}-pages`;

const CORE_ASSETS = [
  '/tr',
  '/tr/makaleler',
  '/site.webmanifest',
  '/assets/css/admin.css',
  '/assets/css/fonts.css',
  '/cdn/logo-light.png',
  '/cdn/logo-dark.png',
  '/cdn/light-android-chrome-192x192.png',
  '/cdn/light-android-chrome-512x512.png',
  '/assets/fonts/space-grotesk-v22-latin-ext-regular.woff2',
  '/assets/fonts/syne-v24-latin-ext-700.woff2'
];

const STATIC_DESTINATIONS = new Set(['style', 'script', 'font', 'image', 'manifest']);
const BYPASS_PREFIXES = ['/yonetim', '/git-deploy.php', '/health.php'];

function shouldBypass(url) {
  return BYPASS_PREFIXES.some((prefix) => url.pathname === prefix || url.pathname.startsWith(`${prefix}/`));
}

async function warmCache() {
  const cache = await caches.open(STATIC_CACHE);
  await Promise.allSettled(
    CORE_ASSETS.map((asset) => cache.add(new Request(asset, { cache: 'reload' })))
  );
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  const response = await fetch(request);
  if (response && response.ok) {
    const cache = await caches.open(STATIC_CACHE);
    cache.put(request, response.clone());
  }
  return response;
}

async function networkFirst(request) {
  const cache = await caches.open(PAGE_CACHE);

  try {
    const response = await fetch(request);
    if (response && response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await cache.match(request);
    if (cached) return cached;

    return (await cache.match('/tr')) || Response.error();
  }
}

self.addEventListener('install', (event) => {
  event.waitUntil(warmCache().then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((key) => key.startsWith('fezadan-') && !key.startsWith(CACHE_VERSION))
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || shouldBypass(url)) return;

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
