<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">💸 Expenses & Income</h2>
            <a href="{{ route('transactions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Transaction</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Expense Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Total Expenses</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['total'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-amber-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Feed & Fodder</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['feed'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-teal-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Veterinary</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['vet'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-indigo-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Labor / Wages</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['labor'], 0) }}</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- FILTERS (collapsible on mobile, inline on desktop)            --}}
            {{-- ============================================================= --}}
            <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 mb-4 sm:mb-6" x-data="{ open: false }">
                <button class="filter-toggle" @click="open = !open" :class="{ 'open': open }">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter Transactions
                        @if (request('type') || request('category') || request('paid_by') || request('from') || request('to'))
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px]">•</span>
                        @endif
                    </span>
                    <svg class="chevron w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="filter-body" :class="{ 'open': open }">
                    <form method="GET" action="{{ route('transactions.index') }}" class="space-y-0">
                        <select name="type" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                            <option value="">All Types</option>
                            <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                        <select name="category" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <select name="paid_by" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                            <option value="">All Payers</option>
                            @foreach ($investors as $inv)
                                <option value="{{ $inv->id }}" {{ (string) request('paid_by') === (string) $inv->id ? 'selected' : '' }}>{{ $inv->name }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm" placeholder="From" />
                        <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm" placeholder="To" />
                        <div class="filter-actions">
                            <button type="submit" class="bg-indigo-600 text-white">Apply</button>
                            <a href="{{ route('transactions.index') }}" class="bg-gray-100 text-gray-600">Reset</a>
                        </div>
                    </form>
                </div>
                {{-- Desktop version --}}
                <form method="GET" action="{{ route('transactions.index') }}" class="hidden sm:flex sm:flex-wrap sm:gap-3 sm:items-center">
                    <select name="type" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                    <select name="category" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <select name="paid_by" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">All Payers</option>
                        @foreach ($investors as $inv)
                            <option value="{{ $inv->id }}" {{ (string) request('paid_by') === (string) $inv->id ? 'selected' : '' }}>{{ $inv->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="From" />
                    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="To" />
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium">Filter</button>
                    <a href="{{ route('transactions.index') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
                </form>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP TABLE (hidden on mobile)                              --}}
            {{-- ============================================================= --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg desktop-only-table">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Category</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-left">Paid By</th>
                                <th class="px-4 py-3 text-left">Method</th>
                                <th class="px-4 py-3 text-left">Recurring</th>
                                <th class="px-4 py-3 text-right">Budget</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Variance</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $t)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600">{{ $t->date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $t->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($t->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $t->category }}</td>
                                    <td class="px-4 py-3 text-gray-500 max-w-[200px] truncate">{{ $t->notes ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $t->investor?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($t->payment_method)
                                            <span class="text-xs text-gray-500">{{ str_replace('_', ' ', ucfirst($t->payment_method)) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($t->is_recurring)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Yes</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ $t->budget_amount ? number_format($t->budget_amount, 0) : '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs
                                        @if($t->variance !== null)
                                            {{ $t->variance >= 0 ? 'text-green-600' : 'text-red-600' }}
                                        @endif">
                                        {{ $t->variance !== null ? number_format($t->variance, 0) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center space-x-2 whitespace-nowrap">
                                        <a href="{{ route('transactions.edit', $t) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                                        <form method="POST" action="{{ route('transactions.destroy', $t) }}" class="inline" onsubmit="return confirm('Delete this transaction?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="px-4 py-8 text-center text-gray-400">No transactions found. <a href="{{ route('transactions.create') }}" class="text-indigo-600 hover:underline">Add one</a></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">{{ $transactions->withQueryString()->links() }}</div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE CARD LIST                                              --}}
            {{-- ============================================================= --}}
            <div class="mobile-card-list">
                @forelse ($transactions as $t)
                    <div class="mobile-card-item">
                        <div class="mobile-card-row mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium shrink-0
                                    {{ $t->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $t->type === 'income' ? '↗' : '↘' }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $t->category }}</p>
                                    <p class="text-xs text-gray-400">{{ $t->date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 font-bold text-base {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 0) }}
                            </span>
                        </div>
                        @if ($t->notes)
                            <p class="text-xs text-gray-500 ml-10 truncate">{{ $t->notes }}</p>
                        @endif
                        @if ($t->investor || $t->budget_amount || $t->is_recurring)
                            <div class="flex items-center gap-3 ml-10 mt-1 text-[10px] text-gray-400">
                                @if ($t->investor) <span>{{ $t->investor->name }}</span> @endif
                                @if ($t->is_recurring) <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Recurring</span> @endif
                                @if ($t->budget_amount) <span>Budget: {{ number_format($t->budget_amount, 0) }}</span> @endif
                            </div>
                        @endif
                        <div class="flex items-center gap-4 pt-2 mt-2 border-t border-gray-50">
                            <a href="{{ route('transactions.edit', $t) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            <form method="POST" action="{{ route('transactions.destroy', $t) }}" class="inline" onsubmit="return confirm('Delete this transaction?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No transactions found.</p>
                        <a href="{{ route('transactions.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">+ Add Transaction</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $transactions->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
