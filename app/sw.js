const CACHE_NAME = 'logbog-pwa-v2';
const ASSETS_TO_CACHE = [
  './',
  './manifest.json',
  '../assets/css/style.css',
  '../assets/icons/favicon.svg',
  '../assets/icons/icon.svg',
  '../assets/icons/icon-192.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// Network-first strategi for data, cache-fallback for app shell
self.addEventListener('fetch', (event) => {
  // Ignorer API-kald og uploads så de altid går til netværket
  if (event.request.url.includes('/api.php') || event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Opdater cache ved gyldige svar
        if (response && response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request);
      })
  );
});
