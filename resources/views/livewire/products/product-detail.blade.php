<div class="max-w-6xl mx-auto space-y-5">

    {{-- ── Header ── --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $product->name }}</h2>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                @if ($product->brand)
                    <span class="badge badge-gray">{{ $product->brand->name }}</span>
                @endif
                @if ($product->category)
                    <span class="badge badge-gray">{{ $product->category->name }}</span>
                @endif
                @if ($product->tracking_type->value === 'serialized')
                    <span class="badge badge-blue">IMEI / Serial</span>
                @else
                    <span class="badge badge-gray">Non-serialized</span>
                @endif
                <span class="{{ $product->is_active ? 'badge-green' : 'badge-red' }} badge">
                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @if ($product->description)
                <p class="text-sm text-gray-500 mt-3 leading-relaxed whitespace-pre-line">
                    {{ $product->description }}
                </p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.edit', $product) }}" wire:navigate class="btn-secondary btn-sm">Edit</a>
            <a href="{{ route('purchases.create') }}" wire:navigate class="btn-primary btn-sm">+ Purchase Stock</a>
        </div>
    </div>

    {{-- ── Variant Selector ── --}}
    @if ($product->variants->count() > 1)
        <div class="card p-4">
            <label class="label text-xs mb-2 block">Select Variant</label>
            <div class="flex flex-wrap gap-2">
                @foreach ($product->variants as $v)
                    <button type="button" wire:click="$set('selectedVariantId', {{ $v->id }})"
                        class="px-4 py-2 rounded-xl text-sm font-medium border-2 transition-colors
                            {{ $selectedVariantId === $v->id
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400' }}">
                        {{ $v->attributes_label ?? 'Standard' }}
                        <span class="opacity-60 ml-1 text-xs">
                            ({{ $v->in_stock_count ?? 0 }} in stock | {{ $v->sold_count ?? 0 }} sold)
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($variant)
        {{-- ── Status Summary Cards ── --}}
        @if ($product->tracking_type->value === 'serialized')
            {{-- Serialized: unit-level counts from product_units table --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                @php
                    $summaryItems = [
                        ['label' => 'In Stock', 'key' => 'in_stock', 'color' => 'bg-green-50 text-green-700'],
                        ['label' => 'Sold', 'key' => 'sold', 'color' => 'bg-gray-50 text-gray-700'],
                        ['label' => 'Reserved', 'key' => 'reserved', 'color' => 'bg-amber-50 text-amber-700'],
                        ['label' => 'Damaged', 'key' => 'damaged', 'color' => 'bg-red-50 text-red-700'],
                        ['label' => 'Written Off', 'key' => 'written_off', 'color' => 'bg-slate-50 text-slate-600'],
                    ];
                @endphp
                @foreach ($summaryItems as $item)
                    <button wire:click="$set('unitStatus', '{{ $item['key'] }}')"
                        class="card p-3 text-left transition-all border-2
                            {{ $unitStatus === $item['key'] ? 'border-indigo-400' : 'border-transparent' }}
                            {{ $item['color'] }}">
                        <div class="text-2xl font-bold">{{ $statusCounts[$item['key']] ?? 0 }}</div>
                        <div class="text-xs font-medium mt-0.5 opacity-75">{{ $item['label'] }}</div>
                    </button>
                @endforeach
            </div>
        @elseif($nonSerializedSummary)
            {{-- Non-serialized: quantity-based counts --}}
            <div class="grid grid-cols-3 gap-4">
                @foreach ([['label' => 'Current Stock', 'value' => $nonSerializedSummary['in_stock'], 'color' => 'bg-green-50 text-green-700', 'sub' => 'units available'], ['label' => 'Total Sold', 'value' => $nonSerializedSummary['total_sold'], 'color' => 'bg-indigo-50 text-indigo-700', 'sub' => 'units sold'], ['label' => 'Total Purchased', 'value' => $nonSerializedSummary['total_purchased'], 'color' => 'bg-blue-50 text-blue-700', 'sub' => 'units received']] as $card)
                    <div class="card p-4 border-0 {{ $card['color'] }}">
                        <div class="text-2xl font-bold">{{ number_format($card['value']) }}</div>
                        <div class="text-sm font-medium mt-0.5">{{ $card['label'] }}</div>
                        <div class="text-xs opacity-60 mt-0.5">{{ $card['sub'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
        {{-- Stock Adjustment Modal --}}
        @livewire('inventory.stock-adjustment-modal')

        {{-- ── Pricing ── --}}
        <div class="card p-4 flex flex-wrap items-center gap-6">
            <div>
                <div class="text-xs text-gray-400 mb-0.5">Selling Price</div>
                <div class="text-xl font-bold text-indigo-700">৳{{ number_format($variant->selling_price, 2) }}</div>
            </div>
            <div class="h-8 border-l border-gray-200"></div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">SKU</div>
                <div class="font-mono text-sm font-semibold text-gray-800">{{ $variant->sku }}</div>
            </div>
            @if ($variant->barcode)
                <div class="h-8 border-l border-gray-200"></div>
                <div>
                    <div class="text-xs text-gray-400 mb-0.5">
                        {{ $product->tracking_type->value === 'serialized' ? 'Carton Barcode' : 'Barcode' }}
                    </div>
                    <div class="font-mono text-sm font-semibold text-gray-800 flex items-center gap-2">
                        {{ $variant->barcode }}
                        <button type="button" x-data
                            @click="navigator.clipboard.writeText('{{ $variant->barcode }}'); $dispatch('notify', {type:'success', message:'Barcode copied'})"
                            class="text-gray-300 hover:text-indigo-600 transition-colors" title="Copy barcode">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
            @if ($product->tracking_type->value === 'non_serialized' && $variant->barcode)
                <a href="{{ route('products.labels.print', $product) }}?variant={{ $variant->id }}" target="_blank"
                    class="ml-auto btn-secondary btn-sm">🏷 Print Labels</a>
            @endif
            @if ($unitStatus)
                <button wire:click="$set('unitStatus', '')"
                    class="text-xs text-gray-400 hover:text-indigo-600 transition-colors">
                    Show all statuses ×
                </button>
            @endif
        </div>

        {{-- ── IMEI Unit Ledger (Serialized) ── --}}
        @if ($product->tracking_type->value === 'serialized' && $units !== null)
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-900 text-sm">Unit / IMEI Ledger</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Full lifecycle — all units ever received, including sold
                        </p>
                    </div>
                    <div class="flex items-center gap-2 sm:ml-auto flex-wrap">
                        <input wire:model.live.debounce.300ms="imeiSearch" type="search" placeholder="Search IMEI…"
                            class="input text-sm w-48">
                        <select wire:model.live="unitStatus" class="input text-sm w-auto">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\UnitStatus::cases() as $status)
                                <option value="{{ $status->value }}">
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="table-th">IMEI / Serial</th>
                                <th class="table-th">2nd IMEI</th>
                                <th class="table-th">Status</th>
                                <th class="table-th">Branch</th>
                                <th class="table-th">Cost</th>
                                <th class="table-th">Received</th>
                                <th class="table-th">Sale Info</th>
                                <th class="table-th">Warranty</th>
                                <th class="table-th">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($units as $unit)
                                @php
                                    $statusColors = [
                                        'in_stock' => 'badge-green',
                                        'sold' => 'badge-gray',
                                        'reserved' => 'badge-yellow',
                                        'returned_pending_inspection' => 'badge-yellow',
                                        'damaged' => 'badge-red',
                                        'lost' => 'badge-red',
                                        'rma_to_supplier' => 'badge-blue',
                                        'written_off' => 'badge-gray',
                                    ];

                                    $saleRecord = $unit->saleRecord ?? null;
                                    $warrantyExpiry = $unit->warrantyExpiresAt();
                                    $shopWarrantyExpiry = $unit->shopWarrantyExpiresAt();
                                    $isUnderMfrWarranty = $warrantyExpiry && $warrantyExpiry->isFuture();
                                    $isUnderShopWarranty = $shopWarrantyExpiry && $shopWarrantyExpiry->isFuture();
                                @endphp
                                <tr class="hover:bg-gray-50 {{ $unit->is_archived ? 'opacity-75' : '' }}">
                                    <td class="table-td">
                                        <div class="font-mono font-semibold text-gray-900">
                                            {{ $unit->serial_number }}
                                        </div>
                                        @if ($unit->purchaseLineItem?->purchase)
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                PO: {{ $unit->purchaseLineItem->purchase->reference_number }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="table-td font-mono text-gray-400 text-xs">
                                        {{ $unit->secondary_serial_number ?? '—' }}
                                    </td>
                                    <td class="table-td">
                                        <span class="badge {{ $statusColors[$unit->status->value] ?? 'badge-gray' }}">
                                            {{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}
                                        </span>
                                        @if ($unit->status->value === 'reserved' && isset($unitHolds[$unit->id]))
                                            @php $hold = $unitHolds[$unit->id]; @endphp
                                            <div class="text-xs text-amber-600 mt-1 max-w-[150px]">
                                                👤 {{ $hold->held_for_name ?: 'Unnamed' }}
                                                @if ($hold->held_for_phone)
                                                    <div class="text-gray-400">{{ $hold->held_for_phone }}</div>
                                                @endif
                                                @if ($hold->hold_expires_at)
                                                    <div
                                                        class="{{ $hold->isHoldExpired() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                                        {{ $hold->isHoldExpired() ? '⚠ Expired' : 'Until' }}
                                                        {{ $hold->hold_expires_at->format('d M') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="table-td text-gray-500 text-sm">
                                        {{ $unit->branch?->name ?? '—' }}
                                    </td>
                                    <td class="table-td font-medium">
                                        ৳{{ number_format($unit->cost_price, 2) }}
                                    </td>
                                    <td class="table-td text-xs text-gray-500">
                                        {{ $unit->created_at->format('d M Y') }}
                                        @if ($unit->purchaseLineItem?->purchase?->supplier)
                                            <div class="text-gray-400">
                                                {{ $unit->purchaseLineItem->purchase->supplier->name }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- ── Sale Info (for sold units) ── --}}
                                    <td class="table-td">
                                        @if ($saleRecord)
                                            <div class="text-xs space-y-0.5">
                                                <div class="font-mono font-semibold text-indigo-600">
                                                    {{ $saleRecord->sale_number }}
                                                </div>
                                                <div class="text-gray-500">
                                                    {{ $unit->sold_at?->format('d M Y') }}
                                                </div>
                                                @if ($saleRecord->customer?->customer_type?->value !== 'walk_in')
                                                    <div class="font-medium text-gray-800">
                                                        {{ $saleRecord->customer?->name }}
                                                    </div>
                                                    <div class="text-gray-400">
                                                        {{ $saleRecord->customer?->phone }}
                                                    </div>
                                                @else
                                                    <div class="text-gray-400">Walk-in</div>
                                                @endif
                                            </div>
                                        @elseif($unit->status->value === 'sold')
                                            <span class="text-xs text-gray-400">Sale data unavailable</span>
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- ── Warranty Status ── --}}
                                    <td class="table-td">
                                        @if ($unit->manufacturer_warranty_months > 0 || $unit->shop_warranty_days > 0)
                                            <div class="text-xs space-y-1">
                                                @if ($unit->manufacturer_warranty_months > 0)
                                                    <div>
                                                        @if ($warrantyExpiry)
                                                            <span
                                                                class="{{ $isUnderMfrWarranty ? 'text-green-600' : 'text-gray-400' }} font-medium">
                                                                {{ $isUnderMfrWarranty ? '🛡 Active' : '⏰ Expired' }}
                                                            </span>
                                                            <div class="text-gray-400">
                                                                Mfr: Until {{ $warrantyExpiry->format('d M Y') }}
                                                            </div>
                                                        @else
                                                            <span class="text-gray-400">
                                                                Mfr: {{ $unit->manufacturer_warranty_months }}mo
                                                                <span class="text-gray-300">(not sold yet)</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if ($unit->shop_warranty_days > 0)
                                                    <div>
                                                        @if ($shopWarrantyExpiry)
                                                            <span
                                                                class="{{ $isUnderShopWarranty ? 'text-blue-600' : 'text-gray-400' }} font-medium text-xs">
                                                                {{ $isUnderShopWarranty ? '✓ Shop' : '✗ Shop' }}
                                                                until {{ $shopWarrantyExpiry->format('d M Y') }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400 text-xs">
                                                                Shop: {{ $unit->shop_warranty_days }}d
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-300 text-xs">No warranty</span>
                                        @endif
                                    </td>
                                    <td class="table-td">
                                        @can('inventory.edit')
                                            @if ($unit->status->value === 'in_stock')
                                                <div class="flex flex-col gap-1 text-xs whitespace-nowrap">
                                                    <button
                                                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $variant->id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'damaged',
                        product_name: '{{ addslashes($product->name) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                                                        class="text-amber-500 hover:underline text-left">⚠ Damage</button>
                                                    <button
                                                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $variant->id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'written_off',
                        product_name: '{{ addslashes($product->name) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                                                        class="text-red-400 hover:underline text-left">✗ Write Off</button>
                                                    <button
                                                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $variant->id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'reserved',
                        product_name: '{{ addslashes($product->name) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                                                        class="text-blue-500 hover:underline text-left">🔒 Reserve</button>
                                                </div>
                                            @elseif ($unit->status->value === 'reserved')
                                                <button
                                                    wire:click="$dispatch('open-stock-adjustment', {
                    unit_id: {{ $unit->id }}, variant_id: {{ $variant->id }},
                    branch_id: {{ $unit->branch_id ?? 0 }}, type: 'unreserved',
                    product_name: '{{ addslashes($product->name) }} – {{ $unit->serial_number }}',
                    tracking_type: 'serialized'
                })"
                                                    class="text-green-600 hover:underline text-xs">🔓 Release Hold</button>
                                            @elseif ($unit->status->value === 'damaged')
                                                <button
                                                    wire:click="$dispatch('open-stock-adjustment', {
                    unit_id: {{ $unit->id }}, variant_id: {{ $variant->id }},
                    branch_id: {{ $unit->branch_id ?? 0 }}, type: 'written_off',
                    product_name: '{{ addslashes($product->name) }} – {{ $unit->serial_number }}',
                    tracking_type: 'serialized'
                })"
                                                    class="text-red-400 hover:underline text-xs">✗ Write Off</button>
                                            @else
                                                <span class="text-gray-300 text-xs">—</span>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="table-td text-center text-gray-400 py-10">
                                        @if ($imeiSearch)
                                            No units found for "{{ $imeiSearch }}".
                                        @elseif($unitStatus)
                                            No units with status "{{ $unitStatus }}".
                                        @else
                                            No units received yet.
                                            <a href="{{ route('purchases.create') }}" wire:navigate
                                                class="text-indigo-600 hover:underline ml-1">
                                                Create a purchase to add IMEIs.
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($units?->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $units->links() }}</div>
                @endif
            </div>

            {{-- ── Non-serialized: Branch Stock ── --}}
        @elseif($product->tracking_type->value === 'non_serialized' && $branchStocks !== null)
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Stock by Branch</h3>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Branch</th>
                            <th class="table-th">Quantity</th>
                            <th class="table-th">Average Cost</th>
                            <th class="table-th">Stock Value</th>
                            @can('inventory.edit')
                                <th class="table-th">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($branchStocks as $stock)
                            <tr>
                                <td class="table-td font-medium">{{ $stock->branch?->name }}</td>
                                <td class="table-td">
                                    <span
                                        class="{{ $stock->quantity > 0 ? 'text-green-700 font-bold' : 'text-red-600 font-bold' }}">
                                        {{ $stock->quantity }}
                                    </span>
                                    @if ($stock->reserved_quantity > 0)
                                        @php $hold = $branchHolds[$stock->branch_id] ?? null; @endphp
                                        <div class="text-xs text-amber-600 mt-0.5">
                                            🔒 {{ $stock->reserved_quantity }} reserved
                                            @if ($hold)
                                                <div class="text-gray-400">
                                                    for {{ $hold->held_for_name ?: 'Unnamed' }}
                                                    @if ($hold->held_for_phone)
                                                        · {{ $hold->held_for_phone }}
                                                    @endif
                                                </div>
                                                @if ($hold->hold_expires_at)
                                                    <div
                                                        class="{{ $hold->isHoldExpired() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                                        {{ $hold->isHoldExpired() ? '⚠ Expired' : 'Until' }}
                                                        {{ $hold->hold_expires_at->format('d M Y') }}
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="table-td text-gray-500">
                                    ৳{{ number_format($stock->average_cost, 2) }}
                                </td>
                                <td class="table-td font-semibold text-gray-800">
                                    ৳{{ number_format($stock->quantity * $stock->average_cost, 2) }}
                                </td>
                                @can('inventory.edit')
                                    <td class="table-td">
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            <button
                                                wire:click="$dispatch('open-stock-adjustment', {
                                    variant_id: {{ $variant->id }}, branch_id: {{ $stock->branch_id }},
                                    type: 'damaged',
                                    product_name: '{{ addslashes($product->name . ($variant->attributes_label ? ' — ' . $variant->attributes_label : '')) }}',
                                    tracking_type: 'non_serialized'
                                })"
                                                class="text-amber-500 hover:underline">⚠ Damage</button>
                                            <button
                                                wire:click="$dispatch('open-stock-adjustment', {
                                    variant_id: {{ $variant->id }}, branch_id: {{ $stock->branch_id }},
                                    type: 'written_off',
                                    product_name: '{{ addslashes($product->name . ($variant->attributes_label ? ' — ' . $variant->attributes_label : '')) }}',
                                    tracking_type: 'non_serialized'
                                })"
                                                class="text-red-400 hover:underline">✗ Write Off</button>
                                            @if ($stock->reserved_quantity > 0)
                                                <button
                                                    wire:click="$dispatch('open-stock-adjustment', {
                                        variant_id: {{ $variant->id }}, branch_id: {{ $stock->branch_id }},
                                        type: 'unreserved',
                                        product_name: '{{ addslashes($product->name . ($variant->attributes_label ? ' — ' . $variant->attributes_label : '')) }}',
                                        tracking_type: 'non_serialized'
                                    })"
                                                    class="text-green-600 hover:underline">🔓 Release</button>
                                            @endif
                                            <button
                                                wire:click="$dispatch('open-stock-adjustment', {
                                    variant_id: {{ $variant->id }}, branch_id: {{ $stock->branch_id }},
                                    type: 'reserved',
                                    product_name: '{{ addslashes($product->name . ($variant->attributes_label ? ' — ' . $variant->attributes_label : '')) }}',
                                    tracking_type: 'non_serialized'
                                })"
                                                class="text-blue-500 hover:underline">🔒 Reserve</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-td text-center text-gray-400 py-8">
                                    No stock received yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
