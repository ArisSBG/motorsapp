/*
 * Service worker de BURKINA MOTORS : rend l'application installable sur le téléphone.
 * Volontairement minimal : AUCUNE page ni donnée n'est mise en cache (données personnelles,
 * montants), tout passe par le réseau. Seule une page « hors connexion » s'affiche si le réseau manque.
 */
const CACHE = 'bm-v1';
const HORS_LIGNE = '/hors-ligne.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll([HORS_LIGNE, '/icons/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((noms) => Promise.all(noms.filter((n) => n !== CACHE).map((n) => caches.delete(n))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return; // laisser le navigateur gérer normalement
    }
    event.respondWith(fetch(event.request).catch(() => caches.match(HORS_LIGNE)));
});
