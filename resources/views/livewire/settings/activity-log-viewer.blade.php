@if (!$hasTable)
    <div class="card p-6 bg-amber-50 border-amber-200 text-center space-y-3">
        <div class="text-amber-800 font-semibold">Activity Log Not Available</div>
        <p class="text-sm text-amber-700">
            Install <code class="bg-amber-100 px-1 rounded">spatie/laravel-activitylog</code> to enable audit logging.
        </p>
    </div>
@else
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
                            <th class="table-th normal-case">Time</th>
                            <th class="table-th normal-case">User</th>
                            <th class="table-th normal-case">Action</th>
                            <th class="table-th normal-case">Subject</th>
                            <th class="table-th normal-case">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}
                                </td>
                                <td class="table-td">
                                    <span
                                        class="font-medium text-sm text-gray-900">{{ $log->user_name ?? ($log->causer?->name ?? 'System') }}</span>
                                </td>
                                <td class="table-td">
                                    <span
                                        class="badge {{ match ($log->event ?? ($log->description ?? '')) {
                                            'created', 'customer.created', 'employee.created' => 'badge-green',
                                            'updated', 'customer.updated', 'employee.updated' => 'badge-blue',
                                            'deleted' => 'badge-red',
                                            default => 'badge-gray',
                                        } }} text-xs">
                                        {{ $log->event ?? ($log->description ?? 'action') }}
                                    </span>
                                </td>
                                <td class="table-td">
                                    <div class="text-xs text-gray-700 font-medium">
                                        {{ class_basename($log->subject_type) }}
                                        @if ($log->subject_id)
                                            <span class="text-gray-400">#{{ $log->subject_id }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $log->description }}</div>
                                </td>

                                {{-- 🛠️ DETAILS COLUMN WITH ATTRIBUTE_CHANGES FALLBACK --}}
                                <td class="table-td">
                                    @php
                                        // 1. properties কলাম না পেলে attribute_changes কলাম থেকে ডাটা নিবে
                                        $rawProps = $log->properties ?? null;
                                        if (is_string($rawProps)) {
                                            $props = json_decode($rawProps, true) ?? [];
                                        } elseif ($rawProps instanceof \Illuminate\Support\Collection) {
                                            $props = $rawProps->toArray();
                                        } else {
                                            $props = (array) $rawProps;
                                        }

                                        // Fallback: Check custom 'attribute_changes' column
                                        if (empty($props) && isset($log->attribute_changes)) {
                                            $attrChanges = $log->attribute_changes;
                                            $props = is_string($attrChanges)
                                                ? json_decode($attrChanges, true) ?? []
                                                : (array) $attrChanges;
                                        }

                                        // 2. Double Nesting (attributes.attributes) আনর‍্যাপ করা
                                        $attributes = $props['attributes'] ?? [];
                                        if (isset($attributes['attributes']) && is_array($attributes['attributes'])) {
                                            $attributes = $attributes['attributes'];
                                        }

                                        $old = $props['old'] ?? [];
                                        if (isset($old['old']) && is_array($old['old'])) {
                                            $old = $old['old'];
                                        }

                                        $customProps = array_diff_key($props, ['attributes' => [], 'old' => []]);
                                    @endphp

                                    @if (!empty($attributes) || !empty($customProps))
                                        <details class="cursor-pointer group">
                                            <summary
                                                class="text-xs text-indigo-600 font-medium hover:underline inline-flex items-center gap-1">
                                                <span>View details</span>
                                            </summary>

                                            <div
                                                class="mt-2 text-xs p-2.5 bg-gray-50 rounded-lg border border-gray-200 space-y-1.5 max-w-sm">
                                                {{-- Model Attributes (Name, Phone, Address, etc.) --}}
                                                @foreach ($attributes as $key => $val)
                                                    <div
                                                        class="flex items-start justify-between gap-2 border-b border-gray-100 last:border-0 pb-1">
                                                        <span
                                                            class="font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                                        <span class="text-gray-900 font-semibold text-right break-all">
                                                            @if (isset($old[$key]))
                                                                <span
                                                                    class="line-through text-red-400 font-normal mr-1">
                                                                    {{ is_array($old[$key]) ? json_encode($old[$key]) : $old[$key] }}
                                                                </span>
                                                                <span class="text-gray-400 mr-1">→</span>
                                                            @endif
                                                            <span class="text-emerald-700">
                                                                {{ is_array($val) ? json_encode($val) : $val }}
                                                            </span>
                                                        </span>
                                                    </div>
                                                @endforeach

                                                {{-- Custom properties --}}
                                                @foreach ($customProps as $key => $val)
                                                    <div
                                                        class="flex items-start justify-between gap-2 border-b border-gray-100 last:border-0 pb-1">
                                                        <span
                                                            class="font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                                        <span class="text-gray-900 font-semibold text-right break-all">
                                                            {{ is_array($val) ? json_encode($val) : $val }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-td text-center text-gray-400 py-10">No activity logged
                                    yet.</td>
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
@endif
