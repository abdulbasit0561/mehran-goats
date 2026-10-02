<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buyer — {{ $customer->name }}</h2>
            <a href="{{ route('customers.edit', $customer) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Edit</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-1">
                    <h3 class="font-semibold text-gray-900 mb-3">Contact</h3>
                    <p class="text-sm text-gray-600">{{ $customer->phone ?? '—' }}</p>
                    <p class="text-sm text-gray-600">{{ $customer->email ?? '—' }}</p>
                    @if ($customer->address) <p class="text-sm text-gray-500 mt-2">{{ $customer->address }}</p> @endif
                    @if ($customer->notes) <p class="text-sm text-gray-500 mt-2">{{ $customer->notes }}</p> @endif
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('customers.edit', $customer) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>
                        <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:underline">All Buyers</a>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-2">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Sales</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $salesCount ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Added</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $customer->created_at?->format('M d, Y') ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
