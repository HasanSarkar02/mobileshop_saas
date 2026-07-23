<div>
    @if ($show)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">

                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">
                        @php
                            $title = match ($adjustmentType) {
                                'damaged' => 'Mark Damaged',
                                'written_off' => 'Write Off Stock',
                                'reserved' => 'Reserve Stock',
                                'unreserved' => 'Release Reserve',
                                default => 'Stock Adjustment',
                            };
                        @endphp
                        {{ $title }}
                    </h3>
                    <button wire:click="$set('show', false)" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
                </div>
                {{-- Branch selector — show if multi-branch OR if branchId not yet resolved --}}
                @if ($trackingType === 'non_serialized' && ($this->branches->count() > 1 || !$branchId))
                    <div>
                        <label class="label text-xs">Branch *</label>
                        <select wire:model.live="branchId" class="input text-sm">
                            <option value="0">Select branch…</option>
                            @foreach ($this->branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="text-sm font-semibold text-gray-700">{{ $productName }}</div>

                {{-- Adjustment Type Tabs --}}
                @if ($trackingType === 'non_serialized')
                    <div class="flex gap-1 flex-wrap">
                        @foreach ([
        'damaged' => '⚠ Damaged',
        'written_off' => '✗ Write Off',
        'reserved' => '🔒 Reserve',
        'unreserved' => '🔓 Release',
    ] as $type => $label)
                            <button wire:click="$set('adjustmentType', '{{ $type }}')"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                                {{ $adjustmentType === $type ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Stock Summary --}}
                @if ($trackingType === 'non_serialized')
                    <div class="bg-gray-50 rounded-xl p-3 grid grid-cols-3 gap-3 text-center text-sm">
                        <div>
                            <div class="text-xs text-gray-400 mb-0.5">Total Stock</div>
                            <div class="font-bold text-gray-900">{{ $currentStock }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 mb-0.5">Reserved</div>
                            <div class="font-bold text-blue-600">{{ $reservedStock }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 mb-0.5">Available</div>
                            <div class="font-bold text-green-600">{{ $this->availableQty }}</div>
                        </div>
                    </div>

                    @if ($damagedStock > 0)
                        <div class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">
                            ⚠ {{ $damagedStock }} units marked as damaged
                            @if ($adjustmentType === 'written_off')
                                <br>
                                <label class="flex items-center gap-2 mt-1 cursor-pointer">
                                    <input wire:model="alreadyDamaged" type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600">
                                    <span>Writing off from damaged stock (no additional journal)</span>
                                </label>
                            @endif
                        </div>
                    @endif
                @endif

                {{-- Branch selector --}}
                @if ($trackingType === 'non_serialized' && $this->branches->count() > 1)
                    <div>
                        <label class="label text-xs">Branch</label>
                        <select wire:model.live="branchId" class="input text-sm">
                            <option value="0">Select branch…</option>
                            @foreach ($this->branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Quantity --}}
                @if ($trackingType === 'non_serialized')
                    <div>
                        <label class="label text-xs">
                            Quantity *
                            @if (in_array($adjustmentType, ['damaged', 'written_off']))
                                <span class="text-gray-400 font-normal">
                                    (Max:
                                    {{ $adjustmentType === 'written_off' && $alreadyDamaged ? $damagedStock : $this->availableQty }})
                                </span>
                            @endif
                        </label>
                        <input wire:model="quantity" type="number" step="0.01" min="0.01"
                            class="input @error('quantity') input-error @enderror">
                        @error('quantity')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- GL Info --}}
                @if (in_array($adjustmentType, ['damaged', 'written_off']))
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-800">
                        📒 Journal: Dr Inventory Shrinkage (6040) / Cr Inventory (1200)
                        @if ($trackingType === 'non_serialized')
                            = ৳{{ number_format((float) ($quantity ?: 0) * ($this->currentStock ?? 0), 2) }}
                        @endif
                    </div>
                @endif

                @if ($adjustmentType === 'reserved')
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs">Held For (Customer Name) *</label>
                            <input wire:model="heldForName" type="text"
                                class="input @error('heldForName') input-error @enderror">
                            @error('heldForName')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label text-xs">Phone (optional)</label>
                            <input wire:model="heldForPhone" type="text" class="input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label text-xs">Hold Until (optional)</label>
                            <input wire:model="holdExpiresAt" type="date" class="input">
                            <p class="text-xs text-gray-400 mt-0.5">Leave blank for no automatic expiry — you'll need to
                                release it manually.</p>
                        </div>
                    </div>
                @endif

                {{-- Reason --}}
                <div>
                    <label class="label text-xs">Reason *</label>
                    <input wire:model="reason" type="text" class="input @error('reason') input-error @enderror"
                        placeholder="e.g. Screen cracked during storage...">
                    @error('reason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button wire:click="save" class="btn-primary flex-1" wire:loading.attr="disabled">
                        <span wire:loading.remove>Confirm</span>
                        <span wire:loading>Processing…</span>
                    </button>
                    <button wire:click="$set('show', false)" class="btn-secondary">Cancel</button>
                </div>

            </div>
        </div>
    @endif
</div>
