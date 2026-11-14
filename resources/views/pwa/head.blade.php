{{-- Installation sur téléphone (PWA) : manifeste, icônes, couleur de la barre, service worker --}}
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#1e40af">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="B. Motors">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>
