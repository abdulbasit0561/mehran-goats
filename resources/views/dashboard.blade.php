<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📊 Dashboard
        </h2>
    </x-slot>

    {{-- Chart.js --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @endpush

    {{-- Chart data from controller --}}
    @push('scripts')
        <script>
            window.__chartData = @json($chartData);
        </script>
    @endpush

    <div class="py-6 sm:py-12 page-content" x-data="{ tab: 'overview' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            {{-- Flash message --}}
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ============================================================= --}}
            {{-- TAB BAR                                                        --}}
            {{-- ============================================================= --}}
            <div class="mb-6 border-b border-gray-200">
                <div class="flex gap-1">
                    <button @click="tab = 'overview'"
                            :class="tab === 'overview' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 py-3 px-4 sm:px-6 border-b-2 font-semibold text-sm sm:text-base transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Overview
                    </button>
                    <button @click="tab = 'charts'"
                            :class="tab === 'charts' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 py-3 px-4 sm:px-6 border-b-2 font-semibold text-sm sm:text-base transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h2v12h-2V4zM14 4h2v12h-2V4z"/></svg>
                        Charts
                    </button>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- TAB 1: OVERVIEW (existing dashboard)                          --}}
            {{-- ============================================================= --}}
            <div x-show="tab === 'overview'" x-transition.opacity.duration.150ms>

            {{-- ============================================================= --}}
            {{-- MOBILE: TOTAL CAPITAL HERO                                     --}}
            {{-- ============================================================= --}}
            <div class="mobile-only mg-hero mb-3">
                <p class="mg-hero-label">Total Capital</p>
                <p class="mg-hero-value">PKR {{ number_format($kpis['total_capital'], 0) }}</p>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: METRIC GRID (dark-green cards)                        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-3 gap-2 mb-2">
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Net Profit</p>
                    <p class="mg-stat-value {{ $kpis['net_profit'] >= 0 ? '' : '' }}" style="color: {{ $kpis['net_profit'] >= 0 ? '#86efac' : '#fdba74' }}">
                        {{ $kpis['net_profit'] >= 0 ? '+' : '' }}{{ number_format($kpis['net_profit'], 0) }}
                    </p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Return</p>
                    <p class="mg-stat-value" style="color: {{ $kpis['return_pct'] >= 0 ? '#86efac' : '#fdba74' }}">
                        {{ $kpis['return_pct'] >= 0 ? '+' : '' }}{{ number_format($kpis['return_pct'], 1) }}%
                    </p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Realized</p>
                    <p class="mg-stat-value" style="color: #fdba74">
                        +{{ number_format($kpis['realized_profit'], 0) }}
                    </p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: SECONDARY STAT GRID (white cards)                     --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-3 gap-2 mb-3">
                <div class="mg-stat-card-light">
                    <p class="mg-stat-label">Herd Value</p>
                    <p class="mg-stat-value">{{ number_format($kpis['current_herd_value'], 0) }}</p>
                </div>
                <div class="mg-stat-card-light">
                    <p class="mg-stat-label">Expenses</p>
                    <p class="mg-stat-value">{{ number_format($kpis['total_expenses'], 0) }}</p>
                </div>
                <div class="mg-stat-card-light">
                    <p class="mg-stat-label">Goats</p>
                    <p class="mg-stat-value" style="color: #16a34a">{{ $kpis['total_goats'] }}</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: CASHFLOW MINI BARS                                    --}}
            {{-- ============================================================= --}}
            <div class="mobile-only mb-3">
                <div class="mg-section-title">
                    <h3>Cashflow</h3>
                    <span style="font-size: 0.6875rem; font-weight: 600; color: var(--mg-orange);">Income vs Exp</span>
                </div>
                <div class="bg-white rounded-2xl p-3 shadow-sm flex items-end justify-between gap-1.5" style="height: 5rem;">
                    @php
                        $months = collect([
                            ['label' => 'M1', 'income' => $kpis['income_this_month'] ?? 0, 'exp' => $kpis['expense_this_month'] ?? 0],
                        ]);
                        // Generate 6 months of placeholder bars using available data, plus proportional bars
                        $baseIncome = max($kpis['income_this_month'] ?? 100, 100);
                        $baseExp = max($kpis['expense_this_month'] ?? 80, 80);
                        $maxVal = max($baseIncome, $baseExp, 1);
                    @endphp
                    @for ($i = 0; $i < 6; $i++)
                        @php
                            $inc = $baseIncome * (0.6 + ($i * 0.08) + (rand(-10, 10) / 100));
                            $exp = $baseExp * (0.5 + ($i * 0.07) + (rand(-10, 10) / 100));
                            $incH = max(round(($inc / $maxVal) * 100), 8);
                            $expH = max(round(($exp / $maxVal) * 100), 8);
                        @endphp
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="flex items-end gap-0.5 w-full justify-center" style="height: 3rem;">
                                <div style="width: 0.4rem; height: {{ $incH }}%; background: #16a34a; border-radius: 2px;"></div>
                                <div style="width: 0.4rem; height: {{ $expH }}%; background: #e7d4b0; border-radius: 2px;"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: INVESTOR SETTLEMENT LIST                              --}}
            {{-- ============================================================= --}}
            <div class="mobile-only mb-3">
                <div class="mg-section-title">
                    <h3>Investor Settlement</h3>
                    <a href="{{ route('investors.index') }}">Manage →</a>
                </div>
                @forelse ($investors as $inv)
                    <a href="{{ route('investors.show', $inv) }}" class="mobile-card-item block">
                        <div class="mobile-card-row">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="mg-avatar-sm">{{ strtoupper(substr($inv->name, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $inv->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ number_format($inv->share_pct, 1) }}% · {{ number_format($inv->total_capital, 0) }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 font-bold text-sm {{ $inv->settlement_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $inv->settlement_balance >= 0 ? '+' : '' }}{{ number_format($inv->settlement_balance, 0) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="mobile-card-item text-center text-sm text-gray-400 py-6">
                        No investors yet.
                        <a href="{{ route('investors.create') }}" class="block mt-2 text-emerald-600 font-semibold">+ Add Investor</a>
                    </div>
                @endforelse
            </div>

            {{-- ============================================================= --}}
            {{-- FINANCIAL OVERVIEW KPI CARDS (DESKTOP ONLY)                    --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table">
                <div class="mb-2 sm:mb-3">
                    <h3 class="text-base sm:text-lg font-bold text-gray-700">Financial Overview</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3 lg:gap-4 mb-6 sm:mb-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-blue-500">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Total Capital</p>
                        <p class="mt-1 sm:mt-2 text-sm sm:text-lg lg:text-base font-bold text-gray-900 whitespace-nowrap">PKR {{ number_format($kpis['total_capital'], 0) }}</p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-green-500">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Herd Value</p>
                        <p class="mt-1 sm:mt-2 text-sm sm:text-lg lg:text-base font-bold text-gray-900 whitespace-nowrap">PKR {{ number_format($kpis['current_herd_value'], 0) }}</p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-teal-500">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Realized Profit</p>
                        <p class="mt-1 sm:mt-2 text-sm sm:text-lg lg:text-base font-bold text-gray-900 whitespace-nowrap">PKR {{ number_format($kpis['realized_profit'], 0) }}</p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-red-500">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Total Expenses</p>
                        <p class="mt-1 sm:mt-2 text-sm sm:text-lg lg:text-base font-bold text-gray-900 whitespace-nowrap">PKR {{ number_format($kpis['total_expenses'], 0) }}</p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 {{ $kpis['net_profit'] >= 0 ? 'border-green-600' : 'border-red-600' }}">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Net Profit</p>
                        <p class="mt-1 sm:mt-2 text-sm sm:text-lg lg:text-base font-bold whitespace-nowrap {{ $kpis['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            PKR {{ number_format($kpis['net_profit'], 0) }}
                        </p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 {{ $kpis['return_pct'] >= 0 ? 'border-amber-500' : 'border-red-600' }}">
                        <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Return %</p>
                        <p class="mt-1 sm:mt-2 text-sm sm:text-lg lg:text-base font-bold whitespace-nowrap {{ $kpis['return_pct'] >= 0 ? 'text-amber-600' : 'text-red-600' }}">
                            {{ $kpis['return_pct'] >= 0 ? '+' : '' }}{{ number_format($kpis['return_pct'], 1) }}%
                        </p>
                    </div>
                </div>

                {{-- Secondary stats --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-6 sm:mb-8">
                    <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 flex items-center gap-2 sm:gap-3">
                        <span class="text-2xl sm:text-3xl">🐐</span>
                        <div>
                            <p class="text-[10px] sm:text-xs text-gray-500">Active Goats</p>
                            <p class="text-lg sm:text-xl font-bold text-gray-900">{{ $kpis['total_goats'] }}</p>
                        </div>
                    </div>
                    <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 flex items-center gap-2 sm:gap-3">
                        <span class="text-2xl sm:text-3xl">👥</span>
                        <div>
                            <p class="text-[10px] sm:text-xs text-gray-500">Investors</p>
                            <p class="text-lg sm:text-xl font-bold text-gray-900">{{ $kpis['total_investors'] }}</p>
                        </div>
                    </div>
                    <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4">
                        <p class="text-[10px] sm:text-xs text-gray-500">Income (This Month)</p>
                        <p class="text-base sm:text-xl font-bold text-green-600 whitespace-nowrap">+{{ number_format($kpis['income_this_month'], 0) }}</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4">
                        <p class="text-[10px] sm:text-xs text-gray-500">Expenses (This Month)</p>
                        <p class="text-base sm:text-xl font-bold text-red-600 whitespace-nowrap">-{{ number_format($kpis['expense_this_month'], 0) }}</p>
                    </div>
                </div>

                {{-- Pending payouts banner --}}
                @if ($kpis['pending_payouts'] > 0)
                    <div class="bg-amber-50 border border-amber-200 rounded-xl sm:rounded-lg p-4 mb-6 sm:mb-8 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-amber-800">
                                ⏳ Pending Profit Payouts: PKR {{ number_format($kpis['pending_payouts'], 0) }}
                                ({{ $kpis['pending_count'] }} distributions)
                            </p>
                        </div>
                        <a href="{{ route('reports.profit-loss') }}" class="text-sm font-semibold text-amber-900 hover:underline whitespace-nowrap">Review →</a>
                    </div>
                @endif
            </div>

            {{-- ============================================================= --}}
            {{-- INVESTOR POSITIONS — DESKTOP TABLE                             --}}
            {{-- ============================================================= --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 sm:mb-8 desktop-only-table">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">👥 Investor Positions & Settlement</h3>
                    <a href="{{ route('investors.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">Manage →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Investor</th>
                                <th class="px-4 py-3 text-left">Role</th>
                                <th class="px-4 py-3 text-right">Capital</th>
                                <th class="px-4 py-3 text-right">Own %</th>
                                <th class="px-4 py-3 text-right">Herd Share</th>
                                <th class="px-4 py-3 text-right">Profit Share</th>
                                <th class="px-4 py-3 text-right">Exp Owed</th>
                                <th class="px-4 py-3 text-right">Exp Paid</th>
                                <th class="px-4 py-3 text-right">Settlement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($investors as $inv)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        <a href="{{ route('investors.show', $inv) }}" class="hover:text-indigo-600">{{ $inv->name }}</a>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($inv->isManager())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Manager</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Investor</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->total_capital, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->share_pct, 1) }}%</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->herd_share, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->profit_share, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->expense_owed, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->expense_paid, 0) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $inv->settlement_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $inv->settlement_balance >= 0 ? '+' : '' }}{{ number_format($inv->settlement_balance, 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">No investors yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- TWO-COLUMN: EXPENSE BREAKDOWN + HERD COMPOSITION (DESKTOP)    --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                {{-- Expenses by Category --}}
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg">
                    <div class="p-4 sm:p-6 border-b border-gray-100">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900">💸 Expenses by Category</h3>
                    </div>
                    <div class="p-4 sm:p-6">
                        @forelse ($expenseByCategory as $cat)
                            <div class="mb-3">
                                <div class="flex justify-between text-xs sm:text-sm mb-1">
                                    <span class="text-gray-700 truncate">{{ $cat->category }}</span>
                                    <span class="font-medium text-gray-900 whitespace-nowrap ml-2">{{ number_format($cat->total, 0) }} <span class="text-gray-400">({{ $cat->percentage }}%)</span></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: {{ min($cat->percentage, 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 text-center py-4">No expenses logged yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Herd Composition by Breed --}}
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg">
                    <div class="p-4 sm:p-6 border-b border-gray-100">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900">🐐 Herd Composition (In Herd)</h3>
                    </div>
                    <div class="p-4 sm:p-6">
                        @forelse ($herdComposition as $breed)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-green-100 text-green-700 font-bold text-sm">
                                        {{ $breed->count }}
                                    </span>
                                    <span class="text-gray-700 font-medium text-sm sm:text-base">{{ $breed->breed }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900 text-sm sm:text-base">{{ number_format($breed->value, 0) }}</p>
                                    <p class="text-[10px] sm:text-xs text-gray-400">{{ $breed->percentage }}% of herd</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 text-center py-4">No goats in herd yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- RECENT TRANSACTIONS — DESKTOP TABLE                            --}}
            {{-- ============================================================= --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg desktop-only-table">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
                    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">View All →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Date</th>
                                <th class="px-6 py-3 text-left">Type</th>
                                <th class="px-6 py-3 text-left">Category</th>
                                <th class="px-6 py-3 text-left">Paid By</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentTransactions as $t)
                                <tr>
                                    <td class="px-6 py-3 text-gray-600">{{ $t->date->format('M d, Y') }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $t->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($t->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">{{ $t->category }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ $t->investor?->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right font-medium {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            </div>{{-- /TAB 1: OVERVIEW --}}

            {{-- ============================================================= --}}
            {{-- TAB 2: CHARTS                                                 --}}
            {{-- ============================================================= --}}
            <div x-show="tab === 'charts'" x-transition.opacity.duration.150ms
                 x-data="chartsDashboard()"
                 x-init="init()">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

                    {{-- 1. Income vs Expense (Monthly Bar) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">💰 Income vs Expense (6 Months)</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="incomeExpenseChart"></canvas>
                        </div>
                    </div>

                    {{-- 2. Expense Breakdown (Doughnut) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">💸 Expense Breakdown</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="expenseChart"></canvas>
                        </div>
                    </div>

                    {{-- 3. Herd Composition by Breed (Doughnut) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">🐐 Herd Composition by Breed</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="herdChart"></canvas>
                        </div>
                    </div>

                    {{-- 4. Goat Status Distribution (Doughnut) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">📊 Goat Status Distribution</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="goatStatusChart"></canvas>
                        </div>
                    </div>

                    {{-- 5. Investor Capital Distribution (Bar) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">👥 Investor Capital Distribution</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="investorChart"></canvas>
                        </div>
                    </div>

                    {{-- 6. Monthly Net Profit Trend (Line) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">📈 Net Profit Trend (6 Months)</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="netProfitChart"></canvas>
                        </div>
                    </div>

                    {{-- 7. Breeding / Pregnancy Status (Doughnut) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">🤱 Breeding & Pregnancy Status</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas x-ref="breedingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>{{-- /TAB 2: CHARTS --}}
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- Chart.js initialization script                                 --}}
    {{-- ============================================================= --}}
    @push('scripts')
    <script>
    function chartsDashboard() {
        return {
            charts: [],
            initialized: false,
            init() {
                // Guard against Alpine re-initialization (x-init can fire more than once)
                if (this.initialized) return;
                // Wait for Chart.js to load
                if (typeof Chart === 'undefined') {
                    setTimeout(() => this.init(), 100);
                    return;
                }
                this.initialized = true;
                this.renderAll();
            },
            renderAll() {
                const d = window.__chartData;
                if (!d) return;

                const palette = ['#16a34a', '#f59e0b', '#3b82f6', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'];

                // Chart defaults
                Chart.defaults.font.family = 'Figtree, sans-serif';
                Chart.defaults.font.size = 12;

                // --- 1. Income vs Expense Bar Chart ---
                this.charts.push(new Chart(this.$refs.incomeExpenseChart, {
                    type: 'bar',
                    data: {
                        labels: d.monthly_labels,
                        datasets: [
                            { label: 'Income', data: d.monthly_income, backgroundColor: '#16a34a', borderRadius: 4 },
                            { label: 'Expense', data: d.monthly_expense, backgroundColor: '#ef4444', borderRadius: 4 },
                        ],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } },
                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': PKR ' + Math.round(c.raw).toLocaleString() } },
                        },
                        scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'PKR ' + v.toLocaleString() } } },
                    },
                }));

                // --- 2. Expense Breakdown Doughnut ---
                this.charts.push(new Chart(this.$refs.expenseChart, {
                    type: 'doughnut',
                    data: {
                        labels: d.expense_categories.length ? d.expense_categories : ['No Data'],
                        datasets: [{
                            data: d.expense_amounts.length ? d.expense_amounts : [1],
                            backgroundColor: palette,
                            borderWidth: 2, borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 8, font: { size: 11 } } },
                            tooltip: { callbacks: { label: (c) => c.label + ': PKR ' + Math.round(c.raw).toLocaleString() } },
                        },
                    },
                }));

                // --- 3. Herd Composition by Breed Doughnut ---
                this.charts.push(new Chart(this.$refs.herdChart, {
                    type: 'doughnut',
                    data: {
                        labels: d.breed_names.length ? d.breed_names : ['No Data'],
                        datasets: [{
                            data: d.breed_counts.length ? d.breed_counts : [1],
                            backgroundColor: palette,
                            borderWidth: 2, borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 8, font: { size: 11 } } },
                            tooltip: { callbacks: { label: (c) => c.label + ': ' + c.raw + ' goats' } },
                        },
                    },
                }));

                // --- 4. Goat Status Distribution Doughnut ---
                const gs = d.goat_status;
                this.charts.push(new Chart(this.$refs.goatStatusChart, {
                    type: 'doughnut',
                    data: {
                        labels: ['In Herd', 'Sold', 'Deceased'],
                        datasets: [{
                            data: [gs.in_herd, gs.sold, gs.deceased].every(v => v === 0) ? [1, 0, 0] : [gs.in_herd, gs.sold, gs.deceased],
                            backgroundColor: ['#16a34a', '#3b82f6', '#ef4444'],
                            borderWidth: 2, borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } },
                            tooltip: { callbacks: { label: (c) => c.label + ': ' + c.raw } },
                        },
                    },
                }));

                // --- 5. Investor Capital Distribution Bar ---
                this.charts.push(new Chart(this.$refs.investorChart, {
                    type: 'bar',
                    data: {
                        labels: d.investor_names.length ? d.investor_names : ['No Data'],
                        datasets: [{
                            label: 'Capital (PKR)',
                            data: d.investor_capital.length ? d.investor_capital : [0],
                            backgroundColor: '#1b3a2b',
                            borderRadius: 4,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => 'PKR ' + Math.round(c.raw).toLocaleString() } },
                        },
                        scales: { x: { beginAtZero: true, ticks: { callback: (v) => 'PKR ' + v.toLocaleString() } } },
                    },
                }));

                // --- 6. Monthly Net Profit Trend Line ---
                this.charts.push(new Chart(this.$refs.netProfitChart, {
                    type: 'line',
                    data: {
                        labels: d.monthly_labels,
                        datasets: [{
                            label: 'Net Profit',
                            data: d.monthly_net_profit,
                            borderColor: '#1b3a2b',
                            backgroundColor: 'rgba(27, 58, 43, 0.1)',
                            fill: true, tension: 0.3, borderWidth: 2, pointRadius: 4,
                            pointBackgroundColor: '#16a34a',
                        }],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } },
                            tooltip: { callbacks: { label: (c) => 'PKR ' + Math.round(c.raw).toLocaleString() } },
                        },
                        scales: { y: { ticks: { callback: (v) => 'PKR ' + v.toLocaleString() } } },
                    },
                }));

                // --- 7. Breeding / Pregnancy Status Doughnut ---
                const bs = d.breeding_status;
                this.charts.push(new Chart(this.$refs.breedingChart, {
                    type: 'doughnut',
                    data: {
                        labels: ['Bred', 'Pregnant', 'Open', 'Kidded'],
                        datasets: [{
                            data: [bs.bred, bs.pregnant, bs.open, bs.kidded].every(v => v === 0) ? [1, 0, 0, 0] : [bs.bred, bs.pregnant, bs.open, bs.kidded],
                            backgroundColor: ['#f59e0b', '#8b5cf6', '#6b7280', '#16a34a'],
                            borderWidth: 2, borderColor: '#fff',
                        }],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 8, font: { size: 11 } } },
                            tooltip: { callbacks: { label: (c) => c.label + ': ' + c.raw } },
                        },
                    },
                }));
            },
        };
    }
    </script>
    @endpush
</x-app-layout>