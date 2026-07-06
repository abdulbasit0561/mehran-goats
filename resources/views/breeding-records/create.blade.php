<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Breeding Record — {{ $goat->tag_id }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('goats.breeding-records.store', $goat) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="sire_id" :value="__('Sire (Male) *')" />
                            <select name="sire_id" id="sire_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach ($sires as $s)
                                    <option value="{{ $s->id }}" {{ old('sire_id', $goat->gender === 'male' ? $goat->id : '') == $s->id ? 'selected' : '' }}>
                                        {{ $s->tag_id }}{{ $s->name ? ' (' . $s->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="dam_id" :value="__('Dam (Female) *')" />
                            <select name="dam_id" id="dam_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach ($dams as $d)
                                    <option value="{{ $d->id }}" {{ old('dam_id', $goat->gender === 'female' ? $goat->id : '') == $d->id ? 'selected' : '' }}>
                                        {{ $d->tag_id }}{{ $d->name ? ' (' . $d->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('dam_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="breeding_date" :value="__('Breeding Date *')" />
                            <x-text-input name="breeding_date" id="breeding_date" type="date" :value="old('breeding_date', date('Y-m-d'))" class="mt-1 block w-full" oninput="updateExpectedDate()" />
                            <p id="expectedDatePreview" class="mt-1 text-xs text-blue-600"></p>
                        </div>
                        <div>
                            <x-input-label for="kidding_date" :value="__('Kidding Date')" />
                            <x-text-input name="kidding_date" id="kidding_date" type="date" :value="old('kidding_date')" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-400">Leave blank if not yet kidded — expected date auto-calculated (+150 days).</p>
                        </div>
                        <div>
                            <x-input-label for="litter_size" :value="__('Litter Size')" />
                            <x-text-input name="litter_size" id="litter_size" type="number" min="0" max="20" :value="old('litter_size', 0)" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('goats.show', $goat) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Add Record') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function updateExpectedDate() {
        const dateInput = document.getElementById('breeding_date');
        const preview = document.getElementById('expectedDatePreview');
        if (dateInput.value) {
            const d = new Date(dateInput.value);
            d.setDate(d.getDate() + 150); // 150-day gestation
            preview.textContent = 'Expected kidding: ' + d.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
        } else {
            preview.textContent = '';
        }
    }
    updateExpectedDate();
    </script>
</x-app-layout>
