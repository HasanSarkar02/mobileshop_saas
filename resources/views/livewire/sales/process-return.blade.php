<div class="max-w-4xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Process Return / Refund</h2>
            <p class="text-gray-500 text-sm mt-0.5">
                Original Sale:
                <span class="font-mono font-semibold text-indigo-600">{{ $sale->sale_number }}</span>
                · {{ $sale->confirmed_at?->format('d M Y') }}
            </p>
        </div>
        <a href="{{ route('sales.show', $sale) }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    {{-- Original Payment Breakdown --}}
    <div class="card p-4">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Original Payment</h3>
        <div class="flex flex-wrap gap-3">
            @foreach ($this->paymentSummary as $p)
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                    <span class="text-xs text-gray-500">{{ $p['method'] }}</span>
                    <span class="font-semibold text-sm text-gray-900">৳{{ number_format($p['amount'], 2) }}</span>
                    @if ($p['type'] === 'finance_partner')
                        <span class="badge badge-blue text-xs">EMI</span>
                    @elseif($p['type'] === 'customer_credit')
                        <span class="badge badge-yellow text-xs">Baki</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Select Items + Refund Amounts --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-900 text-sm">Select Items to Return</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Set the refund amount for each item — can be less than original for damaged goods.
                </p>
            </div>
            <button wire:click="selectAll" class="btn-secondary btn-sm">Select All</button>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach ($returnItems as $idx => $item)
                <div class="p-4 space-y-4 {{ $item['selected'] ? 'bg-blue-50' : '' }}"
                    wire:key="ri-{{ $idx }}">

                    {{-- Item header --}}
                    <div class="flex items-start gap-3">
                        <input wire:model.live="returnItems.{{ $idx }}.selected" type="checkbox"
                            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <div class="font-semibold text-sm text-gray-900">{{ $item['product_name'] }}</div>
                            @if ($item['variant_label'])
                                <div class="text-xs text-gray-500">{{ $item['variant_label'] }}</div>
                            @endif
                            @if ($item['serial_number'])
                                <div class="text-xs font-mono text-indigo-500">{{ $item['serial_number'] }}</div>
                            @endif
                            @if ($item['returned_quantity'] > 0)
                                <div class="text-xs text-amber-600 font-medium mt-0.5">
                                    ⚠ {{ $item['returned_quantity'] }} of {{ $item['original_qty'] }} already returned
                                    previously
                                    — {{ $item['available_qty'] }} available now
                                </div>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs text-gray-400">Available to Return</div>
                            <div class="font-bold text-gray-900">৳{{ number_format($item['max_refund'], 2) }}</div>
                        </div>
                    </div>

                    {{-- Return details (when selected) --}}
                    @if ($item['selected'])
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 ml-7">

                            {{-- Refund Amount --}}
                            <div>
                                <label class="text-xs text-gray-600 font-semibold mb-1 block">
                                    Refund Amount (৳) *
                                </label>
                                <input wire:model.lazy="returnItems.{{ $idx }}.refund_amount" type="number"
                                    step="0.01" min="0" max="{{ $item['max_refund'] }}"
                                    class="input text-sm font-semibold">
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Max: ৳{{ number_format($item['max_refund'], 2) }}
                                    @if ((float) $item['refund_amount'] < (float) $item['max_refund'] && (float) $item['max_refund'] > 0)
                                        · <span class="text-amber-600 font-medium">
                                            Partial
                                            ({{ round(((float) $item['refund_amount'] / (float) $item['max_refund']) * 100) }}%)
                                        </span>
                                    @endif
                                </p>
                                @error("returnItems.{$idx}.refund_amount")
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Quantity (non-serialized only) --}}
                            @if (!$item['serial_number'] && $item['available_qty'] > 1)
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold mb-1 block">Qty to Return</label>
                                    <input wire:model.live="returnItems.{{ $idx }}.quantity" type="number"
                                        min="1" max="{{ $item['available_qty'] }}" class="input text-sm">
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Max: {{ $item['available_qty'] }}
                                    </p>
                                </div>
                            @endif

                            {{-- Condition --}}
                            <div>
                                <label class="text-xs text-gray-600 font-semibold mb-1 block">Condition</label>
                                <select wire:model.live="returnItems.{{ $idx }}.condition"
                                    class="input text-sm">
                                    @foreach (\App\Enums\ReturnCondition::cases() as $cond)
                                        <option value="{{ $cond->value }}">{{ $cond->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Restock --}}
                            <div>
                                <label
                                    class="flex items-center gap-2 text-xs text-gray-600 font-semibold cursor-pointer mt-1">
                                    <input wire:model.live="returnItems.{{ $idx }}.restock" type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600">
                                    Return to stock
                                </label>
                                @if (in_array($item['condition'], ['defective', 'for_parts']))
                                    <p class="text-xs text-red-500 mt-1">
                                        ⚠ Defective condition — review before restocking
                                    </p>
                                @endif
                            </div>

                            {{-- Restock Branch --}}
                            @if ($item['restock'])
                                <div>
                                    <label class="text-xs text-gray-600 font-semibold mb-1 block">Restock Branch</label>
                                    <select wire:model="returnItems.{{ $idx }}.restock_branch_id"
                                        class="input text-sm">
                                        @foreach ($this->branches as $b)
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Condition Notes --}}
                            <div class="sm:col-span-{{ $item['restock'] ? '1' : '2' }} lg:col-span-1">
                                <label class="text-xs text-gray-600 font-semibold mb-1 block">Condition Notes</label>
                                <input wire:model="returnItems.{{ $idx }}.condition_notes" type="text"
                                    class="input text-sm" placeholder="Details about the defect…">
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Total --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <span class="text-sm text-gray-600 font-medium">Total Refund Amount</span>
            <span class="text-2xl font-bold text-indigo-700">
                ৳{{ number_format($this->totalRefundAmount, 2) }}
            </span>
        </div>
    </div>

    {{-- Return Details --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-2">Return Settings</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Return Reason *</label>
                <input wire:model="returnReason" type="text"
                    class="input @error('returnReason') input-error @enderror"
                    placeholder="e.g. Defective screen, changed mind…">
                @error('returnReason')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Refund Method *</label>
                <select wire:model.live="refundMethod" class="input">
                    @foreach (\App\Enums\RefundMethod::cases() as $m)
                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>

            @if ($refundMethod === 'original_payment')
                <div class="sm:col-span-2">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-sm text-indigo-800">
                        💡 Refund will be distributed proportionally across the original payment methods.
                        Finance partner receivables will be reduced proportionally — not fully cancelled.
                    </div>
                </div>
            @elseif($refundMethod === 'store_credit')
                <div class="sm:col-span-2">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                        💳 Refund amount will be added to the customer's store credit (reduces their due balance).
                    </div>
                </div>
            @endif

            <div class="sm:col-span-2">
                <label class="label">Internal Notes</label>
                <textarea wire:model="notes" rows="2" class="input" placeholder="Internal notes about this return…"></textarea>
            </div>
        </div>
    </div>

    {{-- Confirm --}}
    <div class="flex gap-3 pb-8">
        <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-primary">
            <span wire:loading.remove wire:target="save">
                Process Return (৳{{ number_format($this->totalRefundAmount, 2) }})
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
        <a href="{{ route('sales.show', $sale) }}" wire:navigate class="btn-secondary">Cancel</a>
    </div>
</div>
