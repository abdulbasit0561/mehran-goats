<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Caretaker — {{ $caretaker->name }}</h2>
            <a href="{{ route('caretakers.show', $caretaker) }}" class="text-sm text-gray-500 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('caretakers.update', $caretaker) }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" :value="__('Caretaker Name *')" />
                            <x-text-input name="name" id="name" type="text" :value="old('name', $caretaker->name)" class="mt-1 block w-full" />
                            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input name="phone" id="phone" type="text" :value="old('phone', $caretaker->phone)" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <x-input-label for="every_nth_kid" :value="__('Every Nth Kid *')" />
                            <x-text-input name="every_nth_kid" id="every_nth_kid" type="number" min="1" max="100" :value="old('every_nth_kid', $caretaker->every_nth_kid)" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-400">Default 5 = every 5th kid belongs to the caretaker.</p>
                            @error('every_nth_kid') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input name="start_date" id="start_date" type="date" :value="old('start_date', $caretaker->start_date?->format('Y-m-d'))" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="active" value="1" {{ old('active', $caretaker->active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700">Active (agreement in effect)</span>
                            </label>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes', $caretaker->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('caretakers.show', $caretaker) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Update Caretaker') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
