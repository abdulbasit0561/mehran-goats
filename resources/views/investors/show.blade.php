<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Investor — {{ $investor->name }}</h2>
            <div class="flex gap-2 sm:gap-3">
                <a href="{{ route('investors.ownership.create', $investor) }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">Assign Goat</a>
                <a href="{{ route('investors.investments.create', $investor) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Investment</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Info + KPI -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-1">
                    <h3 class="font-semibold text-gray-900 mb-3">Contact</h3>
                    <p class="text-sm text-gray-600">{{ $investor->email ?? '—' }}</p>
                    <p class="text-sm text-gray-600">{{ $investor->phone ?? '—' }}</p>
                    @if ($investor->address) <p class="text-sm text-gray-500 mt-2">{{ $investor->address }}</p> @endif
                    @if ($investor->notes) <p class="text-sm text-gray-500 mt-2">{{ $investor->notes }}</p> @endif
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('investors.edit', $investor) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>
                        <a href="{{ route('reports.investor', $investor) }}" class="text-sm text-indigo-600 hover:underline">View Statement</a>
                        <a href="{{ route('reports.investor.csv', $investor) }}" class="text-sm text-green-600 hover:underline">CSV</a>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-2">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Capital</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((float) $investor->total_capital, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Share %</p>
                            <p class="mt-1 text-2xl font-bold text-indigo-600">{{ number_format($investor->share_pct, 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Goats Owned</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $investor->ownershipUnits->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investments -->
            <div class="bg-white shadow-sm rounded-lg mb-6">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Investment History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Date</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                                <th class="px-6 py-3 text-left">Notes</th>
                                <th class="px-6 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($investor->investments as $inv)
                                <tr>
                                    <td class="px-6 py-3">{{ $inv->date->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-right font-medium text-green-600">{{ number_format($inv->amount, 2) }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ $inv->notes ?? '—' }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <form method="POST" action="{{ route('investments.destroy', $inv) }}" onsubmit="return confirm('Delete this investment?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 text-xs hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No investments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ownership -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Goat Ownership</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Goat</th>
                                <th class="px-6 py-3 text-right">Share %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($investor->ownershipUnits as $unit)
                                <tr>
                                    <td class="px-6 py-3">
                                        @if ($unit->goat)
                                            <a href="{{ route('goats.show', $unit->goat) }}" class="text-indigo-600 hover:underline">{{ $unit->goat->tag_id }}</a>
                                        @else
                                            <span class="text-gray-400">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right">{{ number_format($unit->share_pct, 2) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-6 py-6 text-center text-gray-400">No goat ownership assigned.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
