<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sale — Goat {{ $sale->goat?->tag_id ?? '' }}</h2>
            <a href="{{ route('sales.edit', $sale) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Edit</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-2">
                    <h3 class="font-semibold text-gray-900 mb-4">Sale record</h3>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Goat</dt>
                            <dd class="mt-1">
                                <a href="{{ route('goats.show', $sale->goat_id) }}" class="text-indigo-600 hover:underline font-medium">{{ $sale->goat?->tag_id ?? '—' }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Date</dt>
                            <dd class="mt-1">{{ $sale->sale_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Buyer</dt>
                            <dd class="mt-1">
                                @if ($sale->customer)
                                    <a href="{{ route('customers.show', $sale->customer) }}" class="text-indigo-600 hover:underline">{{ $sale->customer->name }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Sold by</dt>
                            <dd class="mt-1">{{ $sale->seller?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Price</dt>
                            <dd class="mt-1 font-bold text-gray-900">{{ number_format((float) $sale->sale_price, 0) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Weight</dt>
                            <dd class="mt-1">{{ $sale->sale_weight ? $sale->sale_weight . ' kg' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Payment method</dt>
                            <dd class="mt-1">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Notes</dt>
                            <dd class="mt-1">{{ $sale->notes ?? '—' }}</dd>
                        </div>
                    </dl>

                    {{-- Payments --}}
                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <h3 class="font-semibold text-gray-900">Payments</h3>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="text-gray-500">Paid: <strong class="text-gray-900">{{ number_format($sale->paid_amount, 0) }}</strong></span>
                                <span class="text-gray-500">Balance: <strong class="{{ $sale->balance_due > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ number_format($sale->balance_due, 0) }}</strong></span>
                                @if ($sale->status === 'paid')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif ($sale->status === 'partial')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Partial</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
                                @endif
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-gray-500 uppercase text-xs">
                                    <tr>
                                        <th class="text-left py-2">Date</th>
                                        <th class="text-left py-2">Amount</th>
                                        <th class="text-left py-2">Method</th>
                                        <th class="text-left py-2">Reference</th>
                                        <th class="text-left py-2">Notes</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sale->payments as $payment)
                                        <tr class="border-t border-gray-50">
                                            <td class="py-2">{{ $payment->payment_date->format('M d, Y') }}</td>
                                            <td class="py-2 font-medium">{{ number_format((float) $payment->amount, 0) }}</td>
                                            <td class="py-2">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                            <td class="py-2">{{ $payment->reference ?? '—' }}</td>
                                            <td class="py-2 text-gray-500">{{ $payment->notes ?? '—' }}</td>
                                            <td class="py-2 text-right">
                                                <form method="POST" action="{{ route('sales.payments.destroy', [$sale, $payment]) }}" data-confirm="Delete this payment?">
                                                    @csrf @method('DELETE')
                                                    <button class="text-xs text-red-600 hover:underline">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="py-3 text-gray-400 text-sm">No payments recorded yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Record payment form --}}
                        <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                            @csrf
                            <div>
                                <x-input-label for="p_amount" :value="__('Amount *')" />
                                <x-text-input name="amount" id="p_amount" type="number" step="0.01" min="0.01" :value="old('amount', $sale->balance_due > 0 ? $sale->balance_due : '')" class="w-full text-sm" />
                                @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="p_date" :value="__('Date *')" />
                                <x-text-input name="payment_date" id="p_date" type="date" :value="old('payment_date', now()->format('Y-m-d'))" class="w-full text-sm" />
                                @error('payment_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="p_method" :value="__('Method')" />
                                <select name="payment_method" id="p_method" class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'check' => 'Check', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="p_reference" :value="__('Reference')" />
                                <x-text-input name="reference" id="p_reference" type="text" :value="old('reference')" class="w-full text-sm" placeholder="cheque / trx no" />
                            </div>
                            <x-primary-button class="justify-center">{{ __('Record Payment') }}</x-primary-button>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    @if ($sale->photo_path)
                        <div class="bg-white shadow-sm rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2 text-sm">Photo at sale</h3>
                            <a href="{{ Storage::url($sale->photo_path) }}" target="_blank">
                                <img src="{{ Storage::url($sale->photo_path) }}" alt="Goat at sale" class="w-full rounded-md border" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
