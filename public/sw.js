self.addEventListener('push', function(event) {
  const data = event.data ? event.data.json() : {};
  event.waitUntil(
    self.registration.showNotification(data.titre || '🐾 AgriCore', {
      body: data.message || '',
      icon: '/favicon.ico',
      badge: '/favicon.ico',
      requireInteraction: data.niveau === 'critique',
      vibrate: data.niveau === 'critique' ? [200, 100, 200] : [100],
      tag: data.titre,
    })
  );
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(clients.openWindow('/animal'));
});
