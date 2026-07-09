/**
 * Alpine store for offline state.
 *
 * Reactive properties consumed by Blade/Alpine components:
 *   - isOnline: boolean
 *   - pendingCount: number of queued operations
 *   - syncing: boolean
 *   - lastSync: ISO timestamp | null
 *   - lastError: string | null
 *
 * Exposes methods for the UI:
 *   - syncNow(): trigger a manual sync
 *   - refreshPendingCount()
 */

import { getPendingCount } from './db.js';
import { syncOutbox } from './sync.js';

export function createOfflineStore() {
    return {
        isOnline: navigator.onLine,
        pendingCount: 0,
        syncing: false,
        lastSync: null,
        lastError: null,

        async init() {
            await this.refreshPendingCount();

            window.addEventListener('online', async () => {
                this.isOnline = true;
                this.lastError = null;
                // Auto-sync when connection returns
                await this.syncNow();
            });

            window.addEventListener('offline', () => {
                this.isOnline = false;
            });
        },

        async refreshPendingCount() {
            try {
                this.pendingCount = await getPendingCount();
            } catch (e) {
                console.warn('[OfflineStore] Failed to get pending count:', e);
            }
        },

        async syncNow() {
            if (this.syncing) return; // prevent double-sync
            if (!navigator.onLine) {
                this.lastError = 'You are offline. Connect to the internet and try again.';
                return;
            }

            this.syncing = true;
            this.lastError = null;

            try {
                const result = await syncOutbox();
                this.pendingCount = result.remaining;
                this.lastSync = new Date().toISOString();

                if (result.errors > 0) {
                    this.lastError = `${result.errors} item(s) failed to sync. Check the pending list for details.`;
                }
            } catch (e) {
                console.error('[OfflineStore] Sync failed:', e);
                this.lastError = e.message || 'Sync failed. Please try again.';
            } finally {
                this.syncing = false;
                await this.refreshPendingCount();
            }
        },
    };
}
