self.addEventListener('install', (e) => {
  self.skipWaiting();
});

self.addEventListener('push', (event) => {
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: 'https://ui-avatars.com/api/?name=TF&background=8B5CF6&color=fff',
    badge: 'https://ui-avatars.com/api/?name=TF&background=8B5CF6&color=fff',
    vibrate: [200, 100, 200]
  };
  event.waitUntil(self.registration.showNotification(data.title, options));
});
