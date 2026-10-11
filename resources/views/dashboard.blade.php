<x-app-layout>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
            window.__chartData = @json($chartData);
        </script>
    @endpush

    <div class="py-4 sm:py-8 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner space-y-4 sm:space-y-6">

            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-200">
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
            @endif

            @php
                $helloName = \Illuminate\Support\Str::before(Auth::user()->name, ' ');
                $monthBars = $chartData['monthly_income'] ?? [];
                $monthLabels = $chartData['monthly_labels'] ?? [];
                $monthMax = max(1, max($monthBars ?: [0]));
                $soldCount = $chartData['goat_status']['sold'] ?? 0;
            @endphp

            <section class="spike-board" x-data="chartsDashboard()" x-init="init()">
                <div class="spike-top">
                    <article class="spike-card spike-hello">
                        <div>
                            <h2>Congratulations {{ $helloName }}</h2>
                            <p class="hello-copy">{{ $kpis['total_goats'] }} goats are in the herd. {{ $soldCount }} have been sold.</p>
                            <div class="spike-stat">
                                <span class="spike-dot" style="background:#e8fff6;color:#0f9f78">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v8a2 2 0 002 2h10a2 2 0 002-2V8"/></svg>
                                </span>
                                <div>
                                    <b>{{ $kpis['total_goats'] }} in the herd</b>
                                    <span>Active animals</span>
                                </div>
                            </div>
                            <div class="spike-stat">
                                <span class="spike-dot" style="background:#fff6e8;color:#b76e00">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2M12 22a10 10 0 100-20 10 10 0 000 20z"/></svg>
                                </span>
                                <div>
                                    <b>{{ $soldCount }} sold</b>
                                    <span>Left the herd</span>
                                </div>
                            </div>
                            <div class="spike-stat">
                                <span class="spike-dot" style="background:#ecf2ff;color:#5d87ff">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 10-8 0M4 20a8 8 0 0116 0"/></svg>
                                </span>
                                <div>
                                    <b>{{ $kpis['total_investors'] }} investors</b>
                                    <span>Sharing the farm</span>
                                </div>
                            </div>
                        </div>
                        <div class="goat-stage" aria-hidden="true">
                            <svg class="goat-svg" viewBox="0 0 200 170">
                                <ellipse class="goat-shadow" cx="104" cy="156" rx="42" ry="7" fill="#c9d7ee"/>
                                <g class="goat-tail">
                                    <path d="M58 96 q-16 2 -10 20" stroke="#c4a574" stroke-width="4" fill="none" stroke-linecap="round"/>
                                </g>
                                <ellipse cx="96" cy="108" rx="44" ry="26" fill="#f3e4cf"/>
                                <ellipse cx="96" cy="112" rx="28" ry="16" fill="#fff8ef" opacity="0.7"/>
                                <g class="goat-leg goat-leg-a"><rect x="70" y="122" width="8" height="28" rx="4" fill="#e7d3b5"/></g>
                                <g class="goat-leg goat-leg-b"><rect x="86" y="122" width="8" height="28" rx="4" fill="#dcc6a6"/></g>
                                <g class="goat-leg goat-leg-a"><rect x="108" y="122" width="8" height="28" rx="4" fill="#e7d3b5"/></g>
                                <g class="goat-leg goat-leg-b"><rect x="124" y="122" width="8" height="28" rx="4" fill="#dcc6a6"/></g>
                                <path d="M118 96 q10 -18 28 -16" stroke="#f3e4cf" stroke-width="16" stroke-linecap="round"/>
                                <g class="goat-ear">
                                    <ellipse cx="128" cy="70" rx="9" ry="5" fill="#e4cba8" transform="rotate(-28 128 70)"/>
                                </g>
                                <ellipse cx="146" cy="78" rx="22" ry="18" fill="#f7efe3"/>
                                <path d="M132 64 q-2 -18 10 -20" stroke="#e2c56a" stroke-width="3.2" fill="none" stroke-linecap="round"/>
                                <path d="M146 62 q8 -16 16 -10" stroke="#e2c56a" stroke-width="3.2" fill="none" stroke-linecap="round"/>
                                <circle cx="154" cy="76" r="2.3" fill="#2a3547"/>
                                <circle cx="154.7" cy="75.4" r="0.7" fill="#fff"/>
                                <ellipse cx="164" cy="84" rx="8" ry="5.5" fill="#fff"/>
                                <path d="M160 86 q4 3 8 0" stroke="#d7b899" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                                <path d="M138 92 q8 6 16 2" stroke="#5d87ff" stroke-width="3" fill="none" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </article>

                    <article class="spike-card">
                        <h3>Total Capital</h3>
                        <p class="spike-kicker">Money invested in the farm</p>
                        <p class="spike-figure">PKR {{ number_format($kpis['total_capital'], 0) }}</p>
                        <div class="pay-bars" aria-hidden="true">
                            @foreach ($monthLabels as $i => $label)
                                @php
                                    $h = max(12, (int) round((($monthBars[$i] ?? 0) / $monthMax) * 100));
                                @endphp
                                <span>
                                    <i style="--h: {{ $h }}%; --delay: {{ $i * 70 }}ms"></i>
                                    <em>{{ \Illuminate\Support\Str::before($label, ' ') }}</em>
                                </span>
                            @endforeach
                        </div>
                    </article>

                    <article class="spike-card">
                        <h3>Herd Value</h3>
                        <p class="spike-kicker">PKR {{ number_format($kpis['current_herd_value'], 0) }}</p>
                        <div class="spike-chart">
                            <canvas x-ref="herdChart"></canvas>
                        </div>
                    </article>
                </div>

                <div class="spike-mid">
                    <article class="spike-card">
                        <h3>Charts</h3>
                        <p class="spike-kicker">Income and expenses, last six months</p>
                        <div class="spike-chart tall">
                            <canvas x-ref="incomeExpenseChart"></canvas>
                        </div>
                    </article>
                    <article class="spike-card">
                        <h3>Net Profit</h3>
                        <p class="spike-figure {{ $kpis['net_profit'] >= 0 ? '' : '' }}" style="color: {{ $kpis['net_profit'] >= 0 ? '#0f9f78' : '#d92d20' }}">PKR {{ number_format($kpis['net_profit'], 0) }}</p>
                        <p class="spike-delta {{ $kpis['return_pct'] >= 0 ? 'up' : 'down' }}">Return % {{ $kpis['return_pct'] >= 0 ? '+' : '' }}{{ number_format($kpis['return_pct'], 1) }}%</p>
                        <p class="spike-kicker" style="margin-top:0.8rem">Realized Profit PKR {{ number_format($kpis['realized_profit'], 0) }}</p>
                        <div class="spike-chart">
                            <canvas x-ref="netProfitChart"></canvas>
                        </div>
                    </article>
                    <article class="spike-card">
                        <h3>Goat status</h3>
                        <p class="spike-kicker">In herd, sold, and deceased</p>
                        <div class="spike-chart">
                            <canvas x-ref="goatStatusChart"></canvas>
                        </div>
                    </article>
                </div>

                <div class="spike-rest">
                    <article class="spike-card">
                        <h3>Expense mix</h3>
                        <div class="spike-chart">
                            <canvas x-ref="expenseChart"></canvas>
                        </div>
                    </article>
                    <article class="spike-card">
                        <h3>Investor capital</h3>
                        <div class="spike-chart">
                            <canvas x-ref="investorChart"></canvas>
                        </div>
                    </article>
                    <article class="spike-card">
                        <h3>Breeding</h3>
                        <div class="spike-chart">
                            <canvas x-ref="breedingChart"></canvas>
                        </div>
                    </article>
                    <article class="spike-card">
                        <h3>This month</h3>
                        <p class="spike-figure" style="color: {{ $kpis['net_this_month'] >= 0 ? '#0f9f78' : '#d92d20' }}">{{ $kpis['net_this_month'] >= 0 ? '+' : '' }}{{ number_format($kpis['net_this_month'], 0) }}</p>
                        <p class="spike-kicker">Income {{ number_format($kpis['income_this_month'], 0) }}</p>
                        <p class="spike-kicker">Expenses {{ number_format($kpis['expense_this_month'], 0) }}</p>
                    </article>
                </div>
            </section>

            @php
                $aiLatestFarm = \App\Models\AiAnalysis::farm()->latestFirst()->first();
                $aiRuleAlerts = app(\App\Services\Ai\RuleBasedAlertService::class)->alerts();
            @endphp

            <section class="insight-panel">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-gray-900 text-sm sm:text-base flex items-center gap-2">
                        AI Insights &amp; Alerts
                        <span class="text-[10px] font-semibold uppercase bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-full">Live</span>
                    </h3>
                    <a href="{{ route('farm.ai-insights.index') }}" class="text-xs font-semibold text-emerald-800 hover:underline">Open report</a>
                </div>

                <div class="space-y-1.5">
                    @foreach ($aiRuleAlerts->take(5) as $ra)
                        <div class="flex items-start gap-2 text-xs {{ ($ra['severity'] ?? 'warning') === 'alert' ? 'text-red-700' : 'text-amber-700' }}">
                            <span class="font-medium">{{ ($ra['severity'] ?? 'warning') === 'alert' ? '●' : '▲' }}</span>
                            <p class="flex-1"><span class="font-medium">{{ $ra['title'] }}:</span> <span class="text-gray-600">{{ $ra['detail'] }}</span></p>
                        </div>
                    @endforeach
                    @if ($aiLatestFarm)
                        @foreach (collect($aiLatestFarm->alerts ?? [])->take(3) as $a)
                            <div class="flex items-start gap-2 text-xs {{ ($a['severity'] ?? 'info') === 'alert' ? 'text-red-700' : (($a['severity'] ?? 'info') === 'warning' ? 'text-amber-700' : 'text-gray-700') }}">
                                <span class="text-[10px] font-bold uppercase bg-gray-100 text-gray-500 px-1 py-0.5 rounded self-start">AI</span>
                                <p class="flex-1"><span class="font-medium">{{ $a['title'] }}:</span> <span class="text-gray-600">{{ $a['detail'] }}</span></p>
                            </div>
                        @endforeach
                    @endif
                    @if ($aiRuleAlerts->isEmpty() && collect($aiLatestFarm->alerts ?? [])->isEmpty())
                        <p class="text-xs text-emerald-700">No active alerts.</p>
                    @endif
                </div>

                @if ($aiLatestFarm)
                    <div class="mt-3 pt-3 border-t border-stone-100">
                        <p class="text-xs text-gray-600">{{ collect($aiLatestFarm->insights ?? [])->first()['detail'] ?? '' }}</p>
                        <p class="text-[10px] text-gray-400 mt-1.5">Latest herd report: {{ $aiLatestFarm->generated_at->format('M d, g:i A') }}</p>
                    </div>
                @else
                    <div class="mt-3 pt-3 border-t border-stone-100">
                        <p class="text-xs text-gray-400">No AI herd report yet. <a href="{{ route('farm.ai-insights.index') }}" class="text-emerald-800 font-semibold hover:underline">Generate the first one</a>.</p>
                    </div>
                @endif

                <div class="mt-3">
                    <x-ai-disclaimer compact />
                </div>
            </section>

            <div class="mobile-only">
                <div class="mg-section-title">
                    <h3>Investor Settlement</h3>
                    <a href="{{ route('investors.index') }}">Manage</a>
                </div>
                @forelse ($investors as $inv)
                    <a href="{{ route('investors.show', $inv) }}" class="mobile-card-item block">
                        <div class="mobile-card-row">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="mg-avatar-sm">{{ strtoupper(mb_substr($inv->name, 0, 1)) }}</span>
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
                        <a href="{{ route('investors.create') }}" class="block mt-2 text-emerald-700 font-semibold">Add investor</a>
                    </div>
                @endforelse
            </div>

            @if ($kpis['pending_payouts'] > 0)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-medium text-amber-900">
                        Pending payouts: PKR {{ number_format($kpis['pending_payouts'], 0) }}
                        across {{ $kpis['pending_count'] }} {{ \Illuminate\Support\Str::plural('distribution', $kpis['pending_count']) }}
                    </p>
                    <a href="{{ route('reports.profit-loss') }}" class="text-sm font-semibold text-amber-950 hover:underline">Review</a>
                </div>
            @endif

            <section class="panel-card desktop-only-table overflow-hidden !p-0">
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                    <h3>Investor positions</h3>
                    <a href="{{ route('investors.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Manage</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-stone-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Investor</th>
                                <th class="px-4 py-3 text-left">Role</th>
                                <th class="px-4 py-3 text-right">Capital</th>
                                <th class="px-4 py-3 text-right">Own %</th>
                                <th class="px-4 py-3 text-right">Herd share</th>
                                <th class="px-4 py-3 text-right">Profit share</th>
                                <th class="px-4 py-3 text-right">Exp owed</th>
                                <th class="px-4 py-3 text-right">Exp paid</th>
                                <th class="px-4 py-3 text-right">Settlement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($investors as $inv)
                                <tr class="hover:bg-stone-50/80">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        <a href="{{ route('investors.show', $inv) }}" class="hover:text-emerald-800">{{ $inv->name }}</a>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($inv->isManager())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">Manager</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600">Investor</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->total_capital, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->share_pct, 1) }}%</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->herd_share, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->profit_share, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->expense_owed, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($inv->expense_paid, 0) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $inv->settlement_balance >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
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
            </section>

            <div class="desktop-only-table grid grid-cols-1 lg:grid-cols-2 gap-4">
                <article class="panel-card">
                    <h3>Expenses by Category</h3>
                    <p class="panel-note mb-4">Totals already included in the chart above.</p>
                    @forelse ($expenseByCategory as $cat)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-stone-700 truncate">{{ $cat->category }}</span>
                                <span class="font-medium text-stone-900 whitespace-nowrap ml-2">{{ number_format($cat->total, 0) }} <span class="text-stone-400">({{ $cat->percentage }}%)</span></span>
                            </div>
                            <div class="meter">
                                <span class="bg-[#1b3a2b]" style="width: {{ min($cat->percentage, 100) }}%"></span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-stone-400 text-center py-4">No expenses logged yet.</p>
                    @endforelse
                </article>

                <article class="panel-card">
                    <h3>Herd composition</h3>
                    <p class="panel-note mb-3">Value of animals still in the herd.</p>
                    @forelse ($herdComposition as $breed)
                        <div class="flex items-center justify-between py-2.5 border-b border-stone-100 last:border-0">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-emerald-50 text-emerald-800 font-bold text-sm">
                                    {{ $breed->count }}
                                </span>
                                <span class="text-stone-700 font-medium">{{ $breed->breed }}</span>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-stone-900">{{ number_format($breed->value, 0) }}</p>
                                <p class="text-xs text-stone-400">{{ $breed->percentage }}% of value</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-stone-400 text-center py-4">No goats in the herd yet.</p>
                    @endforelse
                </article>
            </div>

            <section class="panel-card desktop-only-table overflow-hidden !p-0">
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                    <h3>Recent transactions</h3>
                    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-stone-500 uppercase text-xs">
                            <tr>
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-left">Type</th>
                                <th class="px-5 py-3 text-left">Category</th>
                                <th class="px-5 py-3 text-left">Paid by</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($recentTransactions as $t)
                                <tr class="hover:bg-stone-50/80">
                                    <td class="px-5 py-3 text-stone-600">{{ $t->date->format('M d, Y') }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $t->type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($t->type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-stone-700">{{ $t->category }}</td>
                                    <td class="px-5 py-3 text-stone-500">{{ $t->investor?->name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right font-medium {{ $t->type === 'income' ? 'text-emerald-700' : 'text-red-600' }}">
                                        {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-stone-400">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
    function chartsDashboard() {
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
                const d = window.__chartData;
                if (!d) return;

                const palette = ['#5d87ff', '#49beff', '#13deb9', '#ffae1f', '#fa896b', '#539bff', '#7c8fac', '#b8c4ff'];
                const money = (v) => 'PKR ' + Math.round(v).toLocaleString();

                Chart.defaults.font.family = '"Plus Jakarta Sans", Figtree, sans-serif';
                Chart.defaults.font.size = 12;
                Chart.defaults.color = '#6b7280';
                Chart.defaults.animation.duration = 1400;
                Chart.defaults.animation.easing = 'easeOutQuart';

                const frame = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, boxWidth: 8 } } },
                };

                this.charts.push(new Chart(this.$refs.incomeExpenseChart, {
                    type: 'line',
                    data: {
                        labels: d.monthly_labels,
                        datasets: [
                            { label: 'Income', data: d.monthly_income, borderColor: '#5d87ff', backgroundColor: 'rgba(93,135,255,0.16)', fill: true, tension: 0.42, borderWidth: 2.5, pointRadius: 0 },
                            { label: 'Expense', data: d.monthly_expense, borderColor: '#49beff', backgroundColor: 'rgba(73,190,255,0.08)', fill: true, tension: 0.42, borderWidth: 2.5, pointRadius: 0 },
                        ],
                    },
                    options: {
                        ...frame,
                        plugins: {
                            ...frame.plugins,
                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + money(c.raw) } },
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, grid: { color: 'rgba(20,38,28,0.06)' }, ticks: { callback: (v) => money(v) } },
                        },
                    },
                }));

                this.charts.push(new Chart(this.$refs.netProfitChart, {
                    type: 'line',
                    data: {
                        labels: d.monthly_labels,
                        datasets: [{
                            label: 'Net',
                            data: d.monthly_net_profit,
                            borderColor: '#5d87ff',
                            backgroundColor: 'rgba(93, 135, 255, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: '#5d87ff',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        }],
                    },
                    options: {
                        ...frame,
                        plugins: {
                            ...frame.plugins,
                            tooltip: { callbacks: { label: (c) => money(c.raw) } },
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { grid: { color: 'rgba(20,38,28,0.06)' }, ticks: { callback: (v) => money(v) } },
                        },
                    },
                }));

                const ring = (ref, labels, values, emptyLabel, tip) => {
                    const empty = !values.length || values.every((v) => Number(v) === 0);
                    this.charts.push(new Chart(this.$refs[ref], {
                        type: 'doughnut',
                        data: {
                            labels: empty ? [emptyLabel] : labels,
                            datasets: [{
                                data: empty ? [1] : values,
                                backgroundColor: empty ? ['#e7ebe4'] : palette,
                                borderWidth: 3,
                                borderColor: '#fff',
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            ...frame,
                            cutout: '68%',
                            plugins: {
                                ...frame.plugins,
                                tooltip: { callbacks: { label: (c) => empty ? emptyLabel : tip(c) } },
                            },
                        },
                    }));
                };

                ring('expenseChart', d.expense_categories, d.expense_amounts, 'No expenses', (c) => c.label + ': ' + money(c.raw));
                ring('herdChart', d.breed_names, d.breed_counts, 'No goats', (c) => c.label + ': ' + c.raw);
                ring(
                    'goatStatusChart',
                    ['In herd', 'Sold', 'Deceased'],
                    [d.goat_status.in_herd, d.goat_status.sold, d.goat_status.deceased],
                    'No goats',
                    (c) => c.label + ': ' + c.raw
                );
                ring(
                    'breedingChart',
                    ['Bred', 'Pregnant', 'Open', 'Kidded'],
                    [d.breeding_status.bred, d.breeding_status.pregnant, d.breeding_status.open, d.breeding_status.kidded],
                    'No records',
                    (c) => c.label + ': ' + c.raw
                );

                this.charts.push(new Chart(this.$refs.investorChart, {
                    type: 'bar',
                    data: {
                        labels: d.investor_names.length ? d.investor_names : ['No investors'],
                        datasets: [{
                            label: 'Capital',
                            data: d.investor_capital.length ? d.investor_capital : [0],
                            backgroundColor: '#5d87ff',
                            borderRadius: 8,
                            maxBarThickness: 18,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        ...frame,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => money(c.raw) } } },
                        scales: {
                            x: { beginAtZero: true, grid: { color: 'rgba(20,38,28,0.06)' }, ticks: { callback: (v) => money(v) } },
                            y: { grid: { display: false } },
                        },
                    },
                }));
            },
        };
    }
    </script>
    @endpush
</x-app-layout>
