const CACHE_NAME = 'teleflow-cache-v20';
self.addEventListener('install', (e) => {
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(keys.map((key) => {
        if (key !== CACHE_NAME) return caches.delete(key);
      }));
    })
  );
});

self.addEventListener('fetch', (e) => {
  // Estrategia: Red primero, si falla, usa el cache.
  // Esto asegura que si hay internet, siempre traiga la v20+
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});
