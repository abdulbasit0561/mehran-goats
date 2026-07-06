<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Goat Details — {{ $goat->tag_id }}</h2>
            <a href="{{ route('goats.index') }}" class="text-sm text-gray-500 hover:underline">← Back to Goats</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Main info card -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <div class="flex flex-wrap items-start gap-6">
                    @if ($goat->photo)
                        <img src="{{ Storage::url($goat->photo) }}" alt="Goat {{ $goat->tag_id }}" class="w-32 h-32 rounded-lg object-cover" />
                    @else
                        <div class="w-32 h-32 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-4xl">🐐</div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Tag</p>
                                <p class="font-medium text-gray-900">{{ $goat->tag_id }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Name</p>
                                <p class="font-medium text-gray-900">{{ $goat->name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Breed</p>
                                <p class="font-medium text-gray-900">{{ $goat->breed->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Gender</p>
                                <p class="font-medium text-gray-900 capitalize">{{ $goat->gender }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Date of Birth</p>
                                <p class="font-medium text-gray-900">{{ $goat->dob?->format('M d, Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Age</p>
                                <p class="font-medium text-gray-900">{{ $goat->age_in_months ? $goat->age_in_months . ' months' : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Weight</p>
                                <p class="font-medium text-gray-900">{{ number_format($goat->weight_kg, 1) }} kg</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Status</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">{{ ucfirst($goat->status) }}</span>
                                @php $activePreg = $goat->activePregnancy(); @endphp
                                @if ($activePreg)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-800 ml-1">
                                        🤰 {{ $activePreg->isConfirmed() ? 'Pregnant' : 'Bred' }} ({{ $activePreg->days_pregnant }}d)
                                    </span>
                                @endif
                            </div>
                            @if ($goat->isFarmBorn())
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Birth</p>
                                    <p class="text-sm text-gray-700">Farm-born</p>
                                    @if ($goat->sire || $goat->dam)
                                        <p class="text-xs text-gray-400">
                                            @if ($goat->sire)<a href="{{ route('goats.show', $goat->sire) }}" class="hover:text-indigo-600">Sire: {{ $goat->sire->tag_id }}</a>@endif
                                            @if ($goat->sire && $goat->dam) · @endif
                                            @if ($goat->dam)<a href="{{ route('goats.show', $goat->dam) }}" class="hover:text-indigo-600">Dam: {{ $goat->dam->tag_id }}</a>@endif
                                        </p>
                                    @endif
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Caretaker</p>
                                @if ($goat->caretaker)
                                    <a href="{{ route('caretakers.show', $goat->caretaker) }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                                        🤝 {{ $goat->caretaker->name }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Purchase Price</p>
                                <p class="font-medium text-gray-900">{{ number_format($goat->purchase_price, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Sale Price</p>
                                <p class="font-medium text-gray-900">{{ number_format($goat->sale_price, 2) }}</p>
                            </div>
                        </div>

                        @if ($goat->notes)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs text-gray-500 uppercase mb-1">Notes</p>
                                <p class="text-sm text-gray-700">{{ $goat->notes }}</p>
                            </div>
                        @endif

                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('goats.edit', $goat) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Edit</a>
                            <form method="POST" action="{{ route('goats.destroy', $goat) }}" onsubmit="return confirm('Delete this goat?')">
                                @csrf @method('DELETE')
                                <button class="px-4 py-2 bg-red-50 text-red-700 rounded-md text-sm font-medium hover:bg-red-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs: Health, Breeding, Ownership -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Health records -->
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Health Records</h3>
                        <a href="{{ route('goats.health-records.create', $goat) }}" class="text-xs text-indigo-600 hover:underline">+ Add</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($goat->healthRecords as $record)
                            <div class="border-l-2 border-indigo-200 pl-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 capitalize">{{ $record->type }}</p>
                                        <p class="text-xs text-gray-500">{{ $record->date->format('M d, Y') }} · {{ number_format($record->cost, 2) }}</p>
                                        @if ($record->notes) <p class="text-xs text-gray-600 mt-1">{{ $record->notes }}</p> @endif
                                    </div>
                                    <form method="POST" action="{{ route('health-records.destroy', $record) }}" onsubmit="return confirm('Delete record?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 text-xs">×</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No health records.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Breeding records -->
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Breeding Records</h3>
                        <a href="{{ route('goats.breeding-records.create', $goat) }}" class="text-xs text-indigo-600 hover:underline">+ Add</a>
                    </div>

                    {{-- Active pregnancy banner --}}
                    @if ($goat->gender === 'female' && $activePreg)
                        <div class="mb-4 rounded-lg bg-pink-50 border border-pink-200 p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-pink-800">
                                        🤰 {{ $activePreg->isConfirmed() ? 'Pregnancy Confirmed' : 'Bred (Unconfirmed)' }}
                                    </p>
                                    <p class="text-xs text-pink-600 mt-1">
                                        Bred {{ $activePreg->breeding_date->format('M d, Y') }} · {{ $activePreg->days_pregnant }} days pregnant
                                    </p>
                                    <p class="text-xs text-pink-600">
                                        Expected kidding: <strong>{{ $activePreg->expected_kidding_date->format('M d, Y') }}</strong>
                                        @if ($activePreg->days_until_due >= 0)({{ $activePreg->days_until_due }} days left) @else(OVERDUE)@endif
                                    </p>
                                    <div class="mt-1 w-full bg-pink-100 rounded-full h-1">
                                        <div class="bg-pink-400 h-1 rounded-full" style="width: {{ $activePreg->pregnancy_progress }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if (! $activePreg->isConfirmed())
                                    <form method="POST" action="{{ route('breeding-records.mark-pregnant', $activePreg) }}">
                                        @csrf
                                        <button class="px-2.5 py-1 bg-pink-600 text-white rounded text-xs font-medium hover:bg-pink-700">✓ Confirm Pregnancy</button>
                                    </form>
                                @endif
                                <a href="{{ route('breeding-records.kidding', $activePreg) }}" class="px-2.5 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">👶 Record Kidding</a>
                                <form method="POST" action="{{ route('breeding-records.mark-open', $activePreg) }}">
                                    @csrf
                                    <button class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded text-xs font-medium hover:bg-gray-200" onclick="return confirm('Mark as did-not-conceive?')">✗ Open</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @php
                            $allBreeding = $goat->siredRecords->merge($goat->birthedRecords)->unique('id')->sortByDesc('breeding_date');
                        @endphp
                        @forelse ($allBreeding as $record)
                            <div class="border-l-2 pl-3 {{ $record->pregnancy_status === 'pregnant' ? 'border-pink-400' : ($record->pregnancy_status === 'kidded' ? 'border-green-400' : ($record->pregnancy_status === 'bred' ? 'border-yellow-400' : 'border-gray-200')) }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $record->breeding_date->format('M d, Y') }}
                                            @if ($record->pregnancy_status !== 'kidded')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs ml-1
                                                    {{ $record->pregnancy_status === 'pregnant' ? 'bg-pink-100 text-pink-700' : '' }}
                                                    {{ $record->pregnancy_status === 'bred' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                    {{ $record->pregnancy_status === 'open' ? 'bg-gray-100 text-gray-500' : '' }}
                                                    {{ $record->pregnancy_status === 'aborted' ? 'bg-red-100 text-red-700' : '' }}">
                                                    {{ ucfirst($record->pregnancy_status) }}
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Sire: {{ $record->sire->tag_id }} · Dam: {{ $record->dam->tag_id }}
                                        </p>
                                        @if ($record->kidding_date)
                                            <p class="text-xs text-gray-600">
                                                Kidded: {{ $record->kidding_date->format('M d, Y') }} · {{ $record->litter_size }} kids
                                                @if ($record->kidding_difficulty) · {{ ucfirst($record->kidding_difficulty) }} @endif
                                            </p>
                                            @if ($record->kids->isNotEmpty())
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($record->kids as $kid)
                                                        <a href="{{ route('goats.show', $kid) }}" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs {{ $kid->gender === 'female' ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' }} hover:underline">
                                                            🐐 {{ $kid->tag_id }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @elseif ($record->expected_kidding_date)
                                            <p class="text-xs text-gray-400">
                                                Expected: {{ $record->expected_kidding_date->format('M d, Y') }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('breeding-records.edit', $record) }}" class="text-gray-400 hover:text-indigo-600 text-xs" title="Edit">✎</a>
                                        <form method="POST" action="{{ route('breeding-records.destroy', $record) }}" onsubmit="return confirm('Delete record?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-500 text-xs">×</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No breeding records.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Ownership -->
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Ownership</h3>
                    </div>
                    <div class="space-y-3">
                        @forelse ($goat->ownershipUnits as $unit)
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $unit->investor->name }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($unit->share_pct, 2) }}% share</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No ownership assigned. <a href="{{ route('investors.index') }}" class="text-indigo-600 hover:underline text-xs">Assign in Investors</a></p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
