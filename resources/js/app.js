
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import Alpine from 'alpinejs';

// PWA: register service worker + install prompt
import './pwa';

// Offline-first: IndexedDB outbox + form interceptor + sync engine
import { initOffline } from './offline/index.js';

// DataTables core + extensions (ES module imports auto-initialise)
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'datatables.net-buttons-bs5';

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

Alpine.store('confirm', {
    open: false,
    message: '',
    form: null,
    ask(message, form) {
        this.message = message;
        this.form = form;
        this.open = true;
    },
    accept() {
        const form = this.form;
        this.open = false;
        this.form = null;
        this.message = '';
        if (!form) return;
        form.dataset.confirmed = '1';
        form.requestSubmit();
    },
    cancel() {
        this.open = false;
        this.form = null;
        this.message = '';
    },
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirmed === '1') {
        delete form.dataset.confirmed;
        return;
    }
    const message = event.submitter?.getAttribute('data-confirm') || form.getAttribute('data-confirm');
    if (!message) return;
    event.preventDefault();
    event.stopPropagation();
    Alpine.store('confirm').ask(message, form);
}, true);

// Initialize offline-first system (registers Alpine.store('offline'))
initOffline(Alpine);

Alpine.start();
