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
            {{-- DESKTOP TABLE (hidden on mobile)                              --}}
            {{-- ============================================================= --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 desktop-only-table">
                <table id="transactions-table" class="w-full text-sm display">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Paid By</th>
                            <th>Method</th>
                            <th>Recurring</th>
                            <th class="text-right!">Budget</th>
                            <th class="text-right!">Amount</th>
                            <th class="text-right!">Variance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initServerDataTable === 'undefined') return;

            window.initServerDataTable('#transactions-table', '{{ route('transactions.data') }}', {
                order: [[0, 'desc']],
                columns: [
                    { data: 'date', name: 'date' },
                    { data: 'type', name: 'type' },
                    { data: 'category', name: 'category' },
                    { data: 'notes', name: 'notes' },
                    { data: 'paid_by', name: 'paid_by' },
                    { data: 'payment_method', name: 'payment_method' },
                    { data: 'is_recurring', name: 'is_recurring' },
                    { data: 'budget_amount', name: 'budget_amount', className: 'text-right' },
                    { data: 'amount', name: 'amount', className: 'text-right' },
                    { data: 'variance', name: 'variance', className: 'text-right' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                filters: [
                    {
                        key: 'type',
                        label: 'All Types',
                        options: [
                            { value: 'income', text: 'Income' },
                            { value: 'expense', text: 'Expense' },
                        ],
                    },
                    {
                        key: 'category',
                        label: 'All Categories',
                        options: [
                            @foreach ($categories as $cat)
                            { value: '{{ $cat }}', text: '{{ $cat }}' },
                            @endforeach
                        ],
                    },
                    {
                        key: 'paid_by',
                        label: 'All Payers',
                        options: [
                            @foreach ($investors as $inv)
                            { value: '{{ $inv->id }}', text: '{{ $inv->name }}' },
                            @endforeach
                        ],
                    },
                ],
            });
        });
    </script>
</x-app-layout>
