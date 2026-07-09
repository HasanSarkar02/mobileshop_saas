@php $shop = auth()->user()?->shop; @endphp

<x-document.layout title="Sales Report" :subtitle="$periodLabel" :shop="$shop">
    <x-document.report-header :title="'Sales Analysis Report'" :period="$periodLabel" :branch="'All Branches'" />

    {{-- KPIs --}}
    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Total Orders</span><span
                    class="doc-kv-value">{{ number_format($summary->orderCount) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Gross Revenue</span><span
                    class="doc-kv-value">৳{{ number_format($summary->grossRevenue, 2) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Discounts</span><span
                    class="doc-kv-value doc-text-red">(৳{{ number_format($summary->totalDiscount, 2) }})</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Net Revenue</span><span
                    class="doc-kv-value doc-text-bold">৳{{ number_format($summary->netRevenue, 2) }}</span></div>
        </div>
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Cost of Goods</span><span
                    class="doc-kv-value doc-text-red">(৳{{ number_format($summary->totalCost, 2) }})</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Gross Profit</span><span
                    class="doc-kv-value doc-text-bold">৳{{ number_format($summary->grossProfit, 2) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Margin</span><span
                    class="doc-kv-value">{{ $summary->profitMarginPct }}%</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Returns ({{ $summary->returnCount }})</span><span
                    class="doc-kv-value doc-text-red">(৳{{ number_format($summary->totalReturns, 2) }})</span></div>
        </div>
    </div>

    {{-- Daily Trend --}}
    <div class="doc-section-title">Daily Sales</div>
    <table class="doc-table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="right">Orders</th>
                <th class="right">Revenue (৳)</th>
                <th class="right">Profit (৳)</th>
                <th class="right">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trend as $day)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($day->sale_date)->format('d M Y') }}</td>
                    <td class="right">{{ $day->orders }}</td>
                    <td class="right mono">{{ number_format($day->revenue, 2) }}</td>
                    <td class="right mono {{ $day->profit >= 0 ? 'doc-text-green' : 'doc-text-red' }}">
                        {{ number_format($day->profit, 2) }}</td>
                    <td class="right">{{ $day->revenue > 0 ? round(($day->profit / $day->revenue) * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td>TOTAL</td>
                <td class="right">{{ $summary->orderCount }}</td>
                <td class="right mono">{{ number_format($summary->netRevenue, 2) }}</td>
                <td class="right mono">{{ number_format($summary->grossProfit, 2) }}</td>
                <td class="right">{{ $summary->profitMarginPct }}%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Top Products --}}
    <div class="doc-section-title" style="margin-top:4mm;">Top Products by Revenue</div>
    <table class="doc-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="right">Qty</th>
                <th class="right">Revenue (৳)</th>
                <th class="right">Profit (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($byProduct->take(20) as $p)
                <tr>
                    <td>{{ $p->product_name }}<br><span class="muted">{{ $p->brand_name }}</span></td>
                    <td class="mono muted">{{ $p->sku }}</td>
                    <td class="right">{{ number_format($p->qty_sold) }}</td>
                    <td class="right mono">{{ number_format($p->revenue, 2) }}</td>
                    <td class="right mono {{ $p->profit >= 0 ? 'doc-text-green' : 'doc-text-red' }}">
                        {{ number_format($p->profit, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-document.signatures :signatories="[
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Authorized By', 'name' => ''],
    ]" />
</x-document.layout>
