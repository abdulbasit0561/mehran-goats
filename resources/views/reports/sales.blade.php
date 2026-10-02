<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📈 Sales Report</h2>
            <div class="flex gap-2">
                <a href="{{ route('reports.sales.csv', ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">CSV</a>
                <a href="{{ route('reports.sales.pdf', ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]) }}" class="px-4 py-2 bg-red-50 text-red-700 rounded-md text-sm font-medium hover:bg-red-100">PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            {{-- Filter --}}
            <div class="bg-white rounded-xl shadow-sm p-4 sm:p-5 mb-5">
                <form method="GET" action="{{ route('reports.sales') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex items-center gap-2">
                        <label class="text-xs sm:text-sm font-medium text-gray-600">From</label>
                        <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs sm:text-sm font-medium text-gray-600">To</label>
                        <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                    </div>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">Apply</button>
                </form>
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Revenue</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($revenue, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Cash Received</p>
                    <p class="mt-1 text-xl font-bold text-green-700">{{ number_format($received, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Outstanding</p>
                    <p class="mt-1 text-xl font-bold text-amber-600">{{ number_format($outstanding, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Animals Sold</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ $count }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Avg Price</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($avgPrice, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Gross Profit</p>
                    <p class="mt-1 text-xl font-bold {{ $grossProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($grossProfit, 0) }}</p>
                </div>
            </div>

            {{-- Sales table --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="text-left px-4 py-3">Date</th>
                                <th class="text-left px-4 py-3">Goat</th>
                                <th class="text-left px-4 py-3">Buyer</th>
                                <th class="text-left px-4 py-3">Seller</th>
                                <th class="text-right px-4 py-3">Price</th>
                                <th class="text-right px-4 py-3">Paid</th>
                                <th class="text-right px-4 py-3">Balance</th>
                                <th class="text-left px-4 py-3">Status</th>
                                <th class="text-right px-4 py-3">Purchase</th>
                                <th class="text-right px-4 py-3">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                @php $profit = (float) $sale->sale_price - (float) ($sale->goat?->purchase_price ?? 0); @endphp
                                <tr class="border-t border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 py-3">{{ $sale->sale_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('goats.show', $sale->goat_id) }}" class="text-indigo-600 hover:underline">{{ $sale->goat?->tag_id ?? '—' }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $sale->customer?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $sale->seller?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $sale->sale_price, 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($sale->paid_amount, 0) }}</td>
                                    <td class="px-4 py-3 text-right {{ $sale->balance_due > 0 ? 'text-amber-600 font-medium' : '' }}">{{ number_format($sale->balance_due, 0) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($sale->status === 'paid')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                        @elseif ($sale->status === 'partial')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Partial</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ number_format((float) ($sale->goat?->purchase_price ?? 0), 0) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $profit >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($profit, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">No sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
