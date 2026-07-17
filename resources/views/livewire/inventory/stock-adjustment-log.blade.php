<div class="space-y-5">
    <h2 class="text-xl font-bold text-gray-900">Stock Adjustment Log</h2>

    {{-- Summary --}}
    @php $s = $this->summary; @endphp
    <div class="grid grid-cols-2 gap-4">
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-xs font-semibold text-amber-500 uppercase mb-1">Total Damaged Value</div>
            <div class="text-2xl font-bold text-amber-700">৳{{ number_format($s->damaged, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase mb-1">Total Written Off Value</div>
            <div class="text-2xl font-bold text-red-700">৳{{ number_format($s->written_off, 0) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 items-center">
        <input wire:model.live="dateFrom" type="date" class="input text-sm w-auto">
        <input wire:model.live="dateTo" type="date" class="input text-sm w-auto">
        <div class="flex gap-1">
            @foreach (['' => 'All', 'damaged' => '⚠ Damaged', 'written_off' => '✗ Written Off', 'reserved' => '🔒 Reserved', 'unreserved' => '🔓 Released'] as $val => $label)
                <button wire:click="$set('typeFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium
                        {{ $typeFilter === $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Product</th>
                        <th class="table-th">IMEI / Unit</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th text-right">Qty</th>
                        <th class="table-th text-right">Cost Value</th>
                        <th class="table-th">Reason</th>
                        <th class="table-th">GL</th>
                        <th class="table-th">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->adjustments as $adj)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                {{ $adj->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="table-td">
                                @php
                                    $tc = match ($adj->adjustment_type) {
                                        'damaged' => 'badge-amber',
                                        'written_off' => 'badge-red',
                                        'reserved' => 'badge-blue',
                                        'unreserved' => 'badge-green',
                                        default => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $tc }} text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $adj->adjustment_type)) }}
                                </span>
                            </td>
                            <td class="table-td text-sm text-gray-900">
                                {{ $adj->variant?->product?->name }}
                                <div class="text-xs text-gray-400">{{ $adj->variant?->sku }}</div>
                            </td>
                            <td class="table-td font-mono text-xs text-gray-500">
                                {{ $adj->productUnit?->serial_number ?? '—' }}
                            </td>
                            <td class="table-td text-xs text-gray-500">{{ $adj->branch?->name }}</td>
                            <td class="table-td text-right">{{ $adj->quantity }}</td>
                            <td
                                class="table-td text-right {{ $adj->total_cost > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                {{ $adj->total_cost > 0 ? '৳' . number_format($adj->total_cost, 2) : '—' }}
                            </td>
                            <td class="table-td text-xs text-gray-700">{{ $adj->reason }}</td>
                            <td class="table-td">
                                @if ($adj->journalEntry)
                                    <span class="text-xs font-mono text-indigo-500">
                                        {{ $adj->journalEntry->entry_number ?? '✓' }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="table-td text-xs text-gray-400">{{ $adj->createdBy?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="table-td text-center text-gray-400 py-10">No adjustments.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->adjustments->hasPages())
            <div class="px-4 py-3 border-t">{{ $this->adjustments->links() }}</div>
        @endif
    </div>
</div>
