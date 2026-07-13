<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profit &amp; Loss Report</h2>
            <a href="{{ route('reports.investors') }}" class="text-sm text-indigo-600 hover:underline">Investor Statements →</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Period selector -->
            <div class="bg-white shadow-sm rounded-lg p-4 mb-6">
                <form method="GET" action="{{ route('reports.profit-loss') }}" class="flex flex-wrap gap-3 items-center">
                    <label class="text-sm text-gray-600">From:</label>
                    <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm" />
                    <label class="text-sm text-gray-600">To:</label>
                    <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm" />
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium">Apply</button>
                    <a href="{{ route('reports.profit-loss') }}" class="text-sm text-gray-500 hover:underline">This Month</a>
                </form>
            </div>

            <!-- P&L Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <p class="text-sm text-gray-500">Total Income</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ number_format($totalIncome, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <p class="text-sm text-gray-500">Total Expense</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ number_format($totalExpense, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <p class="text-sm text-gray-500">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $netProfit >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($netProfit, 2) }}</p>
                </div>
            </div>

            <!-- Distribute profit -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Distribute Profit</h3>
                <p class="text-sm text-gray-500 mb-4">Create distribution records for all investors based on their share % for the period {{ $start->format('M d') }} – {{ $end->format('M d, Y') }}.</p>
                <form method="POST" action="{{ route('reports.distribute') }}" onsubmit="return confirm('Create profit distribution records for all investors?')">
                    @csrf
                    <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}" />
                    <input type="hidden" name="end" value="{{ $end->format('Y-m-d') }}" />
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                        Distribute {{ number_format($netProfit, 2) }} Profit
                    </button>
                </form>
            </div>

            <!-- Distributions -->
            <div class="bg-white shadow-sm rounded-lg mb-6 p-4">
                <div class="p-2 border-b border-gray-100 mb-3">
                    <h3 class="font-semibold text-gray-900">Recent Profit Distributions</h3>
                </div>
                <table id="pl-distributions-table" class="w-full text-sm display">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th>Investor</th>
                            <th>Period</th>
                            <th class="text-right!">Share %</th>
                            <th class="text-right!">Total Profit</th>
                            <th class="text-right!">Amount</th>
                            <th class="text-center!">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($distributions as $dist)
                            <tr>
                                <td class="px-6 py-3 font-medium">{{ $dist->investor->name }}</td>
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
                                <td class="px-6 py-3 text-center">
                                    @if ($dist->status === 'pending')
                                        <form method="POST" action="{{ route('reports.distributions.mark-paid', $dist) }}" class="inline">
                                            @csrf
                                            <button class="text-green-600 text-xs hover:underline">Mark Paid</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Income/Expense detail -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="p-2 border-b border-gray-100 mb-3"><h3 class="font-semibold text-green-700">Income Detail ({{ $income->count() }})</h3></div>
                    <table id="pl-income-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr><th>Date</th><th>Category</th><th class="text-right!">Amount</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($income as $t)
                                <tr>
                                    <td class="px-6 py-2 text-gray-600">{{ $t->date->format('M d') }}</td>
                                    <td class="px-6 py-2">{{ $t->category }}</td>
                                    <td class="px-6 py-2 text-right font-medium text-green-600">{{ number_format($t->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <div class="p-2 border-b border-gray-100 mb-3"><h3 class="font-semibold text-red-700">Expense Detail ({{ $expense->count() }})</h3></div>
                    <table id="pl-expense-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr><th>Date</th><th>Category</th><th class="text-right!">Amount</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($expense as $t)
                                <tr>
                                    <td class="px-6 py-2 text-gray-600">{{ $t->date->format('M d') }}</td>
                                    <td class="px-6 py-2">{{ $t->category }}</td>
                                    <td class="px-6 py-2 text-right font-medium text-red-600">{{ number_format($t->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initClientDataTable === 'undefined') return;
            window.initClientDataTable('#pl-distributions-table', { order: [[1, 'desc']], columnDefs: [{ orderable: false, targets: [5, 6] }] });
            window.initClientDataTable('#pl-income-table', { order: [[0, 'desc']], searching: false, lengthChange: false });
            window.initClientDataTable('#pl-expense-table', { order: [[0, 'desc']], searching: false, lengthChange: false });
        });
    </script>
</x-app-layout>
