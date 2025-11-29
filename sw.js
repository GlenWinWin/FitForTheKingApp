// Minimal Service Worker - Test Version
const CACHE_NAME = 'fit-for-king-test-v1';

self.addEventListener('install', function (event) {
    console.log('Service Worker: Install Event');
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    console.log('Service Worker: Activate Event');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    // Just pass through all requests
    event.respondWith(fetch(event.request));
});