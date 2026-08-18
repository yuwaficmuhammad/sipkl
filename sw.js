self.addEventListener('push', function(event) {
    if (event.data) {
        const data = event.data.json();
        
        const title = data.title || 'Notifikasi Baru SIPKL';
        const options = {
            body: data.body || 'Anda memiliki notifikasi baru.',
            icon: 'assets/img/favicon.svg', // Pastikan path ini benar di root
            badge: 'assets/img/favicon.svg',
            data: {
                url: data.url || '/'
            },
            vibrate: [200, 100, 200]
        };

        event.waitUntil(self.registration.showNotification(title, options));
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(
            clients.matchAll({ type: 'window' }).then(windowClients => {
                // Cek jika sudah ada tab yang buka URL itu, lalu fokus
                for (let i = 0; i < windowClients.length; i++) {
                    const client = windowClients[i];
                    if (client.url.includes(event.notification.data.url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Jika belum, buka tab baru
                if (clients.openWindow) {
                    return clients.openWindow(event.notification.data.url);
                }
            })
        );
    }
});
