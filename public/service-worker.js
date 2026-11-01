const CACHE_NAME = 'priestagenda-shell-v4';
const SHELL_ASSETS = [
    '/offline.html',
    '/icons/priestagenda-192.png',
    '/icons/priestagenda-512.png',
    '/images/elders/paisios.jpg',
    '/images/elders/silouan.jpg',
    '/images/elders/joseph.jpg',
    '/images/elders/orthodox-cross.svg',
    '/images/orthodox-mosaic-bg.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    if (url.pathname.startsWith('/icons/') || url.pathname.startsWith('/images/')) {
        event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
    }
});
