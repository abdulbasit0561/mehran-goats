/**
 * Route mapping — maps form action URLs + HTTP methods to
 * { entity, action, record_id } used by the offline interceptor.
 *
 * Also provides getRedirectUrl() which tells the interceptor where
 * to navigate after queuing a form offline (matching the controller redirects).
 */

/**
 * Parse a form element to determine the entity, action, and record_id.
 *
 * @param {HTMLFormElement} form
 * @returns {{ entity: string, action: string, record_id: number|null } | null}
 */
export function parseEntityFromForm(form) {
    const action = form.getAttribute('action') || '';
    const method = (form.querySelector('input[name="_method"]')?.value || form.getAttribute('method') || 'POST').toUpperCase();
    const url = new URL(action, window.location.origin);

    return matchRoute(url.pathname, method, form);
}
function matchRoute(path, method, form) {
    // Skip auth/profile/logout forms — these shouldn't be queued offline
    if (path === '/logout' || path.startsWith('/login') || path.startsWith('/register')
        || path.startsWith('/forgot-password') || path.startsWith('/reset-password')
        || path === '/profile' || path === '/api/sync/push') {
        return null;
    }

    // Also skip if the form is a GET form (search, filters)
    if (method === 'GET') return null;

    // ─── CREATE (POST) ──────────────────────────────────────────────

    if (method === 'POST') {
        // goats.store: POST /goats
        if (path === '/goats') return { entity: 'goat', action: 'create', record_id: null };

        // goats.bulk-destroy: POST /goats/bulk (bulk delete — skip offline)
        if (path === '/goats/bulk') return null;

        // caretakers.store: POST /caretakers
        if (path === '/caretakers') return { entity: 'caretaker', action: 'create', record_id: null };

        // breeds.store: POST /breeds
        if (path === '/breeds') return { entity: 'breed', action: 'create', record_id: null };

        // investors.store: POST /investors
        if (path === '/investors') return { entity: 'investor', action: 'create', record_id: null };

        // transactions.store: POST /transactions
        if (path === '/transactions') return { entity: 'transaction', action: 'create', record_id: null };

        // budget.store: POST /budget
        if (path === '/budget') return { entity: 'budget_entry', action: 'create', record_id: null };

        // budget.seed: POST /budget/seed (auto-seed — skip offline)
        if (path === '/budget/seed') return null;

        // investments.quick-add: POST /investments/quick-add
        if (path === '/investments/quick-add') {
            return { entity: 'investment', action: 'create', record_id: null };
        }

        // investors.investments.store: POST /investors/{id}/investments
        let m = path.match(/^\/investors\/(\d+)\/investments$/);
        if (m) {
            return { entity: 'investment', action: 'create', record_id: null, parentId: { investor_id: parseInt(m[1]) } };
        }

        // investors.ownership.store: POST /investors/{id}/ownership
        m = path.match(/^\/investors\/(\d+)\/ownership$/);
        if (m) {
            return { entity: 'ownership_unit', action: 'create', record_id: null, parentId: { investor_id: parseInt(m[1]) } };
        }

        // goats.health-records.store: POST /goats/{id}/health-records
        m = path.match(/^\/goats\/(\d+)\/health-records$/);
        if (m) {
            return { entity: 'health_record', action: 'create', record_id: null, parentId: { goat_id: parseInt(m[1]) } };
        }

        // goats.breeding-records.store: POST /goats/{id}/breeding-records
        m = path.match(/^\/goats\/(\d+)\/breeding-records$/);
        if (m) {
            return { entity: 'breeding_record', action: 'create', record_id: null };
        }

        // breeding-records.mark-pregnant: POST /breeding-records/{id}/mark-pregnant
        // breeding-records.mark-open: POST /breeding-records/{id}/mark-open
        // breeding-records.kidding.store: POST /breeding-records/{id}/kidding
        // These are complex operations — skip for offline (they need live data)
        if (path.match(/^\/breeding-records\/\d+\/(mark-pregnant|mark-open|kidding)$/)) {
            return null;
        }

        // reports.distribute: POST /reports/distribute — skip offline
        // reports.distributions.mark-paid: POST /reports/distributions/{id}/mark-paid — skip offline
        if (path.startsWith('/reports/')) return null;

        // budget.inline: POST /budget/{id}/inline — skip offline
        if (path.match(/^\/budget\/\d+\/inline$/)) return null;
    }

    // ─── UPDATE (PUT / PATCH) ───────────────────────────────────────

    if (method === 'PUT' || method === 'PATCH') {
        // goats.update: PUT /goats/{id}
        let m = path.match(/^\/goats\/(\d+)$/);
        if (m) return { entity: 'goat', action: 'update', record_id: parseInt(m[1]) };

        // caretakers.update: PUT /caretakers/{id}
        m = path.match(/^\/caretakers\/(\d+)$/);
        if (m) return { entity: 'caretaker', action: 'update', record_id: parseInt(m[1]) };

        // breeds.update: PUT /breeds/{id}
        m = path.match(/^\/breeds\/(\d+)$/);
        if (m) return { entity: 'breed', action: 'update', record_id: parseInt(m[1]) };

        // investors.update: PUT /investors/{id}
        m = path.match(/^\/investors\/(\d+)$/);
        if (m) return { entity: 'investor', action: 'update', record_id: parseInt(m[1]) };

        // transactions.update: PUT /transactions/{id}
        m = path.match(/^\/transactions\/(\d+)$/);
        if (m) return { entity: 'transaction', action: 'update', record_id: parseInt(m[1]) };

        // breeding-records.update: PUT /breeding-records/{id}
        m = path.match(/^\/breeding-records\/(\d+)$/);
        if (m) return { entity: 'breeding_record', action: 'update', record_id: parseInt(m[1]) };

        // budget.update: PATCH /budget/{id}
        m = path.match(/^\/budget\/(\d+)$/);
        if (m) return { entity: 'budget_entry', action: 'update', record_id: parseInt(m[1]) };

        // profile.update: PATCH /profile — skip offline
        if (path === '/profile') return null;
    }

    // ─── DELETE ─────────────────────────────────────────────────────

    if (method === 'DELETE') {
        // Skip profile.destroy
        if (path === '/profile') return null;

        // goats.destroy: DELETE /goats/{id}
        let m = path.match(/^\/goats\/(\d+)$/);
        if (m) return { entity: 'goat', action: 'delete', record_id: parseInt(m[1]) };

        // caretakers.destroy: DELETE /caretakers/{id}
        m = path.match(/^\/caretakers\/(\d+)$/);
        if (m) return { entity: 'caretaker', action: 'delete', record_id: parseInt(m[1]) };

        // breeds.destroy: DELETE /breeds/{id}
        m = path.match(/^\/breeds\/(\d+)$/);
        if (m) return { entity: 'breed', action: 'delete', record_id: parseInt(m[1]) };

        // investors.destroy: DELETE /investors/{id}
        m = path.match(/^\/investors\/(\d+)$/);
        if (m) return { entity: 'investor', action: 'delete', record_id: parseInt(m[1]) };

        // transactions.destroy: DELETE /transactions/{id}
        m = path.match(/^\/transactions\/(\d+)$/);
        if (m) return { entity: 'transaction', action: 'delete', record_id: parseInt(m[1]) };

        // health-records.destroy: DELETE /health-records/{id}
        m = path.match(/^\/health-records\/(\d+)$/);
        if (m) return { entity: 'health_record', action: 'delete', record_id: parseInt(m[1]) };

        // breeding-records.destroy: DELETE /breeding-records/{id}
        m = path.match(/^\/breeding-records\/(\d+)$/);
        if (m) return { entity: 'breeding_record', action: 'delete', record_id: parseInt(m[1]) };

        // investments.destroy: DELETE /investments/{id}
        m = path.match(/^\/investments\/(\d+)$/);
        if (m) return { entity: 'investment', action: 'delete', record_id: parseInt(m[1]) };

        // ownership.destroy: DELETE /ownership/{id}
        m = path.match(/^\/ownership\/(\d+)$/);
        if (m) return { entity: 'ownership_unit', action: 'delete', record_id: parseInt(m[1]) };

        // budget.destroy: DELETE /budget/{id}
        m = path.match(/^\/budget\/(\d+)$/);
        if (m) return { entity: 'budget_entry', action: 'delete', record_id: parseInt(m[1]) };
    }

    // Unknown form — don't intercept
    return null;
}

/**
 * Determine the redirect URL after an offline form save.
 * Matches the controller's redirect targets so the user experience is consistent.
 *
 * @param {{ entity: string, action: string, record_id: number|null }} info
 * @param {object} data  The serialized form data
 * @returns {string|null}
 */
export function getRedirectUrl(info, data) {
    // For creates, redirect to the entity's index page
    // For updates/deletes, redirect to the show or index page
    const redirects = {
        goat:           '/goats',
        transaction:    '/transactions',
        health_record:  data.goat_id ? `/goats/${data.goat_id}` : '/goats',
        breeding_record: data.dam_id ? `/goats/${data.dam_id}` : '/goats',
        investment:     data.investor_id ? `/investors/${data.investor_id}` : '/investors',
        caretaker:      '/caretakers',
        breed:          '/breeds',
        investor:       '/investors',
        budget_entry:   '/budget',
        ownership_unit: data.investor_id ? `/investors/${data.investor_id}` : '/investors',
    };

    return redirects[info.entity] || '/dashboard';
}
