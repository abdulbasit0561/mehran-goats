<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">💰 Sales</h2>
            <a href="{{ route('sales.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Record Sale</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-red-50 p-3 sm:p-4 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Mobile summary --}}
            <div class="mobile-only grid grid-cols-2 gap-2 mb-3">
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Sales</p>
                    <p class="mg-stat-value">{{ $summary['total_sales'] }}</p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Total Revenue</p>
                    <p class="mg-stat-value">{{ number_format($summary['total_revenue']) }}</p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Outstanding</p>
                    <p class="mg-stat-value text-amber-400">{{ number_format($summary['outstanding']) }}</p>
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="mobile-only">
                <div class="mg-search mb-3">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <form method="GET" action="{{ route('sales.index') }}">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tag / buyer / seller…" />
                    </form>
                </div>

                @forelse ($sales as $sale)
                    <div class="mobile-card-item">
                        <a href="{{ route('sales.show', $sale) }}" class="block">
                            <div class="mobile-card-row mb-2">
                                <div class="flex items-center justify-between gap-2 w-full">
                                    <div class="min-w-0">
                                        <span class="font-semibold text-gray-900 text-sm"> Goat {{ $sale->goat?->tag_id ?? '—' }}</span>
                                        <p class="text-xs text-gray-400">{{ $sale->sale_date->format('M d, Y') }}</p>
                                    </div>
                                    <span class="font-bold text-emerald-700 text-sm">{{ number_format((float) $sale->sale_price, 0) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                                <span class="mg-pill">👤 {{ $sale->customer?->name ?? 'Unknown' }}</span>
                                <span class="mg-pill">🤝 {{ $sale->seller?->name ?? 'Farm' }}</span>
                                <span class="mg-pill">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</span>
                                @if ($sale->status === 'paid')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif ($sale->status === 'partial')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Partial</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
                                @endif
                            </div>
                        </a>
                        <div class="flex items-center gap-4 pt-2 mt-2 border-t border-gray-50">
                            <a href="{{ route('sales.edit', $sale) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            <form method="POST" action="{{ route('sales.destroy', $sale) }}" class="inline" onsubmit="return confirm('Delete this sale? The goat returns to the herd and the income entry is removed.')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No sales recorded yet.</p>
                        <a href="{{ route('sales.create') }}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">+ Record Sale</a>
                    </div>
                @endforelse

                <div class="mt-4">{{ $sales->links() }}</div>
            </div>

            {{-- Desktop table --}}
            <div class="desktop-only-table">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <table id="sales-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th>Goat</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Date</th>
                                <th>Price</th>
                                <th>Method</th>
                                <th>Status</th>
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
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.initServerDataTable === 'undefined') return;

            window.initServerDataTable('#sales-table', '{{ route('sales.data') }}', {
                order: [[3, 'desc']],
                columns: [
                    { data: 'goat', name: 'goat.tag_id' },
                    { data: 'buyer', name: 'customer.name' },
                    { data: 'seller', name: 'seller.name' },
                    { data: 'sale_date', name: 'sale_date' },
                    { data: 'sale_price', name: 'sale_price' },
                    { data: 'payment_method', name: 'payment_method' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
            });
        });
    </script>
</x-app-layout>
