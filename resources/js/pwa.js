/**
 * Mehran Goats — PWA Registration
 *
 * Registers the service worker and handles the install prompt.
 * Exposes install state via window.PwaInstall + DOM events for Alpine.
 */

// Global install state
window.PwaInstall = {
    canInstall: false,
    installed: window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true,
};

let deferredPrompt = null;

// ─── Service Worker Registration ──────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then((reg) => {
                console.log('[PWA] Service Worker registered:', reg.scope);
                setInterval(() => reg.update(), 60 * 60 * 1000);
            })
            .catch((err) => console.warn('[PWA] SW registration failed:', err));
    });
}

// ─── Install prompt handling ──────────────────────────────────────
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    window.PwaInstall.canInstall = true;
    console.log('[PWA] Install prompt available — banner should show');
    // Dispatch a DOM event so Alpine components react instantly
    window.dispatchEvent(new CustomEvent('pwa-install-available'));
});

window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installed');
    deferredPrompt = null;
    window.PwaInstall.canInstall = false;
    window.PwaInstall.installed = true;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});

// ─── Trigger install from the custom banner button ────────────────
window.triggerPwaInstall = () => {
    if (!deferredPrompt) return false;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choice) => {
        console.log(`[PWA] User choice: ${choice.outcome}`);
        if (choice.outcome === 'dismissed') {
            window.PwaInstall.canInstall = true;
            window.dispatchEvent(new CustomEvent('pwa-install-available'));
        }
        deferredPrompt = null;
    });
    return true;
};

window.dismissPwaInstall = () => {
    window.PwaInstall.canInstall = false;
};
