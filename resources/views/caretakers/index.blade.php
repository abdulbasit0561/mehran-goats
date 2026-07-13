<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Caretakers</h2>
            <a href="{{ route('caretakers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Caretaker</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Info banner --}}
            <div class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🤝</span>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">Caretaker Share System</p>
                        <p class="text-xs text-blue-700 mt-1">
                            Instead of a salary, the caretaker is paid in goats. Every <strong>Nth kid</strong> born on the farm belongs to them.
                            The system automatically tracks all kidding events and calculates which kids are owed to each caretaker.
                        </p>
                        <p class="text-xs text-blue-700 mt-2">
                            <strong>Total kids born on farm:</strong> {{ $totalKidsBorn }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Mobile card list --}}
            <div class="mobile-only">
                @forelse ($caretakers as $caretaker)
                    <div class="bg-white shadow-sm rounded-lg p-6 mb-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-xl font-bold">
                                    {{ strtoupper(substr($caretaker->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-gray-900">{{ $caretaker->name }}</h3>
                                        @if ($caretaker->active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Every <strong>{{ $caretaker->every_nth_kid }}th</strong> kid born
                                        @if ($caretaker->phone) · {{ $caretaker->phone }} @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('caretakers.show', $caretaker) }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">View</a>
                                <a href="{{ route('caretakers.edit', $caretaker) }}" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100">Edit</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ $caretaker->total_kids_born }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Total Kids Born</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-blue-700">{{ $caretaker->kids_owed }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Owed</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-green-700">{{ $caretaker->kids_assigned }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Handed Over</p>
                            </div>
                            <div class="bg-amber-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-amber-700">{{ $caretaker->kids_pending }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Pending</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white shadow-sm rounded-lg p-12 text-center">
                        <p class="text-gray-500">No caretakers yet.</p>
                        <a href="{{ route('caretakers.create') }}" class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Caretaker</a>
                    </div>
                @endforelse
            </div>

            {{-- Desktop DataTable --}}
            <div class="desktop-only-table bg-white shadow-sm rounded-lg p-4">
                <table id="caretakers-table" class="w-full text-sm display">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th>Name</th>
                            <th class="text-center!">Status</th>
                            <th class="text-center!">Every Nth</th>
                            <th>Phone</th>
                            <th class="text-center!">Born</th>
                            <th class="text-center!">Owed</th>
                            <th class="text-center!">Handed</th>
                            <th class="text-center!">Pending</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($caretakers as $caretaker)
                            <tr>
                                <td class="font-medium text-gray-900">{{ $caretaker->name }}</td>
                                <td class="text-center">
                                    @if ($caretaker->active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $caretaker->every_nth_kid }}</td>
                                <td class="text-gray-500">{{ $caretaker->phone ?? '—' }}</td>
                                <td class="text-center">{{ $caretaker->total_kids_born }}</td>
                                <td class="text-center font-medium text-blue-700">{{ $caretaker->kids_owed }}</td>
                                <td class="text-center font-medium text-green-700">{{ $caretaker->kids_assigned }}</td>
                                <td class="text-center font-medium text-amber-700">{{ $caretaker->kids_pending }}</td>
                                <td>
                                    <a href="{{ route('caretakers.show', $caretaker) }}" class="text-gray-600 hover:underline text-xs">View</a>
                                    <a href="{{ route('caretakers.edit', $caretaker) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initClientDataTable === 'undefined') return;
            window.initClientDataTable('#caretakers-table', {
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [3, 8] },
                    { className: 'text-center', targets: [1, 2, 4, 5, 6, 7] },
                ],
            });
        });
    </script>
</x-app-layout>
