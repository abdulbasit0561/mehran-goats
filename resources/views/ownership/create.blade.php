<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign Goat Ownership — {{ $investor->name }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('investors.ownership.store', $investor) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="goat_id" :value="__('Goat')" />
                            <select name="goat_id" id="goat_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">— Select Goat —</option>
                                @foreach ($goats as $goat)
                                    <option value="{{ $goat->id }}" {{ old('goat_id') == $goat->id ? 'selected' : '' }}>
                                        {{ $goat->tag_id }}{{ $goat->name ? ' (' . $goat->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="share_pct" :value="__('Share %')" />
                            <x-text-input name="share_pct" id="share_pct" type="number" step="0.01" min="0" max="100" :value="old('share_pct', 100)" class="mt-1 block w-full" />
                            @error('share_pct') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('investors.show', $investor) }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Assign Ownership') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
