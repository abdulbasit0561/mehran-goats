<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗓️ Yearly Clearance Report</h2>
            <a href="{{ route('reports.yearly-clearance.index') }}" class="text-sm text-indigo-600 hover:underline hidden sm:inline">Completed Clearances →</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
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

            {{-- Year selector --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-4">
                <form method="GET" action="{{ route('reports.yearly-clearance.report') }}" class="flex flex-wrap items-center gap-3">
                    <label class="text-xs sm:text-sm font-medium text-gray-600">Financial Year</label>
                    <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                        @php
                            $minYear = \App\Services\YearlyClearanceService::MIN_YEAR;
                            $maxYear = now()->year + 1;
                            $years = [];
                            for ($y = $maxYear; $y >= $minYear; $y--) {
                                $years[$y] = "FY {$y}";
                            }
                        @endphp
                        @foreach ($years as $value => $label)
                            <option value="{{ $value }}" @selected($year === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors">
                        View Report
                    </button>
                    <div class="flex items-center gap-2 ml-auto">
                        @if ($report['completed'])
                            <a href="{{ route('reports.yearly-clearance.show', $report['completed']) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                                View Completed Entry
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @php
                $t = $report['totals'];
                $isCompleted = (bool) $report['completed'];
                $netPositive = $t['net_profit'] >= 0;
            @endphp

            {{-- Status banner --}}
            <div class="mb-4 rounded-xl p-4 {{ $isCompleted ? 'bg-gray-100 border border-gray-300' : 'bg-amber-50 border border-amber-300' }}">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($isCompleted)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-700 text-white">COMPLETED</span>
                        <p class="text-sm text-gray-700">
                            This year was closed on <strong>{{ $report['completed']->completed_at?->format('M d, Y H:i') }}</strong>
                            by <strong>{{ $report['completed']->completer?->name ?? 'Unknown' }}</strong>.
                            The report below is the live-calculated view; the frozen snapshot lives in the completion entry.
                        </p>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white">DRAFT — FOR REVIEW</span>
                        <p class="text-sm text-amber-900">
                            Review the full summary below. When the figures are verified, click <strong>Complete Yearly Clearance</strong> to permanently close FY {{ $year }}.
                        </p>
                    @endif
                </div>
                @if ($year > now()->year)
                    <p class="mt-2 text-xs text-amber-800">
                        ⚠️ FY {{ $year }} is a future year — figures are partial or empty. Completing it now freezes an incomplete record you cannot undo.
                    </p>
                @endif
            </div>

            {{-- KPI cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Sales / Revenue</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-green-600">PKR {{ number_format($t['total_sales'], 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-orange-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Purchases</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-orange-600">PKR {{ number_format($t['total_purchases'], 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Expenses</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-red-600">PKR {{ number_format($t['total_expenses'], 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $netPositive ? 'border-emerald-600' : 'border-red-600' }}">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Net {{ $netPositive ? 'Profit' : 'Loss' }}</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold {{ $netPositive ? 'text-emerald-700' : 'text-red-700' }}">PKR {{ number_format(abs($t['net_profit']), 0) }}</p>
                </div>
            </div>

            {{-- Secondary summary --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-6 overflow-x-auto">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Financial Summary — FY {{ $year }} ({{ \Carbon\Carbon::parse($report['period']['start'])->format('M d, Y') }} – {{ \Carbon\Carbon::parse($report['period']['end'])->format('M d, Y') }})</h3>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        <tr><td class="py-2 pr-4 text-gray-600">Total Sales / Revenue (all income)</td><td class="py-2 text-right font-semibold text-green-700">PKR {{ number_format($t['total_sales'], 2) }}</td></tr>
                        <tr><td class="py-2 pr-4 text-gray-600">Total Purchases (Goat Purchases)</td><td class="py-2 text-right font-semibold text-orange-700">PKR {{ number_format($t['total_purchases'], 2) }}</td></tr>
                        <tr><td class="py-2 pr-4 text-gray-600 font-medium">Gross Profit (Sales − Purchases)</td><td class="py-2 text-right font-semibold {{ $t['gross_profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">PKR {{ number_format($t['gross_profit'], 2) }}</td></tr>
                        <tr><td class="py-2 pr-4 text-gray-600">Business Expenses (excl. purchases)</td><td class="py-2 text-right font-semibold text-red-700">PKR {{ number_format($t['total_expenses_business'], 2) }}</td></tr>
                        <tr class="bg-gray-50"><td class="py-2 pr-4 text-gray-900 font-bold">Net {{ $netPositive ? 'Profit' : 'Loss' }}</td><td class="py-2 text-right font-bold {{ $netPositive ? 'text-emerald-700' : 'text-red-700' }}">PKR {{ number_format($t['net_profit'], 2) }}</td></tr>
                        <tr><td class="py-2 pr-4 text-gray-600">Herd Value (Jan 1)</td><td class="py-2 text-right font-semibold text-gray-700">PKR {{ number_format($t['herd_value_opening'], 2) }}</td></tr>
                        <tr><td class="py-2 pr-4 text-gray-600">Herd Value (Dec 31)</td><td class="py-2 text-right font-semibold text-gray-700">PKR {{ number_format($t['herd_value_closing'], 2) }}</td></tr>
                        <tr><td class="py-2 pr-4 text-gray-600">Outstanding Business Balance (Σ partner finals)</td><td class="py-2 text-right font-semibold text-gray-700">PKR {{ number_format($t['outstanding_business_balance'], 2) }}</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Partner settlement table --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-6 overflow-x-auto">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Partner-wise Settlement — FY {{ $year }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-gray-600 uppercase tracking-wide text-[10px] sm:text-xs">
                                <th class="px-3 py-2">Partner</th>
                                <th class="px-3 py-2 text-right">Share %</th>
                                <th class="px-3 py-2 text-right">Opening</th>
                                <th class="px-3 py-2 text-right">Investment</th>
                                <th class="px-3 py-2 text-right">Profit/Loss Share</th>
                                <th class="px-3 py-2 text-right">Expenses Paid</th>
                                <th class="px-3 py-2 text-right">Withdrawals</th>
                                <th class="px-3 py-2 text-right">Final Balance</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['partners'] as $p)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium text-gray-900">
                                        {{ $p['investor_name'] }}
                                        @if ($p['role'] === 'manager')<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">manager</span>@endif
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($p['share_pct'], 2) }}%</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($p['opening_balance'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-indigo-600 font-medium">{{ number_format($p['investment'], 2) }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $p['profit_share'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($p['profit_share'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($p['expenses_paid_by_partner'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-amber-600 font-medium">{{ number_format($p['withdrawals_received'], 2) }}</td>
                                    <td class="px-3 py-2 text-right font-bold {{ $p['final_balance'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($p['final_balance'], 2) }}</td>
                                    <td class="px-3 py-2">
                                        @if ($p['status'] === 'payable_to_partner')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">Payable to Partner</span>
                                        @elseif ($p['status'] === 'receivable_from_partner')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700">Receivable from Partner</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700">Settled</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">No investors on file — add investors first to settle the year.</td></tr>
                            @endforelse
                        </tbody>
                        @if (count($report['partners']))
                            <tfoot>
                                <tr class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-gray-900">
                                    <td class="px-3 py-2">Total</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(collect($report['partners'])->sum('share_pct'), 2) }}%</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(collect($report['partners'])->sum('opening_balance'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(collect($report['partners'])->sum('investment'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(collect($report['partners'])->sum('profit_share'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(collect($report['partners'])->sum('expenses_paid_by_partner'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format(collect($report['partners'])->sum('withdrawals_received'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($t['outstanding_business_balance'], 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <p class="mt-3 text-[11px] text-gray-400">
                    Final Balance = Opening + Investment + Profit/Loss Share + Expenses Paid by Partner − Withdrawals Received.
                    Positive = business owes partner · Negative = partner owes business · Zero = settled.
                </p>
            </div>

            {{-- Completion action --}}
            @if (! $isCompleted)
                <div class="bg-white shadow-sm rounded-xl p-5 mb-6 border {{ $netPositive ? 'border-emerald-200' : 'border-red-200' }}">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Complete Yearly Clearance</h3>
                    @if (! $netPositive)
                        <div class="mb-3 rounded-md bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
                            ⚠️ FY {{ $year }} closed at a net <strong>loss</strong>. Completing will allocate the loss to partners by share % (entered as negative profit share).
                        </div>
                    @endif
                    <p class="text-xs text-gray-500 mb-4">
                        This creates a permanent completion entry (financial year, clearance date, totals, partner settlements, your name, and this report snapshot).
                        Existing transactions are never modified or deleted — you can always compare years later. This cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('reports.yearly-clearance.complete') }}"
                          data-confirm="Complete the yearly clearance for FY {{ $year }}?&#10;&#10;This creates a permanent historical entry and cannot be undone.&#10;Source transactions are not modified.">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}" />
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Complete Yearly Clearance
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
