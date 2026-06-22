<div class="max-w-5xl mx-auto space-y-5">
    {{-- Header --}}
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
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.edit', $product) }}" wire:navigate class="btn-secondary btn-sm">Edit</a>
            <a href="{{ route('purchases.create') }}" wire:navigate class="btn-primary btn-sm">+ Purchase Stock</a>
        </div>
    </div>

    {{-- Variant Selector --}}
    @if ($product->variants->count() > 1)
        <div class="card p-4">
            <label class="label text-xs">Select Variant</label>
            <div class="flex flex-wrap gap-2 mt-1">
                @foreach ($product->variants as $v)
                    <button type="button" wire:click="$set('selectedVariantId', {{ $v->id }})"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors
                            {{ $selectedVariantId === $v->id
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400' }}">
                        {{ $v->attributes_label ?? 'Standard' }}
                        <span class="ml-1 opacity-70">
                            ({{ $v->in_stock_count ?? '—' }} in stock)
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($variant)
        {{-- Variant Summary --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $summaryItems = [
                    ['label' => 'In Stock', 'key' => 'in_stock', 'color' => 'bg-green-50 text-green-700'],
                    ['label' => 'Sold', 'key' => 'sold', 'color' => 'bg-gray-50 text-gray-700'],
                    ['label' => 'Reserved', 'key' => 'reserved', 'color' => 'bg-amber-50 text-amber-700'],
                    ['label' => 'Damaged', 'key' => 'damaged', 'color' => 'bg-red-50 text-red-700'],
                ];
            @endphp
            @foreach ($summaryItems as $item)
                <div class="card p-4 {{ $item['color'] }} border-0">
                    <div class="text-2xl font-bold">{{ $statusCounts[$item['key']] ?? 0 }}</div>
                    <div class="text-xs font-medium mt-0.5 opacity-80">{{ $item['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Pricing --}}
        <div class="card p-4 flex items-center gap-6">
            <div>
                <div class="text-xs text-gray-400 mb-0.5">Selling Price</div>
                <div class="text-lg font-bold text-indigo-700">৳{{ number_format($variant->selling_price, 2) }}</div>
            </div>
            <div class="h-8 border-l border-gray-200"></div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">SKU</div>
                <div class="font-mono text-sm font-semibold text-gray-800">{{ $variant->sku }}</div>
            </div>
        </div>

        {{-- Serialized: IMEI Unit List --}}
        @if ($product->tracking_type->value === 'serialized' && $units !== null)
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-3">
                    <h3 class="font-semibold text-gray-900 text-sm">Unit / IMEI Ledger</h3>
                    <div class="flex items-center gap-2 sm:ml-auto flex-wrap">
                        <input wire:model.live.debounce.300ms="imeiSearch" type="search" placeholder="Search IMEI…"
                            class="input text-sm w-44">
                        <select wire:model.live="unitStatus" class="input text-sm w-auto">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\UnitStatus::cases() as $status)
                                <option value="{{ $status->value }}">
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
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
                                <th class="table-th">Branch</th>
                                <th class="table-th">Cost</th>
                                <th class="table-th">Status</th>
                                <th class="table-th">Received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($units as $unit)
                                <tr class="hover:bg-gray-50">
                                    <td class="table-td font-mono font-semibold text-gray-800">
                                        {{ $unit->serial_number }}
                                    </td>
                                    <td class="table-td font-mono text-gray-400 text-xs">
                                        {{ $unit->secondary_serial_number ?? '—' }}
                                    </td>
                                    <td class="table-td text-gray-500">{{ $unit->branch?->name }}</td>
                                    <td class="table-td">৳{{ number_format($unit->cost_price, 2) }}</td>
                                    <td class="table-td">
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
                                        @endphp
                                        <span class="badge {{ $statusColors[$unit->status->value] ?? 'badge-gray' }}">
                                            {{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}
                                        </span>
                                    </td>
                                    <td class="table-td text-gray-400 text-xs">
                                        {{ $unit->created_at->format('d M Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="table-td text-center text-gray-400 py-8">No units found.
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

            {{-- Non-serialized: Branch Stock --}}
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
                                </td>
                                <td class="table-td text-gray-500">৳{{ number_format($stock->average_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="table-td text-center text-gray-400 py-8">No stock received
                                    yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
