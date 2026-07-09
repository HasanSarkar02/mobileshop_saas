@php
    $s = $this->summary;
    $sales = $s->sales;
    $cash = $s->cash;
    $inv = $s->inventory;

    // Helper: % change arrow + color
    $change = fn(?float $pct) => match (true) {
        is_null($pct) => '',
        $pct > 0 => "<span class='text-green-600 text-xs font-semibold'>↑ {$pct}%</span>",
        $pct < 0 => "<span class='text-red-500 text-xs font-semibold'>↓ " . abs($pct) . '%</span>',
        default => "<span class='text-gray-400 text-xs'>0%</span>",
    };
@endphp

<div class="space-y-5">

    {{-- ── Period + Branch Filter ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div>
            <h2 class="text-lg font-bold text-gray-900">
                Executive Dashboard
            </h2>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ auth()->user()->shop?->name }}
                @if ($this->branchId)
                    · {{ $this->branches->firstWhere('id', $this->branchId)?->name }}
                @endif
                · {{ $this->selectedPeriodLabel }}
            </p>
        </div>
        <div class="flex items-center gap-2 sm:ml-auto flex-wrap">
            {{-- Period selector --}}
            <div class="flex flex-wrap gap-1">
                @foreach (\App\Reporting\Enums\ReportPeriod::cases() as $p)
                    @if (!in_array($p, [\App\Reporting\Enums\ReportPeriod::Custom]))
                        <button wire:click="$set('period', '{{ $p->value }}')"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors
                                {{ $period === $p->value
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300' }}">
                            {{ $p->label() }}
                        </button>
                    @endif
                @endforeach
            </div>
            {{-- Branch filter --}}
            @if ($this->branches->count() > 1)
                <select wire:model.live="branchId" class="input w-auto text-sm">
                    <option value="0">All Branches</option>
                    @foreach ($this->branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    {{-- ── Pending Action Alerts ───────────────────────────────────────────── --}}
    @php
        $alerts = array_filter([
            $s->pendingExpenseApprovals > 0
                ? [
                    'label' => "{$s->pendingExpenseApprovals} expense(s) pending approval",
                    'route' => 'expenses.index',
                    'color' => 'amber',
                ]
                : null,
            $s->serviceAmountDue > 0
                ? [
                    'label' => 'Service due: ৳' . number_format($s->serviceAmountDue, 0),
                    'route' => 'service.index',
                    'color' => 'red',
                ]
                : null,
            $s->payrollAmountDue > 0
                ? [
                    'label' => 'Payroll approved — ৳' . number_format($s->payrollAmountDue, 0) . ' ready to pay',
                    'route' => 'payroll.index',
                    'color' => 'blue',
                ]
                : null,
            $inv->lowStockSkus > 0
                ? [
                    'label' => "{$inv->lowStockSkus} low-stock item(s)",
                    'route' => 'products.index',
                    'color' => 'orange',
                ]
                : null,
        ]);
    @endphp
    @if (!empty($alerts))
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($alerts as $alert)
                <a href="{{ route($alert['route']) }}" wire:navigate
                    class="flex items-center gap-3 p-3 rounded-xl border-l-4 bg-{{ $alert['color'] }}-50 border-{{ $alert['color'] }}-400 hover:bg-{{ $alert['color'] }}-100 transition-colors">
                    <div class="flex-1 text-sm font-medium text-{{ $alert['color'] }}-800">
                        {{ $alert['label'] }}
                    </div>
                    <span class="text-{{ $alert['color'] }}-500 text-xs">→</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ── Sales KPI Row ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $kpis = [
                [
                    'label' => 'Orders',
                    'value' => number_format($sales->orderCount),
                    'change' => $sales->orderChangePercent(),
                    'prefix' => '',
                    'color' => 'indigo',
                ],
                [
                    'label' => 'Net Revenue',
                    'value' => number_format($sales->netRevenue, 0),
                    'change' => $sales->revenueChangePercent(),
                    'prefix' => '৳',
                    'color' => 'green',
                ],
                [
                    'label' => 'Gross Profit',
                    'value' => number_format($sales->grossProfit, 0),
                    'change' => $sales->profitChangePercent(),
                    'prefix' => '৳',
                    'color' => 'emerald',
                ],
                [
                    'label' => 'Profit Margin',
                    'value' => $sales->profitMarginPct . '%',
                    'change' => null,
                    'prefix' => '',
                    'color' => 'teal',
                ],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="card p-5 bg-{{ $kpi['color'] }}-50 border-0">
                <div class="text-xs font-semibold text-{{ $kpi['color'] }}-500 uppercase tracking-wider mb-1">
                    {{ $kpi['label'] }}
                </div>
                <div class="text-2xl font-bold text-{{ $kpi['color'] }}-800">
                    {{ $kpi['prefix'] }}{{ $kpi['value'] }}
                </div>
                @if ($kpi['change'] !== null)
                    <div class="mt-1">
                        {!! $change($kpi['change']) !!}
                        <span class="text-xs text-gray-400 ml-1">vs prev period</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ── Cash Position ───────────────────────────────────────────────────── --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Cash by provider --}}
        <div class="card p-5">
            <h3 class="font-semibold text-gray-900 text-sm mb-4">Cash & Bank Position</h3>
            @php
                $providerColors = [
                    'cash' => 'bg-green-100 text-green-700',
                    'bank' => 'bg-blue-100 text-blue-700',
                    'bkash' => 'bg-pink-100 text-pink-700',
                    'nagad' => 'bg-orange-100 text-orange-700',
                    'rocket' => 'bg-purple-100 text-purple-700',
                    'upay' => 'bg-yellow-100 text-yellow-700',
                ];
            @endphp
            <div class="space-y-2.5">
                @forelse($cash->byAccount as $name => $balance)
                    @php
                        // Determine color by provider prefix
                        $prov =
                            collect($cash->byProvider)->keys()->first(fn($p) => str_contains(strtolower($name), $p)) ??
                            'other';
                        $colorClass = $providerColors[$prov] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                class="w-2 h-2 rounded-full {{ str_contains($colorClass, 'green') ? 'bg-green-500' : (str_contains($colorClass, 'blue') ? 'bg-blue-500' : 'bg-gray-400') }}"></span>
                            <span class="text-sm text-gray-700 truncate max-w-[140px]">{{ $name }}</span>
                        </div>
                        <span class="font-semibold text-sm {{ $balance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                            ৳{{ number_format($balance, 0) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payment accounts configured.</p>
                @endforelse
            </div>
            <div class="border-t border-gray-100 mt-4 pt-3 flex justify-between items-center">
                <span class="text-xs font-semibold text-gray-500">Total Cash & Bank</span>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-indigo-700">৳{{ number_format($cash->totalBalance, 0) }}</span>
                    <a href="{{ route('treasury.index') }}" wire:navigate
                        class="text-xs text-indigo-600 hover:underline">
                        Manage →
                    </a>
                </div>
            </div>
        </div>

        {{-- Receivables / Payables --}}
        <div class="card p-5">
            <h3 class="font-semibold text-gray-900 text-sm mb-4">Receivables & Payables</h3>
            <div class="space-y-3">
                @foreach ([['label' => 'Customer Dues', 'value' => $cash->customerReceivables, 'color' => 'text-amber-600', 'route' => 'customers.index'], ['label' => 'Finance Partner Dues', 'value' => $cash->fpReceivables, 'color' => 'text-blue-600', 'route' => 'finance-partners.index'], ['label' => 'Supplier Payables', 'value' => $cash->supplierPayables, 'color' => 'text-red-600', 'route' => 'purchases.index']] as $row)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                        <a href="{{ route($row['route']) }}" wire:navigate
                            class="text-sm text-gray-600 hover:text-indigo-600 transition-colors">
                            {{ $row['label'] }}
                        </a>
                        <span class="font-bold {{ $row['color'] }}">
                            ৳{{ number_format($row['value'], 0) }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-gray-100 mt-3 pt-3 flex justify-between">
                <span class="text-xs font-semibold text-gray-500">Net Working Position</span>
                <span class="font-bold {{ $cash->netPosition() >= 0 ? 'text-green-700' : 'text-red-600' }}">
                    ৳{{ number_format($cash->netPosition(), 0) }}
                </span>
            </div>
        </div>

        {{-- Inventory summary --}}
        <div class="card p-5">
            <h3 class="font-semibold text-gray-900 text-sm mb-4">Inventory Position</h3>
            <div class="space-y-3">
                @foreach ([['label' => 'Serialized Units', 'value' => number_format($inv->totalSerializedUnits) . ' units'], ['label' => 'Serialized Value', 'value' => '৳' . number_format($inv->serializedValue, 0)], ['label' => 'Non-serialized Value', 'value' => '৳' . number_format($inv->nonSerializedValue, 0)]] as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ $row['label'] }}</span>
                        <span class="font-semibold text-gray-900">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-gray-100 mt-3 pt-3 flex justify-between">
                <span class="text-xs font-semibold text-gray-500">Total Inventory Value</span>
                <span class="font-bold text-indigo-700">৳{{ number_format($inv->totalInventoryValue, 0) }}</span>
            </div>
            @if ($inv->lowStockSkus > 0)
                <div class="mt-2 text-xs text-amber-600 font-medium">
                    ⚠ {{ $inv->lowStockSkus }} item(s) running low
                </div>
            @endif
        </div>
    </div>

    {{-- ── Main Two-Column Row ──────────────────────────────────────────────── --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Top Products --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Top Products</h3>
                <span class="text-xs text-gray-400">{{ $this->selectedPeriodLabel }}</span>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Product</th>
                        <th class="table-th text-right">Qty</th>
                        <th class="table-th text-right">Revenue</th>
                        <th class="table-th text-right">Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($s->topProducts as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $p->product_name }}</div>
                                <div class="text-xs text-gray-400">{{ $p->brand_name }} · {{ $p->sku }}</div>
                            </td>
                            <td class="table-td text-right text-gray-600">{{ $p->qty_sold }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($p->revenue, 0) }}</td>
                            <td
                                class="table-td text-right {{ $p->profit >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                {{ $p->profit >= 0 ? '+' : '' }}৳{{ number_format($p->profit, 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-td text-center text-gray-400 py-6 text-sm">
                                No sales this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Top Customers --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Top Customers</h3>
                <a href="{{ route('customers.index') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:underline">View all</a>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Customer</th>
                        <th class="table-th text-right">Orders</th>
                        <th class="table-th text-right">Revenue</th>
                        <th class="table-th text-right">Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($s->topCustomers as $c)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $c->name }}</div>
                                <div class="text-xs text-gray-400">{{ $c->phone }}</div>
                            </td>
                            <td class="table-td text-right text-gray-600">{{ $c->order_count }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($c->revenue, 0) }}</td>
                            <td
                                class="table-td text-right {{ $c->outstanding_due > 0 ? 'text-red-600 font-bold' : 'text-gray-300' }}">
                                @if ($c->outstanding_due > 0)
                                    ৳{{ number_format($c->outstanding_due, 0) }}
                                @else
                                    Clear
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-td text-center text-gray-400 py-6 text-sm">No customers.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Bottom Row ───────────────────────────────────────────────────────── --}}
    <div class="grid lg:grid-cols-3 gap-5">

        {{-- Top Employees --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Top Employees</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($s->topEmployees as $idx => $emp)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <div
                            class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600 shrink-0">
                            {{ $idx + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $emp->name }}</div>
                            <div class="text-xs text-gray-400">{{ $emp->order_count }} orders</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-semibold text-sm text-gray-900">৳{{ number_format($emp->revenue, 0) }}
                            </div>
                            <div class="text-xs {{ $emp->profit >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                {{ $emp->profit >= 0 ? '+' : '' }}৳{{ number_format($emp->profit, 0) }} profit
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-gray-400 text-sm">No sales this period.</div>
                @endforelse
            </div>
        </div>

        {{-- Branch Comparison --}}
        @if (count($s->branchSales) > 1)
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Branch Comparison</h3>
                </div>
                @php $maxRevenue = max(array_column($s->branchSales, 'revenue') ?: [1]); @endphp
                <div class="p-5 space-y-4">
                    @foreach ($s->branchSales as $branch)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-900">{{ $branch->name }}</span>
                                <span
                                    class="text-gray-600 font-semibold">৳{{ number_format($branch->revenue, 0) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-500 h-2 rounded-full transition-all"
                                    style="width: {{ $maxRevenue > 0 ? round(($branch->revenue / $maxRevenue) * 100) : 0 }}%">
                                </div>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $branch->order_count }} orders</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Low Stock Alerts --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Low Stock Alerts</h3>
                <a href="{{ route('products.index') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:underline">Products</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($inv->lowStockItems as $item)
                    @php $item = (object) $item; @endphp
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $item->product_name }}</div>
                            <div class="text-xs text-gray-400">{{ $item->sku }}</div>
                        </div>
                        <div class="shrink-0 ml-3">
                            <span class="badge {{ $item->quantity == 0 ? 'badge-red' : 'badge-yellow' }}">
                                {{ $item->quantity }} left
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <div class="text-green-600 font-medium text-sm">✓ All stock levels OK</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Recent Sales ─────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Recent Sales</h3>
            <a href="{{ route('sales.index') }}" wire:navigate class="text-xs text-indigo-600 hover:underline">View
                all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Cashier</th>
                        <th class="table-th">Time</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($s->recentSales as $sale)
                        @php $sale = (object) $sale; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono font-semibold text-indigo-600 text-sm">
                                <a href="{{ route('sales.show', $sale->id) }}" wire:navigate class="hover:underline">
                                    {{ $sale->sale_number }}
                                </a>
                            </td>
                            <td class="table-td text-gray-700 text-sm">{{ $sale->customer_name }}</td>
                            <td class="table-td text-gray-500 text-xs">{{ $sale->cashier_name }}</td>
                            <td class="table-td text-gray-400 text-xs">
                                {{ \Carbon\Carbon::parse($sale->confirmed_at)->diffForHumans() }}
                            </td>
                            <td class="table-td text-right font-bold text-gray-900">
                                ৳{{ number_format($sale->grand_total, 0) }}
                            </td>
                            <td class="table-td">
                                @if ($sale->return_processed)
                                    <span class="badge badge-yellow text-xs">↩ Returned</span>
                                @else
                                    <span class="badge badge-green text-xs">Confirmed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-8">No sales yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
