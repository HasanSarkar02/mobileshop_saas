@php
    $shop = auth()->user()?->shop;
    $run->loadMissing(['slips.earnings', 'slips.deductions', 'branch', 'approvedBy']);

    $signatories = [
        ['title' => 'Prepared By', 'name' => $run->generatedBy?->name ?? ''],
        ['title' => 'HR / Accounts', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Payroll Register" :subtitle="$run->monthName()" :docNumber="$run->run_number" :shop="$shop" :branch="$run->branch"
    :landscape="true" :exportPdfUrl="route('documents.payroll-register.pdf', $run)">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Pay Period', 'value' => $run->monthName()],
        ['label' => 'Run Number', 'value' => $run->run_number],
        ['label' => 'Employees', 'value' => $run->total_employees . ' persons'],
        ['label' => 'Status', 'value' => $run->status->label()],
        ['label' => 'Approved By', 'value' => $run->approvedBy?->name ?? 'Pending'],
        ['label' => 'Approved On', 'value' => $run->approved_at?->format('d M Y') ?? '—'],
        ['label' => 'Scope', 'value' => $run->branch?->name ?? 'All Branches'],
        ['label' => 'Generated', 'value' => now()->format('d M Y H:i')],
    ]" />

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:3%">#</th>
                <th style="width:18%">Employee</th>
                <th style="width:10%">Designation</th>
                <th class="right" style="width:10%">Basic (৳)</th>
                <th class="right" style="width:9%">Allowances (৳)</th>
                <th class="right" style="width:10%">Gross (৳)</th>
                <th class="right" style="width:9%">Deductions (৳)</th>
                <th class="right" style="width:10%">Net (৳)</th>
                <th class="right" style="width:9%">Paid (৳)</th>
                <th class="right" style="width:9%">Balance (৳)</th>
                <th style="width:8%">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBasic = 0;
                $totalAllowances = 0;
                $totalGross = 0;
                $totalDeductions = 0;
                $totalNet = 0;
                $totalPaid = 0;
                $totalBalance = 0;
            @endphp
            @foreach ($run->slips as $i => $slip)
                @php
                    $basic = (float) ($slip->earnings->firstWhere('component_code', 'BASIC')?->computed_value ?? 0);
                    $allowances = (float) $slip->gross_earnings - $basic;
                    $totalBasic += $basic;
                    $totalAllowances += $allowances;
                    $totalGross += (float) $slip->gross_earnings;
                    $totalDeductions += (float) $slip->total_deductions;
                    $totalNet += (float) $slip->net_payable;
                    $totalPaid += (float) $slip->total_paid;
                    $totalBalance += (float) $slip->balance_payable;
                @endphp
                <tr>
                    <td class="center muted">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $slip->employee_name }}</strong>
                        @if ($slip->department_name)
                            <br><span class="muted">{{ $slip->department_name }}</span>
                        @endif
                    </td>
                    <td class="muted">{{ $slip->designation ?? '—' }}</td>
                    <td class="right mono">{{ number_format($basic, 0) }}</td>
                    <td class="right mono">{{ $allowances > 0 ? number_format($allowances, 0) : '—' }}</td>
                    <td class="right mono doc-text-bold">{{ number_format($slip->gross_earnings, 0) }}</td>
                    <td class="right mono doc-text-red">
                        {{ $slip->total_deductions > 0 ? number_format($slip->total_deductions, 0) : '—' }}</td>
                    <td class="right mono doc-text-bold">{{ number_format($slip->net_payable, 0) }}</td>
                    <td class="right mono doc-text-green">
                        {{ $slip->total_paid > 0 ? number_format($slip->total_paid, 0) : '—' }}</td>
                    <td class="right mono {{ $slip->balance_payable > 0 ? 'doc-text-red' : '' }}">
                        {{ $slip->balance_payable > 0 ? number_format($slip->balance_payable, 0) : '✓' }}
                    </td>
                    <td style="font-size:7pt;">{{ $slip->status->label() }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="3">TOTAL ({{ $run->total_employees }} employees)</td>
                <td class="right mono">{{ number_format($totalBasic, 0) }}</td>
                <td class="right mono">{{ number_format($totalAllowances, 0) }}</td>
                <td class="right mono">{{ number_format($totalGross, 0) }}</td>
                <td class="right mono">({{ number_format($totalDeductions, 0) }})</td>
                <td class="right mono">{{ number_format($totalNet, 0) }}</td>
                <td class="right mono">{{ number_format($totalPaid, 0) }}</td>
                <td class="right mono">{{ $totalBalance > 0 ? number_format($totalBalance, 0) : '✓' }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="doc-notes" style="margin-top:3mm;">
        <strong>Total Net Payable:</strong> Taka {{ number_format($run->total_net_payable, 2) }} Only
        &nbsp;|&nbsp; <strong>Period:</strong> {{ $run->monthName() }}
        &nbsp;|&nbsp; <strong>Run:</strong> {{ $run->run_number }}
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
