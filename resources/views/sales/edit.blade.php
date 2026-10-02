<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Sale</h2>
            <a href="{{ route('sales.show', $sale) }}" class="text-sm text-gray-500 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                        <ul class="text-sm text-red-800 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <p class="mb-4 text-sm text-gray-500"> Goat <span class="font-semibold text-gray-800">{{ $sale->goat?->tag_id }}</span> — changing the goat happens from the goat page.</p>

                <form method="POST" action="{{ route('sales.update', $sale) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="customer_id" :value="__('Buyer')" />
                                <select name="customer_id" id="customer_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                    <option value="">Unknown / walk-in</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id', $sale->customer_id) == $customer->id)>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="seller_investor_id" :value="__('Sold by (investor)')" />
                                <select name="seller_investor_id" id="seller_investor_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                    <option value="">Farm</option>
                                    @foreach ($investors as $investor)
                                        <option value="{{ $investor->id }}" @selected(old('seller_investor_id', $sale->seller_investor_id) == $investor->id)>{{ $investor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="sale_date" :value="__('Sale date *')" />
                                <x-text-input name="sale_date" id="sale_date" type="date" :value="old('sale_date', $sale->sale_date->format('Y-m-d'))" class="mt-1 block w-full" />
                                @error('sale_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="sale_price" :value="__('Sale price *')" />
                                <x-text-input name="sale_price" id="sale_price" type="number" step="0.01" min="0.01" :value="old('sale_price', $sale->sale_price)" class="mt-1 block w-full" />
                                @error('sale_price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="sale_weight" :value="__('Weight (kg)')" />
                                <x-text-input name="sale_weight" id="sale_weight" type="number" step="0.01" min="0" :value="old('sale_weight', $sale->sale_weight)" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="payment_method" :value="__('Payment method')" />
                            <select name="payment_method" id="payment_method" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'check' => 'Check', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', $sale->payment_method) == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="photo" :value="__('Replace photo (optional)')" />
                            @if ($sale->photo_path)
                                <img src="{{ Storage::url($sale->photo_path) }}" alt="Sale photo" class="mt-2 h-24 w-24 object-cover rounded-md border" />
                            @endif
                            <input type="file" name="photo" id="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700" />
                            @error('photo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes', $sale->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
