<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Declare Dead — {{ $goat->tag_id }}</h2>
            <a href="{{ route('goats.show', $goat) }}" class="text-sm text-gray-500 hover:underline">← Back to {{ $goat->tag_id }}</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Warning banner --}}
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <p class="text-sm font-semibold text-red-800">This action will permanently mark {{ $goat->tag_id }} as deceased.</p>
                        <p class="text-xs text-red-600 mt-1">
                            {{ $goat->breed->name }} · {{ ucfirst($goat->gender) }}
                            @if ($goat->name) · {{ $goat->name }} @endif
                        </p>
                        <p class="text-xs text-red-600 mt-0.5">The goat will be removed from the active herd and excluded from herd value totals. A death record will be kept for mortality reporting.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('goats.store-death', $goat) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="death_date" :value="__('Date of Death *')" />
                            <x-text-input name="death_date" id="death_date" type="date" :value="old('death_date', date('Y-m-d'))" class="mt-1 block w-full" max="{{ date('Y-m-d') }}" />
                            <p class="mt-1 text-xs text-gray-400">When did this goat die?</p>
                            @error('death_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="cause" :value="__('Cause of Death')" />
                            <x-text-input name="cause" id="cause" type="text" :value="old('cause')" class="mt-1 block w-full" placeholder="e.g. Illness, predator, accident, old age" />
                            @error('cause') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="disposal_method" :value="__('Disposal Method *')" />
                            <select name="disposal_method" id="disposal_method" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach (['buried' => 'Buried', 'sold_to_butcher' => 'Sold to Butcher', 'composted' => 'Composted', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('disposal_method', 'buried') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('disposal_method') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="disposal_value" :value="__('Disposal Value')" />
                            <x-text-input name="disposal_value" id="disposal_value" type="number" step="0.01" min="0" :value="old('disposal_value', 0)" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-400">Amount recovered (e.g. butcher sale price). Leave 0 if none.</p>
                            @error('disposal_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                            @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between items-center">
                        <a href="{{ route('goats.show', $goat) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700" data-confirm="Confirm: mark {{ $goat->tag_id }} as dead? This cannot be undone.">
                            Declare Dead
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
