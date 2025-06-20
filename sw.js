const CACHE_NAME = 'sari-pos-v1';
const urlsToCache = [
  '/sari/',
  '/sari/pos',
  '/sari/inventory', 
  '/sari/products',
  '/sari/sales',
  '/sari/reports',
  '/sari/dashboard',
  '/sari/settings',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'
];

// Install service worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
      .catch(err => console.log('Cache install failed:', err))
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        if (response) {
          return response;
        }
        
        return fetch(event.request).then(response => {
          // Don't cache non-successful responses
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone the response
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });

          return response;
        }).catch(() => {
          // Return offline page for navigation requests
          if (event.request.destination === 'document') {
            return caches.match('/sari/offline.html');
          }
        });
      })
  );
});

// Activate service worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Background sync for offline data
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(syncOfflineData());
  }
});

// Handle offline sales sync
async function syncOfflineData() {
  try {
    const offlineData = await getOfflineData();
    
    for (const sale of offlineData.sales) {
      try {
        const response = await fetch('/sari/api/sync-sale', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(sale)
        });
        
        if (response.ok) {
          await removeOfflineSale(sale.id);
        }
      } catch (error) {
        console.log('Failed to sync sale:', error);
      }
    }
    
    // Send sync complete message to app
    self.clients.matchAll().then(clients => {
      clients.forEach(client => {
        client.postMessage({
          type: 'SYNC_COMPLETE',
          data: { synced: true }
        });
      });
    });
    
  } catch (error) {
    console.log('Background sync failed:', error);
  }
}

// Get offline data from IndexedDB
async function getOfflineData() {
  return new Promise((resolve) => {
    const request = indexedDB.open('SariPOSDB', 1);
    
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction(['offlineSales'], 'readonly');
      const store = transaction.objectStore('offlineSales');
      const getAllRequest = store.getAll();
      
      getAllRequest.onsuccess = () => {
        resolve({ sales: getAllRequest.result });
      };
    };
  });
}

// Remove synced sale from offline storage
async function removeOfflineSale(saleId) {
  return new Promise((resolve) => {
    const request = indexedDB.open('SariPOSDB', 1);
    
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction(['offlineSales'], 'readwrite');
      const store = transaction.objectStore('offlineSales');
      
      store.delete(saleId);
      transaction.oncomplete = () => resolve();
    };
  });
}