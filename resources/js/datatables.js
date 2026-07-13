/**
 * Centralised DataTables defaults + per-page initialiser.
 *
 * Usage in Blade:
 *   document.addEventListener('DOMContentLoaded', () => {
 *     window.initServerDataTable('#goats-table', '{{ route("goats.data") }}', { ... });
 *   });
 *
 * Or for client-side tables:
 *   window.initClientDataTable('#breeds-table', { ... });
 */

// Shared defaults applied to every DataTable
const commonDefaults = {
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100, [-1, 'All']],
    pagingType: 'simple_numbers', // ‹ 1 2 3 ›  (no first/last double arrows)
    language: {
        search: '',
        searchPlaceholder: 'Search…',
        paginate: { previous: '‹', next: '›' },
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_–_END_ of _TOTAL_',
        infoEmpty: 'No records',
        infoFiltered: '(filtered from _MAX_ total)',
        zeroRecords: 'No matching records',
        emptyTable: 'No data available',
    },
};

// Tailwind-ish styling overrides — DOM layout
const styleOverrides = {
    // If custom filters are provided, they go in the left slot of the top row;
    // otherwise just the DataTables search input.
    dom: '<"flex flex-wrap items-center justify-between gap-3 mb-3"' +
            '<"flex flex-wrap items-center gap-2 min-w-0 dt-filter-bar">' +
            '<"flex items-center gap-3 ml-auto"lf>>' +
         'rt' +
         '<"flex flex-wrap items-center justify-between gap-3 mt-3"' +
            '<"text-sm text-gray-500"i>' +
            '<"flex-1 flex justify-end"p>>',
};

/**
 * Build filter <select> elements and a reset button, inject them into
 * the DataTables toolbar slot (.dt-filter-bar).
 */
function buildFilters(api, tableId, filters) {
    if (!filters || filters.length === 0) return;

    var bar = document.querySelector(tableId + '_wrapper .dt-filter-bar');
    if (!bar) return;

    // Clear any leftover
    bar.innerHTML = '';

    var state = {};

    filters.forEach(function (f) {
        var sel = document.createElement('select');
        sel.className = 'dt-filter-select';
        sel.dataset.filterKey = f.key;

        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = f.label;
        sel.appendChild(opt);

        f.options.forEach(function (o) {
            opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            sel.appendChild(opt);
        });

        sel.addEventListener('change', function () {
            state[f.key] = this.value;
            api.ajax.reload();
        });

        state[f.key] = '';
        bar.appendChild(sel);
    });

    // Reset button
    var reset = document.createElement('button');
    reset.type = 'button';
    reset.className = 'dt-filter-reset';
    reset.textContent = 'Reset';
    reset.addEventListener('click', function () {
        bar.querySelectorAll('select').forEach(function (s) { s.value = ''; });
        Object.keys(state).forEach(function (k) { state[k] = ''; });
        api.search('').draw();
    });
    bar.appendChild(reset);

    // Expose state getter on the API instance
    api._filterState = function () { return Object.assign({}, state); };
}

/**
 * Initialise a server-side DataTables instance.
 *
 * @param {string} selector   CSS selector for the <table>
 * @param {string} ajaxUrl    URL returning Yajra DataTables JSON
 * @param {object} extra      Column defs, options to merge, filters array
 */
window.initServerDataTable = function (selector, ajaxUrl, extra) {
    if (typeof $ === 'undefined') return null;

    var config = {
        ...commonDefaults,
        ...styleOverrides,
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'GET',
            data: function (d) {
                // Merge custom filter values
                if (extra.filters && extra.filters.length > 0) {
                    var bar = document.querySelector(selector + '_wrapper .dt-filter-bar');
                    if (bar) {
                        bar.querySelectorAll('select[data-filter-key]').forEach(function (sel) {
                            d[sel.dataset.filterKey] = sel.value;
                        });
                    }
                }
            },
        },
        columns: extra.columns || [],
        columnDefs: extra.columnDefs || [],
        order: extra.order || [[0, 'desc']],
        drawCallback: extra.drawCallback || function () {},
    };

    var api = $(selector).DataTable(config);

    if (extra.filters && extra.filters.length > 0) {
        buildFilters(api, selector, extra.filters);
    }

    return api;
};

/**
 * Initialise a client-side DataTables instance (no AJAX — all data in DOM).
 */
window.initClientDataTable = function (selector, extra) {
    if (typeof $ === 'undefined') return null;

    var config = {
        ...commonDefaults,
        ...styleOverrides,
        responsive: true,
        order: extra.order || [[0, 'asc']],
        columns: extra.columns || undefined,
        columnDefs: extra.columnDefs || [],
        drawCallback: extra.drawCallback || function () {},
    };

    return $(selector).DataTable(config);
};
