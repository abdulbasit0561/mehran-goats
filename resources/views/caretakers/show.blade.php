<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Caretaker — {{ $caretaker->name }}</h2>
            <a href="{{ route('caretakers.index') }}" class="text-sm text-gray-500 hover:underline">← All Caretakers</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Caretaker info + summary --}}
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-2xl font-bold">
                            {{ strtoupper(substr($caretaker->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ $caretaker->name }}</h3>
                                @if ($caretaker->active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                @endif
                            </div>
                            @if ($caretaker->phone)
                                <p class="text-sm text-gray-500 mt-1">📞 {{ $caretaker->phone }}</p>
                            @endif
                            @if ($caretaker->start_date)
                                <p class="text-sm text-gray-500">Agreement since: {{ $caretaker->start_date->format('M d, Y') }}</p>
                            @endif
                            @if ($caretaker->notes)
                                <p class="text-sm text-gray-600 mt-2">{{ $caretaker->notes }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('caretakers.edit', $caretaker) }}" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100">Edit</a>
                        <form method="POST" action="{{ route('caretakers.destroy', $caretaker) }}" onsubmit="return confirm('Delete this caretaker? Assigned goats will be unassigned.')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1.5 bg-red-50 text-red-700 rounded-md text-sm font-medium hover:bg-red-100">Delete</button>
                        </form>
                    </div>
                </div>

                {{-- Summary stats --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-gray-900">{{ $caretaker->total_kids_born }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Kids Born on Farm</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-blue-700">{{ $caretaker->every_nth_kid }}</p>
                        <p class="text-xs text-gray-500 mt-1">Every Nth Kid</p>
                    </div>
                    <div class="bg-indigo-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-indigo-700">{{ $caretaker->kids_owed }}</p>
                        <p class="text-xs text-gray-500 mt-1">Owed to Caretaker</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-green-700">{{ $caretaker->kids_assigned }}</p>
                        <p class="text-xs text-gray-500 mt-1">Handed Over</p>
                    </div>
                    <div class="bg-amber-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-amber-700">{{ $caretaker->kids_pending }}</p>
                        <p class="text-xs text-gray-500 mt-1">Pending Handover</p>
                    </div>
                </div>

                @if ($caretaker->total_kids_born < $caretaker->every_nth_kid)
                    <div class="mt-4 rounded-md bg-gray-50 p-3 text-center">
                        <p class="text-sm text-gray-500">
                            Next share at kid #{{ $caretaker->next_share_at_kid }} — {{ $caretaker->every_nth_kid - $caretaker->total_kids_born }} more kid(s) need to be born.
                        </p>
                    </div>
                @else
                    <div class="mt-4 rounded-md bg-blue-50 p-3 text-center">
                        <p class="text-sm text-blue-700">
                            Next share at kid #{{ $caretaker->next_share_at_kid }}.
                        </p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Birth Ledger --}}
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Birth Ledger</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Every kidding event on the farm, in chronological order. Kids highlighted in green belong to the caretaker.
                    </p>

                    @forelse ($ledger as $row)
                        <div class="mb-3 p-3 rounded-lg {{ $row->share_count > 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-100' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $row->kidding_date->format('M d, Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $row->record->sire->tag_id }} × {{ $row->record->dam->tag_id }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ $row->litter_size }} kid(s)</p>
                                    <p class="text-xs text-gray-400">Kids #{{ $row->kid_start }}–{{ $row->kid_end }}</p>
                                </div>
                            </div>
                            @if ($row->share_count > 0)
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-600 text-white">
                                        🐐 +{{ $row->share_count }} for caretaker
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <p class="text-3xl mb-2">📋</p>
                            <p class="text-sm text-gray-400">No kidding events recorded yet.</p>
                            <p class="text-xs text-gray-400 mt-1">
                                Add breeding records with kidding dates and litter sizes to see the share calculation.
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Assigned Goats --}}
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Goats Handed Over ({{ $assignedGoats->count() }})</h3>
                    <div class="space-y-3">
                        @forelse ($assignedGoats as $goat)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-green-50 border border-green-200">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">🐐</span>
                                    <div>
                                        <a href="{{ route('goats.show', $goat) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $goat->tag_id }}
                                            @if ($goat->name) ({{ $goat->name }}) @endif
                                        </a>
                                        <p class="text-xs text-gray-500">
                                            {{ $goat->breed->name ?? '—' }} · {{ ucfirst($goat->gender) }}
                                            @if ($goat->dob) · Born {{ $goat->dob->format('M d, Y') }} @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-3xl mb-2">🐐</p>
                                <p class="text-sm text-gray-400">No goats assigned yet.</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Assign goats to this caretaker from the goat's edit page.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
