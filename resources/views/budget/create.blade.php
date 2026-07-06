<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Budget Row</h2>
            <a href="{{ route('budget.index') }}" class="text-sm text-gray-500 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('budget.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <x-input-label for="type" :value="__('Type *')" />
                            <select name="type" id="type" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="income" @selected(old('type') === 'income')">Income</option>
                                <option value="expense" @selected(old('type') === 'expense')">Expense</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="category" :value="__('Category *')" />
                            <input type="text" name="category" id="category" value="{{ old('category') }}" placeholder="e.g. Feed & Fodder" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                            @error('category') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="year" :value="__('Year *')" />
                            <x-text-input name="year" id="year" type="number" min="2020" max="2050" :value="old('year', request('year', date('Y')))" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @php $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
                        @foreach (\App\Models\BudgetEntry::MONTHS as $i => $m)
                            <div>
                                <x-input-label for="{{ $m }}" :value="__($monthLabels[$i])" />
                                <x-text-input name="{{ $m }}" id="{{ $m }}" type="number" step="1" min="0" :value="old($m, 0)" class="mt-1 block w-full" />
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Add Budget Row') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
