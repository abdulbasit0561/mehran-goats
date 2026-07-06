<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Record Kidding — {{ $breeding_record->dam->tag_id }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">

                {{-- Dam + Sire info --}}
                <div class="mb-6 rounded-lg bg-gray-50 p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Dam (Mother)</p>
                            <p class="text-sm font-medium text-gray-900">
                                <a href="{{ route('goats.show', $breeding_record->dam) }}" class="hover:text-indigo-600">
                                    {{ $breeding_record->dam->tag_id }} {{ $breeding_record->dam->name ? '(' . $breeding_record->dam->name . ')' : '' }}
                                </a>
                            </p>
                            <p class="text-xs text-gray-400">{{ $breeding_record->dam->breed->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Sire (Father)</p>
                            <p class="text-sm font-medium text-gray-900">{{ $breeding_record->sire->tag_id ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $breeding_record->sire->breed->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-500">
                        Bred: {{ $breeding_record->breeding_date->format('M d, Y') }}
                        · Expected: {{ $breeding_record->expected_kidding_date->format('M d, Y') }}
                    </div>
                </div>

                <form method="POST" action="{{ route('breeding-records.kidding.store', $breeding_record) }}" id="kiddingForm">
                    @csrf
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="kidding_date" :value="__('Kidding Date *')" />
                                <x-text-input name="kidding_date" id="kidding_date" type="date"
                                    :value="old('kidding_date', $breeding_record->expected_kidding_date?->format('Y-m-d') ?? date('Y-m-d'))"
                                    class="mt-1 block w-full" />
                                @error('kidding_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="litter_size" :value="__('Number of Kids *')" />
                                <x-text-input name="litter_size" id="litter_size" type="number" min="1" max="10"
                                    :value="old('litter_size', 1)" class="mt-1 block w-full"
                                    oninput="updateKidFields()" />
                                @error('litter_size') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <x-input-label for="kidding_difficulty" :value="__('Kidding Difficulty')" />
                            <select name="kidding_difficulty" id="kidding_difficulty" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">— Select if applicable —</option>
                                <option value="easy" {{ old('kidding_difficulty') === 'easy' ? 'selected' : '' }}>Easy (normal delivery)</option>
                                <option value="assisted" {{ old('kidding_difficulty') === 'assisted' ? 'selected' : '' }}>Assisted (some help needed)</option>
                                <option value="difficult" {{ old('kidding_difficulty') === 'difficult' ? 'selected' : '' }}>Difficult (hard birth)</option>
                                <option value="cesarean" {{ old('kidding_difficulty') === 'cesarean' ? 'selected' : '' }}>Cesarean (surgical)</option>
                            </select>
                        </div>

                        {{-- Dynamic kid fields --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">Register Kids</p>
                            <p class="text-xs text-gray-400 mb-3">Enter tag IDs for each kid. They will be automatically created with parent lineage.</p>
                            <div id="kidFields" class="space-y-3">
                                {{-- Populated by JavaScript --}}
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Birth notes, complications, etc.">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('goats.show', $breeding_record->dam) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Record Kidding') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const damTag = '{{ $breeding_record->dam->tag_id }}';
        const kidFieldTemplate = (index) => `
            <div class="grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded-lg">
                <div class="col-span-1 flex items-center justify-center pt-2">
                    <span class="text-sm font-bold text-gray-400">${index + 1}</span>
                </div>
                <div class="col-span-4">
                    <input type="text" name="kids[${index}][tag_id]"
                        value="${damTag}-K${index + 1}"
                        placeholder="Tag ID"
                        class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </div>
                <div class="col-span-4">
                    <select name="kids[${index}][gender]" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                        <option value="female">♀ Female (Doe)</option>
                        <option value="male">♂ Male (Buck)</option>
                    </select>
                </div>
                <div class="col-span-3">
                    <input type="number" name="kids[${index}][weight_kg]" step="0.1" min="0" max="50"
                        placeholder="kg"
                        class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </div>
            </div>
        `;

        function updateKidFields() {
            const count = Math.max(1, Math.min(10, parseInt(document.getElementById('litter_size').value) || 1));
            const container = document.getElementById('kidFields');
            container.innerHTML = '';
            for (let i = 0; i < count; i++) {
                container.insertAdjacentHTML('beforeend', kidFieldTemplate(i));
            }
        }

        // Initialize on load
        updateKidFields();
    </script>
</x-app-layout>
