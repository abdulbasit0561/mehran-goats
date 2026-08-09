<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📈 Profit &amp; Loss Report</h2>
            <a href="{{ route('reports.investors') }}" class="text-sm text-indigo-600 hover:underline hidden sm:inline">Investor Statements →</a>
        </div>
    </x-slot>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @endpush

    <div class="py-6 sm:py-12 page-content" x-data="{ tab: 'graphs' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-3 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            {{-- ============================================================= --}}
            {{-- FILTER BAR (above tabs, shared by both)                        --}}
            {{-- ============================================================= --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-4">
                <form id="filter-form" method="GET" action="{{ route('reports.profit-loss') }}" class="space-y-3">
                    {{-- Row 1: Date range --}}
                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="flex items-center gap-2">
                            <label class="text-xs sm:text-sm font-medium text-gray-600">From</label>
                            <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs sm:text-sm font-medium text-gray-600">To</label>
                            <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                        </div>

                        {{-- Quick presets --}}
                        <div class="flex items-center gap-1 ml-auto">
                            <button type="button" onclick="setPreset('thisMonth')" class="text-xs px-2.5 py-1.5 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium">This Month</button>
                            <button type="button" onclick="setPreset('lastMonth')" class="text-xs px-2.5 py-1.5 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium">Last Month</button>
                            <button type="button" onclick="setPreset('thisQuarter')" class="text-xs px-2.5 py-1.5 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium">Quarter</button>
                            <button type="button" onclick="setPreset('thisYear')" class="text-xs px-2.5 py-1.5 rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium">Year</button>
                        </div>
                    </div>

                    {{-- Row 2: Category + Type + Buttons --}}
                    <div class="flex flex-wrap gap-3 items-center">
                        <select name="category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                            <option value="">All Categories</option>
                            <optgroup label="Income">
                                @foreach ($incomeCategories as $cat)
                                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Expense">
                                @foreach ($expenseCategories as $cat)
                                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                                @endforeach
                            </optgroup>
                        </select>

                        <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                            <option value="">All Types</option>
                            <option value="income" @selected($type === 'income')>Income Only</option>
                            <option value="expense" @selected($type === 'expense')>Expense Only</option>
                        </select>

                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors">
                            Apply Filters
                        </button>

                        <div class="flex items-center gap-2 ml-auto">
                            <a href="{{ route('reports.profit-loss.csv', ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'category' => $category, 'type' => $type]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
                               title="Export as CSV">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                CSV
                            </a>
                            <a href="{{ route('reports.profit-loss.pdf', ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'category' => $category, 'type' => $type]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors"
                               title="Export as PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ============================================================= --}}
            {{-- KPI SUMMARY CARDS (shared)                                    --}}
            {{-- ============================================================= --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Income</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-green-600">PKR {{ number_format($totalIncome, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Expense</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-red-600">PKR {{ number_format($totalExpense, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $netProfit >= 0 ? 'border-emerald-600' : 'border-red-600' }}">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">PKR {{ number_format(abs($netProfit), 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Margin</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold {{ $margin >= 0 ? 'text-amber-600' : 'text-red-600' }}">{{ $margin }}%</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- TAB BAR                                                       --}}
            {{-- ============================================================= --}}
            <div class="mb-5 border-b border-gray-200">
                <div class="flex gap-1">
                    <button @click="tab = 'graphs'"
                            :class="tab === 'graphs' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 py-3 px-4 sm:px-6 border-b-2 font-semibold text-sm sm:text-base transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h2v12h-2V4zM14 4h2v12h-2V4z"/></svg>
                        Graphs
                    </button>
                    <button @click="tab = 'data'"
                            :class="tab === 'data' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 py-3 px-4 sm:px-6 border-b-2 font-semibold text-sm sm:text-base transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Data
                    </button>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- TAB 1: GRAPHS                                                 --}}
            {{-- ============================================================= --}}
            <div x-show="tab === 'graphs'" x-transition.opacity.duration.150ms
                 x-data="reportCharts()"
                 x-init="init()">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

                    {{-- 1. Income vs Expense (Monthly Bar) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">💰 Income vs Expense</h3>
                        <div style="position: relative; height: 280px;">
                            <canvas x-ref="incomeExpenseChart"></canvas>
                        </div>
                    </div>

                    {{-- 2. Net Profit Trend (Monthly Line) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">📈 Net Profit Trend</h3>
                        <div style="position: relative; height: 280px;">
                            <canvas x-ref="netProfitChart"></canvas>
                        </div>
                    </div>

                    {{-- 3. Expense Breakdown by Category (Doughnut) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">💸 Expense Breakdown</h3>
                        <div style="position: relative; height: 280px;">
                            <canvas x-ref="expenseChart"></canvas>
                        </div>
                    </div>

                    {{-- 4. Income Breakdown by Category (Doughnut) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">💵 Income Breakdown</h3>
                        <div style="position: relative; height: 280px;">
                            <canvas x-ref="incomeChart"></canvas>
                        </div>
                    </div>

                    {{-- 5. Daily Cashflow (Bar) --}}
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 lg:col-span-2">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-4">📊 Daily Cashflow (Net per Day)</h3>
                        <div style="position: relative; height: 280px;">
                            <canvas x-ref="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>{{-- /TAB 1: GRAPHS --}}

            {{-- ============================================================= --}}
            {{-- TAB 2: DATA                                                   --}}
            {{-- ============================================================= --}}
            <div x-show="tab === 'data'" x-transition.opacity.duration.150ms>

                {{-- Distribute Profit --}}
                <div class="bg-white shadow-sm rounded-xl p-4 sm:p-6 mb-4">
                    <h3 class="font-semibold text-gray-900 mb-2 text-sm sm:text-base">💼 Distribute Profit</h3>
                    <p class="text-xs sm:text-sm text-gray-500 mb-3">Create distribution records for all investors based on their share % for the period <strong>{{ $start->format('M d') }} – {{ $end->format('M d, Y') }}</strong>.</p>
                    @if ($netProfit > 0)
                        <form method="POST" action="{{ route('reports.distribute') }}" onsubmit="return confirm('Create profit distribution records for all investors for this period?')">
                            @csrf
                            <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}" />
                            <input type="hidden" name="end" value="{{ $end->format('Y-m-d') }}" />
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors">
                                Distribute PKR {{ number_format($netProfit, 0) }}
                            </button>
                        </form>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-500">
                            ⚠️ No profit to distribute — net result for this period is <strong>{{ $netProfit >= 0 ? 'zero' : 'a loss of PKR ' . number_format(abs($netProfit), 0) }}</strong>.
                        </div>
                    @endif
                </div>

                {{-- Distributions Table --}}
                <div class="bg-white shadow-sm rounded-xl mb-4 p-4">
                    <div class="pb-3 mb-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 text-sm sm:text-base">📋 Profit Distributions ({{ $distributions->count() }})</h3>
                    </div>
                    @if ($distributions->count())
                        <div class="overflow-x-auto">
                            <table id="pl-distributions-table" class="w-full text-sm display">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                    <tr>
                                        <th>Investor</th>
                                        <th>Period</th>
                                        <th class="text-right!">Share %</th>
                                        <th class="text-right!">Total Profit</th>
                                        <th class="text-right!">Amount</th>
                                        <th class="text-center!">Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($distributions as $dist)
                                        <tr>
                                            <td class="px-4 py-3 font-medium">{{ $dist->investor->name }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $dist->period_start->format('M d') }} – {{ $dist->period_end->format('M d, Y') }}</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($dist->share_pct, 1) }}%</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($dist->total_profit, 0) }}</td>
                                            <td class="px-4 py-3 text-right font-medium">{{ number_format($dist->amount, 0) }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $dist->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ ucfirst($dist->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if ($dist->status === 'pending')
                                                    <form method="POST" action="{{ route('reports.distributions.mark-paid', $dist) }}" class="inline">
                                                        @csrf
                                                        <button class="text-green-600 text-xs hover:underline font-medium">Mark Paid</button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-300 text-xs">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 text-center py-6">No distributions for this period yet.</p>
                    @endif
                </div>

                {{-- Transactions Table --}}
                <div class="bg-white shadow-sm rounded-xl mb-4 p-4">
                    <div class="pb-3 mb-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900 text-sm sm:text-base">📜 All Transactions ({{ $allTransactions->count() }})</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="pl-transactions-table" class="w-full text-sm display">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Investor</th>
                                    <th class="text-right!">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($allTransactions as $t)
                                    <tr>
                                        <td class="px-4 py-2.5 text-gray-600">{{ $t->date->format('M d, Y') }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $t->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($t->type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-700">{{ $t->category }}</td>
                                        <td class="px-4 py-2.5 text-gray-500">{{ $t->investor?->name ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-right font-medium {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Income / Expense split tables --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white shadow-sm rounded-xl p-4">
                        <div class="pb-3 mb-3 border-b border-gray-100">
                            <h3 class="font-semibold text-green-700 text-sm sm:text-base">📈 Income Detail ({{ $income->count() }})</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="pl-income-table" class="w-full text-sm display">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                    <tr><th>Date</th><th>Category</th><th>Investor</th><th class="text-right!">Amount</th></tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($income as $t)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-600">{{ $t->date->format('M d') }}</td>
                                            <td class="px-4 py-2 text-gray-700">{{ $t->category }}</td>
                                            <td class="px-4 py-2 text-gray-500">{{ $t->investor?->name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right font-medium text-green-600">{{ number_format($t->amount, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-xl p-4">
                        <div class="pb-3 mb-3 border-b border-gray-100">
                            <h3 class="font-semibold text-red-700 text-sm sm:text-base">📉 Expense Detail ({{ $expense->count() }})</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="pl-expense-table" class="w-full text-sm display">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                    <tr><th>Date</th><th>Category</th><th>Investor</th><th class="text-right!">Amount</th></tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($expense as $t)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-600">{{ $t->date->format('M d') }}</td>
                                            <td class="px-4 py-2 text-gray-700">{{ $t->category }}</td>
                                            <td class="px-4 py-2 text-gray-500">{{ $t->investor?->name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right font-medium text-red-600">{{ number_format($t->amount, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>{{-- /TAB 2: DATA --}}
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- Chart.js initialization                                        --}}
    {{-- ============================================================= --}}
    @push('scripts')
    <script>
    // Quick date presets
    function setPreset(preset) {
        const form = document.getElementById('filter-form');
        const startInput = form.querySelector('input[name="start"]');
        const endInput = form.querySelector('input[name="end"]');
        const now = new Date();
        let start, end;
        switch (preset) {
            case 'thisMonth':
                start = new Date(now.getFullYear(), now.getMonth(), 1);
                end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                break;
            case 'lastMonth':
                start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                end = new Date(now.getFullYear(), now.getMonth(), 0);
                break;
            case 'thisQuarter':
                const q = Math.floor(now.getMonth() / 3);
                start = new Date(now.getFullYear(), q * 3, 1);
                end = new Date(now.getFullYear(), q * 3 + 3, 0);
                break;
            case 'thisYear':
                start = new Date(now.getFullYear(), 0, 1);
                end = new Date(now.getFullYear(), 11, 31);
                break;
        }
        startInput.value = start.toISOString().slice(0, 10);
        endInput.value = end.toISOString().slice(0, 10);
        form.submit();
    }

    function reportCharts() {
        return {
            charts: [],
            initialized: false,
            init() {
                if (this.initialized) return;
                if (typeof Chart === 'undefined') {
                    setTimeout(() => this.init(), 100);
                    return;
                }
                this.initialized = true;
                this.renderAll();
            },
            renderAll() {
                const d = @json($chartData);
                if (!d) return;

                const palette = ['#16a34a', '#f59e0b', '#3b82f6', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'];
                Chart.defaults.font.family = 'Figtree, sans-serif';
                Chart.defaults.font.size = 12;

                // --- 1. Income vs Expense (Bar) ---
                this.charts.push(new Chart(this.$refs.incomeExpenseChart, {
                    type: 'bar',
                    data: {
                        labels: d.monthly_labels.length ? d.monthly_labels : ['No Data'],
                        datasets: [
                            { label: 'Income', data: d.monthly_income.length ? d.monthly_income : [0], backgroundColor: '#16a34a', borderRadius: 4 },
                            { label: 'Expense', data: d.monthly_expense.length ? d.monthly_expense : [0], backgroundColor: '#ef4444', borderRadius: 4 },
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

                // --- 2. Net Profit Trend (Line) ---
                this.charts.push(new Chart(this.$refs.netProfitChart, {
                    type: 'line',
                    data: {
                        labels: d.monthly_labels.length ? d.monthly_labels : ['No Data'],
                        datasets: [{
                            label: 'Net Profit',
                            data: d.monthly_net.length ? d.monthly_net : [0],
                            borderColor: '#1b3a2b', backgroundColor: 'rgba(27, 58, 43, 0.1)',
                            fill: true, tension: 0.3, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#16a34a',
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

                // --- 3. Expense Breakdown (Doughnut) ---
                this.charts.push(new Chart(this.$refs.expenseChart, {
                    type: 'doughnut',
                    data: {
                        labels: d.expense_cat_labels.length ? d.expense_cat_labels : ['No Data'],
                        datasets: [{
                            data: d.expense_cat_values.length ? d.expense_cat_values : [1],
                            backgroundColor: palette, borderWidth: 2, borderColor: '#fff',
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

                // --- 4. Income Breakdown (Doughnut) ---
                this.charts.push(new Chart(this.$refs.incomeChart, {
                    type: 'doughnut',
                    data: {
                        labels: d.income_cat_labels.length ? d.income_cat_labels : ['No Data'],
                        datasets: [{
                            data: d.income_cat_values.length ? d.income_cat_values : [1],
                            backgroundColor: palette, borderWidth: 2, borderColor: '#fff',
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

                // --- 5. Daily Cashflow (Bar) ---
                this.charts.push(new Chart(this.$refs.dailyChart, {
                    type: 'bar',
                    data: {
                        labels: d.daily_labels.length ? d.daily_labels : ['No Data'],
                        datasets: [{
                            label: 'Net Cashflow',
                            data: d.daily_net.length ? d.daily_net : [0],
                            backgroundColor: d.daily_net.length ? d.daily_net.map(v => v >= 0 ? '#16a34a' : '#ef4444') : ['#ccc'],
                            borderRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => 'PKR ' + Math.round(c.raw).toLocaleString() } },
                        },
                        scales: { y: { ticks: { callback: (v) => 'PKR ' + v.toLocaleString() } } },
                    },
                }));
            },
        };
    }
    </script>
    @endpush

    {{-- DataTables init for Data tab --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initClientDataTable === 'undefined') return;
            const tables = ['#pl-distributions-table', '#pl-transactions-table', '#pl-income-table', '#pl-expense-table'];
            tables.forEach(function (sel) {
                const el = document.querySelector(sel);
                if (el && el.querySelector('tbody tr')) {
                    const opts = { order: [[0, 'desc']], pageLength: 10, searching: true, lengthChange: true };
                    if (sel === '#pl-distributions-table') opts.columnDefs = [{ orderable: false, targets: [5, 6] }];
                    if (sel === '#pl-income-table' || sel === '#pl-expense-table') { opts.searching = false; opts.lengthChange = false; opts.paging = false; }
                    try { window.initClientDataTable(sel, opts); } catch(e) { console.warn('DT init failed for', sel, e); }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
