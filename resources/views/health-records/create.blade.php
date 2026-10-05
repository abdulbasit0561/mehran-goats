<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Health Record — {{ $goat->tag_id }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('goats.health-records.store', $goat) }}" x-data="{ type: '{{ old('type', 'checkup') }}' }">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="type" :value="__('Type *')" />
                            <select name="type" id="type" x-model="type" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach (['vaccination','treatment','checkup','other'] as $t)
                                    <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="date" :value="__('Date *')" />
                            <x-text-input name="date" id="date" type="date" :value="old('date', date('Y-m-d'))" class="mt-1 block w-full" />
                            @error('date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="cost" :value="__('Cost')" />
                            <x-text-input name="cost" id="cost" type="number" step="0.01" min="0" :value="old('cost', 0)" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Clinical details (shown for treatment / checkup) --}}
                    <div x-show="type === 'treatment' || type === 'checkup'"
                         class="mt-5 pt-5 border-t border-gray-200 space-y-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Clinical details</p>
                        <div>
                            <x-input-label for="symptoms" :value="__('Symptoms')" />
                            <textarea name="symptoms" id="symptoms" rows="2" placeholder="e.g. coughing, nasal discharge, lethargy"
                                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('symptoms') }}</textarea>
                            @error('symptoms') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="diagnosis" :value="__('Diagnosis')" />
                                <x-text-input name="diagnosis" id="diagnosis" type="text" :value="old('diagnosis')" placeholder="e.g. Pneumonia" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="vet_name" :value="__('Vet name')" />
                                <x-text-input name="vet_name" id="vet_name" type="text" :value="old('vet_name')" placeholder="e.g. Dr. Ali" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="medication" :value="__('Medication')" />
                                <x-text-input name="medication" id="medication" type="text" :value="old('medication')" placeholder="e.g. Oxytetracycline" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="dosage" :value="__('Dosage')" />
                                <x-text-input name="dosage" id="dosage" type="text" :value="old('dosage')" placeholder="e.g. 1ml per 10kg" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>

                    {{-- Vaccine details (shown for vaccination) --}}
                    <div x-show="type === 'vaccination'" x-cloak
                         class="mt-5 pt-5 border-t border-gray-200 space-y-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Vaccination details</p>
                        <div>
                            <x-input-label for="vaccine_name" :value="__('Vaccine name')" />
                            <x-text-input name="vaccine_name" id="vaccine_name" type="text" :value="old('vaccine_name')" placeholder="e.g. Enterotoxaemia (CDT)" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <style>[x-cloak]{display:none!important}</style>
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('goats.show', $goat) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Add Record') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

