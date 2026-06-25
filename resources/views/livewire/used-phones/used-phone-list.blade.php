<div class="space-y-4">
    {{-- P&L Metrics --}}
    @php $m = $this->metrics; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach ([['label' => 'Total Bought', 'value' => $m['total_count'] . ' phones', 'color' => 'bg-gray-50 text-gray-700'], ['label' => 'Total Spent', 'value' => '৳' . number_format($m['total_spent'], 0), 'color' => 'bg-red-50 text-red-700'], ['label' => 'Total Sold', 'value' => $m['sold_count'] . ' phones', 'color' => 'bg-indigo-50 text-indigo-700'], ['label' => 'Revenue', 'value' => '৳' . number_format($m['total_revenue'], 0), 'color' => 'bg-green-50 text-green-700'], ['label' => 'Net Profit', 'value' => ($m['net_profit'] >= 0 ? '+' : '') . '৳' . number_format($m['net_profit'], 0), 'color' => $m['net_profit'] >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'], ['label' => 'Inventory Value', 'value' => '৳' . number_format($m['inventory_value'], 0), 'color' => 'bg-blue-50 text-blue-700']] as $card)
            <div class="card p-3 border-0 {{ $card['color'] }}">
                <div class="text-sm font-bold">{{ $card['value'] }}</div>
                <div class="text-xs opacity-70 mt-0.5">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="IMEI, model, seller…"
            class="input max-w-xs">
        <div class="flex gap-1">
            @foreach (['' => 'All', 'in_stock' => 'In Stock', 'sold' => 'Sold'] as $key => $label)
                <button wire:click="$set('soldFilter', '{{ $key }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $soldFilter === $key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <a href="{{ route('used-phones.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
            + Buy Used Phone
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Ref</th>
                        <th class="table-th">Phone</th>
                        <th class="table-th">IMEI</th>
                        <th class="table-th">Seller</th>
                        <th class="table-th">Condition</th>
                        <th class="table-th text-right">Paid</th>
                        <th class="table-th text-right">Sell</th>
                        <th class="table-th text-right">P&L</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($acquisitions as $a)
                        @php
                            $isSold = $a->productUnit?->status?->value === 'sold';
                            $condColors = [
                                'excellent' => 'badge-green',
                                'good' => 'badge-blue',
                                'fair' => 'badge-yellow',
                                'poor' => 'badge-red',
                                'for_parts' => 'badge-gray',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer">
                            <td class="table-td font-mono text-xs font-semibold text-indigo-600">
                                {{ $a->acquisition_number }}
                            </td>
                            <td class="table-td">
                                <a href="{{ route('used-phones.show', $a) }}" wire:navigate
                                    class="font-medium text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                    {{ $a->model_description }}
                                </a>

                                @if ($a->variant?->product && $a->variant->product->name !== 'Used Phones (Unlinked)')
                                    <div class="text-xs text-indigo-400">
                                        {{ $a->variant->sku }}
                                    </div>
                                @else
                                    <div class="text-xs text-amber-500">
                                        ⚠ Unlinked (auto-created)
                                    </div>
                                @endif
                            </td>
                            <td class="table-td font-mono text-xs">{{ $a->imei_1 }}</td>
                            <td class="table-td">
                                <div class="font-medium text-sm">{{ $a->seller_name }}</div>
                                <div class="text-xs text-gray-400">{{ $a->seller_phone }}</div>
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $condColors[$a->condition->value] ?? 'badge-gray' }}">
                                    {{ $a->condition->label() }}
                                </span>
                            </td>
                            <td class="table-td text-right font-bold text-red-600">
                                ৳{{ number_format($a->purchase_price, 2) }}
                            </td>
                            <td class="table-td text-right font-semibold">
                                @if ($a->expected_sell_price > 0)
                                    <span class="{{ $isSold ? 'text-green-600' : 'text-gray-500' }}">
                                        ৳{{ number_format($a->expected_sell_price, 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="table-td text-right">
                                @if ($a->expected_sell_price > 0)
                                    @php $pl = $a->expected_sell_price - $a->purchase_price; @endphp
                                    <span class="font-bold {{ $pl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $pl >= 0 ? '+' : '' }}৳{{ number_format($pl, 0) }}
                                    </span>
                                    @if (!$isSold)
                                        <span class="text-xs text-gray-400">(est.)</span>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="table-td">
                                @if ($isSold)
                                    <span class="badge badge-green">Sold</span>
                                @elseif($a->productUnit?->status?->value === 'in_stock')
                                    <span class="badge badge-blue">In Stock</span>
                                @else
                                    <span class="badge badge-gray">
                                        {{ ucfirst(str_replace('_', ' ', $a->productUnit?->status?->value ?? 'unknown')) }}
                                    </span>
                                @endif
                            </td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $a->created_at->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="table-td text-center text-gray-400 py-12">
                                No used phone purchases yet.
                                <a href="{{ route('used-phones.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Buy one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($acquisitions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $acquisitions->links() }}</div>
        @endif
    </div>
</div>
