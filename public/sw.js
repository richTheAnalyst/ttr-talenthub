/*
 * TalentHub service worker.
 *
 * - Precaches the app shell (CSS, JS, icons) and an offline page.
 * - Static assets: stale-while-revalidate.
 * - Page navigations: network first, offline page when the network is down.
 * - Never caches HTML, API responses or CV downloads: those are personal data.
 *
 * Bump VERSION when you change this file or the precached assets.
 */
const VERSION = 'v1';
const STATIC_CACHE = `talenthub-static-${VERSION}`;
const PRECACHE = ['/offline', '/css/app.css', '/js/app.js', '/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('talenthub-') && k !== STATIC_CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Personal or dynamic endpoints go straight to the network.
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/cv/') || url.pathname.startsWith('/admin/export')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline'))
        );
        return;
    }

    const isStatic = /\.(?:css|js|png|jpg|jpeg|svg|ico|webp|woff2?|webmanifest)$/.test(url.pathname);
    if (isStatic) {
        event.respondWith(
            caches.open(STATIC_CACHE).then((cache) =>
                cache.match(request).then((cached) => {
                    const refresh = fetch(request)
                        .then((response) => {
                            if (response && response.ok) {
                                cache.put(request, response.clone());
                            }
                            return response;
                        })
                        .catch(() => cached);
                    return cached || refresh;
                })
            )
        );
    }
});
