<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Goat</h2>
            <a href="{{ route('goats.index') }}" class="text-sm text-gray-500 hover:underline">← Back to Livestock</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('goats.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tag ID -->
                        <div>
                            <x-input-label for="tag_id" :value="__('Tag ID *')" />
                            <x-text-input id="tag_id" name="tag_id" type="text" :value="old('tag_id')" class="mt-1 block w-full" />
                            @error('tag_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" class="mt-1 block w-full" />
                        </div>

                        <!-- Breed -->
                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label for="breed_id" :value="__('Breed *')" />
                                <a href="{{ route('breeds.index') }}" class="text-xs text-indigo-600 hover:underline">+ Manage Breeds</a>
                            </div>
                            <select id="breed_id" name="breed_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach ($breeds as $id => $name)
                                    <option value="{{ $id }}" {{ old('breed_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('breed_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Caretaker -->
                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label for="caretaker_id" :value="__('Caretaker (Share Owner)')" />
                                <a href="{{ route('caretakers.index') }}" class="text-xs text-indigo-600 hover:underline">+ Manage</a>
                            </div>
                            <select id="caretaker_id" name="caretaker_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">— None (Farm-owned) —</option>
                                @foreach ($caretakers as $id => $name)
                                    <option value="{{ $id }}" {{ old('caretaker_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">Assign if this goat belongs to a caretaker as their share.</p>
                        </div>

                        <!-- Gender -->
                        <div>
                            <x-input-label for="gender" :value="__('Gender *')" />
                            <select id="gender" name="gender" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Purchase Date -->
                        <div>
                            <x-input-label for="purchase_date" :value="__('Purchase Date')" />
                            <x-text-input id="purchase_date" name="purchase_date" type="date" :value="old('purchase_date')" class="mt-1 block w-full" />
                        </div>

                        <!-- Purchase Weight -->
                        <div>
                            <x-input-label for="purchase_weight" :value="__('Purchase Weight (kg)')" />
                            <x-text-input id="purchase_weight" name="purchase_weight" type="number" step="0.01" min="0" :value="old('purchase_weight', 0)" class="mt-1 block w-full" />
                        </div>

                        <!-- Vendor -->
                        <div>
                            <x-input-label for="vendor" :value="__('Vendor / Source')" />
                            <x-text-input id="vendor" name="vendor" type="text" :value="old('vendor')" class="mt-1 block w-full" />
                        </div>

                        <!-- DOB -->
                        <div>
                            <x-input-label for="dob" :value="__('Date of Birth')" />
                            <x-text-input id="dob" name="dob" type="date" :value="old('dob')" class="mt-1 block w-full" />
                        </div>

                        <!-- Weight (current) -->
                        <div>
                            <x-input-label for="weight_kg" :value="__('Current Weight (kg)')" />
                            <x-text-input id="weight_kg" name="weight_kg" type="number" step="0.01" min="0" :value="old('weight_kg', 0)" class="mt-1 block w-full" />
                        </div>

                        <!-- Status -->
                        <div>
                            <x-input-label for="status" :value="__('Status *')" />
                            <select id="status" name="status" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach (['kid','grower','breeder','sold','deceased'] as $s)
                                    <option value="{{ $s }}" {{ old('status', 'kid') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Purchase Price -->
                        <div>
                            <x-input-label for="purchase_price" :value="__('Purchase Price (PKR)')" />
                            <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" :value="old('purchase_price', 0)" class="mt-1 block w-full" />
                        </div>

                        <!-- Estimated Value -->
                        <div>
                            <x-input-label for="estimated_value" :value="__('Estimated Current Value (PKR)')" />
                            <x-text-input id="estimated_value" name="estimated_value" type="number" step="0.01" min="0" :value="old('estimated_value', 0)" class="mt-1 block w-full" />
                        </div>

                        <!-- Sale Price -->
                        <div>
                            <x-input-label for="sale_price" :value="__('Sale Price (PKR)')" />
                            <x-text-input id="sale_price" name="sale_price" type="number" step="0.01" min="0" :value="old('sale_price', 0)" class="mt-1 block w-full" />
                        </div>

                        <!-- Sale Date -->
                        <div>
                            <x-input-label for="sale_date" :value="__('Sale / Exit Date')" />
                            <x-text-input id="sale_date" name="sale_date" type="date" :value="old('sale_date')" class="mt-1 block w-full" />
                        </div>

                        <!-- Sale Weight -->
                        <div>
                            <x-input-label for="sale_weight" :value="__('Sale Weight (kg)')" />
                            <x-text-input id="sale_weight" name="sale_weight" type="number" step="0.01" min="0" :value="old('sale_weight', 0)" class="mt-1 block w-full" />
                        </div>

                        <!-- Photo -->
                        <div>
                            <x-input-label for="photo" :value="__('Photo')" />
                            <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-6">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Create Goat') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
