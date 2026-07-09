/**
 * Form interceptor — captures POST/PUT/PATCH/DELETE form submissions
 * when the device is offline and queues them to the IndexedDB outbox.
 *
 * Strategy:
 *   1. On page load, attach submit listeners to ALL forms on the page.
 *   2. When a form is submitted:
 *      a. If online → let it through (normal server submit, no change).
 *      b. If offline → prevent default, serialize the form data, queue
 *         it to the outbox, show a toast, and redirect to the relevant
 *         list page (same as the controller would do).
 *
 * Entity/action detection:
 *   The interceptor reads the form's action URL + method to determine
 *   which entity and action it maps to. The ROUTE_MAP below maps URL
 *   patterns to { entity, action }.
 *
 * File inputs (photos):
 *   File inputs are read as base64 and stored as `photo_base64` in the
 *   data payload. The server's SyncController decodes + saves them.
 */

import { addToOutbox } from './db.js';
import { parseEntityFromForm, getRedirectUrl } from './routes.js';

function isOnline() {
    return navigator.onLine;
}

function generateUUID() {
    if (crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}

/**
 * Serialize a form element into a plain key-value object.
 * Handles text, select, checkbox, radio, textarea, and file inputs.
 */
async function serializeForm(form) {
    const data = {};
    const formData = new FormData(form);

    // Handle standard fields
    for (const [key, value] of formData.entries()) {
        // Skip Laravel's @csrf hidden field and @method override
        if (key === '_token' || key === '_method') continue;

        // Handle array fields like kids[0][tag_id]
        if (key.includes('[')) {
            const result = parseArrayKey(key, value, data);
            data[result.key] = result.value;
            continue;
        }

        // If key already exists, convert to array
        if (key in data) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }

    // Handle checkbox fields that FormData doesn't include when unchecked
    for (const input of form.querySelectorAll('input[type="checkbox"]')) {
        if (input.name && !input.checked) {
            // unchecked checkboxes → treat as 0/false (matches Laravel boolean casting)
            if (!(input.name in data)) {
                data[input.name] = input.value === '1' ? 0 : false;
            }
        }
    }

    // Handle file inputs (read as base64)
    for (const input of form.querySelectorAll('input[type="file"]')) {
        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            const base64 = await readFileAsBase64(file);
            data[input.name + '_base64'] = base64;
            // Remove the original file key (can't serialize File object)
            delete data[input.name];
        }
    }

    return data;
}

/**
 * Parse array-style keys like "kids[0][tag_id]" into nested objects.
 */
function parseArrayKey(key, value, existingData) {
    // Match patterns like kids[0][tag_id] or kids[0][gender]
    const match = key.match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
    if (match) {
        const [, arrayName, index, fieldName] = match;
        if (!existingData[arrayName]) existingData[arrayName] = [];
        if (!existingData[arrayName][parseInt(index)]) existingData[arrayName][parseInt(index)] = {};
        existingData[arrayName][parseInt(index)][fieldName] = value;
        return { key: arrayName, value: existingData[arrayName] };
    }

    // Match patterns like items[] or tags[]
    const simpleArrayMatch = key.match(/^(\w+)\[\]$/);
    if (simpleArrayMatch) {
        const [, arrayName] = simpleArrayMatch;
        if (!existingData[arrayName]) existingData[arrayName] = [];
        existingData[arrayName].push(value);
        return { key: arrayName, value: existingData[arrayName] };
    }

    // Fallback: keep the raw key
    return { key, value };
}

function readFileAsBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result); // includes data:image/...;base64, prefix
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

/**
 * Show a toast notification for offline save confirmation.
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bg = type === 'success' ? '#059669' : '#dc2626';
    toast.style.cssText = `
        position: fixed;
        top: max(16px, env(safe-area-inset-top));
        left: 50%;
        transform: translateX(-50%);
        background: ${bg};
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        max-width: 90vw;
        text-align: center;
        animation: offline-toast-in 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    // Add animation styles once
    if (!document.getElementById('offline-toast-style')) {
        const style = document.createElement('style');
        style.id = 'offline-toast-style';
        style.textContent = `
            @keyframes offline-toast-in {
                from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
                to { opacity: 1; transform: translateX(-50%) translateY(0); }
            }
        `;
        document.head.appendChild(style);
    }

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Attach submit interceptors to all forms on the current page.
 * Called on DOMContentLoaded and after any dynamic form insertion.
 */
export function attachInterceptors() {
    const forms = document.querySelectorAll('form');
    for (const form of forms) {
        if (form.dataset.offlineInterceptor) continue; // already attached
        form.dataset.offlineInterceptor = 'true';

        form.addEventListener('submit', async (e) => {
            // Only intercept when offline
            if (isOnline()) return;

            // Prevent double-submit
            if (form.dataset.offlineSubmitting === 'true') {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            const info = parseEntityFromForm(form);
            if (!info) return; // not a form we handle (e.g. logout, login)

            e.preventDefault();
            e.stopPropagation();
            form.dataset.offlineSubmitting = 'true';

            // Serialize form data
            const data = await serializeForm(form);

            // Inject parent IDs from the URL (e.g. goat_id, investor_id)
            // for nested resource forms (health-records, investments, ownership)
            if (info.parentId) {
                Object.assign(data, info.parentId);
            }

            // Queue to outbox
            const entry = {
                uuid: generateUUID(),
                entity: info.entity,
                action: info.action,
                data,
                record_id: info.record_id || null,
                createdAt: Date.now(),
                status: 'pending',
                error: null,
            };

            try {
                await addToOutbox(entry);
                showToast('Saved offline — will sync when connected', 'success');

                // Update pending count in the Alpine store
                if (window.Alpine && Alpine.store('offline')) {
                    Alpine.store('offline').pendingCount++;
                }

                // Redirect to the appropriate list page (same as controller redirect)
                const redirectUrl = getRedirectUrl(info, data);
                if (redirectUrl) {
                    // Use setTimeout to let the toast render first
                    setTimeout(() => { window.location.href = redirectUrl; }, 600);
                } else {
                    // Fallback: redirect to dashboard
                    setTimeout(() => { window.location.href = '/dashboard'; }, 600);
                }
            } catch (err) {
                console.error('[OfflineInterceptor] Failed to queue form:', err);
                showToast('Failed to save offline entry', 'error');
            }
        }, true); // capture phase — intercept before other handlers
    }
}

/**
 * Re-attach interceptors after HTMX/Turbo/dynamic form insertion.
 */
export function reattach() {
    attachInterceptors();
}
