/**
 * Centralised DataTables defaults + per-page initialiser.
 *
 * Usage in Blade:
 *   <script>
 *     document.addEventListener('DOMContentLoaded', () => {
 *       window.initServerDataTable('#goats-table', '{{ route('goats.data') }}', { ... });
 *     });
 *   </script>
 *
 * Or for client-side tables:
 *   window.initClientDataTable('#breeds-table', { ... });
 */

// Shared defaults applied to every DataTable
const commonDefaults = {
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100, [ -1, 'All' ]],
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

// Tailwind-ish styling overrides
const styleOverrides = {
    // Apply Tailwind classes to DataTables control elements
    dom: '<"flex flex-wrap items-center justify-between gap-3 mb-3"' +
            '<"flex-1 min-w-[200px]"f>' +
            '<"flex items-center gap-3"l>>' +
         'rt' +
         '<"flex flex-wrap items-center justify-between gap-3 mt-3"' +
            '<"text-sm text-gray-500"i>' +
            '<"flex-1 flex justify-end"p>>',
};

/**
 * Initialise a server-side DataTables instance.
 *
 * @param {string} selector   CSS selector for the <table>
 * @param {string} ajaxUrl    URL returning Yajra DataTables JSON
 * @param {object} extra      Column defs, options to merge
 */
window.initServerDataTable = function (selector, ajaxUrl, extra = {}) {
    if (typeof $ === 'undefined') return;

    const config = {
        ...commonDefaults,
        ...styleOverrides,
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'GET',
            data: function (d) {
                // Merge extra params (filters) that can be dynamically changed
                if (extra.extraData && typeof extra.extraData === 'function') {
                    Object.assign(d, extra.extraData());
                }
            },
        },
        columns: extra.columns || [],
        columnDefs: extra.columnDefs || [],
        order: extra.order || [[0, 'desc']],
        drawCallback: extra.drawCallback || function () {},
    };

    return $(selector).DataTable(config);
};

/**
 * Initialise a client-side DataTables instance (no AJAX — all data in DOM).
 *
 * @param {string} selector
 * @param {object} extra  Column defs, options
 */
window.initClientDataTable = function (selector, extra = {}) {
    if (typeof $ === 'undefined') return;

    const config = {
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
