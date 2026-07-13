
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import Alpine from 'alpinejs';

// PWA: register service worker + install prompt
import './pwa';

// Offline-first: IndexedDB outbox + form interceptor + sync engine
import { initOffline } from './offline/index.js';

// DataTables core + extensions
import dt from 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'datatables.net-buttons-bs5';
dt(window, $);

// Custom DataTables initialisation helper
import './datatables';

window.Alpine = Alpine;

// Global store for the mobile "More" sheet — avoids custom-event dispatch issues
Alpine.store('ui', {
    moreOpen: false,
    toggleMore() { this.moreOpen = !this.moreOpen; },
    openMore()  { this.moreOpen = true; },
    closeMore() { this.moreOpen = false; },
});

// Initialize offline-first system (registers Alpine.store('offline'))
initOffline(Alpine);

Alpine.start();
