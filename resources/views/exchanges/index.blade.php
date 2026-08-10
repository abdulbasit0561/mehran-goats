<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔄 Goat Exchanges</h2>
            <a href="{{ route('exchanges.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition">
                + Record Exchange
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ============================================================= --}}
            {{-- MOBILE: SUMMARY STAT CARDS                                   --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-3 gap-2 mb-3">
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold text-gray-900">{{ $summary['total_exchanges'] }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">Exchanges</p>
                </div>
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold text-red-600">{{ number_format($summary['total_cash_paid'], 0) }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">Cash Paid</p>
                </div>
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold text-green-600">{{ number_format($summary['total_cash_received'], 0) }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">Cash Received</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: SEARCH BAR                                           --}}
            {{-- ============================================================= --}}
            <div class="mobile-only mg-search mb-3">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <form method="GET" action="{{ route('exchanges.index') }}">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search exchanges…" />
                </form>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: EXCHANGE LIST                                        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only">
                @forelse ($exchanges as $exchange)
                    <div class="mobile-card-item">
                        <a href="{{ route('exchanges.show', $exchange) }}" class="block">
                            <div class="mobile-card-row mb-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs font-semibold text-gray-500">{{ $exchange->exchange_date?->format('M d, Y') }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    @if ($exchange->cash_direction === 'paid')
                                        <p class="text-sm font-bold text-red-600">-{{ number_format($exchange->cash_amount, 0) }}</p>
                                        <p class="text-[10px] text-gray-400">Paid</p>
                                    @elseif ($exchange->cash_direction === 'received')
                                        <p class="text-sm font-bold text-green-600">+{{ number_format($exchange->cash_amount, 0) }}</p>
                                        <p class="text-[10px] text-gray-400">Received</p>
                                    @else
                                        <p class="text-sm font-medium text-gray-400">Even</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="font-semibold text-gray-900">{{ $exchange->outgoingGoat?->tag_id ?? '—' }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span class="font-semibold text-gray-900">{{ $exchange->incoming_tag_id }}</span>
                                @if ($exchange->counterparty)
                                    <span class="text-xs text-gray-400 ml-1">· {{ $exchange->counterparty }}</span>
                                @endif
                            </div>
                            @if ($exchange->investor)
                                <div class="mt-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800">{{ $exchange->investor->name }}</span>
                                </div>
                            @endif
                        </a>
                        <a href="{{ route('exchanges.edit', $exchange) }}" class="absolute top-2 right-2 text-indigo-600 hover:text-indigo-800 text-xs">✏️</a>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No exchanges recorded yet.</p>
                        <a href="{{ route('exchanges.create') }}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">+ Record Your First Exchange</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $exchanges->withQueryString()->links() }}
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP: SUMMARY BAR                                         --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-indigo-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Total Exchanges</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ $summary['total_exchanges'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Cash Paid Out</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-red-600">{{ number_format($summary['total_cash_paid'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-green-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Cash Received</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-green-600">{{ number_format($summary['total_cash_received'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-teal-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Net Herd Value Change</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold {{ $summary['net_herd_value_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $summary['net_herd_value_change'] >= 0 ? '+' : '' }}{{ number_format($summary['net_herd_value_change'], 0) }}</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP: DATA TABLE                                          --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <table id="exchanges-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th>Date</th>
                                <th>Goat Given Away</th>
                                <th>Goat Received</th>
                                <th>Counterparty</th>
                                <th>Paid / Received By</th>
                                <th class="text-right!">Cash Difference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initServerDataTable === 'undefined') return;

            window.initServerDataTable('#exchanges-table', '{{ route('exchanges.data') }}', {
                order: [[0, 'desc']],
                columns: [
                    { data: 'exchange_date', name: 'exchange_date' },
                    { data: 'outgoing', name: 'outgoing' },
                    { data: 'incoming', name: 'incoming' },
                    { data: 'counterparty', name: 'counterparty' },
                    { data: 'investor', name: 'investor' },
                    { data: 'cash', name: 'cash', className: 'text-right' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                filters: [],
            });
        });
    </script>
</x-app-layout>
