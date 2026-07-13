<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📋 Activity Logs</h2>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

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

            window.initServerDataTable('#activity-logs-table', '{{ route('activity-logs.data') }}', {
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
                filters: [
                    {
                        key: 'action',
                        label: 'All Actions',
                        options: [
                            @foreach ($actions as $value => $label)
                            { value: '{{ $value }}', text: '{{ $label }}' },
                            @endforeach
                        ],
                    },
                    {
                        key: 'entity_type',
                        label: 'All Entities',
                        options: [
                            @foreach ($entities as $label => $value)
                            { value: '{{ $value }}', text: '{{ $label }}' },
                            @endforeach
                        ],
                    },
                    {
                        key: 'user_id',
                        label: 'All Users',
                        options: [
                            @foreach ($users as $id => $name)
                            { value: '{{ $id }}', text: '{{ $name }}' },
                            @endforeach
                        ],
                    },
                ],
            });
        });
    </script>
</x-app-layout>
