<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">💸 Expenses &amp; Income</h2>
            <a href="{{ route('transactions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Transaction</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Summary Cards (respect table filters) --}}
            @php
                $catColors = ['border-amber-500', 'border-teal-500', 'border-indigo-500'];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Total Expenses</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['total_expense'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-green-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Total Income</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['total_income'], 0) }}</p>
                </div>
                @foreach ($summary['categories'] as $index => $cat)
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 {{ $catColors[$index % 3] }}">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">{{ $cat->category }}</p>
                        <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($cat->total, 0) }}</p>
                    </div>
                @endforeach
            </div>

            {{-- ============================================================= --}}
            {{-- BULK ACTION BAR (hidden until items are selected)            --}}
            {{-- ============================================================= --}}
            <div id="bulkActionBar" class="hidden mb-4 rounded-xl bg-indigo-50 border border-indigo-200 p-3 sm:p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm font-semibold text-indigo-800">
                    <span id="bulkCount">0</span> selected
                </span>

                {{-- Bulk Assign Investor --}}
                <form method="POST" action="{{ route('transactions.bulk-assign') }}" class="inline-flex items-center gap-2" id="bulkAssignForm">
                    @csrf @method('PATCH')
                    <div id="bulkAssignIdsContainer" style="display:contents;"></div>
                    <select name="investor_id" id="bulkInvestorSelect" class="rounded-md border-gray-300 text-sm py-1.5 pr-8" required>
                        <option value="">Assign investor…</option>
                        @foreach ($investors as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                        Assign
                    </button>
                </form>

                {{-- Bulk Delete --}}
                <form method="POST" action="{{ route('transactions.bulk-destroy') }}" class="inline-flex items-center gap-2" id="bulkDeleteForm" data-confirm="Delete the selected transactions? This cannot be undone.">
                    @csrf @method('DELETE')
                    <div id="bulkDeleteIdsContainer" style="display:contents;"></div>
                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                        🗑 Delete Selected
                    </button>
                </form>

                <button type="button" id="bulkClearBtn" class="ml-auto text-sm text-gray-500 hover:text-gray-700 font-medium">
                    Clear selection
                </button>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP TABLE (hidden on mobile)                              --}}
            {{-- ============================================================= --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 desktop-only-table">
                <table id="transactions-table" class="w-full text-sm display">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th><input type="checkbox" id="txnSelectAll" class="cursor-pointer" style="accent-color: #4f46e5;"></th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Paid By</th>
                            <th>Method</th>
                            <th>Recurring</th>
                            <th class="text-right!">Budget</th>
                            <th class="text-right!">Amount</th>
                            <th class="text-right!">Variance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE CARD LIST                                              --}}
            {{-- ============================================================= --}}
            <div class="mobile-card-list">
                @forelse ($transactions as $t)
                    <div class="mobile-card-item">
                        <div class="mobile-card-row mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <input type="checkbox" class="txn-mobile-check cursor-pointer shrink-0" value="{{ $t->id }}" style="accent-color: #4f46e5;">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium shrink-0
                                    {{ $t->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $t->type === 'income' ? '↗' : '↘' }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $t->category }}</p>
                                    <p class="text-xs text-gray-400">{{ $t->date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 font-bold text-base {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 0) }}
                            </span>
                        </div>
                        @if ($t->notes)
                            <p class="text-xs text-gray-500 ml-10 truncate">{{ $t->notes }}</p>
                        @endif
                        @if ($t->investor || $t->budget_amount || $t->is_recurring)
                            <div class="flex items-center gap-3 ml-10 mt-1 text-[10px] text-gray-400">
                                @if ($t->investor) <span>{{ $t->investor->name }}</span> @endif
                                @if ($t->is_recurring) <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Recurring</span> @endif
                                @if ($t->budget_amount) <span>Budget: {{ number_format($t->budget_amount, 0) }}</span> @endif
                            </div>
                        @endif
                        <div class="flex items-center gap-4 pt-2 mt-2 border-t border-gray-50">
                            <a href="{{ route('transactions.edit', $t) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            <form method="POST" action="{{ route('transactions.destroy', $t) }}" class="inline" data-confirm="Delete this transaction?">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No transactions found.</p>
                        <a href="{{ route('transactions.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">+ Add Transaction</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $transactions->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ---- Selection tracking (shared across desktop + mobile) ----
            const selectedIds = new Set();
            const bulkActionBar = document.getElementById('bulkActionBar');
            const bulkCountEl = document.getElementById('bulkCount');
            const assignIdsContainer = document.getElementById('bulkAssignIdsContainer');
            const deleteIdsContainer = document.getElementById('bulkDeleteIdsContainer');

            // Populate hidden inputs as ids[] so PHP receives a proper array
            function populateIdsInputs() {
                [assignIdsContainer, deleteIdsContainer].forEach(function (container) {
                    container.innerHTML = '';
                    selectedIds.forEach(function (id) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        container.appendChild(input);
                    });
                });
            }

            function updateBulkBar() {
                const count = selectedIds.size;
                bulkCountEl.textContent = count;
                if (count > 0) {
                    bulkActionBar.classList.remove('hidden');
                    populateIdsInputs();
                } else {
                    bulkActionBar.classList.add('hidden');
                }
            }

            // ---- Desktop: DataTables row checkboxes ----
            const tableEl = document.getElementById('transactions-table');
            const selectAll = document.getElementById('txnSelectAll');

            // Re-sync checkbox states after every DataTables redraw (pagination, search, sort)
            function syncDesktopCheckboxes() {
                tableEl.querySelectorAll('.txn-row-check').forEach(function (cb) {
                    cb.checked = selectedIds.has(parseInt(cb.value));
                    cb.addEventListener('change', function () {
                        const id = parseInt(this.value);
                        if (this.checked) {
                            selectedIds.add(id);
                        } else {
                            selectedIds.delete(id);
                        }
                        updateBulkBar();
                    });
                });
            }

            // Select-all checkbox: selects/deselects all visible rows
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    tableEl.querySelectorAll('.txn-row-check').forEach(function (cb) {
                        const id = parseInt(cb.value);
                        cb.checked = selectAll.checked;
                        if (selectAll.checked) {
                            selectedIds.add(id);
                        } else {
                            selectedIds.delete(id);
                        }
                    });
                    updateBulkBar();
                });
            }

            // ---- Mobile checkboxes ----
            document.querySelectorAll('.txn-mobile-check').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    const id = parseInt(this.value);
                    if (this.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                    updateBulkBar();
                });
            });

            // ---- Clear selection button ----
            document.getElementById('bulkClearBtn').addEventListener('click', function () {
                selectedIds.clear();
                document.querySelectorAll('.txn-row-check, .txn-mobile-check, #txnSelectAll').forEach(function (cb) {
                    cb.checked = false;
                });
                updateBulkBar();
            });

            // ---- Initialise DataTable ----
            if (typeof window.initServerDataTable === 'undefined') return;

            const table = window.initServerDataTable('#transactions-table', '{{ route('transactions.data') }}', {
                order: [[1, 'desc']],
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'date', name: 'date' },
                    { data: 'type', name: 'type' },
                    { data: 'category', name: 'category' },
                    { data: 'notes', name: 'notes' },
                    { data: 'paid_by', name: 'paid_by' },
                    { data: 'payment_method', name: 'payment_method' },
                    { data: 'is_recurring', name: 'is_recurring' },
                    { data: 'budget_amount', name: 'budget_amount', className: 'text-right' },
                    { data: 'amount', name: 'amount', className: 'text-right' },
                    { data: 'variance', name: 'variance', className: 'text-right' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                filters: [
                    {
                        key: 'type',
                        label: 'All Types',
                        options: [
                            { value: 'income', text: 'Income' },
                            { value: 'expense', text: 'Expense' },
                        ],
                    },
                    {
                        key: 'category',
                        label: 'All Categories',
                        options: [
                            @foreach ($categories as $cat)
                            { value: '{{ $cat }}', text: '{{ $cat }}' },
                            @endforeach
                        ],
                    },
                    {
                        key: 'paid_by',
                        label: 'All Payers',
                        options: [
                            @foreach ($investors as $inv)
                            { value: '{{ $inv->id }}', text: '{{ $inv->name }}' },
                            @endforeach
                        ],
                    },
                ],
                drawCallback: function () {
                    syncDesktopCheckboxes();
                },
            });
        });
    </script>
</x-app-layout>
