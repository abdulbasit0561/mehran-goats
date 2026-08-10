<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Exchange Details</h2>
            <a href="{{ route('exchanges.index') }}" class="text-sm text-gray-500 hover:underline">← Back to Exchanges</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">

                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-green-50">
                    <p class="text-xs text-gray-400 uppercase font-semibold">Exchange Date</p>
                    <p class="text-lg font-bold text-gray-900">{{ $exchange->exchange_date->format('M d, Y') }}</p>
                    @if ($exchange->counterparty)
                        <p class="text-sm text-gray-500 mt-1">with {{ $exchange->counterparty }}</p>
                    @endif
                </div>

                {{-- Goats comparison --}}
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                    {{-- Outgoing --}}
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">Given Away</span>
                        </div>
                        @if ($exchange->outgoingGoat)
                            <a href="{{ route('goats.show', $exchange->outgoingGoat) }}" class="block mb-3">
                                <p class="text-2xl font-bold text-indigo-600 hover:underline">{{ $exchange->outgoingGoat->tag_id }}</p>
                                <p class="text-sm text-gray-500">{{ $exchange->outgoingGoat->breed?->name }} · {{ ucfirst($exchange->outgoingGoat->gender) }}</p>
                            </a>
                        @else
                            <p class="text-2xl font-bold text-gray-400 mb-3">—</p>
                        @endif
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 uppercase font-semibold">Agreed Value</p>
                            <p class="text-xl font-bold text-blue-700">{{ number_format($exchange->outgoing_value, 0) }} <span class="text-xs font-normal">PKR</span></p>
                        </div>
                        <div class="mt-2 text-xs text-gray-400">
                            Marked as <span class="font-medium text-blue-600">Sold</span> at this value.
                        </div>
                    </div>

                    {{-- Incoming --}}
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Received</span>
                        </div>
                        @if ($exchange->incomingGoat)
                            <a href="{{ route('goats.show', $exchange->incomingGoat) }}" class="block mb-3">
                                <p class="text-2xl font-bold text-indigo-600 hover:underline">{{ $exchange->incomingGoat->tag_id }}</p>
                                <p class="text-sm text-gray-500">{{ $exchange->incomingGoat->breed?->name }} · {{ ucfirst($exchange->incomingGoat->gender) }}</p>
                            </a>
                        @else
                            <p class="text-2xl font-bold text-gray-900 mb-3">{{ $exchange->incoming_tag_id }}</p>
                        @endif
                        <div class="bg-green-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 uppercase font-semibold">Agreed Value</p>
                            <p class="text-xl font-bold text-green-700">{{ number_format($exchange->incoming_value, 0) }} <span class="text-xs font-normal">PKR</span></p>
                        </div>
                        <div class="mt-2 text-xs text-gray-400">
                            @if($exchange->incoming_weight_kg > 0)
                                Weight at exchange: {{ $exchange->incoming_weight_kg }} kg
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Cash settlement --}}
                <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Cash Settlement</p>
                            @if ($exchange->cash_direction === 'paid')
                                <p class="text-xs text-gray-400">You paid extra to complete the exchange.</p>
                            @elseif ($exchange->cash_direction === 'received')
                                <p class="text-xs text-gray-400">You received cash back from the exchange.</p>
                            @else
                                <p class="text-xs text-gray-400">Even exchange — no cash difference.</p>
                            @endif
                        </div>
                        <div class="text-right">
                            @if ($exchange->cash_direction === 'paid')
                                <p class="text-2xl font-bold text-red-600">-{{ number_format($exchange->cash_amount, 0) }}</p>
                                <p class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $exchange->payment_method)) }} · Expense: Goat Purchases</p>
                            @elseif ($exchange->cash_direction === 'received')
                                <p class="text-2xl font-bold text-green-600">+{{ number_format($exchange->cash_amount, 0) }}</p>
                                <p class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $exchange->payment_method)) }} · Income: Other Income</p>
                            @else
                                <p class="text-2xl font-bold text-gray-400">0</p>
                            @endif
                        </div>
                    </div>
                    @if ($exchange->investor)
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-xs text-gray-400">Cash handled by:</p>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">{{ $exchange->investor->name }}</span>
                            @if ($exchange->cash_direction === 'paid')
                                <p class="text-xs text-gray-500 mt-1">This investor paid the cash difference — it's tagged on their expense transactions.</p>
                            @elseif ($exchange->cash_direction === 'received')
                                <p class="text-xs text-gray-500 mt-1">This investor received the cash difference — it's tagged on their income transactions.</p>
                            @endif
                        </div>
                    @elseif ($exchange->cash_direction !== 'none')
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-xs text-gray-400">Cash handled by: <span class="font-medium text-gray-600">General Farm</span></p>
                            <p class="text-xs text-gray-500">No specific investor tagged — split by expense split % across all investors.</p>
                        </div>
                    @endif
                </div>

                {{-- Net herd value impact --}}
                <div class="px-4 sm:px-6 py-3 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">Net Herd Value Change</p>
                        <p class="text-lg font-bold {{ $exchange->net_value_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $exchange->net_value_change >= 0 ? '+' : '' }}{{ number_format($exchange->net_value_change, 0) }} PKR
                        </p>
                    </div>
                </div>

                {{-- Notes --}}
                @if ($exchange->notes)
                    <div class="px-4 sm:px-6 py-4 border-t border-gray-100">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Notes</p>
                        <p class="text-sm text-gray-600">{{ $exchange->notes }}</p>
                    </div>
                @endif

                {{-- Transactions created --}}
                <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <p class="text-xs text-gray-400 uppercase font-semibold mb-2">Transactions Auto-Generated</p>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li class="flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            Income: Goat Sales — {{ number_format($exchange->outgoing_value, 0) }} PKR (outgoing goat sold)
                        </li>
                        @if ($exchange->cash_direction === 'paid')
                            <li class="flex items-center gap-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span>
                                Expense: Goat Purchases — {{ number_format($exchange->cash_amount, 0) }} PKR (cash difference paid)
                            </li>
                        @elseif ($exchange->cash_direction === 'received')
                            <li class="flex items-center gap-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                                Income: Other Income — {{ number_format($exchange->cash_amount, 0) }} PKR (cash difference received)
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
