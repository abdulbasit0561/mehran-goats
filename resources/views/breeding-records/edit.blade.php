<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Breeding Record</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">

                @if ($breeding_record->pregnancy_status === 'kidded' && $breeding_record->kids()->exists())
                    <div class="mb-4 rounded-md bg-amber-50 p-3 border border-amber-200">
                        <p class="text-xs text-amber-700">
                            ⚠️ This breeding record has registered kids. Changing the sire/dam will not change the kids' parentage records.
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('breeding-records.update', $breeding_record) }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="sire_id" :value="__('Sire (Male) *')" />
                            <select name="sire_id" id="sire_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach ($sires as $s)
                                    <option value="{{ $s->id }}" {{ old('sire_id', $breeding_record->sire_id) == $s->id ? 'selected' : '' }}>
                                        {{ $s->tag_id }}{{ $s->name ? ' (' . $s->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="dam_id" :value="__('Dam (Female) *')" />
                            <select name="dam_id" id="dam_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach ($dams as $d)
                                    <option value="{{ $d->id }}" {{ old('dam_id', $breeding_record->dam_id) == $d->id ? 'selected' : '' }}>
                                        {{ $d->tag_id }}{{ $d->name ? ' (' . $d->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('dam_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="breeding_date" :value="__('Breeding Date *')" />
                            <x-text-input name="breeding_date" id="breeding_date" type="date" :value="old('breeding_date', $breeding_record->breeding_date->format('Y-m-d'))" class="mt-1 block w-full" />
                        </div>

                        @if ($breeding_record->isPregnant())
                            <div class="rounded-md bg-blue-50 p-3 border border-blue-200">
                                <p class="text-xs text-blue-700">
                                    Expected kidding date: <strong>{{ $breeding_record->expected_kidding_date->format('M d, Y') }}</strong>
                                    (breeding date + 150 days gestation)
                                </p>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="kidding_date" :value="__('Kidding Date')" />
                            <x-text-input name="kidding_date" id="kidding_date" type="date" :value="old('kidding_date', $breeding_record->kidding_date?->format('Y-m-d'))" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-400">Leave blank if not yet kidded.</p>
                        </div>
                        <div>
                            <x-input-label for="litter_size" :value="__('Litter Size')" />
                            <x-text-input name="litter_size" id="litter_size" type="number" min="0" max="20" :value="old('litter_size', $breeding_record->litter_size)" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes', $breeding_record->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('goats.show', $breeding_record->dam_id) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Update Record') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
