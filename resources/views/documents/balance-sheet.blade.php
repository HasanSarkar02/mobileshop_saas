@php
    $shop = auth()->user()?->shop;
    $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp
<x-document.layout title="Balance Sheet" :subtitle="'As of ' . $asOfLabel" :shop="$shop">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        {{-- Assets --}}
        <div>
            <table class="doc-table">
                <thead>
                    <tr style="background:#1e40af;color:white;">
                        <th colspan="3" style="text-align:left;padding:8px;">ASSETS</th>
                    </tr>
                    <tr>
                        <th style="width:10%">Code</th>
                        <th>Account</th>
                        <th class="right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $row)
                        <tr>
                            <td class="mono muted">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="right">{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="2">Total Assets</td>
                        <td class="right">{{ number_format($totalAssets, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Liabilities + Equity --}}
        <div style="display:flex;flex-direction:column;gap:12px;">
            <table class="doc-table">
                <thead>
                    <tr style="background:#991b1b;color:white;">
                        <th colspan="3" style="text-align:left;padding:8px;">LIABILITIES</th>
                    </tr>
                    <tr>
                        <th style="width:10%">Code</th>
                        <th>Account</th>
                        <th class="right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($liabilities as $row)
                        <tr>
                            <td class="mono muted">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="right">{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="2">Total Liabilities</td>
                        <td class="right">{{ number_format($totalLiabilities, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="doc-table">
                <thead>
                    <tr style="background:#14532d;color:white;">
                        <th colspan="3" style="text-align:left;padding:8px;">EQUITY</th>
                    </tr>
                    <tr>
                        <th style="width:10%">Code</th>
                        <th>Account</th>
                        <th class="right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equity as $row)
                        <tr>
                            <td class="mono muted">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="right {{ $row['balance'] < 0 ? 'doc-text-red' : '' }}">
                                {{ number_format($row['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="2">Total Equity</td>
                        <td class="right">{{ number_format($totalEquity, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Check --}}
            <div
                style="background:#1e293b;color:white;padding:10px 12px;border-radius:6px;display:flex;justify-content:space-between;font-weight:700;">
                <span>Total Liabilities + Equity</span>
                <span>৳{{ number_format($totalLiabilities + $totalEquity, 2) }}</span>
            </div>
        </div>
    </div>
    <x-document.signatures :signatories="$signatories" />
</x-document.layout>
