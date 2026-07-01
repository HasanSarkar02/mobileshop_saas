<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Sales Analysis</h2>
    </div>

    {{-- Filter --}}
    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$branches" />

    @php $s = $this->summary; @endphp

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([['label' => 'Total Orders', 'value' => number_format($s->orderCount), 'prefix' => '', 'color' => 'indigo', 'sub' => 'orders'], ['label' => 'Net Revenue', 'value' => number_format($s->netRevenue, 0), 'prefix' => '৳', 'color' => 'green', 'sub' => number_format($s->avgOrderValue, 0) . '৳ avg'], ['label' => 'Gross Profit', 'value' => number_format($s->grossProfit, 0), 'prefix' => '৳', 'color' => 'emerald', 'sub' => $s->profitMarginPct . '% margin'], ['label' => 'Returns', 'value' => number_format($s->totalReturns, 0), 'prefix' => '৳', 'color' => 'red', 'sub' => $s->returnCount . ' credit notes']] as $kpi)
            <div class="card p-5 bg-{{ $kpi['color'] }}-50 border-0">
                <div class="text-xs font-semibold text-{{ $kpi['color'] }}-500 uppercase tracking-wider mb-1">
                    {{ $kpi['label'] }}
                </div>
                <div class="text-2xl font-bold text-{{ $kpi['color'] }}-800">
                    {{ $kpi['prefix'] }}{{ $kpi['value'] }}
                </div>
                <div class="text-xs text-{{ $kpi['color'] }}-500 mt-1">{{ $kpi['sub'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- View Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @foreach ([['key' => 'overview', 'label' => 'Overview'], ['key' => 'products', 'label' => 'By Product'], ['key' => 'customers', 'label' => 'By Customer'], ['key' => 'employees', 'label' => 'By Employee'], ['key' => 'payment', 'label' => 'By Payment Method']] as $tab)
                <button wire:click="$set('activeView', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeView === $tab['key']
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Overview Tab --}}
        <div wire:show="activeView === 'overview'" class="overflow-x-auto">
            @if ($this->dailyTrend->isNotEmpty())
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Date</th>
                            <th class="table-th text-right">Orders</th>
                            <th class="table-th text-right">Revenue</th>
                            <th class="table-th text-right">Profit</th>
                            <th class="table-th text-right">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($this->dailyTrend as $day)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($day->sale_date)->format('d M Y') }}
                                </td>
                                <td class="table-td text-right text-gray-600">{{ $day->orders }}</td>
                                <td class="table-td text-right font-semibold">৳{{ number_format($day->revenue, 0) }}
                                </td>
                                <td
                                    class="table-td text-right {{ $day->profit >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                    {{ $day->profit >= 0 ? '+' : '' }}৳{{ number_format($day->profit, 0) }}
                                </td>
                                <td class="table-td text-right text-gray-500">
                                    {{ $day->revenue > 0 ? round(($day->profit / $day->revenue) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td class="table-td font-bold">Total</td>
                            <td class="table-td text-right font-bold">{{ number_format($s->orderCount) }}</td>
                            <td class="table-td text-right font-bold text-indigo-700">
                                ৳{{ number_format($s->netRevenue, 0) }}</td>
                            <td
                                class="table-td text-right font-bold {{ $s->grossProfit >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ $s->grossProfit >= 0 ? '+' : '' }}৳{{ number_format($s->grossProfit, 0) }}
                            </td>
                            <td class="table-td text-right font-bold text-gray-700">{{ $s->profitMarginPct }}%</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <div class="p-10 text-center text-gray-400">No sales data for this period.</div>
            @endif
        </div>

        {{-- By Product Tab --}}
        <div wire:show="activeView === 'products'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Product</th>
                        <th class="table-th">SKU</th>
                        <th class="table-th text-right">Qty Sold</th>
                        <th class="table-th text-right">Revenue</th>
                        <th class="table-th text-right">Profit</th>
                        <th class="table-th text-right">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->topProducts as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $row->product_name }}</div>
                                <div class="text-xs text-gray-400">{{ $row->brand_name }}</div>
                            </td>
                            <td class="table-td font-mono text-xs text-gray-500">{{ $row->sku }}</td>
                            <td class="table-td text-right font-semibold">{{ number_format($row->qty_sold) }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($row->revenue, 0) }}</td>
                            <td
                                class="table-td text-right {{ $row->profit >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                {{ $row->profit >= 0 ? '+' : '' }}৳{{ number_format($row->profit, 0) }}
                            </td>
                            <td class="table-td text-right text-gray-500">
                                {{ $row->revenue > 0 ? round(($row->profit / $row->revenue) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-8">No product sales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- By Customer Tab --}}
        <div wire:show="activeView === 'customers'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Customer</th>
                        <th class="table-th text-right">Orders</th>
                        <th class="table-th text-right">Revenue</th>
                        <th class="table-th text-right">Profit</th>
                        <th class="table-th text-right">Outstanding Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->topCustomers as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $row->name }}</div>
                                <div class="text-xs text-gray-400">{{ $row->phone }}</div>
                            </td>
                            <td class="table-td text-right text-gray-600">{{ $row->order_count }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($row->revenue, 0) }}</td>
                            <td
                                class="table-td text-right {{ $row->profit >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                {{ $row->profit >= 0 ? '+' : '' }}৳{{ number_format($row->profit, 0) }}
                            </td>
                            <td
                                class="table-td text-right {{ $row->outstanding_due > 0 ? 'text-red-600 font-bold' : 'text-gray-300' }}">
                                @if ($row->outstanding_due > 0)
                                    ৳{{ number_format($row->outstanding_due, 0) }}
                                @else
                                    Clear
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-8">No customer data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- By Employee Tab --}}
        <div wire:show="activeView === 'employees'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Employee</th>
                        <th class="table-th text-right">Orders</th>
                        <th class="table-th text-right">Revenue</th>
                        <th class="table-th text-right">Profit</th>
                        <th class="table-th text-right">Avg Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->byEmployee as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium text-gray-900">{{ $row->name }}</td>
                            <td class="table-td text-right text-gray-600">{{ $row->order_count }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($row->revenue, 0) }}</td>
                            <td
                                class="table-td text-right {{ $row->profit >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                {{ $row->profit >= 0 ? '+' : '' }}৳{{ number_format($row->profit, 0) }}
                            </td>
                            <td class="table-td text-right text-gray-500">
                                ৳{{ $row->order_count > 0 ? number_format($row->revenue / $row->order_count, 0) : 0 }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-8">No employee data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- By Payment Method Tab --}}
        <div wire:show="activeView === 'payment'" class="p-5">
            @php $totalPayment = $this->byPaymentMethod->sum('total_amount'); @endphp
            <div class="space-y-3">
                @forelse($this->byPaymentMethod as $row)
                    @php $pct = $totalPayment > 0 ? round($row->total_amount / $totalPayment * 100, 1) : 0; @endphp
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span
                                class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $row->provider) }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-gray-500">{{ $row->sale_count }} sales</span>
                                <span
                                    class="font-bold text-gray-900">৳{{ number_format($row->total_amount, 0) }}</span>
                                <span class="text-xs text-gray-400 w-10 text-right">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-400 py-8">No payment data.</p>
                @endforelse
            </div>
            @if ($this->byPaymentMethod->isNotEmpty())
                <div class="border-t border-gray-200 mt-4 pt-4 flex justify-between font-bold">
                    <span>Total</span>
                    <span class="text-indigo-700">৳{{ number_format($totalPayment, 0) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
