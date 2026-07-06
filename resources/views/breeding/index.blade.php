<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Breeding & Pregnancy</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Birth Rate Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-3xl font-bold text-pink-600">{{ $stats['pregnant_count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Pregnant Does</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-3xl font-bold text-indigo-600">{{ $stats['total_kids_born'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Kids Born</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-3xl font-bold text-green-600">{{ $stats['kidding_rate'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">Kidding Rate</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-3xl font-bold text-blue-600">{{ $stats['avg_litter_size'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Avg Litter Size</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-700">{{ $stats['total_breedings'] }}</p>
                    <p class="text-xs text-gray-500">Total Breedings</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-700">{{ $stats['births_this_year'] }}</p>
                    <p class="text-xs text-gray-500">Births This Year</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-700">{{ $stats['births_this_month'] }}</p>
                    <p class="text-xs text-gray-500">Births This Month</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-700">{{ $stats['breeding_does'] }}</p>
                    <p class="text-xs text-gray-500">Breeding Does</p>
                </div>
            </div>

            {{-- Overdue pregnancies --}}
            @if ($overdueKiddings->isNotEmpty())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-red-800 mb-3 flex items-center gap-2">
                        ⚠️ Overdue ({{ $overdueKiddings->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach ($overdueKiddings as $record)
                            <div class="flex items-center justify-between bg-white rounded-md px-3 py-2">
                                <div>
                                    <a href="{{ route('goats.show', $record->dam) }}" class="text-sm font-medium text-red-700 hover:underline">
                                        {{ $record->dam->tag_id }} {{ $record->dam->name ? '(' . $record->dam->name . ')' : '' }}
                                    </a>
                                    <p class="text-xs text-red-600">Due {{ $record->expected_kidding_date->format('M d, Y') }} ({{ abs($record->days_until_due) }} days overdue)</p>
                                </div>
                                <a href="{{ route('breeding-records.kidding', $record) }}" class="px-3 py-1 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700">Record Kidding</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Pregnant Does --}}
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        🤰 Pregnant Does ({{ $pregnantRecords->count() }})
                    </h3>
                    <div class="space-y-3">
                        @forelse ($pregnantRecords as $record)
                            <div class="border border-gray-100 rounded-lg p-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <a href="{{ route('goats.show', $record->dam) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $record->dam->tag_id }} {{ $record->dam->name ? '(' . $record->dam->name . ')' : '' }}
                                        </a>
                                        <p class="text-xs text-gray-500">
                                            {{ $record->dam->breed->name ?? '—' }} · Sire: {{ $record->sire->tag_id ?? '—' }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Bred {{ $record->breeding_date->format('M d, Y') }} ({{ $record->days_pregnant }} days pregnant)
                                        </p>
                                        @if ($record->isConfirmed())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-700 mt-1">Pregnancy Confirmed</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 mt-1">Bred (unconfirmed)</span>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">{{ $record->expected_kidding_date->format('M d') }}</p>
                                        <p class="text-xs @if($record->days_until_due < 0) text-red-500 @else text-gray-400 @endif">
                                            @if ($record->days_until_due < 0) Overdue {{ abs($record->days_until_due) }}d @else {{ $record->days_until_due }}d left @endif
                                        </p>
                                    </div>
                                </div>
                                {{-- Progress bar --}}
                                <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-pink-400 h-1.5 rounded-full transition-all" style="width: {{ $record->pregnancy_progress }}%"></div>
                                </div>
                                <div class="mt-2 flex gap-2">
                                    @if (! $record->isConfirmed())
                                        <form method="POST" action="{{ route('breeding-records.mark-pregnant', $record) }}">
                                            @csrf
                                            <button class="px-2.5 py-1 bg-pink-50 text-pink-700 rounded text-xs font-medium hover:bg-pink-100">✓ Confirm Pregnancy</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('breeding-records.kidding', $record) }}" class="px-2.5 py-1 bg-green-50 text-green-700 rounded text-xs font-medium hover:bg-green-100">👶 Record Kidding</a>
                                    <a href="{{ route('breeding-records.edit', $record) }}" class="px-2.5 py-1 bg-gray-50 text-gray-600 rounded text-xs font-medium hover:bg-gray-100">Edit</a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-3xl mb-2">🤰</p>
                                <p class="text-sm text-gray-400">No pregnant does right now.</p>
                                <p class="text-xs text-gray-400 mt-1">Record a breeding from a doe's detail page.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent Kiddings --}}
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        👶 Recent Births
                    </h3>
                    <div class="space-y-3">
                        @forelse ($recentKiddings as $record)
                            <div class="border border-gray-100 rounded-lg p-3">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <a href="{{ route('goats.show', $record->dam) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $record->dam->tag_id }} {{ $record->dam->name ? '(' . $record->dam->name . ')' : '' }}
                                        </a>
                                        <p class="text-xs text-gray-500">
                                            Kidded {{ $record->kidding_date->format('M d, Y') }} · {{ $record->litter_size }} kid(s)
                                            @if ($record->kidding_difficulty) · {{ ucfirst($record->kidding_difficulty) }} @endif
                                        </p>
                                    </div>
                                </div>
                                @if ($record->kids->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($record->kids as $kid)
                                            <a href="{{ route('goats.show', $kid) }}" class="inline-flex items-center px-2 py-1 rounded text-xs {{ $kid->gender === 'female' ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' }} hover:underline">
                                                🐐 {{ $kid->tag_id }}
                                                @if ($kid->caretaker) 🤝 @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-3xl mb-2">🐐</p>
                                <p class="text-sm text-gray-400">No kiddings recorded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Breeding Does Quick Reference --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 mt-6">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    ♀ Breeding Does ({{ $breedingDoes->count() }})
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($breedingDoes as $doe)
                        <a href="{{ route('goats.show', $doe) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-full text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">
                            {{ $doe->tag_id }}
                            @if ($doe->activePregnancy())
                                <span class="w-2 h-2 bg-pink-500 rounded-full" title="Pregnant"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Punjab breeding season info --}}
            <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📋</span>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Pakistan Punjab Breeding Season</p>
                        <p class="text-xs text-amber-700 mt-1">
                            Goats have two breeding seasons in Punjab:
                            <strong>Autumn</strong> (Sep–Nov, kidding Jan–Apr) and <strong>Spring</strong> (Feb–Apr, kidding Jul–Sep).
                            Gestation period is approximately <strong>150 days</strong> (~5 months).
                            Does come into heat every 18–22 days. Twins occur in ~40% of births, triplets ~8%.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
