const CACHE = 'shop-erp-v1';
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    '/',
    '/offline.html',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    if (e.request.url.includes('/livewire')) return;
    if (e.request.url.includes('livewire-a2f90aa8')) return;

    e.respondWith(
        fetch(e.request)
            .then(res => {
                if (res && res.status === 200 && e.request.url.startsWith(self.location.origin)) {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(e.request, clone));
                }
                return res;
            })
            .catch(() => caches.match(e.request).then(cached => cached || caches.match(OFFLINE_URL)))
    );
});