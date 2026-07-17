<div class="max-w-4xl mx-auto space-y-4">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">New Purchase / Stock Receive</h2>
            <p class="text-xs text-gray-400 mt-0.5">All inventory changes and accounting entries are created
                automatically.</p>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            {{-- Header Fields --}}
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="label">Supplier *</label>
                    <select wire:model="supplierId" class="input @error('supplierId') input-error @enderror">
                        <option value="0">Select supplier…</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplierId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Branch *</label>
                    <select wire:model="branchId" class="input @error('branchId') input-error @enderror">
                        <option value="0">Select branch…</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branchId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Purchase Date *</label>
                    <input wire:model="purchaseDate" type="date"
                        class="input @error('purchaseDate') input-error @enderror">
                    @error('purchaseDate')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Line Items --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Items Received</h3>
                    <button type="button" wire:click="addLine" class="btn-secondary btn-sm">+ Add Item</button>
                </div>

                @foreach ($lines as $idx => $line)
                    <div class="border border-gray-200 rounded-xl p-4 space-y-4 relative"
                        wire:key="line-{{ $idx }}">

                        {{-- Remove Line --}}
                        @if (count($lines) > 1)
                            <button type="button" wire:click="removeLine({{ $idx }})"
                                class="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif

                        {{-- Product Search --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <label class="label">Product / Variant *</label>
                            <input type="text" wire:model="searches.{{ $idx }}"
                                wire:input="searchProduct({{ $idx }}, $event.target.value)"
                                @focus="open = true" placeholder="Type to search products…"
                                class="input @error('lines.' . $idx . '.product_variant_id') input-error @enderror"
                                autocomplete="off">
                            @error('lines.' . $idx . '.product_variant_id')
                                <p class="error">{{ $message }}</p>
                            @enderror

                            {{-- Search Dropdown --}}
                            @if (!empty($searchResults[$idx]))
                                <div class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
                                    x-show="open">
                                    @foreach ($searchResults[$idx] as $result)
                                        <button type="button"
                                            wire:click="selectVariant({{ $idx }}, {{ $result['id'] }}, '{{ addslashes($result['label']) }}', '{{ $result['tracking_type'] }}')"
                                            @click="open = false"
                                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center justify-between gap-2 border-b border-gray-50 last:border-0">
                                            <div>
                                                <span class="font-medium text-gray-900">{{ $result['label'] }}</span>
                                                <span class="text-xs text-gray-400 ml-1">{{ $result['sku'] }}</span>
                                            </div>
                                            @if ($result['tracking_type'] === 'serialized')
                                                <span class="badge badge-blue shrink-0">IMEI</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Cost & Quantity --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="label">Unit Cost (৳) *</label>
                                <input wire:model.live="lines.{{ $idx }}.unit_cost" type="number"
                                    step="0.01" min="0.01"
                                    class="input @error('lines.' . $idx . '.unit_cost') input-error @enderror"
                                    placeholder="0.00">
                                @error('lines.' . $idx . '.unit_cost')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="label">Qty *</label>
                                <input wire:model.live="lines.{{ $idx }}.quantity" type="number"
                                    min="1" class="input" placeholder="1">
                            </div>
                            <div>
                                <label class="label">Mfr. Warranty (months)</label>
                                <input wire:model="lines.{{ $idx }}.manufacturer_warranty_months"
                                    type="number" min="0" class="input" placeholder="12">
                            </div>
                            <div>
                                <label class="label">Shop Warranty (days)</label>
                                <input wire:model="lines.{{ $idx }}.shop_warranty_days" type="number"
                                    min="0" class="input" placeholder="7">
                            </div>
                        </div>

                        {{-- Line Total --}}
                        <div class="text-right text-sm font-semibold text-gray-700">
                            Line Total:
                            ৳{{ number_format(((float) ($line['unit_cost'] ?? 0)) * ((int) ($line['quantity'] ?? 1)), 2) }}
                        </div>

                        {{-- IMEI Entry Section (serialized products only) --}}
                        @if ($line['tracking_type'] === 'serialized')
                            <div class="border-t border-dashed border-indigo-200 pt-4 space-y-3">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-blue">IMEI / Serial Numbers Required</span>
                                    <span class="text-xs text-gray-400">Enter {{ $line['quantity'] }} IMEI(s)</span>
                                </div>
                                @error('lines.' . $idx . '.serial_numbers')
                                    <p class="error">{{ $message }}</p>
                                @enderror

                                @foreach ($line['serial_numbers'] as $si => $serial)
                                    <div class="grid sm:grid-cols-2 gap-2 items-start"
                                        wire:key="serial-{{ $idx }}-{{ $si }}">
                                        <div>
                                            <label class="text-xs text-gray-500 mb-0.5 block">IMEI 1
                                                #{{ $si + 1 }} *</label>
                                            <input
                                                wire:model="lines.{{ $idx }}.serial_numbers.{{ $si }}.serial_number"
                                                wire:change="validateImei({{ $idx }}, {{ $si }})"
                                                type="text" inputmode="numeric" maxlength="15"
                                                placeholder="14–15 digit IMEI"
                                                class="input text-mono @error('lines.' . $idx . '.serial_numbers.' . $si . '.serial_number') input-error @enderror">
                                            @error('lines.' . $idx . '.serial_numbers.' . $si . '.serial_number')
                                                <p class="error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500 mb-0.5 block">IMEI 2 (Dual SIM —
                                                optional)</label>
                                            <input
                                                wire:model="lines.{{ $idx }}.serial_numbers.{{ $si }}.secondary_serial_number"
                                                type="text" inputmode="numeric" maxlength="15"
                                                placeholder="Second IMEI (if any)" class="input text-mono">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Grand Total + Submit --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2 border-t border-gray-100">
                <div class="text-lg font-bold text-gray-900">
                    Grand Total: <span class="text-indigo-700">৳{{ number_format($totalAmount, 2) }}</span>
                </div>
                <div class="flex gap-3 sm:ml-auto">
                    <a href="{{ route('purchases.index') }}" wire:navigate class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Receive Stock & Save</span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    class="opacity-25" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                            </svg>
                            Processing…
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
