<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Caretaker</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <div class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">🤝</span>
                        <div>
                            <p class="text-sm font-semibold text-blue-900">How the share works</p>
                            <p class="text-xs text-blue-700 mt-1">
                                Instead of a salary, the caretaker gets every Nth kid born on the farm.
                                For example, if N = 5 and 10 kids have been born, the caretaker is owed 2 goats
                                (the 5th and 10th kid).
                            </p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('caretakers.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" :value="__('Caretaker Name *')" />
                            <x-text-input name="name" id="name" type="text" :value="old('name')" class="mt-1 block w-full" placeholder="e.g. Ali" />
                            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input name="phone" id="phone" type="text" :value="old('phone')" class="mt-1 block w-full" placeholder="03xx-xxxxxxx" />
                        </div>

                        <div>
                            <x-input-label for="every_nth_kid" :value="__('Every Nth Kid *')" />
                            <x-text-input name="every_nth_kid" id="every_nth_kid" type="number" min="1" max="100" :value="old('every_nth_kid', 5)" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-400">Default 5 = every 5th kid belongs to the caretaker.</p>
                            @error('every_nth_kid') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input name="start_date" id="start_date" type="date" :value="old('start_date')" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="active" value="1" {{ old('active', 1) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700">Active (agreement in effect)</span>
                            </label>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Any additional details about the agreement...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('caretakers.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Add Caretaker') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
