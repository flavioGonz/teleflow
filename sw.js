// TeleFlow — Service Worker v8 con strategies diferenciadas
const CACHE_NAME = 'teleflow-cache-v202606041520';
const APP_SHELL = [
  '/',
  '/index.php',
  '/manifest.json',
  '/icon-192.svg',
  '/offline.html',
  'https://cdn.tailwindcss.com',
  'https://unpkg.com/react@18.2.0/umd/react.production.min.js',
  'https://unpkg.com/react-dom@18.2.0/umd/react-dom.production.min.js',
  'https://unpkg.com/@babel/standalone@7.24.7/babel.min.js',
  'https://fonts.googleapis.com/icon?family=Material+Icons+Round',
  'https://cdn.jsdelivr.net/npm/hls.js@1.5/dist/hls.min.js'
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache =>
      Promise.allSettled(APP_SHELL.map(url => cache.add(url).catch(() => null)))
    ).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  const sameOrigin = url.origin === self.location.origin;
  const path = url.pathname;

  // API → network-only
  if (sameOrigin && path.startsWith('/api/')) return;

  // assets/ → cache-first (con cache-bust ?v= del HTML)
  if (sameOrigin && path.startsWith('/assets/')) {
    e.respondWith(
      caches.match(e.request).then(cached => {
        if (cached) return cached;
        return fetch(e.request).then(resp => {
          if (resp.ok) {
            const clone = resp.clone();
            caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
          }
          return resp;
        }).catch(() => cached);
      })
    );
    return;
  }

  // HTML/navegación → network-first → cache → offline.html
  if (e.request.mode === 'navigate' || e.request.destination === 'document' || (sameOrigin && (path === '/' || path.endsWith('.php')))) {
    e.respondWith(
      fetch(e.request).then(resp => {
        if (resp.ok) {
          const clone = resp.clone();
          caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
        }
        return resp;
      }).catch(() =>
        caches.match(e.request).then(c => c || caches.match('/index.php')).then(c => c || caches.match('/offline.html'))
      )
    );
    return;
  }

  // Imágenes (incluye snapshots RTSP) → cache-first
  if (e.request.destination === 'image') {
    e.respondWith(
      caches.match(e.request).then(cached => {
        if (cached) return cached;
        return fetch(e.request).then(resp => {
          if (resp.ok && resp.status === 200) {
            const clone = resp.clone();
            caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
          }
          return resp;
        }).catch(() => cached || new Response('', { status: 404 }));
      })
    );
    return;
  }

  // Default: stale-while-revalidate (CDNs, fonts)
  e.respondWith(
    caches.match(e.request).then(cached => {
      const fetchPromise = fetch(e.request).then(resp => {
        if (resp.ok) {
          const clone = resp.clone();
          caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
        }
        return resp;
      }).catch(() => cached);
      return cached || fetchPromise;
    })
  );
});

// Push notifications
self.addEventListener('push', e => {
  const data = e.data ? e.data.json() : { title: 'TeleFlow', body: 'Nueva alerta' };
  e.waitUntil(
    self.registration.showNotification(data.title || 'TeleFlow', {
      body: data.body || '',
      icon: '/icon-192.svg',
      badge: '/icon-192.svg',
      tag: data.tag || 'teleflow-alert',
      data: data,
      requireInteraction: data.requireInteraction || false,
      vibrate: [200, 100, 200],
      actions: data.actions || [],
    })
  );
});

self.addEventListener('notificationclick', e => {
  const action = e.action;
  e.notification.close();
  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(cls => {
      const cl = cls.find(c => c.url.includes('/softphone')) || cls[0];
      if (action === 'answer' || action === 'reject') {
        if (cl) { cl.postMessage({ type: 'CALL_ACTION', action: action }); cl.focus(); }
      } else {
        if (cl) return cl.focus();
        return clients.openWindow('/');
      }
    })
  );
});

self.addEventListener('message', e => {
  if (e.data && e.data.type === 'NOTIFY') {
    self.registration.showNotification(e.data.title || 'TeleFlow', {
      body: e.data.body || '',
      icon: '/icon-192.svg',
      tag: e.data.tag || 'teleflow',
      data: e.data,
      vibrate: [300, 100, 300, 100, 300],
      requireInteraction: true,
      actions: [
        { action: 'answer', title: 'Contestar' },
        { action: 'reject', title: 'Rechazar' }
      ]
    });
  }
  if (e.data && e.data.type === 'SKIP_WAITING') self.skipWaiting();
});
