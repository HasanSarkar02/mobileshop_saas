<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Payroll Reports</h2>
        <a href="{{ route('payroll.index') }}" wire:navigate class="btn-secondary btn-sm">← Dashboard</a>
    </div>

    {{-- Report Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @foreach ([['key' => 'outstanding', 'label' => 'Outstanding Salary'], ['key' => 'payments', 'label' => 'Payment Register'], ['key' => 'loans', 'label' => 'Loan Recovery'], ['key' => 'audit', 'label' => 'Audit Log']] as $tab)
                <button wire:click="$set('activeReport', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeReport === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Filters Bar --}}
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-3">
            @if (in_array($activeReport, ['payments', 'audit']))
                <div>
                    <label class="label text-xs">Month</label>
                    <input wire:model.live="yearMonth" type="month" class="input text-sm w-auto">
                </div>
            @endif
            @if ($activeReport === 'loans')
                <div>
                    <label class="label text-xs">Employee</label>
                    <select wire:model.live="userId" class="input text-sm w-auto">
                        <option value="0">All Employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button onclick="window.print()" class="btn-secondary btn-sm ml-auto">🖨 Print</button>
        </div>

        {{-- ── Outstanding Salary ── --}}
        @if ($activeReport === 'outstanding')
            @if ($outstandingTotal > 0)
                <div class="px-5 py-3 bg-red-50 border-b border-red-100 flex items-center justify-between">
                    <span class="text-sm font-semibold text-red-800">
                        Total Outstanding Salary
                    </span>
                    <span class="font-bold text-red-700 text-lg">
                        ৳{{ number_format($outstandingTotal, 2) }}
                    </span>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Employee</th>
                            <th class="table-th">Pay Period</th>
                            <th class="table-th text-right">Net Payable</th>
                            <th class="table-th text-right">Paid</th>
                            <th class="table-th text-right">Balance</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($outstandingSlips as $slip)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-semibold text-gray-900">{{ $slip->employee_name }}</td>
                                <td class="table-td text-gray-500 text-sm">
                                    {{ $slip->payrollRun?->monthName() }}
                                </td>
                                <td class="table-td text-right">৳{{ number_format($slip->net_payable, 0) }}</td>
                                <td class="table-td text-right text-green-600">
                                    {{ $slip->total_paid > 0 ? '৳' . number_format($slip->total_paid, 0) : '—' }}
                                </td>
                                <td class="table-td text-right font-bold text-red-600">
                                    ৳{{ number_format($slip->balance_payable, 0) }}
                                </td>
                                <td class="table-td">
                                    <span class="badge {{ $slip->status->badgeClass() }} text-xs">
                                        {{ $slip->status->label() }}
                                    </span>
                                </td>
                                <td class="table-td">
                                    @can('payroll.pay')
                                        <a href="{{ route('payroll.pay', $slip) }}" wire:navigate
                                            class="text-xs text-green-600 hover:underline font-medium">
                                            Pay →
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-td text-center text-gray-400 py-8">
                                    🎉 No outstanding salaries!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($outstandingSlips?->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $outstandingSlips->links() }}</div>
            @endif
        @endif

        {{-- ── Payment Register ── --}}
        @if ($activeReport === 'payments')
            @if (($paymentRegisterTotal ?? 0) > 0)
                <div class="px-5 py-3 bg-green-50 border-b border-green-100 flex items-center justify-between">
                    <span class="text-sm font-semibold text-green-800">
                        Total Payments — {{ \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->format('F Y') }}
                    </span>
                    <span class="font-bold text-green-700 text-lg">
                        ৳{{ number_format($paymentRegisterTotal, 2) }}
                    </span>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Payment No.</th>
                            <th class="table-th">Employee</th>
                            <th class="table-th">Pay Period</th>
                            <th class="table-th">Date</th>
                            <th class="table-th">Via</th>
                            <th class="table-th">Method</th>
                            <th class="table-th">Reference</th>
                            <th class="table-th text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($paymentRegister as $pmt)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-mono text-indigo-600 text-sm">
                                    {{ $pmt->payment_number }}
                                </td>
                                <td class="table-td font-medium text-gray-900">
                                    {{ $pmt->slip?->employee_name }}
                                </td>
                                <td class="table-td text-gray-500 text-sm">
                                    {{ $pmt->payrollRun?->monthName() }}
                                </td>
                                <td class="table-td text-gray-500 text-sm whitespace-nowrap">
                                    {{ $pmt->payment_date->format('d M Y') }}
                                </td>
                                <td class="table-td text-gray-600 text-sm">
                                    {{ $pmt->paymentAccount?->name }}
                                </td>
                                <td class="table-td text-xs text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $pmt->payment_method) }}
                                </td>
                                <td class="table-td font-mono text-xs text-gray-400">
                                    {{ $pmt->reference_number ?? '—' }}
                                </td>
                                <td class="table-td text-right font-bold text-green-600">
                                    ৳{{ number_format($pmt->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-td text-center text-gray-400 py-8">
                                    No payments in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($paymentRegister?->isNotEmpty())
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="7" class="table-td font-bold">Total</td>
                                <td class="table-td text-right font-bold text-green-700 text-base">
                                    ৳{{ number_format($paymentRegisterTotal, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($paymentRegister?->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $paymentRegister->links() }}</div>
            @endif
        @endif

        {{-- ── Loan Recovery Report ── --}}
        @if ($activeReport === 'loans')
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Loan No.</th>
                            <th class="table-th">Employee</th>
                            <th class="table-th">Type</th>
                            <th class="table-th">Disbursed</th>
                            <th class="table-th text-right">Total (৳)</th>
                            <th class="table-th text-right">Recovered (৳)</th>
                            <th class="table-th text-right">Outstanding (৳)</th>
                            <th class="table-th text-right">Monthly (৳)</th>
                            <th class="table-th">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($activeLoans as $loan)
                            @php $recovered = $loan->totalRecovered(); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-mono text-indigo-600 text-sm">
                                    {{ $loan->loan_number }}
                                </td>
                                <td class="table-td font-semibold text-gray-900">
                                    {{ $loan->user?->name }}
                                </td>
                                <td class="table-td">
                                    <span class="badge badge-blue text-xs">
                                        {{ ucfirst($loan->loan_type) }}
                                    </span>
                                </td>
                                <td class="table-td text-gray-500 text-sm">
                                    {{ $loan->disbursement_date->format('d M Y') }}
                                </td>
                                <td class="table-td text-right">৳{{ number_format($loan->total_amount, 0) }}</td>
                                <td class="table-td text-right text-green-600 font-semibold">
                                    ৳{{ number_format($recovered, 0) }}
                                </td>
                                <td
                                    class="table-td text-right {{ $loan->outstanding_balance > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">
                                    {{ $loan->outstanding_balance > 0 ? '৳' . number_format($loan->outstanding_balance, 0) : '✓ Cleared' }}
                                </td>
                                <td class="table-td text-right text-gray-600">
                                    ৳{{ number_format($loan->monthly_deduction, 0) }}/mo
                                </td>
                                <td class="table-td">
                                    @php
                                        $lbadge = match ($loan->status->value) {
                                            'active' => 'badge-red',
                                            'fully_recovered' => 'badge-green',
                                            'waived' => 'badge-yellow',
                                            default => 'badge-gray',
                                        };
                                    @endphp
                                    <span class="badge {{ $lbadge }} text-xs">
                                        {{ ucfirst(str_replace('_', ' ', $loan->status->value)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="table-td text-center text-gray-400 py-8">
                                    No loan records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($activeLoans?->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $activeLoans->links() }}</div>
            @endif
        @endif

        {{-- ── Payroll Audit Log ── --}}
        @if ($activeReport === 'audit')
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Time</th>
                            <th class="table-th">User</th>
                            <th class="table-th">Action</th>
                            <th class="table-th">Reference</th>
                            <th class="table-th text-right">Amount</th>
                            <th class="table-th">Old Status</th>
                            <th class="table-th">New Status</th>
                            <th class="table-th">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d M H:i') }}
                                </td>
                                <td class="table-td font-medium text-sm text-gray-900">
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td class="table-td">
                                    @php
                                        $actionColors = [
                                            'generated' => 'badge-blue',
                                            'approved' => 'badge-green',
                                            'payment_made' => 'badge-green',
                                            'rejected' => 'badge-red',
                                            'cancelled' => 'badge-red',
                                            'reversed' => 'badge-gray',
                                            'payment_reversed' => 'badge-gray',
                                            'loan_disbursed' => 'badge-indigo',
                                            'loan_recovered' => 'badge-blue',
                                            'loan_waived' => 'badge-yellow',
                                        ];
                                        $actionValue = $log->action?->value ?? $log->action;
                                    @endphp
                                    <span class="badge {{ $actionColors[$actionValue] ?? 'badge-gray' }} text-xs">
                                        {{ ucfirst(str_replace('_', ' ', $actionValue)) }}
                                    </span>
                                </td>
                                <td class="table-td font-mono text-xs text-gray-500">
                                    {{ class_basename($log->reference_type) }} #{{ $log->reference_id }}
                                </td>
                                <td class="table-td text-right text-gray-700">
                                    {{ $log->amount ? '৳' . number_format($log->amount, 0) : '—' }}
                                </td>
                                <td class="table-td text-xs text-gray-400">{{ $log->old_status ?? '—' }}</td>
                                <td class="table-td text-xs text-gray-600">{{ $log->new_status ?? '—' }}</td>
                                <td class="table-td text-xs text-gray-300 font-mono">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-td text-center text-gray-400 py-8">
                                    No audit log entries for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($auditLogs?->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $auditLogs->links() }}</div>
            @endif
        @endif

    </div>
</div>
