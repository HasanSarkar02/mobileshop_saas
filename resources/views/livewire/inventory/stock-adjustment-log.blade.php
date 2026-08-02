<div class="space-y-5">
    <h2 class="text-xl font-bold text-gray-900">Stock Adjustment Log</h2>

    {{-- Summary --}}
    @php $s = $summary; @endphp
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
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search product, SKU, or IMEI…"
            class="input text-sm w-64">
        <input wire:model.live="dateFrom" type="date" class="input text-sm w-auto">
        <input wire:model.live="dateTo" type="date" class="input text-sm w-auto">
        <div class="flex flex-wrap gap-1">
            @foreach (['' => 'All', 'opening_stock' => '🏁 Opening', 'opening_stock_reversal' => '↩ Corrections', 'damaged' => '⚠ Damaged', 'written_off' => '✗ Written Off', 'reserved' => '🔒 Reserved', 'unreserved' => '🔓 Released'] as $val => $label)
                <button wire:click="$set('typeFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium
                        {{ $typeFilter === $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        @if (
            $search ||
                $dateFrom !== now()->startOfMonth()->format('Y-m-d') ||
                $dateTo !== now()->format('Y-m-d') ||
                $typeFilter)
            <button wire:click="$set('search', ''); $set('typeFilter', '')"
                class="text-xs text-gray-400 hover:text-indigo-600">Clear filters ×</button>
        @endif
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
                        <th class="table-th text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($adjustments as $adj)
                        @php
                            $isReversalRow = $adj->adjustment_type === 'opening_stock_reversal';
                            $canReverse = $adj->adjustment_type === 'opening_stock' && !$adj->isReversed();
                            $tc = match ($adj->adjustment_type) {
                                'damaged' => 'badge-amber',
                                'written_off' => 'badge-red',
                                'reserved' => 'badge-blue',
                                'unreserved' => 'badge-green',
                                'opening_stock' => 'badge-indigo',
                                'opening_stock_reversal' => 'badge-gray',
                                default => 'badge-gray',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $isReversalRow ? 'bg-gray-50' : '' }}">
                            <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                {{ $adj->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $tc }} text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $adj->adjustment_type)) }}
                                </span>
                            </td>
                            <td class="table-td text-sm text-gray-900">
                                {{ $adj->variant?->product?->name }}
                                <div class="text-xs text-gray-400">{{ $adj->variant?->sku }}</div>
                                @if ($isReversalRow && $adj->reversalOf)
                                    <div class="text-xs text-gray-400 mt-0.5">↩ reversal of #{{ $adj->reversal_of_id }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td font-mono text-xs text-gray-500">
                                @if ($adj->productUnit)
                                    {{ $adj->productUnit->serial_number ?? '—' }}
                                @elseif ($adj->productUnits->isNotEmpty())
                                    <div class="max-w-[150px] truncate"
                                        title="{{ $adj->productUnits->pluck('serial_number')->filter()->join(', ') }}">
                                        {{ $adj->productUnits->pluck('serial_number')->filter()->join(', ') }}
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="table-td text-xs text-gray-500">{{ $adj->branch?->name }}</td>
                            <td class="table-td text-right {{ $isReversalRow ? 'text-red-600 font-semibold' : '' }}">
                                {{ $isReversalRow ? '−' : '' }}{{ $adj->quantity }}
                            </td>
                            <td
                                class="table-td text-right {{ $adj->total_cost > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                {{ $adj->total_cost > 0 ? '৳' . number_format($adj->total_cost, 2) : '—' }}
                            </td>
                            <td class="table-td text-xs text-gray-700">
                                {{ $adj->reason }}
                                @if ($isReversalRow && $adj->reversal_reason)
                                    <div class="text-gray-400 mt-0.5">{{ $adj->reversal_reason }}</div>
                                @endif
                            </td>
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
                            <td class="table-td text-right">
                                @if ($isReversalRow)
                                    <span class="text-xs text-gray-400">—</span>
                                @elseif ($adj->isReversed())
                                    <span class="badge badge-gray text-xs">Reversed</span>
                                @elseif ($canReverse && auth()->user()->isOwner())
                                    <button wire:click="openReverseModal({{ $adj->id }})"
                                        class="text-xs text-red-500 hover:underline font-medium">
                                        Reverse
                                    </button>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="table-td text-center text-gray-400 py-10">
                                @if ($search)
                                    No adjustments match "{{ $search }}".
                                @else
                                    No adjustments.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($adjustments->hasPages())
            <div class="px-4 py-3 border-t">{{ $adjustments->links() }}</div>
        @endif
    </div>

    {{-- Reverse Confirmation Modal --}}
    @if ($showReverseModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div>
                    <h3 class="font-bold text-gray-900 text-lg">Reverse Opening Stock Entry</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        This will roll back the stock quantity and post a correcting accounting entry
                        (Dr Opening Balance Equity / Cr Inventory). The original entry is kept for audit —
                        nothing is deleted.
                    </p>
                </div>
                <div>
                    <label class="label">Reason for correction *</label>
                    <textarea wire:model="reversalReason" rows="3" class="input @error('reversalReason') input-error @enderror"
                        placeholder="e.g. Wrong cost price entered — should be ৳320, not ৳230"></textarea>
                    @error('reversalReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3 justify-end pt-2 border-t border-gray-100">
                    <button wire:click="$set('showReverseModal', false)" class="btn-secondary">Cancel</button>
                    <button wire:click="confirmReverse" wire:loading.attr="disabled" wire:target="confirmReverse"
                        class="btn-danger">
                        <span wire:loading.remove wire:target="confirmReverse">Confirm Reversal</span>
                        <span wire:loading wire:target="confirmReverse">Reversing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
