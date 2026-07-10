@php
    $shop = auth()->user()?->shop;
    $cf = $cashFlow;

    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Cash Flow Statement" :subtitle="$periodLabel" :shop="$shop">
    <x-document.report-header :title="'Cash Flow Statement (Direct Method)'" :period="$periodLabel" :branch="$branchId ? \App\Models\Branch::find($branchId)?->name : 'All Branches'" />

    <table class="doc-table" style="margin-bottom:4mm;">
        <thead>
            <tr>
                <th style="width:65%">Item</th>
                <th class="right">Amount (৳)</th>
                <th class="right" style="width:20%">Subtotal (৳)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Operating --}}
            <tr class="subtotal-row">
                <td colspan="3"><strong>A. OPERATING ACTIVITIES</strong></td>
            </tr>
            @foreach ([['label' => 'Cash Received from Sales', 'val' => $cf['sales_receipts'], 'positive' => true], ['label' => 'Less: Sales Returns / Refunds', 'val' => -$cf['sales_returns'], 'positive' => false], ['label' => 'Cash Received from Service Jobs', 'val' => $cf['service_receipts'], 'positive' => true], ['label' => 'Cash Paid to Suppliers', 'val' => -$cf['supplier_paid'], 'positive' => false], ['label' => 'Cash Paid for Operating Expenses', 'val' => -$cf['expense_paid'], 'positive' => false], ['label' => 'Cash Paid for Salaries & Wages', 'val' => -$cf['salary_paid'], 'positive' => false]] as $row)
                <tr>
                    <td style="padding-left:8mm;">{{ $row['label'] }}</td>
                    <td class="right mono {{ $row['val'] >= 0 ? 'doc-text-green' : 'doc-text-red' }}">
                        {{ $row['val'] >= 0 ? '+' : '' }}{{ number_format($row['val'], 2) }}
                    </td>
                    <td></td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td><strong>Net Cash from Operating Activities</strong></td>
                <td></td>
                <td
                    class="right mono doc-text-bold {{ $cf['net_operating'] >= 0 ? 'doc-text-green' : 'doc-text-red' }}">
                    {{ $cf['net_operating'] >= 0 ? '+' : '' }}{{ number_format($cf['net_operating'], 2) }}
                </td>
            </tr>

            {{-- Financing --}}
            <tr class="subtotal-row">
                <td colspan="3"><strong>B. FINANCING ACTIVITIES</strong></td>
            </tr>
            @foreach ([['label' => 'Owner / Partner Capital Injected', 'val' => $cf['capital_in']], ['label' => 'Owner / Partner Withdrawals', 'val' => -$cf['capital_out']], ['label' => 'Loans Received', 'val' => $cf['loans_in']], ['label' => 'Loan Repayments (incl. interest)', 'val' => -$cf['loans_out']]] as $row)
                @if (abs($row['val']) > 0)
                    <tr>
                        <td style="padding-left:8mm;">{{ $row['label'] }}</td>
                        <td class="right mono {{ $row['val'] >= 0 ? 'doc-text-green' : 'doc-text-red' }}">
                            {{ $row['val'] >= 0 ? '+' : '' }}{{ number_format($row['val'], 2) }}
                        </td>
                        <td></td>
                    </tr>
                @endif
            @endforeach
            <tr class="subtotal-row">
                <td><strong>Net Cash from Financing Activities</strong></td>
                <td></td>
                <td
                    class="right mono doc-text-bold {{ $cf['net_financing'] >= 0 ? 'doc-text-green' : 'doc-text-red' }}">
                    {{ $cf['net_financing'] >= 0 ? '+' : '' }}{{ number_format($cf['net_financing'], 2) }}
                </td>
            </tr>

            {{-- Net Change + Balances --}}
            <tr class="grand-total-row">
                <td>C. NET CHANGE IN CASH (A + B)</td>
                <td></td>
                <td class="right mono">
                    {{ $cf['net_change'] >= 0 ? '+' : '' }}{{ number_format($cf['net_change'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding-left:8mm;">Opening Cash Balance</td>
                <td></td>
                <td class="right mono">{{ number_format($cf['opening_balance'], 2) }}</td>
            </tr>
            <tr class="grand-total-row">
                <td>CLOSING CASH BALANCE</td>
                <td></td>
                <td class="right mono">{{ number_format($cf['closing_balance'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="doc-notes">
        Prepared using the <strong>Direct Method</strong>.
        Operating activities are derived from the General Ledger journal entries.
        Financing activities are sourced from the Treasury Transaction module.
        Internal fund transfers between accounts are excluded.
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
