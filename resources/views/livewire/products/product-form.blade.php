<div class="max-w-3xl mx-auto space-y-5">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">
                {{ $product?->exists ? 'Edit Product' : 'New Product' }}
            </h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Product = the item. Variants = each color/storage/spec with its own price.
            </p>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">

            {{-- ── Product Details ── --}}
            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-1">
                    Product Details
                </legend>

                <div>
                    <label class="label">Product Name *</label>
                    <input wire:model.live.debounce.400ms="name" type="text" placeholder="e.g. Samsung Galaxy M12"
                        class="input @error('name') input-error @enderror">
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    {{-- Brand --}}
                    <div>
                        <label class="label">Brand</label>
                        <div class="flex gap-2">
                            <select wire:model="brandId" class="input flex-1">
                                <option value="0">Select brand…</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">
                                        {{ $brand->name }}{{ $brand->shop_id ? ' (custom)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="toggleBrandForm" class="btn-secondary btn-sm shrink-0"
                                title="Add brand">
                                +
                            </button>
                        </div>
                        @if ($showBrandForm)
                            <div class="mt-2 flex gap-2">
                                <input wire:model="newBrandName" type="text" placeholder="New brand name"
                                    class="input flex-1 text-sm">
                                <button type="button" wire:click="quickAddBrand"
                                    class="btn-success btn-sm">Add</button>
                            </div>
                            @error('newBrandName')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="label">Category</label>
                        <div class="flex gap-2">
                            <select wire:model="categoryId" class="input flex-1">
                                <option value="0">Select category…</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->parent ? $cat->parent->name . ' › ' : '' }}{{ $cat->name }}
                                        {{ $cat->shop_id ? ' (custom)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="toggleCategoryForm" class="btn-secondary btn-sm shrink-0"
                                title="Add category">
                                +
                            </button>
                        </div>
                        @if ($showCategoryForm)
                            <div class="mt-2 flex gap-2">
                                <input wire:model="newCategoryName" type="text" placeholder="New category name"
                                    class="input flex-1 text-sm">
                                <button type="button" wire:click="quickAddCategory"
                                    class="btn-success btn-sm">Add</button>
                            </div>
                            @error('newCategoryName')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                {{-- Tracking Type — CRITICAL decision --}}
                <div>
                    <label class="label">Stock Tracking Type *</label>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label
                            class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors
                            {{ $trackingType === 'serialized' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input wire:model.live="trackingType" type="radio" value="serialized"
                                class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">Serialized (IMEI)</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Track each unit individually by IMEI / Serial number.
                                    Use for mobile phones, tablets, laptops.
                                </div>
                            </div>
                        </label>
                        <label
                            class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors
                            {{ $trackingType === 'non_serialized' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input wire:model.live="trackingType" type="radio" value="non_serialized"
                                class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">Non-serialized (Qty)</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Track by quantity only. Use for accessories,
                                    cases, cables, chargers, SIM cards.
                                </div>
                            </div>
                        </label>
                    </div>
                    @if ($product?->exists)
                        <p class="mt-2 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            ⚠️ Changing tracking type after stock has been received may cause data inconsistency.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="label">Description (optional)</label>
                    <textarea wire:model="description" rows="2" class="input" placeholder="Internal notes about this product…"></textarea>
                </div>
            </fieldset>

            <hr class="border-gray-100">

            {{-- ── Variants ── --}}
            <fieldset class="space-y-3">
                <div class="flex items-center justify-between pb-1">
                    <div>
                        <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            Variants
                        </legend>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Each variant = one SKU with its own price (e.g. "Black 64GB", "Blue 128GB").
                            Single variant products: leave label blank, just set SKU + price.
                        </p>
                    </div>
                    <button type="button" wire:click="addVariant" class="btn-secondary btn-sm shrink-0">
                        + Add Variant
                    </button>
                </div>

                @error('variants')
                    <p class="error">{{ $message }}</p>
                @enderror

                @foreach ($variants as $idx => $variant)
                    @if (!$variant['_destroy'])
                        <div class="border border-gray-200 rounded-xl p-4 space-y-3"
                            wire:key="variant-{{ $idx }}">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500">
                                    Variant {{ $idx + 1 }}
                                    @if (!empty($variant['attributes_label']))
                                        — {{ $variant['attributes_label'] }}
                                    @endif
                                </span>
                                <button type="button" wire:click="removeVariant({{ $idx }})"
                                    class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="label text-xs">Variant Label</label>
                                    <input wire:model.live="variants.{{ $idx }}.attributes_label"
                                        type="text" placeholder="e.g. Blue 128GB"
                                        class="input text-sm @error('variants.' . $idx . '.attributes_label') input-error @enderror">
                                    <p class="text-xs text-gray-400 mt-0.5">Blank = no variation (single variant)</p>
                                </div>
                                <div>
                                    <label class="label text-xs">SKU *</label>
                                    <input wire:model="variants.{{ $idx }}.sku" type="text"
                                        placeholder="SAM-M12-01"
                                        class="input text-sm uppercase @error('variants.' . $idx . '.sku') input-error @enderror">
                                    @error('variants.' . $idx . '.sku')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Selling Price (৳) *</label>
                                    <input wire:model="variants.{{ $idx }}.selling_price" type="number"
                                        step="0.01" min="0" placeholder="15000"
                                        class="input text-sm @error('variants.' . $idx . '.selling_price') input-error @enderror">
                                    @error('variants.' . $idx . '.selling_price')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @if (!empty($variant['id']))
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                        <input wire:model="variants.{{ $idx }}.is_active" type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600">
                                        Active (uncheck to hide from POS)
                                    </label>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </fieldset>

            {{-- ── Submit ── --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $product?->exists ? 'Update Product' : 'Create Product' }}
                    </span>
                    <span wire:loading>Saving…</span>
                </button>
                <a href="{{ route('products.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
