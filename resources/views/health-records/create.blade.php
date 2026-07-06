<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Health Record — {{ $goat->tag_id }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('goats.health-records.store', $goat) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="type" :value="__('Type *')" />
                            <select name="type" id="type" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
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
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('goats.show', $goat) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Add Record') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
