<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Opening Stock Entry</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Enter existing physical inventory into the system.
                    Each entry posts:
                    <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                        Dr Inventory (1200) / Cr Opening Balance Equity (3020)
                    </span>
                </p>
            </div>
        </div>
        <div class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
            ⚠ <strong>One-time operation.</strong>
            Use this only for stock that existed before this system was set up.
            All future stock must come through <strong>Purchases</strong>.
        </div>
    </div>

    {{-- Step 1 — Select Branch + Product --}}
    <div class="card p-5 space-y-4">
        <h3 class="font-semibold text-gray-900 text-sm">Step 1 — Select Branch & Product</h3>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Branch *</label>
                <select wire:model="branchId" class="input @error('branchId') input-error @enderror">
                    <option value="0">Select branch…</option>
                    @foreach ($this->branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('branchId')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <label class="label">Product / Variant *</label>
                <input wire:model.live.debounce.300ms="productSearch" wire:input="searchProduct" @focus="open = true"
                    type="text" placeholder="Search by name or SKU…"
                    class="input @error('variantId') input-error @enderror" autocomplete="off">
                @error('variantId')
                    <p class="error">{{ $message }}</p>
                @enderror

                @if (!empty($searchResults))
                    <div class="absolute z-30 mt-1 w-full bg-white border border-gray-200
                                rounded-xl shadow-lg overflow-hidden"
                        x-show="open">
                        @foreach ($searchResults as $r)
                            <button type="button"
                                wire:click="selectVariant({{ $r['variant_id'] }}, '{{ addslashes($r['label']) }}', '{{ $r['tracking_type'] }}')"
                                @click="open = false"
                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50
                                       flex items-center justify-between border-b border-gray-50">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $r['label'] }}</span>
                                    <span class="text-xs text-gray-400 ml-2">{{ $r['sku'] }}</span>
                                </div>
                                @if ($r['tracking_type'] === 'serialized')
                                    <span class="badge badge-blue shrink-0 text-xs">IMEI</span>
                                @else
                                    <span class="badge badge-gray shrink-0 text-xs">Qty</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Step 2 — Enter Stock --}}
    @if ($showForm && $variantId)
        <div class="card p-5 space-y-4 border-2 border-indigo-200">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">
                    Step 2 — Enter Stock for
                    <span class="text-indigo-600">{{ $productName }}</span>
                </h3>
                <span class="badge {{ $trackingType === 'serialized' ? 'badge-blue' : 'badge-gray' }}">
                    {{ $trackingType === 'serialized' ? 'IMEI / Serialized' : 'Non-Serialized' }}
                </span>
            </div>

            @if ($trackingType === 'non_serialized')
                {{-- Non-serialized --}}
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Current Quantity *</label>
                        <input wire:model="quantity" type="number" step="0.01" min="0.01"
                            class="input @error('quantity') input-error @enderror" placeholder="e.g. 50">
                        @error('quantity')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Average Cost Price (৳) *</label>
                        <input wire:model="unitCost" type="number" step="0.01" min="0"
                            class="input @error('unitCost') input-error @enderror" placeholder="e.g. 250">
                        @error('unitCost')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-0.5">
                            What you paid per unit on average.
                        </p>
                    </div>
                </div>

                @if ($quantity && $unitCost)
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-indigo-700">Inventory Value to be recorded:</span>
                            <span class="font-bold text-indigo-800">
                                ৳{{ number_format((float) $quantity * (float) $unitCost, 2) }}
                            </span>
                        </div>
                        <div class="text-xs text-indigo-500 mt-1">
                            Dr Inventory Asset (1200) {{ number_format((float) $quantity * (float) $unitCost, 2) }} /
                            Cr Opening Balance Equity (3020)
                            {{ number_format((float) $quantity * (float) $unitCost, 2) }}
                        </div>
                    </div>
                @endif
            @else
                {{-- Serialized — IMEI rows --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="label mb-0">IMEI / Serial Numbers</label>
                        <button wire:click="addImeiRow" type="button" class="btn-secondary btn-sm">
                            + Add Row
                        </button>
                    </div>

                    {{-- Header --}}
                    <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-gray-500 px-1">
                        <div class="col-span-4">IMEI 1 *</div>
                        <div class="col-span-3">IMEI 2 (optional)</div>
                        <div class="col-span-2">Cost (৳)</div>
                        <div class="col-span-2">Warranty (mo)</div>
                        <div class="col-span-1"></div>
                    </div>

                    @foreach ($imeiRows as $idx => $row)
                        <div class="grid grid-cols-12 gap-2 items-center" wire:key="imei-{{ $idx }}">
                            <div class="col-span-4">
                                <input wire:model="imeiRows.{{ $idx }}.serial_number" type="text"
                                    class="input text-sm font-mono" placeholder="IMEI 1">
                            </div>
                            <div class="col-span-3">
                                <input wire:model="imeiRows.{{ $idx }}.secondary_serial_number" type="text"
                                    class="input text-sm font-mono" placeholder="IMEI 2">
                            </div>
                            <div class="col-span-2">
                                <input wire:model="imeiRows.{{ $idx }}.cost_price" type="number"
                                    step="0.01" min="0" class="input text-sm" placeholder="0">
                            </div>
                            <div class="col-span-2">
                                <input wire:model="imeiRows.{{ $idx }}.warranty_months" type="number"
                                    min="0" class="input text-sm" placeholder="12">
                            </div>
                            <div class="col-span-1 text-center">
                                @if (count($imeiRows) > 1)
                                    <button wire:click="removeImeiRow({{ $idx }})"
                                        class="text-red-400 hover:text-red-600">✕</button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="text-xs text-gray-400">
                        {{ collect($imeiRows)->filter(fn($r) => !empty(trim($r['serial_number'])))->count() }}
                        IMEI(s) entered |
                        Total Value:
                        ৳{{ number_format(collect($imeiRows)->sum(fn($r) => (float) ($r['cost_price'] ?? 0)), 2) }}
                    </div>
                </div>
            @endif

            <div class="flex gap-3 pt-2 border-t border-gray-100">
                <button wire:click="save" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">✓ Record Opening Stock</span>
                    <span wire:loading wire:target="save">Processing…</span>
                </button>
                <button wire:click="$set('showForm', false)" class="btn-secondary">Cancel</button>
            </div>
        </div>
    @endif

    {{-- Recent Entries --}}
    @if (!empty($recentEntries))
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Recent Opening Stock Entries</h3>
                <a href="{{ route('inventory.adjustments') }}?type=opening_stock" wire:navigate
                    class="text-xs text-indigo-600 hover:underline">View all →</a>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Product</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th text-right">Qty</th>
                        <th class="table-th text-right">Total Cost</th>
                        <th class="table-th">Date</th>
                        <th class="table-th">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recentEntries as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td text-sm text-gray-900">
                                {{ $entry['variant']['product']['name'] ?? '—' }}
                                <div class="text-xs text-gray-400">{{ $entry['variant']['sku'] ?? '' }}</div>
                            </td>
                            <td class="table-td text-sm text-gray-500">{{ $entry['branch']['name'] ?? '—' }}</td>
                            <td class="table-td text-right font-semibold">{{ $entry['quantity'] }}</td>
                            <td class="table-td text-right text-indigo-700 font-semibold">
                                {{ $entry['total_cost'] > 0 ? '৳' . number_format($entry['total_cost'], 2) : '—' }}
                            </td>
                            <td class="table-td text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($entry['created_at'])->format('d M Y H:i') }}
                            </td>
                            <td class="table-td text-xs text-gray-400">{{ $entry['created_by']['name'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
