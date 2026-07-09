/**
 * Sync engine — replays queued outbox items to the server.
 *
 * Reads all pending items from the outbox, batches them into a single
 * POST /api/sync/push, processes results, removes successful items,
 * and marks failed items with their error.
 *
 * Returns: { synced, errors, remaining }
 */

import {
    getAllOutbox,
    updateOutboxItem,
    removeFromOutbox,
    setCachedData,
} from './db.js';

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

export async function syncOutbox() {
    const items = await getAllOutbox();
    if (items.length === 0) {
        return { synced: 0, errors: 0, remaining: 0 };
    }

    // Mark all as 'syncing'
    for (const item of items) {
        if (item.status !== 'failed') {
            await updateOutboxItem(item.uuid, { status: 'syncing' });
        }
    }

    // Build batch payload
    const operations = items.map(item => ({
        id:        item.uuid,
        entity:    item.entity,
        action:    item.action,
        data:      item.data,
        record_id: item.record_id || null,
    }));

    let response;
    try {
        response = await fetch('/api/sync/push', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ operations }),
        });
    } catch (e) {
        // Network error — mark all back as pending
        for (const item of items) {
            await updateOutboxItem(item.uuid, {
                status: 'pending',
                error: null,
            });
        }
        throw new Error('Network error during sync. Will retry automatically.');
    }

    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Server responded ${response.status}: ${errorText.substring(0, 200)}`);
    }

    const json = await response.json();
    const resultsById = {};
    for (const r of (json.results || [])) {
        resultsById[r.id] = r;
    }

    let synced = 0;
    let errors = 0;

    for (const item of items) {
        const result = resultsById[item.uuid];

        if (!result) {
            errors++;
            continue;
        }

        if (result.status === 'success') {
            await removeFromOutbox(item.uuid);
            synced++;
        } else {
            await updateOutboxItem(item.uuid, {
                status: 'failed',
                error: typeof result.error === 'string'
                    ? result.error
                    : JSON.stringify(result.error),
            });
            errors++;
        }
    }

    // After successful push, pull fresh data to update the cache
    try {
        await pullAndCache();
    } catch (e) {
        console.warn('[Sync] Pull after push failed (non-fatal):', e);
    }

    const remaining = await getAllOutbox();
    return { synced, errors, remaining: remaining.length };
}

/**
 * Fetch all data from the server and cache it in IndexedDB for offline viewing.
 */
export async function pullAndCache() {
    let response;
    try {
        response = await fetch('/api/sync/pull', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });
    } catch (e) {
        console.warn('[Sync] Pull failed — network error:', e);
        return null;
    }

    if (!response.ok) {
        console.warn('[Sync] Pull failed — HTTP', response.status);
        return null;
    }

    const json = await response.json();

    if (json.data) {
        for (const [key, value] of Object.entries(json.data)) {
            await setCachedData(key, {
                items: value,
                syncedAt: json.synced_at,
            });
        }
    }

    return json;
}
