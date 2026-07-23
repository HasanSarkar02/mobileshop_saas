<div class="max-w-4xl mx-auto space-y-5">
    {{-- Header --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <div class="font-mono font-bold text-indigo-700 text-lg">{{ $purchase->reference_number }}</div>
            <div class="text-sm text-gray-500 mt-1">
                {{ $purchase->supplier->name }} · {{ $purchase->branch->name }} ·
                {{ $purchase->purchase_date->format('d M Y') }}
            </div>
        </div>
        @if (!in_array($purchase->payment_status, ['paid']) || $purchase->lineItems->isNotEmpty())
            @can('purchases.manage')
                <a href="{{ route('purchases.return', $purchase) }}" wire:navigate class="btn-secondary btn-sm">
                    ↩ Return Items
                </a>
            @endcan
        @endif
        <div class="flex items-center gap-3">
            <span class="{{ $purchase->payment_status === 'paid' ? 'badge-green' : 'badge-yellow' }} badge">
                {{ ucfirst($purchase->payment_status) }}
            </span>
            <span class="text-lg font-bold text-gray-900">৳{{ number_format($purchase->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- Payment Status --}}
    <div class="card p-4">
        <h3 class="font-semibold text-gray-900 text-sm mb-3">Payment Status</h3>
        <div class="grid grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-400">Purchase Total</div>
                <div class="font-bold text-gray-700">৳{{ number_format($purchase->total_amount, 2) }}</div>
            </div>
            @if ($purchase->totalReturned() > 0)
                <div>
                    <div class="text-xs text-gray-400">Returns</div>
                    <div class="font-bold text-amber-600">-৳{{ number_format($purchase->totalReturned(), 2) }}</div>
                </div>
            @endif
            <div>
                <div class="text-xs text-gray-400">Net Payable</div>
                <div class="font-bold text-gray-900">৳{{ number_format($purchase->effectiveTotalAmount(), 2) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400">Amount Paid</div>
                <div class="font-bold text-green-700">৳{{ number_format($purchase->amount_paid, 2) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400">Outstanding</div>
                @php $outstanding = $purchase->effectiveTotalAmount() - (float)$purchase->amount_paid; @endphp
                <div class="font-bold {{ $outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $outstanding > 0 ? '৳' . number_format($outstanding, 2) : '✓ Cleared' }}
                </div>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <span
                class="badge {{ match ($purchase->payment_status) {
                    'paid' => 'badge-green',
                    'partial' => 'badge-yellow',
                    default => 'badge-red',
                } }}">
                {{ ucfirst($purchase->payment_status) }}
            </span>
            @if ($outstanding > 0)
                <a href="{{ route('suppliers.show', $purchase->supplier) }}" wire:navigate
                    class="text-xs text-indigo-600 hover:underline">
                    Record Payment at Supplier Profile →
                </a>
            @endif
            @can('purchases.manage')
                <a href="{{ route('purchases.return', $purchase) }}" wire:navigate
                    class="text-xs text-amber-600 hover:underline ml-auto">
                    ↩ Return Items
                </a>
            @endcan
        </div>
    </div>

    {{-- Line Items --}}
    @foreach ($purchase->lineItems as $item)
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                <div class="flex-1">
                    <span class="font-semibold text-gray-900 text-sm">
                        {{ $item->variant->product->brand?->name }} {{ $item->variant->product->name }}
                        @if ($item->variant->attributes_label)
                            <span class="text-gray-400 font-normal">— {{ $item->variant->attributes_label }}</span>
                        @endif
                    </span>
                    <span class="text-xs text-gray-400 ml-2">{{ $item->variant->sku }}</span>
                </div>
                <div class="text-sm text-gray-600">
                    {{ $item->quantity }} × ৳{{ number_format($item->unit_cost, 2) }}
                    = <strong>৳{{ number_format($item->line_total, 2) }}</strong>
                </div>
            </div>

            {{-- Serialized units (IMEI list) --}}
            {{-- purchase-detail.blade.php — replace the unconditional units block --}}
            @if ($item->units_count > 0)
                <div class="px-5 py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $item->units_count }} serialized unit(s) received</span>
                    <button wire:click="toggleLineItem({{ $item->id }})"
                        class="text-xs text-indigo-600 hover:underline font-medium">
                        {{ $expandedLineItemId === $item->id ? 'Hide IMEIs ▲' : 'Show IMEIs ▼' }}
                    </button>
                </div>

                @if ($expandedLineItemId === $item->id)
                    <div class="divide-y divide-gray-50">
                        @foreach ($this->unitsForLineItem($item->id) as $unit)
                            <div class="px-5 py-2.5 flex items-center gap-3 text-sm">
                                <span class="font-mono text-gray-800 font-medium">{{ $unit->serial_number }}</span>
                                @if ($unit->secondary_serial_number)
                                    <span class="text-gray-400 text-xs">/ {{ $unit->secondary_serial_number }}</span>
                                @endif
                                <span class="badge badge-green ml-auto">{{ ucfirst($unit->status->value) }}</span>
                                @if ($unit->manufacturer_warranty_months > 0)
                                    <span class="text-xs text-gray-400">{{ $unit->manufacturer_warranty_months }}mo
                                        warranty</span>
                                @endif
                            </div>
                        @endforeach
                        @if ($item->units_count > 200)
                            <div class="px-5 py-2 text-xs text-gray-400 text-center">
                                Showing first 200 of {{ $item->units_count }} — use Product Detail's IMEI search for
                                the full ledger.
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="px-5 py-3 text-sm text-gray-500">
                    {{ $item->quantity }} units received into stock (non-serialized)
                </div>
            @endif
        </div>
    @endforeach

    {{-- Purchase Returns --}}
    @if ($purchase->returns->isNotEmpty())
        <div class="card overflow-hidden border-amber-200">
            <div class="px-5 py-3 bg-amber-50 border-b border-amber-200 flex items-center justify-between">
                <h3 class="font-semibold text-amber-900 text-sm">
                    ↩ Purchase Returns ({{ $purchase->returns->count() }})
                </h3>
                <span class="text-sm font-bold text-amber-700">
                    Total Returned: ৳{{ number_format($purchase->totalReturned(), 2) }}
                </span>
            </div>
            @foreach ($purchase->returns as $return)
                <div class="p-4 border-b border-amber-100 last:border-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-mono font-bold text-amber-700 text-sm">
                                {{ $return->return_number }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ $return->return_date->format('d M Y') }} ·
                                {{ ucfirst(str_replace('_', ' ', $return->settlement_type)) }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                Reason: {{ $return->return_reason }}
                            </div>
                            {{-- Return items list --}}
                            <div class="mt-2 space-y-1">
                                @foreach ($return->items as $item)
                                    <div class="text-xs text-gray-600">
                                        • {{ $item->variant?->product?->name }}
                                        ({{ $item->variant?->sku }})
                                        × {{ $item->quantity }}
                                        @ ৳{{ number_format($item->unit_cost, 2) }}
                                        <span class="badge badge-gray text-xs ml-1">
                                            {{ ucfirst($item->condition) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs text-gray-400">Return Value</div>
                            <div class="font-bold text-amber-700 text-lg">
                                ৳{{ number_format($return->total_amount, 2) }}
                            </div>
                            <div class="badge badge-green text-xs mt-1">
                                {{ $return->settlement_type === 'credit_note' ? 'Credit Note' : 'Cash Refunded' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Effective balance after returns --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Original Amount</span>
                    <span class="font-semibold">৳{{ number_format($purchase->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-amber-600">Less: Returns</span>
                    <span class="font-semibold text-amber-600">
                        -৳{{ number_format($purchase->totalReturned(), 2) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm mt-1 pt-1 border-t border-gray-200">
                    <span class="font-bold text-gray-900">Net Payable</span>
                    <span class="font-bold text-indigo-700">
                        ৳{{ number_format($purchase->effectiveTotalAmount(), 2) }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    <div class="text-xs text-gray-400 text-center pb-4">
        Recorded by {{ $purchase->createdBy?->name ?? 'System' }} · {{ $purchase->created_at->format('d M Y H:i') }}
    </div>
</div>
