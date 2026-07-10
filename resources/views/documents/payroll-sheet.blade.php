@php
    $shop = $run->shop ?? auth()->user()?->shop;
    $run->loadMissing('items.user.employeeProfile');

    $signatories = [
        ['title' => 'Prepared By', 'name' => $run->createdBy?->name ?? ''],
        ['title' => 'HR / Accounts', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Payroll Register" :subtitle="$run->monthName()" :docNumber="'PR-' . $run->year . '-' . str_pad($run->month, 2, '0', STR_PAD_LEFT)" :shop="$shop" :landscape="$run->items->count() > 6"
    :exportPdfUrl="route('documents.payroll.pdf', $run)">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Pay Period', 'value' => $run->monthName()],
        ['label' => 'Status', 'value' => $run->status->label()],
        ['label' => 'Employees', 'value' => $run->items->count() . ' persons'],
        ['label' => 'Generated', 'value' => now()->format('d M Y H:i')],
    ]" />

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:22%">Employee</th>
                <th class="right" style="width:11%">Basic</th>
                <th class="right" style="width:10%">Allowances</th>
                <th class="right" style="width:11%">Gross</th>
                <th class="right" style="width:10%">Bonus</th>
                <th class="right" style="width:11%">Deductions</th>
                <th class="right" style="width:11%">Net Salary</th>
                <th style="width:10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($run->items as $i => $item)
                <tr>
                    <td class="center muted">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item->user?->name }}</strong>
                        @if ($item->user?->employeeProfile?->designation)
                            <br><span class="muted">{{ $item->user->employeeProfile->designation }}</span>
                        @endif
                    </td>
                    <td class="right mono">৳{{ number_format($item->base_salary, 0) }}</td>
                    <td class="right mono">
                        ৳{{ number_format($item->house_allowance + $item->transport_allowance + $item->other_allowance, 0) }}
                    </td>
                    <td class="right mono doc-text-bold">৳{{ number_format($item->gross_salary, 0) }}</td>
                    <td class="right mono" style="color:#16a34a;">
                        {{ $item->bonus > 0 ? '+৳' . number_format($item->bonus, 0) : '—' }}</td>
                    <td class="right mono" style="color:#dc2626;">
                        {{ $item->total_deductions > 0 ? '(৳' . number_format($item->total_deductions, 0) . ')' : '—' }}
                    </td>
                    <td class="right mono doc-text-bold" style="color:#1e3a5f;">
                        ৳{{ number_format($item->net_salary, 0) }}</td>
                    <td class="center" style="font-size:7.5pt;">{{ $item->is_paid ? '✓ Paid' : 'Pending' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="doc-text-bold">TOTAL</td>
                <td class="right mono doc-text-bold">৳{{ number_format($run->items->sum('base_salary'), 0) }}</td>
                <td class="right mono doc-text-bold">
                    ৳{{ number_format($run->items->sum(fn($i) => $i->house_allowance + $i->transport_allowance + $i->other_allowance), 0) }}
                </td>
                <td class="right mono doc-text-bold">৳{{ number_format($run->total_gross, 0) }}</td>
                <td class="right mono doc-text-bold" style="color:#16a34a;">
                    ৳{{ number_format($run->items->sum('bonus'), 0) }}</td>
                <td class="right mono doc-text-bold" style="color:#dc2626;">
                    (৳{{ number_format($run->total_deductions, 0) }})</td>
                <td class="right mono doc-text-bold" style="color:#1e3a5f;font-size:10pt;">
                    ৳{{ number_format($run->total_net, 0) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="doc-notes" style="margin-top:3mm;">
        <strong>Total Net Payable:</strong> Taka {{ number_format($run->total_net, 2) }} Only
        &nbsp;|&nbsp;
        <strong>Period:</strong> {{ $run->monthName() }}
        &nbsp;|&nbsp;
        <strong>Approved By:</strong> {{ $run->approvedBy?->name ?? 'Pending' }}
        @if ($run->approved_at)
            &nbsp;on {{ $run->approved_at->format('d M Y') }}
        @endif
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
