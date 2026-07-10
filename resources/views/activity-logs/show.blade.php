<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('activity-logs.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Back</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Activity Log Detail</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            {{-- Summary card --}}
            <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-4 sm:p-6 mb-4 sm:mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $log->actionBadge['class'] }}">
                        {{ $log->actionBadge['label'] }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $log->entity_label }} @if($log->entity_id)#{{ $log->entity_id }} @endif</span>
                </div>
                <p class="text-lg font-semibold text-gray-900 mb-2">{{ $log->description ?? '—' }}</p>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400 uppercase text-xs">User</span>
                        <p class="text-gray-700 font-medium">{{ $log->user_name ?? 'System' }} @if($log->user) ({{ $log->user->email }}) @endif</p>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-xs">Timestamp</span>
                        <p class="text-gray-700">{{ $log->created_at->format('M d, Y g:i:s A') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-xs">IP Address</span>
                        <p class="text-gray-700">{{ $log->ip_address ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-xs">User Agent</span>
                        <p class="text-gray-500 text-xs truncate" title="{{ $log->user_agent }}">{{ $log->user_agent ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Before/After Diff --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                {{-- Old Values --}}
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg overflow-hidden">
                    <div class="bg-red-50 px-4 py-3 border-b border-red-100">
                        <h3 class="text-sm font-semibold text-red-800">📉 Before (Old Values)</h3>
                    </div>
                    <div class="p-4">
                        @if ($log->old_values && count($log->old_values) > 0)
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($log->old_values as $field => $value)
                                        <tr>
                                            <td class="py-2 pr-3 text-gray-500 font-medium align-top whitespace-nowrap">{{ $field }}</td>
                                            <td class="py-2 text-gray-700 break-all">{{ is_array($value) ? json_encode($value) : (string) $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-sm text-gray-400 py-4 text-center">No previous values recorded</p>
                        @endif
                    </div>
                </div>

                {{-- New Values --}}
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg overflow-hidden">
                    <div class="bg-green-50 px-4 py-3 border-b border-green-100">
                        <h3 class="text-sm font-semibold text-green-800">📈 After (New Values)</h3>
                    </div>
                    <div class="p-4">
                        @if ($log->new_values && count($log->new_values) > 0)
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($log->new_values as $field => $value)
                                        <tr>
                                            <td class="py-2 pr-3 text-gray-500 font-medium align-top whitespace-nowrap">{{ $field }}</td>
                                            <td class="py-2 text-gray-700 break-all">{{ is_array($value) ? json_encode($value) : (string) $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-sm text-gray-400 py-4 text-center">No new values recorded</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
