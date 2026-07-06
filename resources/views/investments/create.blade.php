<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Investment — {{ $investor->name }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('investors.investments.store', $investor) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="amount" :value="__('Amount *')" />
                            <x-text-input name="amount" id="amount" type="number" step="0.01" min="0.01" :value="old('amount')" class="mt-1 block w-full" />
                            @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="date" :value="__('Date *')" />
                            <x-text-input name="date" id="date" type="date" :value="old('date', date('Y-m-d'))" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('investors.show', $investor) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Add Investment') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
