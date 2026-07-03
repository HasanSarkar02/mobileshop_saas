<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Activity Log</h2>
        <p class="text-xs text-gray-400">All system actions by your team</p>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search action, user, subject…"
            class="input max-w-xs">
        <input wire:model.live="dateFrom" type="date" class="input w-auto">
        <input wire:model.live="dateTo" type="date" class="input w-auto">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Time</th>
                        <th class="table-th">User</th>
                        <th class="table-th">Action</th>
                        <th class="table-th">Subject</th>
                        <th class="table-th">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}
                            </td>
                            <td class="table-td">
                                <span class="font-medium text-sm text-gray-900">{{ $log->user_name }}</span>
                            </td>
                            <td class="table-td">
                                <span
                                    class="badge {{ match ($log->event ?? '') {
                                        'created' => 'badge-green',
                                        'updated' => 'badge-blue',
                                        'deleted' => 'badge-red',
                                        default => 'badge-gray',
                                    } }} text-xs">
                                    {{ $log->event ?? 'action' }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="text-xs text-gray-700">
                                    {{ class_basename($log->subject_type) }}
                                    @if ($log->subject_id)
                                        <span class="text-gray-400">#{{ $log->subject_id }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400">{{ $log->description }}</div>
                            </td>
                            <td class="table-td">
                                @if ($log->properties && $log->properties !== '[]' && $log->properties !== '{}')
                                    <details class="cursor-pointer">
                                        <summary class="text-xs text-indigo-500 hover:underline">View changes</summary>
                                        <pre class="text-xs text-gray-500 mt-1 max-w-xs overflow-x-auto whitespace-pre-wrap">{{ json_encode(json_decode($log->properties), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-10">No activity logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
