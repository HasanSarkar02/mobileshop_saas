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
        <div class="flex items-center gap-3">
            <span class="{{ $purchase->payment_status === 'paid' ? 'badge-green' : 'badge-yellow' }} badge">
                {{ ucfirst($purchase->payment_status) }}
            </span>
            <span class="text-lg font-bold text-gray-900">৳{{ number_format($purchase->total_amount, 2) }}</span>
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
            @if ($item->units->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach ($item->units as $unit)
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
                </div>
            @else
                {{-- Non-serialized: just show quantity --}}
                <div class="px-5 py-3 text-sm text-gray-500">
                    {{ $item->quantity }} units received into stock (non-serialized)
                </div>
            @endif
        </div>
    @endforeach

    <div class="text-xs text-gray-400 text-center pb-4">
        Recorded by {{ $purchase->createdBy?->name ?? 'System' }} · {{ $purchase->created_at->format('d M Y H:i') }}
    </div>
</div>
