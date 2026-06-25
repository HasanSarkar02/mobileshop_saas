<div class="max-w-3xl mx-auto space-y-5">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Buy Used Phone</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Records purchase from an individual seller. Phone enters inventory immediately.
            </p>
        </div>
        <form wire:submit="save" class="p-6 space-y-6">

            {{-- Seller Info --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Seller Information
                </legend>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Seller Name *</label>
                        <input wire:model="sellerName" type="text"
                            class="input @error('sellerName') input-error @enderror" placeholder="Full name">
                        @error('sellerName')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Seller Phone</label>
                        <input wire:model="sellerPhone" type="tel" class="input" placeholder="01XXXXXXXXX">
                    </div>
                    <div>
                        <label class="label">NID Number</label>
                        <input wire:model="sellerNid" type="text" class="input"
                            placeholder="National ID (recommended)">
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <input wire:model="sellerAddress" type="text" class="input">
                    </div>
                </div>
            </fieldset>

            {{-- Phone Details --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Phone Details
                </legend>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">IMEI 1 *</label>
                        <input wire:model="imei1" type="text" inputmode="numeric" maxlength="15"
                            class="input font-mono @error('imei1') input-error @enderror"
                            placeholder="14–15 digit IMEI">
                        @error('imei1')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">IMEI 2 (Dual SIM)</label>
                        <input wire:model="imei2" type="text" inputmode="numeric" maxlength="15"
                            class="input font-mono" placeholder="Optional">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Model / Description *</label>
                        <input wire:model="modelDescription" type="text"
                            class="input @error('modelDescription') input-error @enderror"
                            placeholder="e.g. Samsung Galaxy A52 128GB Blue">
                        @error('modelDescription')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Link to catalog product (optional) --}}
                    <div class="sm:col-span-2">
                        <label class="label">
                            Link to Product Catalog
                            <span class="text-xs font-normal text-gray-400 ml-1">
                                — optional but recommended for proper inventory tracking
                            </span>
                        </label>
                        @if ($selectedVariantLabel)
                            <div class="flex items-center gap-2 p-2.5 border border-indigo-200 bg-indigo-50 rounded-xl">
                                <span
                                    class="text-sm text-indigo-800 font-medium flex-1">{{ $selectedVariantLabel }}</span>
                                <button wire:click="clearVariant" type="button"
                                    class="text-indigo-400 hover:text-red-500 text-xs">
                                    × Clear
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <input wire:model.live.debounce.300ms="variantSearch" type="search"
                                    placeholder="Search existing product…" class="input text-sm" autocomplete="off">
                                <div wire:show="showVariantDrop"
                                    class="absolute top-full left-0 right-0 z-20 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                    @foreach ($variantResults as $r)
                                        <button type="button"
                                            wire:click="selectVariant({{ $r['id'] }}, '{{ addslashes($r['label']) }}', {{ $r['price'] }})"
                                            class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-indigo-50 border-b border-gray-50 last:border-0 text-left text-sm">
                                            <span>{{ $r['label'] }}</span>
                                            <span class="text-xs text-gray-400">{{ $r['sku'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="label">Condition *</label>
                        <select wire:model="condition" class="input">
                            @foreach (\App\Enums\PhoneCondition::cases() as $cond)
                                <option value="{{ $cond->value }}">{{ $cond->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Accessories Included</label>
                        <input wire:model="accessories" type="text" class="input"
                            placeholder="e.g. Charger, Box, Earphones">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Condition Notes</label>
                        <textarea wire:model="conditionNotes" rows="2" class="input"
                            placeholder="Details about scratches, defects, battery health…"></textarea>
                    </div>
                </div>
            </fieldset>

            {{-- Financial --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Payment to Seller
                </legend>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Purchase Price (৳) *</label>
                        <input wire:model="purchasePrice" type="number" step="0.01" min="1"
                            class="input font-semibold @error('purchasePrice') input-error @enderror" placeholder="0">
                        @error('purchasePrice')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Expected Selling Price (৳)</label>
                        <input wire:model="expectedSellPrice" type="number" step="0.01" min="0"
                            class="input" placeholder="0">
                    </div>
                    <div>
                        <label class="label">Pay From *</label>
                        <select wire:model="paymentAccountId"
                            class="input @error('paymentAccountId') input-error @enderror">
                            <option value="0">Select account…</option>
                            @foreach ($this->paymentAccounts as $pa)
                                <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                            @endforeach
                        </select>
                        @error('paymentAccountId')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Branch (Receive To) *</label>
                        <select wire:model="branchId" class="input @error('branchId') input-error @enderror">
                            <option value="0">Select branch…</option>
                            @foreach ($this->branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Notes</label>
                        <input wire:model="notes" type="text" class="input">
                    </div>
                </div>

                @if ($purchasePrice && $expectedSellPrice && (float) $expectedSellPrice > 0)
                    <div
                        class="{{ (float) $expectedSellPrice - (float) $purchasePrice > 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-xl p-3 text-sm">
                        Expected Profit:
                        <strong
                            class="{{ (float) $expectedSellPrice - (float) $purchasePrice > 0 ? 'text-green-700' : 'text-red-700' }}">
                            ৳{{ number_format((float) $expectedSellPrice - (float) $purchasePrice, 2) }}
                        </strong>
                    </div>
                @endif
            </fieldset>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Buy Phone & Add to Inventory</span>
                    <span wire:loading>Processing…</span>
                </button>
                <a href="{{ route('used-phones.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
