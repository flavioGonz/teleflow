const CACHE_NAME = 'teleflow-cache-v21';
const STATIC_ASSETS = ['/teleflow/', '/teleflow/index.php', '/teleflow/manifest.json'];

self.addEventListener('install', e => { self.skipWaiting(); });

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});

// ── PUSH NOTIFICATIONS ────────────────────────────────────────────────────────
self.addEventListener('push', e => {
  const data = e.data ? e.data.json() : { title: 'TeleFlow', body: 'Nueva alerta' };
  e.waitUntil(
    self.registration.showNotification(data.title || 'TeleFlow', {
      body: data.body || '',
      icon: '/teleflow/icon-192.png',
      badge: '/teleflow/icon-192.png',
      tag: data.tag || 'teleflow-alert',
      data: data,
      requireInteraction: data.requireInteraction || false,
      vibrate: [200, 100, 200],
      actions: data.actions || [],
    })
  );
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(cls => {
      const url = '/teleflow/';
      const cl = cls.find(c => c.url.includes('/teleflow'));
      if (cl) { cl.focus(); return; }
      return clients.openWindow(url);
    })
  );
});

// ── BACKGROUND SYNC (fallback si no hay push server) ─────────────────────────
self.addEventListener('message', e => {
  if (e.data && e.data.type === 'NOTIFY') {
    self.registration.showNotification(e.data.title || 'TeleFlow', {
      body: e.data.body || '',
      icon: '/teleflow/icon-192.png',
      tag: e.data.tag || 'teleflow',
      vibrate: [100, 50, 100],
    });
  }
});
