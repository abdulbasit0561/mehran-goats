<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Breed — {{ $breed->name }}</h2>
            <a href="{{ route('breeds.index') }}" class="text-sm text-gray-500 hover:underline">← Back to Breeds</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('breeds.update', $breed) }}">
                    @csrf @method('PUT')

                    <div>
                        <x-input-label for="name" :value="__('Breed Name *')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $breed->name)"
                                      class="mt-1 block w-full" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-4">
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('description', $breed->description) }}</textarea>
                        @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('breeds.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Update Breed') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
