<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🐐 Livestock Ledger</h2>
            <a href="{{ route('goats.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition">
                + Add Goat
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
            {{-- MOBILE: STAT CARDS ROW                                        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-4 gap-2 mb-3">
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold" style="color: #16a34a;">{{ $summary['in_herd_count'] }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">In Herd</p>
                </div>
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold text-gray-900">{{ $summary['sold_count'] }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">Sold</p>
                </div>
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold" style="color: #ec4899;">{{ $summary['pregnant_count'] }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">Pregnant</p>
                </div>
                <div class="mg-stat-card-light text-center" style="padding: 0.5rem 0.25rem;">
                    <p class="text-xl font-extrabold" style="color: #6366f1;">{{ $summary['kids_born'] }}</p>
                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mt-0.5">Born</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: SEARCH BAR                                            --}}
            {{-- ============================================================= --}}
            <div class="mobile-only mg-search mb-3">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <form method="GET" action="{{ route('goats.index') }}" id="goatMobileSearch">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tag / breed / vendor…" />
                </form>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: LIVESTOCK LIST                                        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only">
                @forelse ($goats as $goat)
                    <div class="mobile-card-item">
                        <a href="{{ route('goats.show', $goat) }}" class="block">
                            <div class="mobile-card-row mb-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-sm font-bold text-gray-900 shrink-0">{{ $goat->tag_id }}</span>
                                    @if($goat->gender === 'female' && $goat->activePregnancy())
                                        <span class="text-xs" title="Pregnant">🤰</span>
                                    @endif
                                    @if($goat->birth_type === 'farm_born')
                                        <span class="text-xs" title="Farm-born">🌱</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $goat->breed->name }} · {{ ucfirst($goat->gender) }}</p>
                                        @if($goat->status === 'breeder')
                                            <span class="text-xs font-medium" style="color: #16a34a;">Breeder</span>
                                        @elseif($goat->status === 'sold')
                                            <span class="text-xs font-medium text-gray-400">Sold</span>
                                            @if($goat->sale)
                                                <a href="{{ route('sales.show', $goat->sale) }}" class="text-xs text-indigo-600 hover:underline ml-1">→ sale</a>
                                            @endif
                                        @elseif($goat->status === 'deceased')
                                            <span class="text-xs font-medium" style="color: #dc2626;">Deceased</span>
                                        @else
                                            <span class="text-xs font-medium" style="color: #d97706;">{{ ucfirst($goat->status) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-base font-bold text-gray-900">{{ number_format($goat->current_value, 0) }}</p>
                                    @if($goat->status === 'sold')
                                        <p class="text-xs font-semibold {{ $goat->realized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $goat->realized_gain_loss >= 0 ? '+' : '' }}{{ number_format($goat->realized_gain_loss, 0) }}
                                        </p>
                                    @else
                                        @php $delta = $goat->current_value - $goat->purchase_price; @endphp
                                        <p class="text-xs font-semibold {{ $delta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 0) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </a>
                        <div class="flex items-center gap-4 pt-2 border-t border-gray-50">
                            <span class="text-xs text-gray-500 font-semibold">#{{ $goat->id }}</span>
                            <a href="{{ route('goats.edit', $goat) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            @if($goat->gender === 'female' && !in_array($goat->status, ['sold', 'deceased']))
                                @php $activePreg = $goat->activePregnancy(); @endphp
                                @if($activePreg)
                                    <a href="{{ route('breeding-records.kidding', $activePreg) }}" class="text-xs font-medium" style="color: #ec4899;">👶 Kidding</a>
                                @else
                                    <a href="{{ route('goats.breeding-records.create', $goat) }}" class="text-xs font-medium" style="color: #ec4899;">🤰 Mark Bred</a>
                                @endif
                            @endif
                            <form method="POST" action="{{ route('goats.destroy', $goat) }}" class="inline" data-confirm="Delete this goat?">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No goats found.</p>
                        <a href="{{ route('goats.create') }}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">+ Add Your First Goat</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $goats->withQueryString()->links() }}
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP ONLY (everything below hidden on mobile)              --}}
            {{-- ============================================================= }}
            {{-- HERD SUMMARY BAR (desktop) --}}
            <div class="desktop-only-table grid grid-cols-2 md:grid-cols-5 gap-2 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $summary['purchased_count'] }}</p>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Total Purchased</p>
                </div>
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ $summary['in_herd_count'] }}</p>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">In Herd</p>
                </div>
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-indigo-600">{{ $summary['sold_count'] }}</p>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Sold</p>
                </div>
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-gray-500">{{ $summary['died_count'] }}</p>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Died</p>
                </div>
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold {{ $summary['mortality_rate'] > 15 ? 'text-red-600' : 'text-amber-600' }}">{{ $summary['mortality_rate'] }}%</p>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Mortality Rate</p>
                </div>
            </div>

            {{-- FINANCIAL SUMMARY (desktop) --}}
            <div class="desktop-only-table grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-blue-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Purchase Cost</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['total_purchase'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-indigo-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Sales Revenue</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['total_sales'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 {{ $summary['realized_profit'] >= 0 ? 'border-green-500' : 'border-red-500' }}">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Realized Profit</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold {{ $summary['realized_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($summary['realized_profit'], 0) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-5 border-l-4 border-teal-500">
                    <p class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Herd Value</p>
                    <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-gray-900">{{ number_format($summary['current_herd_value'], 0) }}</p>
                </div>
            </div>

            {{-- DESKTOP TABLE --}}
            <div class="desktop-only-table">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <table id="goats-table" class="w-full text-sm display">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th>Tag</th>
                                <th>Breed</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Breeding</th>
                                <th>Purchase Date</th>
                                <th>Vendor</th>
                                <th class="text-right!">Purchase Price</th>
                                <th class="text-right!">Est. Value</th>
                                <th class="text-right!">Gain/Loss</th>
                                <th>Days</th>
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

            window.initServerDataTable('#goats-table', '{{ route('goats.data') }}', {
                order: [[0, 'asc']],
                columns: [
                    { data: 'tag_id', name: 'tag_id' },
                    { data: 'breed_name', name: 'breed_name' },
                    { data: 'gender', name: 'gender' },
                    { data: 'status_badge', name: 'status_badge' },
                    { data: 'breeding', name: 'breeding' },
                    { data: 'purchase_date', name: 'purchase_date' },
                    { data: 'vendor', name: 'vendor' },
                    { data: 'purchase_price', name: 'purchase_price', className: 'text-right' },
                    { data: 'estimated_value', name: 'estimated_value', className: 'text-right' },
                    { data: 'gain_loss', name: 'gain_loss', className: 'text-right' },
                    { data: 'days_held', name: 'days_held', className: 'text-center' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                filters: [
                    {
                        key: 'status',
                        label: 'All Statuses',
                        options: [
                            @foreach (['kid','grower','breeder','sold','deceased'] as $s)
                            { value: '{{ $s }}', text: '{{ ucfirst($s) }}' },
                            @endforeach
                        ],
                    },
                    {
                        key: 'breed',
                        label: 'All Breeds',
                        options: [
                            @foreach ($breeds as $id => $name)
                            { value: '{{ $id }}', text: '{{ $name }}' },
                            @endforeach
                        ],
                    },
                    {
                        key: 'pregnant',
                        label: 'All Breeding',
                        options: [
                            { value: 'yes', text: '🤰 Pregnant/Bred' },
                            { value: 'open', text: 'Not Pregnant' },
                        ],
                    },
                ],
            });
        });
    </script>
</x-app-layout>
