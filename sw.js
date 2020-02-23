self.addEventListener('install', function(e){
  e.waitUntil(
    caches.open('pearscom').then(function(cache){
    return cache.addAll([
      '/',
      '/index.php',
      '/login.php',
      '/signup.php',
      '/style/style.css',
      '/images/newfav.png'
      ]);
    })
  );
});

self.addEventListener('fetch', function(e){
  e.respondWith(
    caches.match(e.request).then(function(response){
      return response || fetch(e.request);  
    })  
  );
});
