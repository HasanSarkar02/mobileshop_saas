@php
    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => "Employee's Signature", 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Salary Slip" :subtitle="$slip->payrollRun?->monthName()" :docNumber="'SLIP-' . $slip->id" :shop="$shop" :branch="$slip->payrollRun?->branch"
    :exportPdfUrl="route('documents.payroll-slip.pdf', $slip)">
    {{-- Meta Band --}}
    <x-document.meta :cols="4" :items="[
        ['label' => 'Pay Period', 'value' => $slip->payrollRun?->monthName()],
        ['label' => 'Employment Type', 'value' => ucfirst(str_replace('_', ' ', $slip->employment_type))],
        ['label' => 'Working Days', 'value' => $slip->working_days . ' days'],
        ['label' => 'Days Worked', 'value' => number_format($slip->days_worked, 1) . ' days'],
    ]" />

    {{-- Employee Info --}}
    <x-document.parties :to="[
        'title' => 'Employee Details',
        'name' => $slip->employee_name,
        'lines' => [
            $slip->designation ? 'Designation: ' . $slip->designation : null,
            $slip->department_name ? 'Department: ' . $slip->department_name : null,
            $slip->payrollRun?->branch ? 'Branch: ' . $slip->payrollRun->branch->name : null,
        ],
    ]" :extra="[
        'title' => 'Attendance',
        'lines' => [
            'Working Days: ' . $slip->working_days,
            'Days Worked: ' . number_format($slip->days_worked, 1),
            $slip->absent_days > 0 ? 'Absent: ' . number_format($slip->absent_days, 1) : null,
            $slip->overtime_hours > 0 ? 'Overtime: ' . number_format($slip->overtime_hours, 1) . 'h' : null,
            $slip->leaves_paid > 0 ? 'Paid Leave: ' . number_format($slip->leaves_paid, 1) : null,
        ],
    ]" />

    {{-- Earnings & Deductions --}}
    <div class="doc-two-col" style="margin-bottom:4mm;gap:4mm;">
        {{-- Earnings --}}
        <div>
            <div class="doc-section-title">Earnings</div>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width:70%">Component</th>
                        <th class="right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($slip->earnings as $earning)
                        <tr>
                            <td>
                                {{ $earning->component_name }}
                                @if ($earning->calculation_basis && $earning->calculation_type !== 'fixed')
                                    <br><span class="muted"
                                        style="font-size:7pt;">{{ $earning->calculation_basis }}</span>
                                @endif
                            </td>
                            <td class="right mono">{{ number_format($earning->computed_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td class="doc-text-bold">Gross Earnings</td>
                        <td class="right mono doc-text-bold">{{ number_format($slip->gross_earnings, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Deductions --}}
        <div>
            <div class="doc-section-title">Deductions</div>
            @if ($slip->deductions->isNotEmpty())
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th style="width:70%">Component</th>
                            <th class="right">Amount (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slip->deductions as $deduction)
                            <tr>
                                <td>{{ $deduction->component_name }}</td>
                                <td class="right mono doc-text-red">{{ number_format($deduction->computed_value, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="subtotal-row">
                            <td class="doc-text-bold">Total Deductions</td>
                            <td class="right mono doc-text-bold doc-text-red">
                                {{ number_format($slip->total_deductions, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <div style="padding:3mm;color:#6B7280;font-size:8pt;">No deductions.</div>
            @endif
        </div>
    </div>

    {{-- NET PAYABLE Box --}}
    <div
        style="border:3pt solid #1e3a5f;padding:5mm 6mm;display:flex;justify-content:space-between;align-items:center;margin-bottom:3mm;">
        <div>
            <div style="font-size:7pt;color:#6B7280;text-transform:uppercase;letter-spacing:0.05em;">Gross Earnings
            </div>
            <div style="font-size:11pt;color:#1e3a5f;font-family:var(--doc-mono);">
                ৳{{ number_format($slip->gross_earnings, 2) }}</div>
            <div style="font-size:7pt;color:#dc2626;margin-top:1mm;">Less: Deductions:
                ৳{{ number_format($slip->total_deductions, 2) }}</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:8pt;color:#6B7280;text-transform:uppercase;letter-spacing:0.05em;">Net Payable</div>
            <div style="font-size:20pt;font-weight:700;color:#1e3a5f;font-family:var(--doc-mono);">
                ৳{{ number_format($slip->net_payable, 2) }}</div>
        </div>
    </div>

    {{-- Amount in Words --}}
    <div class="doc-amount-words">
        <strong>Amount in Words:</strong> Taka {{ number_format($slip->net_payable, 2) }} Only
    </div>

    {{-- Payment History --}}
    @if ($slip->activePayments->isNotEmpty())
        <div class="doc-section-title" style="margin-top:3mm;">Payment Records</div>
        <table class="doc-table" style="margin-bottom:3mm;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment No.</th>
                    <th>Via</th>
                    <th>Method</th>
                    <th class="right">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($slip->activePayments as $pmt)
                    <tr>
                        <td>{{ $pmt->payment_date->format('d M Y') }}</td>
                        <td class="mono muted">{{ $pmt->payment_number }}</td>
                        <td>{{ $pmt->paymentAccount?->name }}</td>
                        <td class="muted">{{ ucfirst(str_replace('_', ' ', $pmt->payment_method)) }}</td>
                        <td class="right mono doc-text-green">{{ number_format($pmt->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="4" class="doc-text-bold">Total Paid</td>
                    <td class="right mono doc-text-bold doc-text-green">{{ number_format($slip->total_paid, 2) }}</td>
                </tr>
                @if ((float) $slip->balance_payable > 0)
                    <tr>
                        <td colspan="4" class="doc-text-red doc-text-bold">Balance Remaining</td>
                        <td class="right mono doc-text-red doc-text-bold">
                            {{ number_format($slip->balance_payable, 2) }}</td>
                    </tr>
                @endif
            </tfoot>
        </table>
    @endif

    {{-- Signatures --}}
    <x-document.signatures :signatories="$signatories" />

    <div class="doc-notes" style="margin-top:3mm;">
        This is a computer-generated salary slip. Contact HR for any discrepancies.
    </div>

</x-document.layout>
