<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Investor Statements</h2>
            <a href="{{ route('reports.profit-loss') }}" class="text-sm text-indigo-600 hover:underline">← P&L Report</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Investor</th>
                                <th class="px-6 py-3 text-right">Capital</th>
                                <th class="px-6 py-3 text-right">Share %</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($investors as $investor)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium">{{ $investor->name }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format((float) $investor->total_capital, 2) }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format($investor->share_pct, 2) }}%</td>
                                    <td class="px-6 py-3 text-center space-x-3">
                                        <a href="{{ route('reports.investor', $investor) }}" class="text-indigo-600 hover:underline text-xs">View Statement</a>
                                        <a href="{{ route('reports.investor.csv', $investor) }}" class="text-green-600 hover:underline text-xs">CSV</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No investors yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
