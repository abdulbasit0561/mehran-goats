<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📋 Activity Logs</h2>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            {{-- Filters --}}
            <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 mb-4 sm:mb-6">
                <div class="flex flex-wrap gap-2 sm:gap-3 sm:items-center">
                    <select id="filter-action" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm">
                        <option value="">All Actions</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select id="filter-entity" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm">
                        <option value="">All Entities</option>
                        @foreach ($entities as $label => $value)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select id="filter-user" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm">
                        <option value="">All Users</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <input type="date" id="filter-from" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm" />
                    <input type="date" id="filter-to" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm" />
                    <button type="button" id="filter-reset" class="text-sm text-gray-500 hover:underline">Reset</button>
                </div>
            </div>

            {{-- Desktop Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 desktop-only-table">
                <table id="activity-logs-table" class="w-full text-sm display">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Description</th>
                            <th>IP</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            {{-- Mobile Card List --}}
            <div class="mobile-card-list">
                @forelse ($logs as $log)
                    <a href="{{ route('activity-logs.show', $log) }}" class="mobile-card-item block hover:bg-gray-50">
                        <div class="mobile-card-row mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium shrink-0 {{ $log->actionBadge['class'] }}">
                                    @if ($log->action === 'create') + @elseif ($log->action === 'update') ✎ @else ✕ @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $log->description ?? $log->entity_label }}</p>
                                    <p class="text-xs text-gray-400">{{ $log->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">{{ $log->user_name ?? 'System' }}</span>
                        </div>
                        <div class="flex items-center gap-3 ml-10 mt-1 text-[10px] text-gray-400">
                            <span>{{ $log->entity_label }}</span>
                            <span>{{ $log->actionBadge['label'] }}</span>
                            @if ($log->ip_address) <span>{{ $log->ip_address }}</span> @endif
                        </div>
                    </a>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm">No activity logs found.</p>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $logs->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initServerDataTable === 'undefined') return;

            var tbl = window.initServerDataTable('#activity-logs-table', '{{ route('activity-logs.data') }}', {
                order: [[0, 'desc']],
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'user_name', name: 'user_name' },
                    { data: 'action_badge', name: 'action_badge' },
                    { data: 'entity_label', name: 'entity_label' },
                    { data: 'description', name: 'description' },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                extraData: function () {
                    return {
                        entity_type: document.getElementById('filter-entity')?.value || '',
                        action: document.getElementById('filter-action')?.value || '',
                        user_id: document.getElementById('filter-user')?.value || '',
                        from: document.getElementById('filter-from')?.value || '',
                        to: document.getElementById('filter-to')?.value || '',
                    };
                }
            });

            ['filter-action', 'filter-entity', 'filter-user', 'filter-from', 'filter-to'].forEach(function (id) {
                document.getElementById(id)?.addEventListener('change', function () { tbl.ajax.reload(); });
            });
            document.getElementById('filter-reset')?.addEventListener('click', function () {
                ['filter-action', 'filter-entity', 'filter-user', 'filter-from', 'filter-to'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.value = '';
                });
                tbl.ajax.reload();
            });
        });
    </script>
</x-app-layout>
