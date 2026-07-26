@php
    $shop = auth()->user()?->shop;
    $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp
<x-document.layout title="Trial Balance" :subtitle="'As of ' . $asOfLabel" :shop="$shop">

    @if (!$data['balanced'])
        <div
            style="background:#fef2f2;border:1px solid #fca5a5;padding:8px 12px;border-radius:6px;margin-bottom:12px;font-size:8pt;color:#dc2626;">
            ⚠ Trial Balance is NOT balanced. Difference:
            ৳{{ number_format(abs($data['total_dr'] - $data['total_cr']), 2) }}
        </div>
    @endif

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:8%">Code</th>
                <th>Account Name</th>
                <th style="width:12%">Type</th>
                <th class="right" style="width:15%">Debit (৳)</th>
                <th class="right" style="width:15%">Credit (৳)</th>
            </tr>
        </thead>
        <tbody>
            @php $lastType = ''; @endphp
            @foreach ($data['rows'] as $row)
                @if ($row['type'] !== $lastType)
                    <tr style="background:#f8fafc;">
                        <td colspan="5"
                            style="font-weight:700;font-size:8pt;color:#4f46e5;padding:6px 8px;text-transform:uppercase;">
                            {{ ucfirst($row['type']) }}s
                        </td>
                    </tr>
                    @php $lastType = $row['type']; @endphp
                @endif
                <tr>
                    <td class="mono muted">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="muted" style="font-size:7.5pt;">{{ ucfirst($row['type']) }}</td>
                    <td class="right {{ $row['debit_balance'] > 0 ? 'doc-text-bold' : 'muted' }}">
                        {{ $row['debit_balance'] > 0 ? number_format($row['debit_balance'], 2) : '—' }}
                    </td>
                    <td class="right {{ $row['credit_balance'] > 0 ? 'doc-text-bold' : 'muted' }}">
                        {{ $row['credit_balance'] > 0 ? number_format($row['credit_balance'], 2) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="3">TOTAL</td>
                <td class="right">{{ number_format($data['total_dr'], 2) }}</td>
                <td class="right">{{ number_format($data['total_cr'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($data['balanced'])
        <div style="text-align:center;margin-top:10px;font-size:8pt;color:#16a34a;font-weight:600;">
            ✓ Trial Balance is balanced
        </div>
    @endif
    <x-document.signatures :signatories="$signatories" />
</x-document.layout>
