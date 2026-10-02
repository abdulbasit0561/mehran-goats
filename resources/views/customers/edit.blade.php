<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Buyer</h2>
            <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('customers.update', $customer) }}">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" :value="__('Name *')" />
                            <x-text-input name="name" id="name" type="text" :value="old('name', $customer->name)" class="mt-1 block w-full" />
                            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input name="phone" id="phone" type="text" :value="old('phone', $customer->phone)" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input name="email" id="email" type="email" :value="old('email', $customer->email)" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="address" :value="__('Address')" />
                            <textarea name="address" id="address" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('address', $customer->address) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
