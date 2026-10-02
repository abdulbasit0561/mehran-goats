<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Record Sale</h2>
            <a href="{{ route('sales.index') }}" class="text-sm text-gray-500 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('sales.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="goat_id" :value="__('Goat (from herd) *')" />
                            <select name="goat_id" id="goat_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                <option value="">— Select a goat —</option>
                                @foreach ($goats as $goat)
                                    <option value="{{ $goat->id }}" @selected(old('goat_id') == $goat->id)>
                                        {{ $goat->tag_id }} — {{ $goat->breed?->name ?? '' }} ({{ ucfirst($goat->status) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('goat_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="customer_id" :value="__('Buyer')" />
                                <select name="customer_id" id="customer_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                    <option value="">Unknown / walk-in</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-400">No buyer? <a href="{{ route('customers.create') }}" class="text-indigo-600 hover:underline">Add a buyer</a></p>
                            </div>
                            <div>
                                <x-input-label for="seller_investor_id" :value="__('Sold by (investor)')" />
                                <select name="seller_investor_id" id="seller_investor_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                    <option value="">Farm</option>
                                    @foreach ($investors as $investor)
                                        <option value="{{ $investor->id }}" @selected(old('seller_investor_id') == $investor->id)>{{ $investor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="sale_date" :value="__('Sale date *')" />
                                <x-text-input name="sale_date" id="sale_date" type="date" :value="old('sale_date', now()->format('Y-m-d'))" class="mt-1 block w-full" />
                                @error('sale_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="sale_price" :value="__('Sale price *')" />
                                <x-text-input name="sale_price" id="sale_price" type="number" step="0.01" min="0.01" :value="old('sale_price')" class="mt-1 block w-full" />
                                @error('sale_price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="sale_weight" :value="__('Weight (kg)')" />
                                <x-text-input name="sale_weight" id="sale_weight" type="number" step="0.01" min="0" :value="old('sale_weight')" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="payment_method" :value="__('Payment method')" />
                            <select name="payment_method" id="payment_method" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'check' => 'Check', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', 'cash') == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="photo" :value="__('Photo of the sold goat (optional)')" />
                            <input type="file" name="photo" id="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700" />
                            @error('photo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4 bg-gray-50">
                            <x-input-label for="initial_paid_amount" :value="__('Amount received now')" />
                            <input type="number" name="initial_paid_amount" id="initial_paid_amount" step="0.01" min="0"
                                   value="{{ old('initial_paid_amount') }}"
                                   placeholder="leave empty for full payment"
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                            <p class="mt-1 text-xs text-gray-400">Empty = paid in full. Enter a smaller amount if the buyer will pay in installments — you can record the rest on the sale page.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Record Sale') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
