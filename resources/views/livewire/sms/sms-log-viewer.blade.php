<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">SMS Log & History</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Track all sent SMS notifications and delivery statuses
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search number, message, template…"
            class="input max-w-xs">

        <select wire:model.live="status" class="input w-auto">
            <option value="">All Statuses</option>
            <option value="sent">Sent</option>

            <option value="success">Success</option>
            <option value="delivered">Delivered</option>
            <option value="failed">Failed</option>
            <option value="pending">Pending</option>
        </select>

        <input wire:model.live="dateFrom" type="date" class="input w-auto">
        <input wire:model.live="dateTo" type="date" class="input w-auto">
    </div>

    {{-- Table Card --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="table-th normal-case">Time</th>
                        <th class="table-th normal-case">Recipient</th>
                        <th class="table-th normal-case">Message</th>
                        <th class="table-th normal-case">Status</th>
                        <th class="table-th normal-case">Cost</th>
                        <th class="table-th normal-case">Sent By</th>
                        <th class="table-th normal-case">Response</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            {{-- Date & Time --}}
                            <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                {{ $log->created_at?->format('d M Y H:i:s') }}
                            </td>

                            {{-- To Number --}}
                            <td class="table-td whitespace-nowrap">
                                <span class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                    {{ $log->to_number }}
                                </span>
                                @if ($log->template)
                                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-mono">
                                        {{ $log->template }}
                                    </div>
                                @endif
                            </td>

                            {{-- Message Content --}}
                            <td class="table-td max-w-md">
                                <div class="text-xs text-gray-700 dark:text-gray-300 break-words line-clamp-2"
                                    title="{{ $log->message }}">
                                    {{ $log->message }}
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="table-td whitespace-nowrap">
                                <span
                                    class="badge {{ match (strtolower($log->status ?? '')) {
                                        'sent', 'success', 'delivered' => 'badge-green',
                                        'failed', 'error' => 'badge-red',
                                        'pending' => 'badge-yellow',
                                        default => 'badge-gray',
                                    } }} text-xs capitalize">
                                    {{ $log->status ?? 'unknown' }}
                                </span>
                            </td>

                            {{-- Cost --}}
                            <td
                                class="table-td text-xs font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $log->cost ? number_format($log->cost, 2) . ' ৳' : '—' }}
                            </td>

                            {{-- Sent By --}}
                            <td class="table-td text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $log->createdBy?->name ?? 'System' }}
                            </td>

                            {{-- Response / Details --}}
                            <td class="table-td">
                                @if ($log->provider_response || $log->message_id)
                                    <details class="cursor-pointer group">
                                        <summary
                                            class="text-xs text-indigo-600 font-medium hover:underline inline-flex items-center gap-1">
                                            <span>View</span>
                                        </summary>
                                        <div
                                            class="mt-2 text-[11px] p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 space-y-1 max-w-xs break-all font-mono">
                                            @if ($log->message_id)
                                                <div><strong class="text-gray-500">Msg ID:</strong>
                                                    {{ $log->message_id }}</div>
                                            @endif
                                            @if ($log->provider_response)
                                                <div><strong class="text-gray-500">API Res:</strong>
                                                    {{ is_array($log->provider_response) ? json_encode($log->provider_response) : $log->provider_response }}
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-10">
                                No SMS logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
