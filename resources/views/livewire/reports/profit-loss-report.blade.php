<div class="max-w-4xl mx-auto space-y-5 print:space-y-3">

    {{-- Print Header --}}
    <div class="hidden print:block text-center mb-6">
        <h1 class="text-2xl font-bold">{{ auth()->user()->shop?->name }}</h1>
        <p class="text-gray-600 text-sm">Profit & Loss Statement</p>
        <p class="text-gray-500 text-xs">{{ $periodLabel }}</p>
    </div>

    {{-- Filter --}}
    <div class="print:hidden">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xl font-bold text-gray-900">Profit & Loss Statement</h2>
        </div>
        <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$branches" />
    </div>

    @php $r = $this->report; @endphp

    {{-- Report Body --}}
    <div class="card overflow-hidden">
        {{-- Period Header --}}
        <div class="px-6 py-4 bg-indigo-700 text-white">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-lg">Profit & Loss</h3>
                <span class="text-indigo-200 text-sm">{{ $periodLabel }}</span>
            </div>
        </div>

        <div class="divide-y divide-gray-100">

            {{-- ── REVENUE ─────────────────────────────────────────────────── --}}
            @include('livewire.reports.partials.pl-section', [
                'title' => 'REVENUE',
                'titleColor' => 'text-gray-700',
                'rows' => [
                    ['label' => 'Sales Revenue', 'amount' => $r->salesRevenue, 'indent' => 1],
                    ['label' => 'Service Revenue', 'amount' => $r->serviceRevenue, 'indent' => 1],
                    ['label' => 'Sales Returns', 'amount' => -$r->salesReturns, 'indent' => 1, 'contra' => true],
                    [
                        'label' => 'Sales Discounts',
                        'amount' => -$r->salesDiscounts,
                        'indent' => 1,
                        'contra' => true,
                    ],
                ],
                'total' => $r->netRevenue,
                'totalLabel' => 'Net Revenue',
                'totalBold' => true,
                'prev' => $r->previousPeriod?->netRevenue,
            ])

            {{-- ── COST OF SALES ────────────────────────────────────────────── --}}
            @include('livewire.reports.partials.pl-section', [
                'title' => 'COST OF SALES',
                'titleColor' => 'text-gray-700',
                'rows' => [
                    ['label' => 'Cost of Goods Sold', 'amount' => $r->costOfGoodsSold, 'indent' => 1],
                    ['label' => 'Cost of Service Parts', 'amount' => $r->costOfServiceParts, 'indent' => 1],
                ],
                'total' => $r->costOfGoodsSold + $r->costOfServiceParts,
                'totalLabel' => 'Total Cost of Sales',
            ])

            {{-- ── GROSS PROFIT ─────────────────────────────────────────────── --}}
            <div class="px-6 py-4 bg-green-50 flex items-center justify-between">
                <div>
                    <span class="font-bold text-green-900 text-base">Gross Profit</span>
                    <span class="text-green-600 text-sm ml-2">({{ $r->grossMarginPct }}% margin)</span>
                </div>
                <div class="text-right">
                    <div class="font-bold text-green-800 text-lg">
                        ৳{{ number_format($r->grossProfit, 2) }}
                    </div>
                    @if ($r->previousPeriod)
                        @php $diff = $r->grossProfit - $r->previousPeriod->grossProfit; @endphp
                        <div class="text-xs {{ $diff >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            {{ $diff >= 0 ? '↑' : '↓' }} ৳{{ number_format(abs($diff), 0) }} vs prev
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── OPERATING EXPENSES ───────────────────────────────────────── --}}
            <div class="px-6 py-3 bg-gray-50">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Operating Expenses</span>
            </div>
            @foreach ($r->expensesByAccount as $name => $amount)
                <div class="px-6 py-2.5 flex items-center justify-between hover:bg-gray-50">
                    <span class="text-sm text-gray-700 pl-4">{{ $name }}</span>
                    <span class="text-sm text-red-600 font-medium">৳{{ number_format($amount, 2) }}</span>
                </div>
            @endforeach
            <div class="px-6 py-3 flex justify-between border-t border-gray-200 bg-gray-50">
                <span class="font-semibold text-gray-700">Total Operating Expenses</span>
                <span class="font-bold text-red-600">৳{{ number_format($r->totalOperatingExpenses, 2) }}</span>
            </div>

            {{-- ── NET PROFIT ───────────────────────────────────────────────── --}}
            <div class="px-6 py-5 {{ $r->netProfit >= 0 ? 'bg-green-700' : 'bg-red-700' }} text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-bold text-xl">Net Profit</div>
                        <div class="text-sm opacity-80">{{ $r->netMarginPct }}% net margin</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-2xl">
                            {{ $r->netProfit >= 0 ? '+' : '' }}৳{{ number_format($r->netProfit, 2) }}
                        </div>
                        @if ($r->previousPeriod)
                            @php $d = $r->profitVsPrevious(); @endphp
                            <div class="text-sm opacity-80">
                                vs prev: {{ $d >= 0 ? '+' : '' }}৳{{ number_format($d, 0) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
