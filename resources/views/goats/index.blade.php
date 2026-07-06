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
                            <form method="POST" action="{{ route('goats.destroy', $goat) }}" class="inline" onsubmit="return confirm('Delete this goat?')">
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

            {{-- FILTERS (desktop) --}}
            <div class="desktop-only-table bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 mb-4 sm:mb-6">
                <form method="GET" action="{{ route('goats.index') }}" class="hidden sm:flex sm:flex-wrap sm:gap-3 sm:items-center">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tag/name/vendor…" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    <select name="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Statuses</option>
                        @foreach (['kid','grower','breeder','sold','deceased'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <select name="breed" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Breeds</option>
                        @foreach ($breeds as $id => $name)
                            <option value="{{ $id }}" {{ (string) request('breed') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="pregnant" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Breeding</option>
                        <option value="yes" {{ request('pregnant') === 'yes' ? 'selected' : '' }}>🤰 Pregnant/Bred</option>
                        <option value="open" {{ request('pregnant') === 'open' ? 'selected' : '' }}>Not Pregnant</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium hover:bg-gray-700">Filter</button>
                    <a href="{{ route('goats.index') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
                </form>
            </div>

            {{-- DESKTOP TABLE --}}
            <form id="bulkForm" method="POST" action="{{ route('goats.bulk-destroy') }}" onsubmit="return confirm('Delete the selected goats? This cannot be undone.')" class="desktop-only-table">
                @csrf @method('DELETE')

                {{-- Bulk action bar --}}
                <div id="bulkBar" class="hidden bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-indigo-800">
                        <span id="selectedCount">0</span> goat(s) selected
                    </span>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition">
                        🗑️ Delete Selected
                    </button>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" id="selectAll" class="rounded text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                                    </th>
                                    <th class="px-4 py-3 text-left">Tag</th>
                                    <th class="px-4 py-3 text-left">Breed</th>
                                    <th class="px-4 py-3 text-left">Gender</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Breeding</th>
                                    <th class="px-4 py-3 text-left">Purchase Date</th>
                                    <th class="px-4 py-3 text-left">Vendor</th>
                                    <th class="px-4 py-3 text-right">Purchase Price</th>
                                    <th class="px-4 py-3 text-right">Est. Value</th>
                                    <th class="px-4 py-3 text-right">Gain/Loss</th>
                                    <th class="px-4 py-3 text-center">Days</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($goats as $goat)
                                    <tr class="hover:bg-gray-50" x-data="{ checked: false }">
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" name="ids[]" value="{{ $goat->id }}"
                                                   class="row-check rounded text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                                   x-model="checked" />
                                        </td>
                                        <td class="px-4 py-3 font-medium text-indigo-600">
                                            <a href="{{ route('goats.show', $goat) }}">{{ $goat->tag_id }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $goat->breed->name }}</td>
                                        <td class="px-4 py-3 capitalize">{{ $goat->gender }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                @if($goat->status === 'sold') bg-blue-100 text-blue-800
                                                @elseif($goat->status === 'deceased') bg-gray-200 text-gray-700
                                                @elseif($goat->status === 'breeder') bg-green-100 text-green-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ ucfirst($goat->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($goat->gender === 'female' && !in_array($goat->status, ['sold', 'deceased']))
                                                @php $activePreg = $goat->activePregnancy(); @endphp
                                                @if($activePreg)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-700">
                                                        🤰 {{ $activePreg->isConfirmed() ? 'Pregnant' : 'Bred' }} ({{ $activePreg->days_pregnant }}d)
                                                    </span>
                                                    <div class="mt-1 flex gap-2">
                                                        @if(!$activePreg->isConfirmed())
                                                            <form method="POST" action="{{ route('breeding-records.mark-pregnant', $activePreg) }}">
                                                                @csrf
                                                                <button class="text-xs text-pink-600 hover:underline">Confirm</button>
                                                            </form>
                                                        @endif
                                                        <a href="{{ route('breeding-records.kidding', $activePreg) }}" class="text-xs text-green-600 hover:underline">Kidding</a>
                                                    </div>
                                                @else
                                                    <a href="{{ route('goats.breeding-records.create', $goat) }}" class="text-xs text-pink-600 hover:underline font-medium">🤰 Mark Bred</a>
                                                @endif
                                            @else
                                                <span class="text-gray-300 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $goat->purchase_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $goat->vendor ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($goat->purchase_price, 0) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-700">{{ number_format($goat->current_value, 0) }}</td>
                                        <td class="px-4 py-3 text-right font-medium
                                            @if($goat->realized_gain_loss > 0) text-green-600
                                            @elseif($goat->realized_gain_loss < 0) text-red-600
                                            @else text-gray-400 @endif">
                                            @if($goat->status === 'sold')
                                                {{ $goat->realized_gain_loss >= 0 ? '+' : '' }}{{ number_format($goat->realized_gain_loss, 0) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-500">{{ $goat->days_held ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center space-x-2 whitespace-nowrap">
                                            <a href="{{ route('goats.edit', $goat) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                                            <form method="POST" action="{{ route('goats.destroy', $goat) }}" class="inline" onsubmit="return confirm('Delete this goat?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-xs">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="px-4 py-8 text-center text-gray-400">No goats found. <a href="{{ route('goats.create') }}" class="text-indigo-600 hover:underline">Add your first goat</a></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100">
                        {{ $goats->withQueryString()->links() }}
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAll');
            const bulkBar   = document.getElementById('bulkBar');
            const countEl   = document.getElementById('selectedCount');
            const checkboxes = () => document.querySelectorAll('.row-check');

            function updateBar() {
                const checked = document.querySelectorAll('.row-check:checked');
                countEl.textContent = checked.length;
                bulkBar.classList.toggle('hidden', checked.length === 0);
            }

            selectAll?.addEventListener('change', function () {
                checkboxes().forEach(cb => { cb.checked = this.checked; });
                updateBar();
            });

            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('row-check')) {
                    const total = checkboxes().length;
                    const checked = document.querySelectorAll('.row-check:checked').length;
                    if (selectAll) {
                        selectAll.checked = total > 0 && total === checked;
                        selectAll.indeterminate = checked > 0 && checked < total;
                    }
                    updateBar();
                }
            });
        });
    </script>
</x-app-layout>
