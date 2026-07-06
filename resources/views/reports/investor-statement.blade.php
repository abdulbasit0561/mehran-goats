<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Statement — {{ $investor->name }}</h2>
            <div class="flex gap-3">
                <a href="{{ route('reports.investor.csv', $investor) }}" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">Download CSV</a>
                <a href="{{ route('reports.investors') }}" class="text-sm text-gray-500 hover:underline">← All Investors</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <p class="text-sm text-gray-500">Total Capital Invested</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((float) $investor->total_capital, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <p class="text-sm text-gray-500">Farm Share %</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ number_format($investor->share_pct, 2) }}%</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <p class="text-sm text-gray-500">Total Distributions Received</p>
                    <p class="mt-2 text-3xl font-bold text-green-700">{{ number_format((float) $investor->profitDistributions->where('status','paid')->sum('amount'), 2) }}</p>
                </div>
            </div>

            <!-- Distributions -->
            <div class="bg-white shadow-sm rounded-lg mb-6">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Profit Distributions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Period</th>
                                <th class="px-6 py-3 text-right">Share %</th>
                                <th class="px-6 py-3 text-right">Total Profit</th>
                                <th class="px-6 py-3 text-right">Your Amount</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($investor->profitDistributions as $dist)
                                <tr>
                                    <td class="px-6 py-3 text-gray-600">{{ $dist->period_start->format('M d') }} – {{ $dist->period_end->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format($dist->share_pct, 2) }}%</td>
                                    <td class="px-6 py-3 text-right">{{ number_format($dist->total_profit, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-medium">{{ number_format($dist->amount, 2) }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $dist->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ ucfirst($dist->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400">No distributions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Investments -->
            <div class="bg-white shadow-sm rounded-lg mb-6">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Investments</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Date</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                                <th class="px-6 py-3 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($investor->investments as $inv)
                                <tr>
                                    <td class="px-6 py-3">{{ $inv->date->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-right font-medium">{{ number_format($inv->amount, 2) }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ $inv->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-6 text-center text-gray-400">No investments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Transactions -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Linked Transactions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Date</th>
                                <th class="px-6 py-3 text-left">Type</th>
                                <th class="px-6 py-3 text-left">Category</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $t)
                                <tr>
                                    <td class="px-6 py-3">{{ $t->date->format('M d, Y') }}</td>
                                    <td class="px-6 py-3">{{ ucfirst($t->type) }}</td>
                                    <td class="px-6 py-3">{{ $t->category }}</td>
                                    <td class="px-6 py-3 text-right font-medium {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">{{ number_format($t->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No linked transactions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">{{ $transactions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
