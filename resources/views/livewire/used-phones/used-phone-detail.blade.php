<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-start justify-between gap-4">
        <div>
            <div class="font-mono font-bold text-indigo-700 text-lg">
                {{ $acquisition->acquisition_number }}
            </div>
            <h2 class="text-xl font-bold text-gray-900 mt-1">{{ $acquisition->model_description }}</h2>
            <div class="flex flex-wrap gap-2 mt-2">
                @php
                    $condColors = [
                        'excellent' => 'badge-green',
                        'good' => 'badge-blue',
                        'fair' => 'badge-yellow',
                        'poor' => 'badge-red',
                        'for_parts' => 'badge-gray',
                    ];
                @endphp
                <span class="badge {{ $condColors[$acquisition->condition->value] ?? 'badge-gray' }}">
                    {{ $acquisition->condition->label() }}
                </span>
                @if ($saleRecord)
                    <span class="badge badge-green">✓ Sold</span>
                @else
                    <span class="badge badge-blue">In Inventory</span>
                @endif
            </div>
        </div>
        <a href="{{ route('used-phones.index') }}" wire:navigate class="btn-secondary btn-sm shrink-0">← Back</a>
    </div>

    {{-- Stock Adjustment Modal --}}
    @livewire('inventory.stock-adjustment-modal')

    {{-- Unit Actions --}}
    @if ($acquisition->productUnit && !$saleRecord)
        @php $unit = $acquisition->productUnit; @endphp
        <div class="card p-4 flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Actions</span>

            <span
                class="badge {{ match ($unit->status->value) {
                    'in_stock' => 'badge-green',
                    'reserved' => 'badge-yellow',
                    'damaged' => 'badge-red',
                    'written_off' => 'badge-gray',
                    default => 'badge-gray',
                } }}">
                {{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}
            </span>

            @can('inventory.edit')
                @if ($unit->status->value === 'in_stock')
                    <button
                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $acquisition->product_variant_id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'damaged',
                        product_name: '{{ addslashes($acquisition->model_description) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                        class="btn-secondary btn-sm text-amber-600">⚠ Mark Damaged</button>

                    <button
                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $acquisition->product_variant_id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'written_off',
                        product_name: '{{ addslashes($acquisition->model_description) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                        class="btn-secondary btn-sm text-red-600">✗ Mark Lost / Stolen</button>

                    <button
                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $acquisition->product_variant_id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'reserved',
                        product_name: '{{ addslashes($acquisition->model_description) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                        class="btn-secondary btn-sm text-blue-600">🔒 Reserve</button>
                @elseif ($unit->status->value === 'reserved')
                    <button
                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $acquisition->product_variant_id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'unreserved',
                        product_name: '{{ addslashes($acquisition->model_description) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                        class="btn-secondary btn-sm text-green-600">🔓 Release Hold</button>
                @elseif ($unit->status->value === 'damaged')
                    <button
                        wire:click="$dispatch('open-stock-adjustment', {
                        unit_id: {{ $unit->id }}, variant_id: {{ $acquisition->product_variant_id }},
                        branch_id: {{ $unit->branch_id ?? 0 }}, type: 'written_off',
                        product_name: '{{ addslashes($acquisition->model_description) }} – {{ $unit->serial_number }}',
                        tracking_type: 'serialized'
                    })"
                        class="btn-secondary btn-sm text-red-600">✗ Write Off</button>
                @endif
            @endcan
        </div>
    @endif

    {{-- P&L Card --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs text-red-500 font-medium">Purchased For</div>
            <div class="text-xl font-bold text-red-700">৳{{ number_format($acquisition->purchase_price, 2) }}</div>
        </div>
        <div class="card p-4 border-0 {{ $saleRecord ? 'bg-green-50' : 'bg-gray-50' }}">
            <div class="text-xs {{ $saleRecord ? 'text-green-500' : 'text-gray-400' }} font-medium">
                {{ $saleRecord ? 'Sold For' : 'Expected Sell Price' }}
            </div>
            <div class="text-xl font-bold {{ $saleRecord ? 'text-green-700' : 'text-gray-500' }}">
                @if ($saleRecord)
                    ৳{{ number_format($saleRecord->line_total, 2) }}
                @elseif($acquisition->expected_sell_price > 0)
                    ৳{{ number_format($acquisition->expected_sell_price, 2) }}
                @else
                    —
                @endif
            </div>
        </div>
        <div
            class="card p-4 border-0 {{ $profit !== null ? ($profit >= 0 ? 'bg-green-50' : 'bg-red-50') : 'bg-gray-50' }}">
            <div
                class="text-xs {{ $profit !== null ? ($profit >= 0 ? 'text-green-500' : 'text-red-500') : 'text-gray-400' }} font-medium">
                {{ $profit !== null ? 'Actual Profit' : 'Expected Profit' }}
            </div>
            <div
                class="text-xl font-bold {{ $profit !== null ? ($profit >= 0 ? 'text-green-700' : 'text-red-700') : 'text-gray-500' }}">
                @if ($profit !== null)
                    {{ $profit >= 0 ? '+' : '' }}৳{{ number_format($profit, 2) }}
                @elseif($acquisition->expected_sell_price > 0)
                    {{ $acquisition->expected_sell_price - $acquisition->purchase_price >= 0 ? '+' : '' }}৳{{ number_format($acquisition->expected_sell_price - $acquisition->purchase_price, 2) }}
                    <span class="text-xs font-normal opacity-60">(est.)</span>
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- Phone Info + Seller --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div class="card p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Phone Details</h3>
            @foreach ([['label' => 'IMEI 1', 'value' => $acquisition->imei_1, 'mono' => true], ['label' => 'IMEI 2', 'value' => $acquisition->imei_2 ?? '—', 'mono' => true], ['label' => 'Condition', 'value' => $acquisition->condition->label()], ['label' => 'Accessories', 'value' => $acquisition->accessories ?? '—'], ['label' => 'Received At', 'value' => $acquisition->branch?->name]] as $row)
                <div class="flex gap-3">
                    <span class="text-xs text-gray-400 w-24 shrink-0 pt-0.5">{{ $row['label'] }}</span>
                    <span
                        class="{{ $row['mono'] ?? false ? 'font-mono text-indigo-600' : 'text-gray-800' }} text-sm font-medium">
                        {{ $row['value'] }}
                    </span>
                </div>
            @endforeach
            @if ($acquisition->condition_notes)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 text-xs text-amber-800 mt-2">
                    {{ $acquisition->condition_notes }}
                </div>
            @endif
        </div>

        <div class="card p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <h3 class="font-semibold text-gray-900 text-sm">Seller Information</h3>
                @can('used_phones.manage')
                    @if (!$editingSeller)
                        <button wire:click="startEditingSeller" type="button"
                            class="text-xs text-indigo-600 hover:underline">✎ Edit</button>
                    @endif
                @endcan
            </div>

            @if ($editingSeller)
                <div class="space-y-3">
                    <div>
                        <label class="label">Seller Name *</label>
                        <input wire:model="sellerName" type="text"
                            class="input @error('sellerName') input-error @enderror">
                        @error('sellerName')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input wire:model="sellerPhone" type="tel" class="input">
                    </div>
                    <div>
                        <label class="label">NID Number</label>
                        <input wire:model="sellerNid" type="text" class="input">
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <input wire:model="sellerAddress" type="text" class="input">
                    </div>
                </div>
            @else
                @foreach ([['label' => 'Name', 'value' => $acquisition->seller_name], ['label' => 'Phone', 'value' => $acquisition->seller_phone ?? '—'], ['label' => 'NID', 'value' => $acquisition->seller_nid ?? '—'], ['label' => 'Address', 'value' => $acquisition->seller_address ?? '—']] as $row)
                    <div class="flex gap-3">
                        <span class="text-xs text-gray-400 w-20 shrink-0 pt-0.5">{{ $row['label'] }}</span>
                        <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Seller Documents --}}
    {{-- Seller Documents --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2 mb-3">Seller Documents</h3>

        @if ($editingSeller)
            <div class="grid sm:grid-cols-3 gap-4 mb-4">
                @foreach ([['prop' => 'sellerPhoto', 'existing' => $acquisition->seller_photo_path, 'label' => 'Seller Photo'], ['prop' => 'sellerNidFront', 'existing' => $acquisition->seller_nid_front_path, 'label' => 'NID Front'], ['prop' => 'sellerNidBack', 'existing' => $acquisition->seller_nid_back_path, 'label' => 'NID Back']] as $doc)
                    <div>
                        <label class="label">{{ $doc['label'] }}</label>
                        @if ($this->{$doc['prop']})
                            <img src="{{ $this->{$doc['prop']}->temporaryUrl() }}"
                                class="w-full h-32 object-cover rounded-xl border border-gray-200 mb-2">
                        @elseif ($doc['existing'])
                            <img src="{{ asset('storage/' . $doc['existing']) }}"
                                class="w-full h-32 object-cover rounded-xl border border-gray-200 mb-2 opacity-60">
                            <p class="text-xs text-gray-400 mb-1">Current — pick a file to replace</p>
                        @endif
                        <input wire:model="{{ $doc['prop'] }}" type="file" accept="image/*"
                            class="input text-xs @error($doc['prop']) input-error @enderror">
                        <p wire:loading wire:target="{{ $doc['prop'] }}" class="text-xs text-gray-400 mt-1">Uploading…
                        </p>
                        @error($doc['prop'])
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2 pt-2 border-t border-gray-100">
                <button wire:click="saveSellerInfo" type="button" class="btn-primary btn-sm"
                    wire:loading.attr="disabled" wire:target="saveSellerInfo">
                    <span wire:loading.remove wire:target="saveSellerInfo">Save Changes</span>
                    <span wire:loading wire:target="saveSellerInfo">Saving…</span>
                </button>
                <button wire:click="cancelEditingSeller" type="button" class="btn-secondary btn-sm">Cancel</button>
            </div>
        @else
            <div class="grid sm:grid-cols-3 gap-5">
                @php
                    $sellerDocs = [
                        ['label' => 'Seller Photo', 'path' => $acquisition->seller_photo_path],
                        ['label' => 'NID Front', 'path' => $acquisition->seller_nid_front_path],
                        ['label' => 'NID Back', 'path' => $acquisition->seller_nid_back_path],
                    ];
                @endphp
                @foreach ($sellerDocs as $doc)
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-gray-500">{{ $doc['label'] }}</p>
                        @if ($doc['path'])
                            <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" class="block">
                                <img src="{{ asset('storage/' . $doc['path']) }}"
                                    class="w-full h-40 object-cover rounded-xl border border-gray-200 hover:opacity-80 transition-opacity">
                            </a>
                        @else
                            <div
                                class="w-full h-40 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center text-gray-300 text-xs">
                                Not uploaded
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Catalog Link --}}
    @if ($acquisition->variant)
        <div class="card p-4 bg-indigo-50 border-indigo-200">
            <h3 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-2">Linked to Catalog</h3>
            <div class="text-sm font-medium text-indigo-900">
                {{ $acquisition->variant?->product?->name }}
                @if ($acquisition->variant?->attributes_label)
                    — {{ $acquisition->variant->attributes_label }}
                @endif
            </div>
            <div class="text-xs text-indigo-600 mt-0.5">SKU: {{ $acquisition->variant?->sku }}</div>
            @if ($acquisition->productUnit)
                <div class="text-xs text-indigo-600 mt-0.5">
                    Unit Status:
                    <strong>{{ ucfirst(str_replace('_', ' ', $acquisition->productUnit->status->value)) }}</strong>
                </div>
            @endif
        </div>
    @endif

    {{-- Sale Info (if sold) --}}
    @if ($saleRecord)
        <div class="card p-5 bg-green-50 border-green-200">
            <h3 class="font-semibold text-green-900 text-sm border-b border-green-200 pb-2 mb-3">Sale Information</h3>
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <div class="flex gap-3">
                    <span class="text-green-600 w-24 shrink-0">Invoice</span>
                    <a href="{{ route('sales.show', $saleRecord->sale) }}" wire:navigate
                        class="font-mono font-bold text-indigo-600 hover:underline">
                        {{ $saleRecord->sale->sale_number }}
                    </a>
                </div>
                <div class="flex gap-3">
                    <span class="text-green-600 w-24 shrink-0">Sale Date</span>
                    <span
                        class="font-medium text-gray-900">{{ $saleRecord->sale->confirmed_at?->format('d M Y') }}</span>
                </div>
                <div class="flex gap-3">
                    <span class="text-green-600 w-24 shrink-0">Customer</span>
                    <span class="font-medium text-gray-900">
                        {{ $saleRecord->sale->customer?->name ?? 'Walk-in' }}
                    </span>
                </div>
                <div class="flex gap-3">
                    <span class="text-green-600 w-24 shrink-0">Sold For</span>
                    <span class="font-bold text-green-800">৳{{ number_format($saleRecord->line_total, 2) }}</span>
                </div>
                <div class="flex gap-3">
                    <span class="text-green-600 w-24 shrink-0">Cashier</span>
                    <span class="text-gray-700">{{ $saleRecord->sale->cashier?->name }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="card p-4 bg-blue-50 border-blue-200">
            <p class="text-sm text-blue-800">
                📱 This phone is currently
                <strong>{{ ucfirst(str_replace('_', ' ', $acquisition->productUnit?->status?->value ?? 'in stock')) }}</strong>
                at <strong>{{ $acquisition->branch?->name }}</strong>.
                @if ($acquisition->productUnit)
                    <a href="{{ route('pos') }}" wire:navigate class="text-indigo-600 hover:underline ml-1">
                        Sell it via POS →
                    </a>
                @endif
            </p>
        </div>
    @endif

    {{-- Meta --}}
    <div class="text-xs text-gray-400 text-center pb-4">
        Recorded by {{ $acquisition->createdBy?->name }} · {{ $acquisition->created_at->format('d M Y H:i') }}
    </div>
</div>
