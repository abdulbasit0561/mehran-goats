/**
 * IndexedDB wrapper for the offline-first system.
 *
 * Two object stores:
 *   - outbox: queued form submissions waiting to sync
 *   - cache:  cached server data for offline viewing
 *
 * Each outbox record:
 *   { id, uuid, entity, action, data, record_id, createdAt, status, error }
 *   status: 'pending' | 'syncing' | 'synced' | 'failed'
 */

const DB_NAME    = 'mehran-goats-offline';
const DB_VERSION = 1;

let _db = null;

export function openDB() {
    if (_db) return Promise.resolve(_db);

    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            // Outbox store — keyed by UUID
            if (!db.objectStoreNames.contains('outbox')) {
                const outbox = db.createObjectStore('outbox', { keyPath: 'uuid' });
                outbox.createIndex('status', 'status', { unique: false });
                outbox.createIndex('createdAt', 'createdAt', { unique: false });
            }

            // Cache store — keyed by store name (goats, breeds, etc.)
            if (!db.objectStoreNames.contains('cache')) {
                db.createObjectStore('cache', { keyPath: 'key' });
            }
        };

        request.onsuccess = (event) => {
            _db = event.target.result;
            resolve(_db);
        };

        request.onerror = (event) => {
            console.error('[OfflineDB] Failed to open IndexedDB:', event.target.error);
            reject(event.target.error);
        };
    });
}

// ─── OUTBOX OPERATIONS ────────────────────────────────────────────────

export async function addToOutbox(entry) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').add(entry);
        tx.oncomplete = () => resolve(entry);
        tx.onerror    = () => reject(tx.error);
    });
}

export async function getAllOutbox() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readonly');
        const req = tx.objectStore('outbox').getAll();
        req.onsuccess = () => resolve(req.result.sort((a, b) => a.createdAt - b.createdAt));
        req.onerror   = () => reject(req.error);
    });
}

export async function getPendingCount() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readonly');
        const req = tx.objectStore('outbox').count();
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    });
}

export async function updateOutboxItem(uuid, updates) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readwrite');
        const store = tx.objectStore('outbox');
        const getReq = store.get(uuid);
        getReq.onsuccess = () => {
            const item = getReq.result;
            if (!item) return resolve(null);
            const updated = { ...item, ...updates };
            store.put(updated);
        };
        tx.oncomplete = () => resolve(getReq.result);
        tx.onerror    = () => reject(tx.error);
    });
}

export async function removeFromOutbox(uuid) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').delete(uuid);
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}

export async function clearOutbox() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').clear();
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}

// ─── CACHE OPERATIONS ─────────────────────────────────────────────────

export async function setCachedData(key, data) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cache', 'readwrite');
        tx.objectStore('cache').put({
            key,
            data,
            cachedAt: Date.now(),
        });
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}

export async function getCachedData(key) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cache', 'readonly');
        const req = tx.objectStore('cache').get(key);
        req.onsuccess = () => resolve(req.result ? req.result.data : null);
        req.onerror   = () => reject(req.error);
    });
}

export async function getAllCachedData() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cache', 'readonly');
        const req = tx.objectStore('cache').getAll();
        req.onsuccess = () => {
            const result = {};
            for (const row of req.result) {
                result[row.key] = row.data;
            }
            resolve(result);
        };
        req.onerror = () => reject(req.error);
    });
}

export async function clearCache() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cache', 'readwrite');
        tx.objectStore('cache').clear();
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}
