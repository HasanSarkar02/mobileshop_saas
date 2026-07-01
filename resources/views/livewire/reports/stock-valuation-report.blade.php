<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Stock Valuation Report</h2>
        <span class="text-xs text-gray-400">As of {{ $periodLabel }}</span>
    </div>

    {{-- Filter (branch only — stock is always current, not date-ranged) --}}
    <div class="card p-4">
        <div class="flex items-center gap-3">
            @if ($this->branches->count() > 1)
                <div>
                    <label class="label text-xs">Branch</label>
                    <select wire:model.live="branchId" class="input text-sm w-40">
                        <option value="0">All Branches</option>
                        @foreach ($this->branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="ml-auto">
                <button onclick="window.print()" class="btn-secondary btn-sm">🖨 Print</button>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    @php $sm = $this->summary; @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-2xl font-bold text-indigo-700">{{ number_format($sm->serialized_units) }}</div>
            <div class="text-xs font-medium text-indigo-500 mt-0.5">Serialized Units (In Stock)</div>
            <div class="text-sm text-indigo-600 mt-1 font-semibold">৳{{ number_format($sm->serialized_value, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-blue-50">
            <div class="text-2xl font-bold text-blue-700">{{ number_format($sm->non_serialized_qty) }}</div>
            <div class="text-xs font-medium text-blue-500 mt-0.5">Non-Serialized Units</div>
            <div class="text-sm text-blue-600 mt-1 font-semibold">৳{{ number_format($sm->non_serialized_value, 0) }}
            </div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-2xl font-bold text-green-700">৳{{ number_format($sm->total_value, 0) }}</div>
            <div class="text-xs font-medium text-green-500 mt-0.5">Total Inventory Value</div>
        </div>
    </div>

    {{-- Stock Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Product</th>
                        <th class="table-th">Brand / Category</th>
                        <th class="table-th">SKU</th>
                        <th class="table-th">Type</th>
                        <th class="table-th text-right">Qty</th>
                        <th class="table-th text-right">Avg Cost</th>
                        <th class="table-th text-right">Cost Value</th>
                        <th class="table-th text-right">Retail Value</th>
                        <th class="table-th text-right">Potential Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->valuationData as $row)
                        @php $potential = $row->retail_value - $row->total_cost_value; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium text-sm text-gray-900">{{ $row->product_name }}</td>
                            <td class="table-td text-xs">
                                <div class="text-gray-700">{{ $row->brand }}</div>
                                <div class="text-gray-400">{{ $row->category }}</div>
                            </td>
                            <td class="table-td font-mono text-xs text-gray-500">{{ $row->sku }}</td>
                            <td class="table-td">
                                @if ($row->tracking_type === 'serialized')
                                    <span class="badge badge-blue text-xs">IMEI</span>
                                @else
                                    <span class="badge badge-gray text-xs">Qty</span>
                                @endif
                            </td>
                            <td class="table-td text-right font-semibold">{{ number_format($row->qty) }}</td>
                            <td class="table-td text-right text-gray-500">৳{{ number_format($row->avg_cost, 2) }}</td>
                            <td class="table-td text-right font-semibold text-red-600">
                                ৳{{ number_format($row->total_cost_value, 0) }}</td>
                            <td class="table-td text-right font-semibold text-blue-600">
                                ৳{{ number_format($row->retail_value, 0) }}</td>
                            <td
                                class="table-td text-right {{ $potential >= 0 ? 'text-green-600' : 'text-red-500' }} font-bold">
                                {{ $potential >= 0 ? '+' : '' }}৳{{ number_format($potential, 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-td text-center text-gray-400 py-10">No stock.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->valuationData->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td colspan="6" class="table-td font-bold">Total</td>
                            <td class="table-td text-right font-bold text-red-700">
                                ৳{{ number_format($this->valuationData->sum('total_cost_value'), 0) }}
                            </td>
                            <td class="table-td text-right font-bold text-blue-700">
                                ৳{{ number_format($this->valuationData->sum('retail_value'), 0) }}
                            </td>
                            <td class="table-td text-right font-bold text-green-700">
                                +৳{{ number_format($this->valuationData->sum('retail_value') - $this->valuationData->sum('total_cost_value'), 0) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
