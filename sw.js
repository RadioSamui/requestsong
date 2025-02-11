self.addEventListener("install", (event) => {
    console.log("Service Worker установлен.");
    event.waitUntil(
        caches.open("radio-cache").then((cache) => {
            return cache.addAll([
                "/",
                "/index.php",
                "/styles.css",
                "/manifest.json",
                "https://radiosamui.online/assets.favicon.png",
                "https://radiosamui.online/assets.favicon.png"
            ]);
        })
    );
});

self.addEventListener("fetch", (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
