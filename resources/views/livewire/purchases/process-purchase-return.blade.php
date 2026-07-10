<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Process Purchase Return</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Original Purchase:
                <span class="font-mono font-semibold text-indigo-600">{{ $purchase->reference_number }}</span>
                · Supplier: <strong>{{ $purchase->supplier?->name }}</strong>
            </p>
        </div>
        <a href="{{ route('purchases.show', $purchase) }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    {{-- Items --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Select Items to Return</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach ($returnItems as $idx => $item)
                <div class="p-4 space-y-3 {{ $item['selected'] ? 'bg-blue-50' : '' }}"
                    wire:key="ri-{{ $idx }}">
                    <div class="flex items-start gap-3">
                        <input wire:model.live="returnItems.{{ $idx }}.selected" type="checkbox"
                            class="mt-1 rounded border-gray-300 text-indigo-600">
                        <div class="flex-1">
                            <div class="font-semibold text-sm text-gray-900">{{ $item['product_name'] }}</div>
                            <div class="text-xs text-gray-400">SKU: {{ $item['sku'] }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs text-gray-400">Unit Cost</div>
                            <div class="font-bold text-gray-900">৳{{ number_format($item['unit_cost'], 2) }}</div>
                        </div>
                    </div>

                    @if ($item['selected'])
                        <div class="grid sm:grid-cols-3 gap-3 ml-7">
                            {{-- Quantity --}}
                            <div>
                                <label class="text-xs text-gray-600 font-semibold mb-1 block">
                                    Quantity *
                                </label>
                                <input wire:model.lazy="returnItems.{{ $idx }}.quantity" type="number"
                                    min="1" max="{{ $item['original_qty'] }}" class="input text-sm">
                                <p class="text-xs text-gray-400 mt-0.5">Max: {{ $item['original_qty'] }}</p>
                            </div>

                            {{-- Condition --}}
                            <div>
                                <label class="text-xs text-gray-600 font-semibold mb-1 block">Condition</label>
                                <select wire:model="returnItems.{{ $idx }}.condition" class="input text-sm">
                                    <option value="good">Good / Resalable</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="defective">Defective</option>
                                </select>
                            </div>

                            {{-- Serialized unit picker --}}
                            @if (!empty($item['available_units']))
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold mb-1 block">
                                        Select Unit (IMEI)
                                    </label>
                                    <select wire:model="returnItems.{{ $idx }}.product_unit_id"
                                        class="input text-sm">
                                        <option value="">Select unit…</option>
                                        @foreach ($item['available_units'] as $unitId => $serial)
                                            <option value="{{ $unitId }}">{{ $serial }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Line total preview --}}
                            <div class="flex items-end">
                                <div>
                                    <div class="text-xs text-gray-400">Return Value</div>
                                    <div class="font-bold text-red-600 text-sm">
                                        ৳{{ number_format($item['unit_cost'] * $item['quantity'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Total --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <span class="text-sm text-gray-600 font-medium">Total Return Amount</span>
            <span class="text-2xl font-bold text-red-700">
                ৳{{ number_format($this->totalReturnAmount, 2) }}
            </span>
        </div>
    </div>

    {{-- Return Settings --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-2">Return Settings</h3>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Return Date *</label>
                <input wire:model="returnDate" type="date" class="input @error('returnDate') input-error @enderror">
                @error('returnDate')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label">Settlement Type *</label>
                <select wire:model.live="settlementType" class="input">
                    <option value="credit_note">Credit Note (reduces what we owe supplier)</option>
                    <option value="cash_refund">Cash Refund (supplier pays us back)</option>
                </select>
            </div>

            @if ($settlementType === 'cash_refund')
                <div>
                    <label class="label">Refund Into Account *</label>
                    <select wire:model="refundAccountId" class="input">
                        <option value="0">Select account…</option>
                        @foreach ($this->paymentAccounts as $pa)
                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                        @endforeach
                    </select>
                    @error('refundAccountId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="sm:col-span-2">
                <label class="label">Return Reason *</label>
                <input wire:model="returnReason" type="text"
                    class="input @error('returnReason') input-error @enderror"
                    placeholder="e.g. Defective batch, wrong model delivered…">
                @error('returnReason')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="label">Notes</label>
                <textarea wire:model="notes" rows="2" class="input" placeholder="Internal notes…"></textarea>
            </div>
        </div>

        {{-- Accounting Info --}}
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-sm text-indigo-800">
            @if ($settlementType === 'credit_note')
                💡 <strong>Credit Note:</strong>
                Dr Accounts Payable (2000) / Cr Purchase Returns (5010) + Cr Inventory (1200)
                — reduces what we owe the supplier.
            @else
                💡 <strong>Cash Refund:</strong>
                Dr Cash/Bank / Cr Purchase Returns (5010) + Cr Inventory (1200)
                — supplier physically returns money to us.
            @endif
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex gap-3 pb-8">
        <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-primary">
            <span wire:loading.remove wire:target="save">
                Process Return (৳{{ number_format($this->totalReturnAmount, 2) }})
            </span>
            <span wire:loading wire:target="save" class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                        class="opacity-25" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                </svg>
                Processing…
            </span>
        </button>
        <a href="{{ route('purchases.show', $purchase) }}" wire:navigate class="btn-secondary">Cancel</a>
    </div>
</div>
