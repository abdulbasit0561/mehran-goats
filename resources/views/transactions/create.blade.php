<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Transaction</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('transactions.store') }}">
                    @csrf

                    <!-- Type + Category, linked via Alpine -->
                    <div class="mb-4" x-data="{
                        type: '{{ old('type', 'expense') }}',
                        incomeCats: {{ \Illuminate\Support\Js::from($incomeCategories) }},
                        expenseCats: {{ \Illuminate\Support\Js::from($expenseCategories) }},
                        get cats() {
                            return this.type === 'income' ? this.incomeCats : this.expenseCats;
                        }
                    }">
                        <x-input-label :value="__('Type *')" />
                        <div class="mt-2 flex gap-6 mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="income" x-model="type" class="text-indigo-600" />
                                <span class="text-sm font-medium text-green-700">Income</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="expense" x-model="type" class="text-indigo-600" />
                                <span class="text-sm font-medium text-red-700">Expense</span>
                            </label>
                        </div>

                        <x-input-label for="category" :value="__('Category *')" />
                        <select name="category" id="category"
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <template x-for="cat in cats" :key="cat">
                                <option :value="cat" x-text="cat"></option>
                            </template>
                        </select>
                        @error('category') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="amount" :value="__('Amount (PKR) *')" />
                            <x-text-input name="amount" id="amount" type="number" step="0.01" min="0.01" :value="old('amount')" class="mt-1 block w-full" />
                            @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="date" :value="__('Date *')" />
                            <x-text-input name="date" id="date" type="date" :value="old('date', date('Y-m-d'))" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-input-label for="goat_id" :value="__('Goat (optional)')" />
                            <select name="goat_id" id="goat_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">— None —</option>
                                @foreach ($goats as $goat)
                                    <option value="{{ $goat->id }}" {{ old('goat_id') == $goat->id ? 'selected' : '' }}>{{ $goat->tag_id }}{{ $goat->name ? ' (' . $goat->name . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="investor_id" :value="__('Paid By (Investor)')" />
                            <select name="investor_id" id="investor_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">— None —</option>
                                @foreach ($investors as $investor)
                                    <option value="{{ $investor->id }}" {{ old('investor_id') == $investor->id ? 'selected' : '' }}>{{ $investor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-input-label for="payment_method" :value="__('Payment Method')" />
                            <select name="payment_method" id="payment_method" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                                <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option>
                                <option value="check" @selected(old('payment_method') === 'check')>Check</option>
                                <option value="other" @selected(old('payment_method') === 'other')>Other</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="budget_amount" :value="__('Budget Amount (PKR)')" />
                            <x-text-input name="budget_amount" id="budget_amount" type="number" step="0.01" min="0" :value="old('budget_amount')" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_recurring" value="1" {{ old('is_recurring') ? 'checked' : '' }} class="rounded text-indigo-600" />
                            <span class="text-sm font-medium text-gray-700">Recurring expense?</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="notes" :value="__('Notes / Description')" />
                        <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('transactions.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Add Transaction') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
