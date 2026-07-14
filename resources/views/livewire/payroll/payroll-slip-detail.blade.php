<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold text-gray-900">{{ $slip->employee_name }}</h2>
                <span class="badge {{ $slip->status->badgeClass() }}">{{ $slip->status->label() }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $slip->payrollRun?->monthName() }}
                @if ($slip->designation)
                    · {{ $slip->designation }}
                @endif
                @if ($slip->department_name)
                    · {{ $slip->department_name }}
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            {{-- Print Payslip --}}
            <a href="{{ route('documents.payroll-slip', $slip) }}" target="_blank" class="btn-secondary btn-sm">🖨 Print
                Payslip</a>

            @if ($slip->status->canAcceptPayment())
                @can('payroll.pay')
                    <a href="{{ route('payroll.pay', $slip) }}" wire:navigate class="btn-primary btn-sm">
                        💸 Pay ৳{{ number_format($slip->balance_payable, 0) }}
                    </a>
                @endcan
            @endif

            <a href="{{ route('payroll.run.show', $slip->payrollRun) }}" wire:navigate class="btn-secondary btn-sm">←
                Run</a>
        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Gross Earnings</div>
            <div class="text-2xl font-bold text-indigo-800">৳{{ number_format($slip->gross_earnings, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Deductions</div>
            <div class="text-2xl font-bold text-red-700">৳{{ number_format($slip->total_deductions, 0) }}</div>
        </div>
        <div class="card p-4 border-0 {{ (float) $slip->balance_payable > 0 ? 'bg-amber-50' : 'bg-green-50' }}">
            <div
                class="text-xs font-semibold {{ (float) $slip->balance_payable > 0 ? 'text-amber-500' : 'text-green-500' }} uppercase tracking-wider mb-1">
                {{ (float) $slip->balance_payable > 0 ? 'Balance Due' : 'Net Payable' }}
            </div>
            <div
                class="text-2xl font-bold {{ (float) $slip->balance_payable > 0 ? 'text-amber-700' : 'text-green-700' }}">
                ৳{{ number_format((float) $slip->balance_payable > 0 ? $slip->balance_payable : $slip->net_payable, 0) }}
            </div>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5">

        {{-- Earnings Breakdown --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-green-50 border-b border-green-100">
                <h3 class="font-semibold text-green-800 text-sm">💰 Earnings</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($slip->earnings as $earning)
                    <div class="px-5 py-2.5 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $earning->component_name }}</div>
                            @if ($earning->calculation_basis)
                                <div class="text-xs text-gray-400">{{ $earning->calculation_basis }}</div>
                            @endif
                        </div>
                        <div class="font-semibold text-green-700">৳{{ number_format($earning->computed_value, 0) }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t-2 border-green-200 bg-green-50 flex justify-between">
                <span class="font-bold text-green-900">Gross Total</span>
                <span class="font-bold text-green-800">৳{{ number_format($slip->gross_earnings, 2) }}</span>
            </div>
        </div>

        {{-- Deductions Breakdown --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-red-50 border-b border-red-100">
                <h3 class="font-semibold text-red-800 text-sm">📤 Deductions</h3>
            </div>
            @if ($slip->deductions->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach ($slip->deductions as $deduction)
                        <div class="px-5 py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $deduction->component_name }}</div>
                                @if ($deduction->calculation_basis)
                                    <div class="text-xs text-gray-400">{{ $deduction->calculation_basis }}</div>
                                @endif
                            </div>
                            <div class="font-semibold text-red-600">
                                -৳{{ number_format($deduction->computed_value, 0) }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-4 text-sm text-gray-400">No deductions.</div>
            @endif
            <div class="px-5 py-3 border-t-2 border-red-200 bg-red-50 flex justify-between">
                <span class="font-bold text-red-900">Total Deductions</span>
                <span class="font-bold text-red-800">-৳{{ number_format($slip->total_deductions, 2) }}</span>
            </div>
            <div class="px-5 py-4 bg-gray-900 text-white flex justify-between items-center">
                <span class="font-bold text-lg">NET PAYABLE</span>
                <span class="font-bold text-2xl">৳{{ number_format($slip->net_payable, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Loan Recoveries --}}
    @if ($slip->loanRecoveries->isNotEmpty())
        <div class="card p-4 bg-blue-50 border-blue-200">
            <h3 class="font-semibold text-blue-800 text-sm mb-2">Loan Recovery Breakdown</h3>
            @foreach ($slip->loanRecoveries as $recovery)
                <div class="flex items-center justify-between text-sm py-1">
                    <span class="text-blue-700">
                        {{ $recovery->loan?->loan_number }} ({{ ucfirst($recovery->loan?->loan_type) }})
                    </span>
                    <div class="text-right">
                        <span class="font-semibold">৳{{ number_format($recovery->amount_recovered, 0) }}</span>
                        <span class="text-xs text-blue-500 ml-2">Remaining:
                            ৳{{ number_format($recovery->balance_after, 0) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Payment History --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Payment History</h3>
            @if ($slip->status->canAcceptPayment())
                @can('payroll.pay')
                    <a href="{{ route('payroll.pay', $slip) }}" wire:navigate class="btn-primary btn-sm">+ Record
                        Payment</a>
                @endcan
            @endif
        </div>

        @php
            $allPayments = \App\Models\PayrollPayment::where('slip_id', $slip->id)
                ->with(['paymentAccount', 'reversedBy'])
                ->orderByDesc('payment_date')
                ->get();
        @endphp

        @if ($allPayments->isEmpty())
            <div class="p-6 text-center text-gray-400 text-sm">No payments recorded yet.</div>
        @else
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Payment No.</th>
                        <th class="table-th">Date</th>
                        <th class="table-th">Via</th>
                        <th class="table-th">Method</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($allPayments as $pmt)
                        <tr class="{{ $pmt->status === 'reversed' ? 'opacity-50 bg-gray-50' : 'hover:bg-gray-50' }}"
                            wire:key="pmt-{{ $pmt->id }}">
                            <td class="table-td font-mono text-indigo-600 text-sm">{{ $pmt->payment_number }}</td>
                            <td class="table-td text-gray-500 text-sm">{{ $pmt->payment_date->format('d M Y') }}</td>
                            <td class="table-td text-sm text-gray-700">{{ $pmt->paymentAccount?->name }}</td>
                            <td class="table-td text-xs capitalize text-gray-500">
                                {{ str_replace('_', ' ', $pmt->payment_method) }}
                            </td>
                            <td
                                class="table-td text-right font-bold {{ $pmt->status === 'reversed' ? 'line-through text-gray-400' : 'text-green-600' }}">
                                ৳{{ number_format($pmt->amount, 2) }}
                            </td>
                            <td class="table-td">
                                <span
                                    class="badge {{ $pmt->status === 'paid' ? 'badge-green' : 'badge-gray' }} text-xs">
                                    {{ ucfirst($pmt->status) }}
                                </span>
                                @if ($pmt->status === 'reversed')
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        by {{ $pmt->reversedBy?->name }} {{ $pmt->reversed_at?->format('d M') }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td">
                                @if ($pmt->status === 'paid')
                                    @can('payroll.reverse')
                                        <button wire:click="openReversePayment({{ $pmt->id }})"
                                            class="text-xs text-red-400 hover:underline font-medium">
                                            Reverse
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="4" class="table-td font-bold">Total Paid</td>
                        <td class="table-td text-right font-bold text-green-700">
                            ৳{{ number_format($slip->total_paid, 2) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    {{-- Reverse Payment Modal --}}
    @if ($showReverseModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Reverse Payment</h3>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                    A reversal journal entry will be posted dated today.
                    The balance will be restored to this employee's slip.
                    Any loan recoveries from this payment will also be restored.
                </div>
                <div>
                    <label class="label">Reason *</label>
                    <textarea wire:model="reversalReason" rows="3" class="input"
                        placeholder="e.g. Paid to wrong account, bank transfer failed…"></textarea>
                    @error('reversalReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="reversePayment" class="btn-danger flex-1" wire:loading.attr="disabled"
                        wire:target="reversePayment">
                        <span wire:loading.remove>Reverse Payment</span>
                        <span wire:loading>Processing…</span>
                    </button>
                    <button wire:click="$set('showReverseModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
