<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🛒 Buyers</h2>
            <a href="{{ route('customers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Buyer</a>
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

            {{-- ============================================================= --}}
            {{-- MOBILE: SUMMARY CARDS                                         --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-2 gap-2 mb-3">
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Buyers</p>
                    <p class="mg-stat-value">{{ $customers->total() }}</p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">On This Page</p>
                    <p class="mg-stat-value">{{ count($customers) }}</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: BUYER CARDS                                           --}}
            {{-- ============================================================= --}}
            <div class="mobile-only">
                {{-- Mobile search --}}
                <div class="mg-search mb-3">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <form method="GET" action="{{ route('customers.index') }}">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / phone / address…" />
                    </form>
                </div>

                @forelse ($customers as $customer)
                    <div class="mobile-card-item">
                        <a href="{{ route('customers.show', $customer) }}" class="block">
                            <div class="mobile-card-row mb-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="mg-avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <span class="font-semibold text-gray-900 text-sm truncate">{{ $customer->name }}</span>
                                        <p class="text-xs text-gray-400 truncate">{{ $customer->phone ?? $customer->email ?? 'Buyer' }}</p>
                                    </div>
                                </div>
                            </div>
                            @if ($customer->address)
                                <p class="text-xs text-gray-500 truncate mb-1">📍 {{ $customer->address }}</p>
                            @endif
                        </a>
                        <div class="flex items-center gap-4 pt-2 mt-2 border-t border-gray-50">
                            <a href="{{ route('customers.edit', $customer) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline" data-confirm="Delete this buyer?">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No buyers yet.</p>
                        <a href="{{ route('customers.create') }}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">+ Add Buyer</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $customers->links() }}
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP ONLY (table)                                          --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <table id="customers-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th>Buyer</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Added</th>
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

            window.initServerDataTable('#customers-table', '{{ route('customers.data') }}', {
                order: [[0, 'asc']],
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'phone', name: 'phone' },
                    { data: 'email', name: 'email' },
                    { data: 'address', name: 'address' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
            });
        });
    </script>
</x-app-layout>
