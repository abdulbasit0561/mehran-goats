

import Alpine from 'alpinejs';

// PWA: register service worker + install prompt
import './pwa';

window.Alpine = Alpine;

// Global store for the mobile "More" sheet — avoids custom-event dispatch issues
Alpine.store('ui', {
    moreOpen: false,
    toggleMore() { this.moreOpen = !this.moreOpen; },
    openMore()  { this.moreOpen = true; },
    closeMore() { this.moreOpen = false; },
});

Alpine.start();
