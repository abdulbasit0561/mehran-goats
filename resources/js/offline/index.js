/**
 * Offline-first module entry point.
 *
 * Bootstraps the IndexedDB layer, Alpine store, form interceptor,
 * and initial data caching. Imported from app.js.
 */

import { createOfflineStore } from './store.js';
import { attachInterceptors } from './interceptor.js';
import { pullAndCache } from './sync.js';
import { renderOfflineData } from './viewer.js';
import { getPendingCount } from './db.js';

export async function initOffline(Alpine) {
    // Register the Alpine store before Alpine.start()
    Alpine.store('offline', createOfflineStore());

    // Attach form interceptors once DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            attachInterceptors();
            // Render cached data for offline viewing
            renderOfflineData();
        });
    } else {
        attachInterceptors();
        renderOfflineData();
    }

    // Cache reference data in the background (when online + authenticated)
    if (navigator.onLine) {
        // Only pull data if the user is authenticated (CSRF meta tag present = behind auth middleware)
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            // Pull + cache after a short delay so it doesn't compete with page render
            setTimeout(async () => {
                try {
                    await pullAndCache();
                } catch (e) {
                    console.warn('[Offline] Initial data pull failed (non-fatal):', e);
                }
            }, 2000);
        }
    }

    // Sync any pending items on load (when online)
    if (navigator.onLine) {
        const count = await getPendingCount();
        if (count > 0) {
            // Auto-sync pending items after page load
            setTimeout(async () => {
                if (Alpine.store('offline')) {
                    await Alpine.store('offline').syncNow();
                }
            }, 1500);
        }
    }

    console.log('[Offline] Offline-first system initialized.');
}
