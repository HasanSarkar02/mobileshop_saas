@php
    $shop = auth()->user()?->shop;
    $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Profit & Loss Statement" :subtitle="$periodLabel" :shop="$shop" :branch="$branch"
    :exportPdfUrl="route('reports.pl.pdf', request()->all())" :exportCsvUrl="route('reports.pl.csv', request()->all())">
    <x-document.report-header :title="'Profit & Loss Statement'" :period="$periodLabel" :branch="$branch?->name ?? 'All Branches'" />

    @php
        $r = $report;
        function plRow(string $label, float $amount, bool $contra = false, int $indent = 0): string
        {
            $indentStyle = $indent ? 'padding-left:' . $indent * 6 . 'mm;' : '';
            $amtColor = $contra && $amount > 0 ? 'color:#dc2626;' : '';
            $display =
                $contra && $amount > 0
                    ? '(৳' . number_format(abs($amount), 2) . ')'
                    : '৳' . number_format(abs($amount), 2);
            if ($amount == 0) {
                return '';
            }
            return "<tr><td style='{$indentStyle}'>{$label}</td><td class='right mono' style='{$amtColor}'>{$display}</td></tr>";
        }
    @endphp

    {{-- Revenue Section --}}
    <table class="doc-table" style="margin-bottom:3mm;">
        <thead>
            <tr>
                <th>REVENUE</th>
                <th class="right">{{ $periodLabel }}</th>
                @if ($r->previousPeriod)
                    <th class="right">Previous Period</th>
                    <th class="right">Change</th>
                @endif
            </tr>
        </thead>
        <tbody>
            {!! plRow('Sales Revenue', $r->salesRevenue, false, 1) !!}
            {!! plRow('Service Revenue', $r->serviceRevenue, false, 1) !!}
            {!! plRow('Sales Returns', $r->salesReturns, true, 1) !!}
            {!! plRow('Sales Discounts', $r->salesDiscounts, true, 1) !!}
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td class="doc-text-bold">Net Revenue</td>
                <td class="right mono doc-text-bold">৳{{ number_format($r->netRevenue, 2) }}</td>
                @if ($r->previousPeriod)
                    <td class="right mono">৳{{ number_format($r->previousPeriod->netRevenue, 2) }}</td>
                    @php $diff = $r->netRevenue - $r->previousPeriod->netRevenue; @endphp
                    <td class="right mono" style="color:{{ $diff >= 0 ? '#16a34a' : '#dc2626' }}">
                        {{ $diff >= 0 ? '+' : '' }}৳{{ number_format($diff, 2) }}
                    </td>
                @endif
            </tr>
        </tfoot>
    </table>

    {{-- Cost of Sales --}}
    <table class="doc-table" style="margin-bottom:3mm;">
        <thead>
            <tr>
                <th>COST OF SALES</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            {!! plRow('Cost of Goods Sold', $r->costOfGoodsSold, false, 1) !!}
            {!! plRow('Cost of Service Parts', $r->costOfServiceParts, false, 1) !!}
        </tbody>
        <tfoot>
            <tr class="subtotal-row">
                <td class="doc-text-bold">Gross Profit ({{ $r->grossMarginPct }}% margin)</td>
                <td class="right mono doc-text-bold">৳{{ number_format($r->grossProfit, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Operating Expenses --}}
    <table class="doc-table" style="margin-bottom:3mm;">
        <thead>
            <tr>
                <th>OPERATING EXPENSES</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($r->expensesByAccount as $name => $amount)
                @if ($amount > 0)
                    <tr>
                        <td style="padding-left:6mm;">{{ $name }}</td>
                        <td class="right mono" style="color:#dc2626;">৳{{ number_format($amount, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="subtotal-row">
                <td class="doc-text-bold">Total Operating Expenses</td>
                <td class="right mono doc-text-bold" style="color:#dc2626;">
                    ৳{{ number_format($r->totalOperatingExpenses, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Net Profit --}}
    <table class="doc-table" style="margin-bottom:4mm;">
        <thead>
            <tr>
                <th>OPERATING PROFIT ({{ $r->operatingMarginPct }}% margin)</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="grand-total-row">
                <td style="font-size:12pt;font-weight:700;">NET PROFIT / LOSS</td>
                <td class="right mono"
                    style="font-size:12pt;font-weight:700;color:{{ $r->netProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                    {{ $r->netProfit >= 0 ? '+' : '' }}৳{{ number_format($r->netProfit, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="doc-notes">
        <strong>Note:</strong> This P&L is prepared on an accrual basis using double-entry accounting records.
        Figures are extracted from the General Ledger journal entries for the stated period.
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
