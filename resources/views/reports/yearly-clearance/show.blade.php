<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">✅ Yearly Clearance — {{ $clearance->period_label }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('reports.yearly-clearance.csv', $clearance) }}" class="text-sm text-gray-500 hover:underline">CSV</a>
                <a href="{{ route('reports.yearly-clearance.pdf', $clearance) }}" class="text-sm text-red-600 hover:underline">PDF</a>
                <a href="{{ route('reports.yearly-clearance.index') }}" class="text-sm text-indigo-600 hover:underline hidden sm:inline">All Clearances →</a>
            </div>
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

            {{-- Completion metadata --}}
            <div class="bg-gray-100 border border-gray-300 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 font-medium">Financial Year</p>
                        <p class="font-semibold text-gray-900">{{ $clearance->period_label }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($clearance->report_payload['period']['start'] ?? "{$clearance->year}-01-01")->format('M d, Y') }} – {{ \Carbon\Carbon::parse($clearance->report_payload['period']['end'] ?? "{$clearance->year}-12-31")->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 font-medium">Clearance Date</p>
                        <p class="font-semibold text-gray-900">{{ $clearance->clearance_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 font-medium">Completed By</p>
                        <p class="font-semibold text-gray-900">{{ $clearance->completer?->name ?? 'Unknown' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 font-medium">Completed At</p>
                        <p class="font-semibold text-gray-900">{{ $clearance->completed_at?->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- KPI cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Sales</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-green-600">PKR {{ number_format((float) $clearance->total_sales, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-orange-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Purchases</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-orange-600">PKR {{ number_format((float) $clearance->total_purchases, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Total Expenses</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold text-red-600">PKR {{ number_format((float) $clearance->total_expenses, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $clearance->net_profit >= 0 ? 'border-emerald-600' : 'border-red-600' }}">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide">Net {{ $clearance->net_profit >= 0 ? 'Profit' : 'Loss' }}</p>
                    <p class="mt-1 text-lg sm:text-2xl font-bold {{ $clearance->net_profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">PKR {{ number_format(abs((float) $clearance->net_profit), 0) }}</p>
                </div>
            </div>

            {{-- Financial summary --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Completion Entry — Financial Summary</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr><td class="py-2 pr-4 text-gray-600">Total Sales / Revenue</td><td class="py-2 text-right font-semibold">PKR {{ number_format((float) $clearance->total_sales, 2) }}</td></tr>
                            <tr><td class="py-2 pr-4 text-gray-600">Total Purchases</td><td class="py-2 text-right font-semibold">PKR {{ number_format((float) $clearance->total_purchases, 2) }}</td></tr>
                            <tr><td class="py-2 pr-4 text-gray-600">Gross Profit</td><td class="py-2 text-right font-semibold">PKR {{ number_format((float) $clearance->gross_profit, 2) }}</td></tr>
                            <tr><td class="py-2 pr-4 text-gray-600">Business Expenses (excl. purchases)</td><td class="py-2 text-right font-semibold">PKR {{ number_format((float) $clearance->total_expenses_business, 2) }}</td></tr>
                            <tr class="bg-gray-50"><td class="py-2 pr-4 font-bold text-gray-900">Net {{ $clearance->net_profit >= 0 ? 'Profit' : 'Loss' }}</td><td class="py-2 text-right font-bold {{ $clearance->net_profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">PKR {{ number_format(abs((float) $clearance->net_profit), 2) }}</td></tr>
                            <tr><td class="py-2 pr-4 text-gray-600">Herd Value (Opening / Closing)</td><td class="py-2 text-right font-semibold">PKR {{ number_format((float) $clearance->herd_value_opening, 2) }} / {{ number_format((float) $clearance->herd_value_closing, 2) }}</td></tr>
                            <tr><td class="py-2 pr-4 text-gray-600">Outstanding Business Balance</td><td class="py-2 text-right font-semibold">PKR {{ number_format((float) $clearance->outstanding_business_balance, 2) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Partner settlement --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Partner-wise Settlement</h3>
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
                            @forelse ($clearance->partners as $p)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $p->investor_name }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format((float) $p->share_pct, 2) }}%</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format((float) $p->opening_balance, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-indigo-600 font-medium">{{ number_format((float) $p->investment, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $p->profit_share >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $p->profit_share, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format((float) $p->expenses_paid_by_partner, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-amber-600 font-medium">{{ number_format((float) $p->withdrawals_received, 2) }}</td>
                                    <td class="px-3 py-2 text-right font-bold {{ $p->final_balance >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format((float) $p->final_balance, 2) }}</td>
                                    <td class="px-3 py-2">
                                        @if ($p->status === 'payable_to_partner')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">Payable to Partner</span>
                                        @elseif ($p->status === 'receivable_from_partner')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700">Receivable from Partner</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700">Settled</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">No partner settlement lines.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($clearance->partners->isNotEmpty())
                            <tfoot>
                                <tr class="bg-gray-50 border-t-2 border-gray-200 font-semibold text-gray-900">
                                    <td class="px-3 py-2">Total</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->partners->sum('share_pct'), 2) }}%</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->partners->sum('opening_balance'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->partners->sum('investment'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->partners->sum('profit_share'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->partners->sum('expenses_paid_by_partner'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->partners->sum('withdrawals_received'), 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $clearance->outstanding_business_balance, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <p class="mt-3 text-[11px] text-gray-400">
                    This is a permanent historical record created at completion. Source transactions are untouched — figures are the frozen snapshot from {{ $clearance->completed_at?->format('M d, Y H:i') }}.
                </p>
            </div>

            {{-- Link back to live report for the same year --}}
            <div class="text-center">
                <a href="{{ route('reports.yearly-clearance.report', ['year' => $clearance->year]) }}"
                   class="text-sm text-indigo-600 hover:underline">View live-calculated report for FY {{ $clearance->year }}</a>
            </div>

        </div>
    </div>
</x-app-layout>
