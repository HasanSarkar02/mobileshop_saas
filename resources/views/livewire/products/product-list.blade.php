<div class="space-y-4">
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search products…" class="input max-w-xs">
        <select wire:model.live="trackingType" class="input w-auto">
            <option value="">All types</option>
            <option value="serialized">Serialized (IMEI)</option>
            <option value="non_serialized">Non-serialized</option>
        </select>
        <select wire:model.live="categoryId" class="input w-auto">
            <option value="0">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="brandId" class="input w-auto">
            <option value="0">All brands</option>
            @foreach ($brands as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="lowStockOnly" class="rounded border-gray-300 text-indigo-600">
            Low stock only
        </label>
        @if ($lowStockOnly)
            <div class="flex items-center gap-2 text-sm bg-orange-50 text-orange-700 px-3 py-2 rounded-lg">
                Showing low-stock products only
                <button wire:click="$set('lowStockOnly', false)" class="underline">Clear</button>
            </div>
        @endif

        <a href="{{ route('products.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
            + Add Product
        </a>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Product</th>
                        <th class="table-th">Brand / Category</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Variants</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <a href="{{ route('products.show', $product) }}" wire:navigate
                                    class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="table-td">
                                <div class="text-gray-700">{{ $product->brand?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $product->category?->name }}</div>
                            </td>
                            <td class="table-td">
                                @if ($product->tracking_type === \App\Enums\ProductTrackingType::Serialized)
                                    <span class="badge badge-blue">IMEI / Serial</span>
                                @else
                                    <span class="badge badge-gray">Non-serialized</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($product->variants as $v)
                                        <span
                                            class="badge {{ $v->current_stock == 0 ? 'badge-red' : ($v->is_low_stock ? 'badge-yellow' : 'badge-green') }}">
                                            {{ $v->attributes_label ?? 'Standard' }}: {{ $v->current_stock }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="table-td">
                                <span class="{{ $product->is_active ? 'badge-green' : 'badge-gray' }} badge">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('products.edit', $product) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">Edit</a>
                                    <button wire:click="toggleActive({{ $product->id }})"
                                        class="text-xs {{ $product->is_active ? 'text-red-500' : 'text-green-600' }} hover:underline font-medium">
                                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-12">
                                No products yet.
                                <a href="{{ route('products.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Add your first product</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $products->links() }}</div>
        @endif
    </div>
</div>
