<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Exchange</h2>
            <a href="{{ route('exchanges.show', $exchange) }}" class="text-sm text-gray-500 hover:underline">← Back to Exchange</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-3 border border-red-200">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('exchanges.update', $exchange) }}">
                    @csrf
                    @method('PUT')

                    {{-- Exchange date --}}
                    <div class="mb-6">
                        <x-input-label for="exchange_date" :value="__('Exchange Date *')" />
                        <x-text-input id="exchange_date" name="exchange_date" type="date" :value="old('exchange_date', $exchange->exchange_date->format('Y-m-d'))" class="mt-1 block w-full" />
                        @error('exchange_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- === YOUR GOAT (GIVEN AWAY) === --}}
                    <div class="mb-6 p-4 rounded-lg bg-blue-50 border border-blue-100">
                        <h3 class="text-sm font-bold text-blue-800 mb-3">🐐 Your Goat (Given Away)</h3>
                        <div class="mb-3">
                            <p class="text-sm text-gray-600">Selected goat: <span class="font-semibold">{{ $exchange->outgoingGoat?->tag_id ?? '—' }}</span></p>
                            <p class="text-xs text-gray-400">The outgoing goat cannot be changed after the exchange is recorded.</p>
                        </div>
                        <div>
                            <x-input-label for="outgoing_value" :value="__('Agreed Value (PKR) *')" />
                            <x-text-input id="outgoing_value" name="outgoing_value" type="number" step="0.01" min="0" :value="old('outgoing_value', $exchange->outgoing_value)" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-400">The value you're trading your goat for.</p>
                            @error('outgoing_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- === GOAT YOU'RE RECEIVING === --}}
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-100">
                        <h3 class="text-sm font-bold text-green-800 mb-3">🐄 Goat Received (New Goat)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-600">Tag ID: <span class="font-semibold">{{ $exchange->incoming_tag_id }}</span></p>
                                <p class="text-xs text-gray-400">The incoming goat's tag ID cannot be changed after creation.</p>
                            </div>
                            <div>
                                <x-input-label for="breed_id" :value="__('Breed *')" />
                                <select id="breed_id" name="breed_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">— Select breed —</option>
                                    @foreach ($breeds as $id => $name)
                                        <option value="{{ $id }}" {{ old('breed_id', $exchange->breed_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('breed_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="incoming_gender" :value="__('Gender *')" />
                                <select id="incoming_gender" name="incoming_gender" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="male" {{ old('incoming_gender', $exchange->incoming_gender) === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('incoming_gender', $exchange->incoming_gender) === 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('incoming_gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="incoming_weight_kg" :value="__('Weight (kg)')" />
                                <x-text-input id="incoming_weight_kg" name="incoming_weight_kg" type="number" step="0.01" min="0" :value="old('incoming_weight_kg', $exchange->incoming_weight_kg)" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="incoming_value" :value="__('Agreed Value (PKR) *')" />
                                <x-text-input id="incoming_value" name="incoming_value" type="number" step="0.01" min="0" :value="old('incoming_value', $exchange->incoming_value)" class="mt-1 block w-full" />
                                @error('incoming_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- === CASH DIFFERENCE PREVIEW === --}}
                    <div class="mb-6 p-4 rounded-lg bg-gray-50 border border-gray-200" id="cash-preview-wrap">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-700">Cash Difference</p>
                                <p class="text-xs text-gray-400" id="cash-direction-text"></p>
                            </div>
                            <p class="text-2xl font-bold" id="cash-amount-display"></p>
                        </div>
                    </div>

                    {{-- === ADDITIONAL DETAILS === --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <x-input-label for="counterparty" :value="__('Counterparty')" />
                            <x-text-input id="counterparty" name="counterparty" type="text" :value="old('counterparty', $exchange->counterparty)" class="mt-1 block w-full" placeholder="Person you exchanged with" />
                        </div>
                        <div>
                            <x-input-label for="payment_method" :value="__('Payment Method')" />
                            <select id="payment_method" name="payment_method" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach (['cash', 'bank_transfer', 'check', 'other'] as $pm)
                                    <option value="{{ $pm }}" {{ old('payment_method', $exchange->payment_method) === $pm ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $pm)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- === INVESTOR LINKAGE === --}}
                    <div class="mb-6 p-4 rounded-lg bg-purple-50 border border-purple-100">
                        <h3 class="text-sm font-bold text-purple-800 mb-3">💰 Who Paid / Received the Cash Difference?</h3>
                        <select id="investor_id" name="investor_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">— General Farm (no specific investor) —</option>
                            @foreach ($investors as $id => $name)
                                <option value="{{ $id }}" {{ old('investor_id', $exchange->investor_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-400">If an investor funded the cash difference, select them here. Their contribution will be tracked.</p>
                    </div>

                    <div class="mb-6">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes', $exchange->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('exchanges.show', $exchange) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Update Exchange') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateCashPreview() {
            const outgoing = parseFloat(document.getElementById('outgoing_value').value) || 0;
            const incoming = parseFloat(document.getElementById('incoming_value').value) || 0;
            const wrap = document.getElementById('cash-preview-wrap');
            const amountEl = document.getElementById('cash-amount-display');
            const textEl = document.getElementById('cash-direction-text');

            if (outgoing === 0 && incoming === 0) {
                wrap.style.display = 'none';
                return;
            }

            const diff = incoming - outgoing;
            wrap.style.display = 'block';

            if (diff > 0) {
                amountEl.textContent = '- ' + Math.round(diff).toLocaleString() + ' PKR';
                amountEl.className = 'text-2xl font-bold text-red-600';
                textEl.textContent = 'You pay this amount (expense: Goat Purchases)';
            } else if (diff < 0) {
                amountEl.textContent = '+ ' + Math.round(Math.abs(diff)).toLocaleString() + ' PKR';
                amountEl.className = 'text-2xl font-bold text-green-600';
                textEl.textContent = 'You receive this amount (income: Other Income)';
            } else {
                amountEl.textContent = '0 PKR';
                amountEl.className = 'text-2xl font-bold text-gray- exchange';
                textEl.textContent = 'Even exchange — no cash difference';
            }
        }

        document.getElementById('outgoing_value').addEventListener('input', updateCashPreview);
        document.getElementById('incoming_value').addEventListener('input', updateCashPreview);

        updateCashPreview();
    </script>
</x-app-layout>
