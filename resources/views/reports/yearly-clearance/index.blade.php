<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗓️ Yearly Clearance</h2>
            <a href="{{ route('reports.yearly-clearance.report', ['year' => now()->year]) }}" class="text-sm text-indigo-600 hover:underline hidden sm:inline">Year Report →</a>
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

            {{-- Info card --}}
            <div class="bg-white shadow-sm rounded-xl p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Annual Settlement &amp; Year Closure</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Close a financial year, settle every partner's position, and keep a permanent historical record.
                            Source transactions are never modified.
                        </p>
                    </div>
                    <a href="{{ route('reports.yearly-clearance.report', ['year' => now()->year]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Open FY {{ now()->year }} Report
                    </a>
                </div>
            </div>

            @if ($clearances->isEmpty())
                <div class="bg-white shadow-sm rounded-xl p-10 text-center">
                    <p class="text-gray-400 text-sm">No yearly clearance has been completed yet.</p>
                    <p class="text-gray-400 text-xs mt-1">Generate a report for a year, review the figures, then complete the clearance.</p>
                </div>
            @else
                {{-- Completed clearances --}}
                <div class="bg-white shadow-sm rounded-xl p-4 mb-6 overflow-x-auto">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Completed Clearances</h3>
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-gray-600 uppercase tracking-wide text-[10px] sm:text-xs">
                                <th class="px-3 py-2">Financial Year</th>
                                <th class="px-3 py-2">Clearance Date</th>
                                <th class="px-3 py-2 text-right">Sales</th>
                                <th class="px-3 py-2 text-right">Purchases</th>
                                <th class="px-3 py-2 text-right">Expenses</th>
                                <th class="px-3 py-2 text-right">Net Profit/Loss</th>
                                <th class="px-3 py-2">Completed By</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($clearances as $c)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-semibold text-gray-900">
                                        <a href="{{ route('reports.yearly-clearance.show', $c) }}" class="text-indigo-600 hover:underline">FY {{ $c->year }}</a>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $c->clearance_date->format('M d, Y') }}</td>
                                    <td class="px-3 py-2 text-right text-green-700">{{ number_format((float) $c->total_sales, 0) }}</td>
                                    <td class="px-3 py-2 text-right text-orange-700">{{ number_format((float) $c->total_purchases, 0) }}</td>
                                    <td class="px-3 py-2 text-right text-red-700">{{ number_format((float) $c->total_expenses, 0) }}</td>
                                    <td class="px-3 py-2 text-right font-bold {{ $c->net_profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $c->net_profit, 0) }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $c->completer?->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <a href="{{ route('reports.yearly-clearance.show', $c) }}" class="text-indigo-600 hover:underline">View</a>
                                        <a href="{{ route('reports.yearly-clearance.csv', $c) }}" class="ml-2 text-gray-500 hover:underline">CSV</a>
                                        <a href="{{ route('reports.yearly-clearance.pdf', $c) }}" class="ml-2 text-red-600 hover:underline">PDF</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Year-over-year comparison --}}
                @if ($clearances->count() > 1)
                    <div class="bg-white shadow-sm rounded-xl p-4 overflow-x-auto">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Year-over-Year Comparison</h3>
                        <table class="min-w-full text-xs sm:text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-gray-600 uppercase tracking-wide text-[10px] sm:text-xs">
                                    <th class="px-3 py-2">Year</th>
                                    <th class="px-3 py-2 text-right">Sales</th>
                                    <th class="px-3 py-2 text-right">Δ Sales</th>
                                    <th class="px-3 py-2 text-right">Expenses</th>
                                    <th class="px-3 py-2 text-right">Δ Expenses</th>
                                    <th class="px-3 py-2 text-right">Net</th>
                                    <th class="px-3 py-2 text-right">Δ Net</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @php
                                    $sorted = $clearances->sortBy('year')->values();
                                @endphp
                                @foreach ($sorted as $i => $c)
                                    @php
                                        $prev = $i > 0 ? $sorted[$i - 1] : null;
                                        $deltaSales = $prev ? (float) $c->total_sales - (float) $prev->total_sales : null;
                                        $deltaExp   = $prev ? (float) $c->total_expenses - (float) $prev->total_expenses : null;
                                        $deltaNet   = $prev ? (float) $c->net_profit - (float) $prev->net_profit : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 font-semibold text-gray-900">FY {{ $c->year }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format((float) $c->total_sales, 0) }}</td>
                                        <td class="px-3 py-2 text-right {{ $deltaSales === null ? 'text-gray-300' : ($deltaSales >= 0 ? 'text-green-600' : 'text-red-600') }}">
                                            {{ $deltaSales === null ? '—' : ($deltaSales >= 0 ? '+' : '') . number_format($deltaSales, 0) }}
                                        </td>
                                        <td class="px-3 py-2 text-right">{{ number_format((float) $c->total_expenses, 0) }}</td>
                                        <td class="px-3 py-2 text-right {{ $deltaExp === null ? 'text-gray-300' : ($deltaExp <= 0 ? 'text-green-600' : 'text-red-600') }}">
                                            {{ $deltaExp === null ? '—' : ($deltaExp >= 0 ? '+' : '') . number_format($deltaExp, 0) }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold {{ $c->net_profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $c->net_profit, 0) }}</td>
                                        <td class="px-3 py-2 text-right {{ $deltaNet === null ? 'text-gray-300' : ($deltaNet >= 0 ? 'text-green-600' : 'text-red-600') }}">
                                            {{ $deltaNet === null ? '—' : ($deltaNet >= 0 ? '+' : '') . number_format($deltaNet, 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
