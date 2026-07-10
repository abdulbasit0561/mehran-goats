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
                <form method="GET" action="{{ route('activity-logs.index') }}" class="flex flex-wrap gap-2 sm:gap-3 sm:items-center">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search description…" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm flex-1 min-w-[150px]" />
                    <select name="action" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm">
                        <option value="">All Actions</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="entity_type" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm">
                        <option value="">All Entities</option>
                        @foreach ($entities as $label => $value)
                            <option value="{{ $value }}" {{ request('entity_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="user_id" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm">
                        <option value="">All Users</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}" {{ (string) request('user_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm" />
                    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-300 rounded-lg sm:rounded-md px-3 py-2 text-sm" />
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg sm:rounded-md text-sm font-medium">Filter</button>
                    <a href="{{ route('activity-logs.index') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
                </form>
            </div>

            {{-- Desktop Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg desktop-only-table">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">When</th>
                                <th class="px-4 py-3 text-left">User</th>
                                <th class="px-4 py-3 text-left">Action</th>
                                <th class="px-4 py-3 text-left">Entity</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-left">IP</th>
                                <th class="px-4 py-3 text-center">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $log->user_name ?? 'System' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $log->actionBadge['class'] }}">
                                            {{ $log->actionBadge['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $log->entity_label }}</td>
                                    <td class="px-4 py-3 text-gray-500 max-w-[300px] truncate">{{ $log->description ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('activity-logs.show', $log) }}" class="text-indigo-600 hover:underline text-xs">Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No activity logs found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">{{ $logs->withQueryString()->links() }}</div>
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
</x-app-layout>
