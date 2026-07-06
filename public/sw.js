/**
 * Mehran Goats — Service Worker
 *
 * Caching strategies:
 *   - App shell (offline page, manifest, icons): precached on install
 *   - HTML navigation requests: network-first → cache → offline page
 *   - CSS / JS / fonts (assets): stale-while-revalidate
 *   - Images: cache-first with fallback
 *   - Non-GET (forms: POST/PUT/DELETE): never cached, pass through to network
 */

const CACHE_VERSION = 'mehran-goats-v1';
const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-192-maskable.png',
    '/icons/apple-touch-icon.png',
    '/favicon.svg',
];

// ─── Install: precache the app shell ──────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
            .catch((err) => console.warn('[SW] Precache failed:', err))
    );
});

// ─── Activate: clean up old caches ────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key !== CACHE_VERSION)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

// ─── Fetch: apply caching strategies ──────────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only handle GET — POST/PUT/DELETE forms always go to network
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) return;

    // Don't intercept non-GET (above) or non-http(s)
    if (!url.protocol.startsWith('http')) return;

    // --- Navigation (HTML pages): network-first with offline fallback ---
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then(
                        (cached) => cached || caches.match('/offline.html')
                    )
                )
        );
        return;
    }

    // --- Static assets (CSS, JS, fonts): stale-while-revalidate ---
    const isAsset =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/assets/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.woff2') ||
        url.hostname.includes('bunny.net'); // fonts

    if (isAsset) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fetchPromise = fetch(request)
                    .then((response) => {
                        if (response && response.status === 200) {
                            const clone = response.clone();
                            caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached || fetchPromise;
            })
        );
        return;
    }

    // --- Images: cache-first ---
    if (request.destination === 'image') {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached || fetch(request).then((response) => {
                    if (response && response.status === 200) {
                        const clone = response.clone();
                        caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
            )
        );
        return;
    }

    // --- Everything else: try network, fall back to cache ---
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
