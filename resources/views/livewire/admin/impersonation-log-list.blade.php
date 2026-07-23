<div class="space-y-5">
    <h2 class="text-xl font-bold text-gray-900">Impersonation Logs</h2>

    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search target user or shop…"
            class="input max-w-xs text-sm">
        <select wire:model.live="adminFilter" class="input text-sm w-auto">
            <option value="">All Admins</option>
            @foreach ($this->admins as $admin)
                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input wire:model.live="activeOnly" type="checkbox" value="1"
                class="rounded border-gray-300 text-indigo-600">
            Active sessions only
        </label>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Admin</th>
                        <th class="table-th">Impersonated User</th>
                        <th class="table-th">Shop</th>
                        <th class="table-th">Reason</th>
                        <th class="table-th">Started</th>
                        <th class="table-th">Ended</th>
                        <th class="table-th">Duration</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($this->logs as $log)
                        @php
                            $durationMinutes = $log->ended_at ? $log->started_at->diffInMinutes($log->ended_at) : null;
                        @endphp
                        <tr class="hover:bg-gray-50" wire:key="log-{{ $log->id }}">
                            <td class="table-td font-medium text-gray-900">{{ $log->superAdmin?->name ?? '—' }}</td>
                            <td class="table-td">
                                <div class="font-medium text-gray-900">{{ $log->target?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $log->target?->email }}</div>
                            </td>
                            <td class="table-td text-gray-600">{{ $log->shop?->name ?? '—' }}</td>
                            <td class="table-td text-gray-500 text-sm max-w-xs truncate">{{ $log->reason ?? '—' }}</td>
                            <td class="table-td text-xs text-gray-500">{{ $log->started_at?->format('d M Y H:i') }}</td>
                            <td class="table-td text-xs text-gray-500">{{ $log->ended_at?->format('d M Y H:i') ?? '—' }}
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                {{ $durationMinutes !== null ? $durationMinutes . ' min' : '—' }}</td>
                            <td class="table-td">
                                @if ($log->ended_at)
                                    <span class="badge badge-gray text-xs">Ended</span>
                                @else
                                    <span class="badge badge-green text-xs">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-td text-center text-gray-400 py-10">No impersonation
                                activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $this->logs->links() }}</div>
        @endif
    </div>
</div>
