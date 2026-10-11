<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transaction Detail</h2>
            <a href="{{ route('transactions.index') }}" class="text-sm text-gray-500 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <dl class="divide-y divide-gray-100">
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Date</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $transaction->date->format('M d, Y') }}</dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Type</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $transaction->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Category</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $transaction->category }}</dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Amount</dt>
                        <dd class="text-sm font-bold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                            PKR {{ number_format($transaction->amount, 2) }}
                        </dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Paid By</dt>
                        <dd class="text-sm text-gray-900">{{ $transaction->investor?->name ?? '—' }}</dd>
                    </div>
                    @if ($transaction->goat)
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Goat</dt>
                            <dd><a href="{{ route('goats.show', $transaction->goat) }}" class="text-indigo-600 hover:underline text-sm">{{ $transaction->goat->tag_id }}</a></dd>
                        </div>
                    @endif
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Payment Method</dt>
                        <dd class="text-sm text-gray-900">{{ $transaction->payment_method ? str_replace('_', ' ', ucfirst($transaction->payment_method)) : '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-sm text-gray-500">Recurring</dt>
                        <dd class="text-sm text-gray-900">{{ $transaction->is_recurring ? 'Yes' : 'No' }}</dd>
                    </div>
                    @if ($transaction->budget_amount)
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Budget</dt>
                            <dd class="text-sm text-gray-900">PKR {{ number_format($transaction->budget_amount, 2) }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Variance</dt>
                            <dd class="text-sm font-medium {{ $transaction->variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                PKR {{ number_format($transaction->variance, 2) }}
                            </dd>
                        </div>
                    @endif
                    @if ($transaction->notes)
                        <div class="py-3">
                            <dt class="text-sm text-gray-500 mb-1">Notes</dt>
                            <dd class="text-sm text-gray-900">{{ $transaction->notes }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('transactions.edit', $transaction) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Edit</a>
                    <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" data-confirm="Delete this transaction?">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
