<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">👥 Investors & Settlement</h2>
            <div class="flex gap-2 sm:gap-3">
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-investment-modal'))" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700">+ Investment</button>
                <a href="{{ route('investors.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Investor</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ============================================================= --}}
            {{-- MOBILE: SUMMARY CARDS (Total Capital + Investor Count)        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-2 gap-2 mb-3">
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Total Capital</p>
                    <p class="mg-stat-value">{{ number_format($totalCapital, 0) }}</p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Investors</p>
                    <p class="mg-stat-value">{{ count($investors) }}</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: INVESTOR CARDS                                        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only">
                {{-- Expense split warning --}}
                @if (count($investors) > 0 && abs($totalExpenseSplit - 100) > 0.5)
                    <div class="mb-3 rounded-xl bg-amber-50 p-3 border border-amber-200">
                        <p class="text-xs font-medium text-amber-800">
                            ⚠️ Expense Split totals {{ number_format($totalExpenseSplit, 1) }}% — should be 100%.
                        </p>
                    </div>
                @endif

                {{-- Mobile search --}}
                <div class="mg-search mb-3">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <form method="GET" action="{{ route('investors.index') }}">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / email / phone…" />
                    </form>
                </div>

                @forelse ($investors as $investor)
                    <div class="mobile-card-item">
                        <a href="{{ route('investors.show', $investor) }}" class="block">
                            <div class="mobile-card-row mb-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="mg-avatar">{{ strtoupper(substr($investor->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-gray-900 text-sm truncate">{{ $investor->name }}</span>
                                            @if ($investor->isManager())
                                                <span class="mg-pill mg-pill-gray">MGR</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-400 truncate">{{ $investor->email ?? $investor->phone ?? 'Investor' }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-gray-900">{{ number_format($investor->total_capital, 0) }}</p>
                                    <p class="text-xs text-gray-400">{{ number_format($investor->share_pct, 1) }}%</p>
                                </div>
                            </div>

                            {{-- Progress bar --}}
                            <div class="mg-progress mb-2">
                                <div class="mg-progress-bar" style="width: {{ min($investor->share_pct, 100) }}%"></div>
                            </div>

                            {{-- Payment info + settle --}}
                            <div class="mobile-card-row">
                                <p class="text-xs text-gray-400">Exp paid {{ number_format($investor->expense_paid, 0) }} / owed {{ number_format($investor->expense_owed, 0) }}</p>
                                <span class="mg-settle {{ $investor->settlement_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Settle {{ $investor->settlement_balance >= 0 ? '+' : '' }}{{ number_format($investor->settlement_balance, 0) }}
                                </span>
                            </div>
                        </a>
                        <div class="flex items-center gap-4 pt-2 mt-2 border-t border-gray-50">
                            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-investment-modal', { detail: { investorId: {{ $investor->id }} } }))" class="text-xs text-emerald-600 font-medium">💰 Investment</button>
                            <a href="{{ route('investors.edit', $investor) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            <form method="POST" action="{{ route('investors.destroy', $investor) }}" class="inline" onsubmit="return confirm('Delete this investor?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No investors yet.</p>
                        <a href="{{ route('investors.create') }}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">+ Add Investor</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $investors->withQueryString()->links() }}
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP ONLY (filters + table)                                --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table">
                {{-- Expense split warning --}}
                @if (count($investors) > 0 && abs($totalExpenseSplit - 100) > 0.5)
                    <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-amber-50 p-3 sm:p-4 border border-amber-200">
                        <p class="text-xs sm:text-sm font-medium text-amber-800">
                            ⚠️ Expense Split % totals {{ number_format($totalExpenseSplit, 1) }}% — should be 100%.
                        </p>
                    </div>
                @endif

                {{-- Filters --}}
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 mb-4 sm:mb-6" x-data="{ open: false }">
                    <button class="filter-toggle" @click="open = !open" :class="{ 'open': open }">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Search Investors
                        </span>
                        <svg class="chevron w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="filter-body" :class="{ 'open': open }">
                        <form method="GET" action="{{ route('investors.index') }}" class="space-y-0">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/email/phone…" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm" />
                            <div class="filter-actions">
                                <button type="submit" class="bg-indigo-600 text-white">Search</button>
                                <a href="{{ route('investors.index') }}" class="bg-gray-100 text-gray-600">Reset</a>
                            </div>
                        </form>
                    </div>
                    {{-- Desktop version --}}
                <div class="hidden sm:flex sm:flex-wrap sm:gap-3 sm:items-center">
                    <input type="text" id="filter-q" placeholder="Search name/email/phone…" class="border border-gray-300 rounded-md px-3 py-2 text-sm flex-1 min-w-0 sm:max-w-xs" />
                    <button type="button" id="filter-reset" class="text-sm text-gray-500 hover:underline">Reset</button>
                </div>
                </div>

                {{-- Settlement explanation (desktop only) --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 hidden sm:block">
                    <p class="text-sm text-blue-800">
                        <strong>How Settlement Works:</strong> Ownership % is driven by each investor's capital contribution.
                        Expense Split % is editable and must total 100%.
                        <strong>Settlement Balance = Paid − Owed</strong>. Positive = overpaid (pool owes them). Negative = they owe the pool/manager.
                    </p>
                </div>

                {{-- Desktop table --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <table id="investors-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th>Investor</th>
                                <th>Role</th>
                                <th class="text-right!">Capital</th>
                                <th class="text-right!">Own %</th>
                                <th class="text-right!">Herd Share</th>
                                <th class="text-right!">Profit Share</th>
                                <th class="text-right!">Split %</th>
                                <th class="text-right!">Exp Owed</th>
                                <th class="text-right!">Exp Paid</th>
                                <th class="text-right!">Settlement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- QUICK-ADD INVESTMENT MODAL (Alpine.js)                         --}}
    {{-- ============================================================= --}}
    <div x-data="{
            open: false,
            investorId: '',
            init() {
                window.addEventListener('open-investment-modal', (e) => {
                    this.investorId = e.detail?.investorId || '';
                    this.open = true;
                });
                @if (session('errors'))
                    @php $errs = session('errors'); @endphp
                    @if ($errs && ($errs->has('amount') || $errs->has('date') || $errs->has('investor_id')))
                        this.open = true;
                    @endif
                @endif
            }
        }" x-init="init()"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none;">
        {{-- Backdrop --}}
        <div x-show="open" @click="open = false" class="absolute inset-0 bg-gray-900/50"></div>
        {{-- Modal --}}
        <div x-show="open" x-transition
             class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto">
            <form method="POST" action="{{ route('investments.quick-add') }}">
                @csrf
                <div class="p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">💰 Add Investment</h3>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @if ($allInvestors->isEmpty())
                        <div class="text-center py-6">
                            <p class="text-sm text-gray-500 mb-3">No investors available yet.</p>
                            <a href="{{ route('investors.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">+ Add Investor</a>
                        </div>
                    @else
                        <div class="space-y-4">
                            {{-- Investor Select --}}
                            <div>
                                <label for="quick-investor" class="block text-sm font-medium text-gray-700 mb-1">Investor *</label>
                                <select name="investor_id" id="quick-investor" x-model="investorId"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="" disabled>Select an investor…</option>
                                    @foreach ($allInvestors as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('investor_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label for="quick-amount" class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                                <input type="number" name="amount" id="quick-amount" step="0.01" min="0.01" value="{{ old('amount') }}"
                                       placeholder="e.g. 50000"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                                @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Date --}}
                            <div>
                                <label for="quick-date" class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                                <input type="date" name="date" id="quick-date" value="{{ old('date', date('Y-m-d')) }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                                @error('date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label for="quick-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" id="quick-notes" rows="2" placeholder="Optional notes…"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>

                @if (!$allInvestors->isEmpty())
                    <div class="px-5 sm:px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                        <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">Record Investment</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initServerDataTable === 'undefined') return;

            var tbl = window.initServerDataTable('#investors-table', '{{ route('investors.data') }}', {
                order: [[0, 'asc']],
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'role', name: 'role' },
                    { data: 'total_capital', name: 'total_capital', className: 'text-right' },
                    { data: 'share_pct', name: 'share_pct', className: 'text-right' },
                    { data: 'herd_share', name: 'herd_share', className: 'text-right' },
                    { data: 'profit_share', name: 'profit_share', className: 'text-right' },
                    { data: 'expense_split_pct', name: 'expense_split_pct', className: 'text-right' },
                    { data: 'expense_owed', name: 'expense_owed', className: 'text-right' },
                    { data: 'expense_paid', name: 'expense_paid', className: 'text-right' },
                    { data: 'settlement_balance', name: 'settlement_balance', className: 'text-right' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
            });

            // Search input integration
            var searchInput = document.getElementById('filter-q');
            if (searchInput) {
                var debounceTimer;
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        tbl.search(searchInput.value).draw();
                    }, 300);
                });
            }
            document.getElementById('filter-reset')?.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                tbl.search('').draw();
            });
        });
    </script>
</x-app-layout>
