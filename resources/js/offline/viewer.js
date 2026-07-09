/**
 * Offline data viewer — renders cached server data + pending outbox items
 * into list pages when the device is offline.
 *
 * Strategy:
 *   1. On page load, if offline, check if the page has an empty list
 *      (Blade rendered no data because the server was unreachable).
 *   2. Read cached data from IndexedDB for the current entity type.
 *   3. Read pending outbox items for the same entity type.
 *   4. Render both into the page's list container.
 *
 * Detection of current entity type:
 *   Based on the URL pathname (e.g., /goats → 'goat', /transactions → 'transaction').
 *
 * Rendering:
 *   Uses simple card HTML matching the existing mobile-card-item style.
 *   Pending items are shown with an amber "Pending" badge.
 */

import { getAllCachedData } from './db.js';
import { getAllOutbox } from './db.js';

/**
 * Map the current URL pathname to an entity type and the
 * container selector where list items are rendered.
 */
function detectEntityFromPath() {
    const path = window.location.pathname;

    if (path === '/goats' || path.startsWith('/goats')) return { entity: 'goat', listType: 'goats' };
    if (path === '/transactions') return { entity: 'transaction', listType: 'transactions' };
    if (path === '/investors') return { entity: 'investor', listType: 'investors' };
    if (path === '/caretakers') return { entity: 'caretaker', listType: 'caretakers' };
    if (path === '/breeds') return { entity: 'breed', listType: 'breeds' };
    if (path === '/breeding') return { entity: 'breeding_record', listType: 'breeding_records' };
    if (path === '/budget') return { entity: 'budget_entry', listType: 'budget_entries' };

    return null;
}

/**
 * Render a single item as a card based on entity type.
 * Uses the mobile-card-item CSS classes.
 */
function renderItem(entity, data, isPending) {
    const badge = isPending
        ? '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full px-2 py-0.5">Pending</span>'
        : '';

    const escape = (s) => {
        const div = document.createElement('div');
        div.textContent = s ?? '';
        return div.innerHTML;
    };

    const money = (v) => v ? '$' + Number(v).toLocaleString('en-US', { maximumFractionDigits: 2 }) : '';

    switch (entity) {
        case 'goat':
            return `
                <div class="mobile-card-item ${isPending ? 'ring-2 ring-amber-300' : ''}">
                    <div class="mobile-card-row">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold">
                                ${escape((data.name || data.tag_id || '?')[0])}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">${escape(data.name || data.tag_id || 'Unnamed')}</p>
                                <p class="text-xs text-gray-500">${escape(data.tag_id || '')} • ${escape(data.gender || '')} • ${escape(data.status || '')}</p>
                            </div>
                        </div>
                        ${badge}
                    </div>
                </div>`;

        case 'transaction':
            const isIncome = data.type === 'income';
            return `
                <div class="mobile-card-item ${isPending ? 'ring-2 ring-amber-300' : ''}">
                    <div class="mobile-card-row">
                        <div>
                            <p class="font-semibold text-gray-900">${escape(data.category || 'Transaction')}</p>
                            <p class="text-xs text-gray-500">${escape(data.date || '')} • ${escape(data.type || '')}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold ${isIncome ? 'text-emerald-600' : 'text-red-600'}">
                                ${isIncome ? '+' : '-'}${money(data.amount)}
                            </p>
                            ${badge}
                        </div>
                    </div>
                </div>`;

        case 'investor':
            return `
                <div class="mobile-card-item ${isPending ? 'ring-2 ring-amber-300' : ''}">
                    <div class="mobile-card-row">
                        <div>
                            <p class="font-semibold text-gray-900">${escape(data.name || 'Investor')}</p>
                            <p class="text-xs text-gray-500">${escape(data.role || '')} ${data.email ? '• ' + escape(data.email) : ''}</p>
                        </div>
                        ${badge}
                    </div>
                </div>`;

        case 'caretaker':
            return `
                <div class="mobile-card-item ${isPending ? 'ring-2 ring-amber-300' : ''}">
                    <div class="mobile-card-row">
                        <div>
                            <p class="font-semibold text-gray-900">${escape(data.name || 'Caretaker')}</p>
                            <p class="text-xs text-gray-500">${data.phone ? escape(data.phone) + ' • ' : ''}Every ${escape(data.every_nth_kid || 5)}th kid</p>
                        </div>
                        ${badge}
                    </div>
                </div>`;

        case 'breed':
            return `
                <div class="mobile-card-item ${isPending ? 'ring-2 ring-amber-300' : ''}">
                    <div class="mobile-card-row">
                        <div>
                            <p class="font-semibold text-gray-900">${escape(data.name || 'Breed')}</p>
                            <p class="text-xs text-gray-500">${escape(data.description || '')}</p>
                        </div>
                        ${badge}
                    </div>
                </div>`;

        default:
            return `
                <div class="mobile-card-item ${isPending ? 'ring-2 ring-amber-300' : ''}">
                    <div class="mobile-card-row">
                        <div>
                            <p class="font-semibold text-gray-900">${escape(Object.values(data).filter(v => typeof v === 'string')[0] || 'Entry')}</p>
                        </div>
                        ${badge}
                    </div>
                </div>`;
    }
}

/**
 * Find the list container on the page.
 * Looks for common patterns used in the Blade views.
 */
function findListContainer() {
    // Try common selectors from the Blade templates
    const candidates = [
        '.space-y-3',           // common list container
        '[data-list]',          // explicit data attribute
        '.mobile-list',         // mobile list
        'main .divide-y',       // divide-y list
        'main > div > div',     // generic content container
    ];

    for (const sel of candidates) {
        const el = document.querySelector(sel);
        if (el && el.children.length <= 1) return el; // empty or nearly empty
    }

    // If nothing found, create a container in <main>
    const main = document.querySelector('main');
    if (main) {
        const container = document.createElement('div');
        container.className = 'space-y-3 mt-4';
        main.appendChild(container);
        return container;
    }

    return null;
}

/**
 * Check if the page shows an empty state (no data from server).
 */
function pageIsEmpty() {
    const body = document.body.textContent || '';
    // Check for common empty-state phrases in the app
    return body.includes('No records') || body.includes('No goats') ||
           body.includes('No transactions') || body.includes('No investors') ||
           body.includes('No breeds') || body.includes('No caretakers') ||
           body.includes('nothing here') || body.includes('No data');
}

/**
 * Main entry point — called on page load when offline.
 * Renders cached data and pending items into the page.
 */
export async function renderOfflineData() {
    if (navigator.onLine) return; // only when offline

    const detected = detectEntityFromPath();
    if (!detected) return; // not a list page we handle

    const cached = await getAllCachedData();
    const outbox = await getAllOutbox();

    const cachedItems = cached[detected.listType]?.items || [];
    const pendingItems = outbox.filter(
        item => item.entity === detected.entity && (item.status === 'pending' || item.status === 'failed')
    );

    const totalItems = cachedItems.length + pendingItems.length;
    if (totalItems === 0) return;

    // Find or create the list container
    const container = findListContainer();
    if (!container) return;

    // Only inject if the page is empty or has minimal content
    if (!pageIsEmpty() && container.children.length > 2) return;

    // Build the offline data section
    const section = document.createElement('div');
    section.className = 'offline-data-section mt-4 mb-4';
    section.innerHTML = `
        <div class="flex items-center gap-2 mb-3 px-1">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-semibold text-amber-700">
                Offline Data (${totalItems} item${totalItems !== 1 ? 's' : ''})
            </span>
        </div>
    `;

    const list = document.createElement('div');
    list.className = 'space-y-2';

    // Render pending items first (with amber badge)
    for (const item of pendingItems) {
        const card = renderItem(detected.entity, item.data, true);
        list.insertAdjacentHTML('beforeend', card);
    }

    // Render cached items
    for (const item of cachedItems) {
        const card = renderItem(detected.entity, typeof item === 'object' ? item : { value: item }, false);
        list.insertAdjacentHTML('beforeend', card);
    }

    section.appendChild(list);
    container.insertBefore(section, container.firstChild);
}
