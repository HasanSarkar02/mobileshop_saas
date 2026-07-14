<div class="max-w-5xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-bold text-gray-900 font-mono">{{ $run->run_number }}</h2>
                    <span class="badge {{ $run->status->badgeClass() }} text-sm">
                        {{ $run->status->label() }}
                    </span>
                </div>
                <div class="text-gray-600 font-semibold mt-1">{{ $run->monthName() }}</div>
                <div class="text-sm text-gray-400 mt-0.5">
                    {{ $run->branch?->name ?? 'All Branches' }}
                    @if ($run->department)
                        · {{ $run->department->name }}
                    @endif
                    · {{ $run->total_employees }} employees
                </div>
                @if ($run->description)
                    <div class="text-xs text-gray-400 mt-1 italic">{{ $run->description }}</div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-2 shrink-0">
                @if ($this->canSubmit)
                    <button wire:click="submit" wire:confirm="Submit {{ $run->run_number }} for Owner approval?"
                        wire:loading.attr="disabled" wire:target="submit" class="btn-primary btn-sm">
                        <span wire:loading.remove wire:target="submit">📤 Submit for Approval</span>
                        <span wire:loading wire:target="submit">Submitting…</span>
                    </button>
                @endif

                @if ($this->canApprove)
                    <button wire:click="approve"
                        wire:confirm="Approve {{ $run->run_number }}? This will post the salary journal entry."
                        wire:loading.attr="disabled" wire:target="approve" class="btn-success btn-sm">
                        <span wire:loading.remove wire:target="approve">✓ Approve & Post Journal</span>
                        <span wire:loading wire:target="approve">Approving…</span>
                    </button>
                @endif

                @if ($this->canCancel)
                    <button wire:click="$set('showCancelModal', true)" class="btn-secondary btn-sm">
                        ✕ Cancel Run
                    </button>
                @endif

                @if ($this->canReverse)
                    <button wire:click="$set('showReverseModal', true)" class="btn-danger btn-sm">
                        ↩ Reverse Run
                    </button>
                @endif

                <a href="{{ route('payroll.runs') }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Gross Earnings</div>
            <div class="text-xl font-bold text-indigo-800">৳{{ number_format($run->total_gross_earnings, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Total Deductions</div>
            <div class="text-xl font-bold text-red-700">৳{{ number_format($run->total_deductions, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-gray-50">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Net Payable</div>
            <div class="text-xl font-bold text-gray-900">৳{{ number_format($run->total_net_payable, 0) }}</div>
        </div>
        <div class="card p-4 border-0 {{ (float) $run->total_paid > 0 ? 'bg-green-50' : 'bg-gray-50' }}">
            <div
                class="text-xs font-semibold {{ (float) $run->total_paid > 0 ? 'text-green-500' : 'text-gray-500' }} uppercase tracking-wider mb-1">
                Paid</div>
            <div class="text-xl font-bold {{ (float) $run->total_paid > 0 ? 'text-green-700' : 'text-gray-400' }}">
                ৳{{ number_format($run->total_paid, 0) }}
            </div>
            @php $remaining = (float)$run->total_net_payable - (float)$run->total_paid; @endphp
            @if ($remaining > 0)
                <div class="text-xs text-red-500 mt-0.5">Balance: ৳{{ number_format($remaining, 0) }}</div>
            @endif
        </div>
    </div>

    {{-- Approval Timeline --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-3">Status Timeline</h3>
        <div class="space-y-2">
            <div class="flex items-center gap-3 text-sm">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                    <span class="text-green-600 text-xs">✓</span>
                </div>
                <span>Generated by <strong>{{ $run->generatedBy?->name }}</strong></span>
                <span class="text-gray-400">{{ $run->created_at->format('d M Y H:i') }}</span>
            </div>
            @if ($run->reviewed_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                        <span class="text-yellow-600 text-xs">→</span>
                    </div>
                    <span>Submitted for review by <strong>{{ $run->reviewedBy?->name }}</strong></span>
                    <span class="text-gray-400">{{ $run->reviewed_at->format('d M Y H:i') }}</span>
                </div>
            @endif
            @if ($run->approved_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <span class="text-green-600 text-xs">✓</span>
                    </div>
                    <span>Approved & journal posted by <strong>{{ $run->approvedBy?->name }}</strong></span>
                    <span class="text-gray-400">{{ $run->approved_at->format('d M Y H:i') }}</span>
                </div>
            @endif
            @if ($run->cancelled_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <span class="text-red-600 text-xs">✗</span>
                    </div>
                    <span>Cancelled by <strong>{{ $run->cancelledBy?->name }}</strong></span>
                    <span class="text-gray-400">{{ $run->cancelled_at->format('d M Y H:i') }}</span>
                    @if ($run->cancellation_reason)
                        <span class="text-gray-500 text-xs">— {{ $run->cancellation_reason }}</span>
                    @endif
                </div>
            @endif
            @if ($run->reversed_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                        <span class="text-gray-600 text-xs">↩</span>
                    </div>
                    <span>Reversed by <strong>{{ $run->reversedBy?->name }}</strong></span>
                    <span class="text-gray-400">{{ $run->reversed_at->format('d M Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Slip List --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Employee Payslips</h3>
            @if ($run->status->canAcceptPayments())
                <a href="#slips" class="text-xs text-indigo-600">
                    Scroll to pay individual employees ↓
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" id="slips">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Employee</th>
                        <th class="table-th">Dept / Designation</th>
                        <th class="table-th text-right">Gross</th>
                        <th class="table-th text-right">Deductions</th>
                        <th class="table-th text-right">Net</th>
                        <th class="table-th text-right">Paid</th>
                        <th class="table-th text-right">Balance</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($run->slips as $slip)
                        <tr class="hover:bg-gray-50" wire:key="slip-{{ $slip->id }}">
                            <td class="table-td font-semibold text-gray-900">
                                {{ $slip->employee_name }}
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                <div>{{ $slip->department_name ?? '—' }}</div>
                                <div>{{ $slip->designation ?? '—' }}</div>
                            </td>
                            <td class="table-td text-right text-gray-700">
                                ৳{{ number_format($slip->gross_earnings, 0) }}
                            </td>
                            <td class="table-td text-right text-red-500">
                                {{ (float) $slip->total_deductions > 0 ? '-৳' . number_format($slip->total_deductions, 0) : '—' }}
                            </td>
                            <td class="table-td text-right font-bold text-gray-900">
                                ৳{{ number_format($slip->net_payable, 0) }}
                            </td>
                            <td class="table-td text-right text-green-600">
                                {{ (float) $slip->total_paid > 0 ? '৳' . number_format($slip->total_paid, 0) : '—' }}
                            </td>
                            <td class="table-td text-right">
                                <span
                                    class="{{ (float) $slip->balance_payable > 0 ? 'font-bold text-red-600' : 'text-gray-300' }}">
                                    {{ (float) $slip->balance_payable > 0 ? '৳' . number_format($slip->balance_payable, 0) : '✓' }}
                                </span>
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $slip->status->badgeClass() }} text-xs">
                                    {{ $slip->status->label() }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    @if (in_array($run->status->value, ['approved', 'processing_payment', 'partially_paid', 'paid']))
                                        <a href="{{ route('documents.payroll-register', $run) }}" target="_blank"
                                            class="btn-secondary btn-sm">🖨 Payroll Register</a>
                                    @endif
                                    <a href="{{ route('payroll.slip.show', $slip) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">
                                        View
                                    </a>
                                    @if ($slip->status->canAcceptPayment())
                                        @can('payroll.pay')
                                            <a href="{{ route('payroll.pay', $slip) }}" wire:navigate
                                                class="text-xs text-green-600 hover:underline font-medium">
                                                Pay
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="2" class="table-td font-bold">Total ({{ $run->total_employees }} employees)
                        </td>
                        <td class="table-td text-right font-bold text-indigo-700">
                            ৳{{ number_format($run->total_gross_earnings, 0) }}</td>
                        <td class="table-td text-right font-bold text-red-600">
                            -৳{{ number_format($run->total_deductions, 0) }}</td>
                        <td class="table-td text-right font-bold text-gray-900 text-base">
                            ৳{{ number_format($run->total_net_payable, 0) }}</td>
                        <td class="table-td text-right font-bold text-green-700">
                            ৳{{ number_format($run->total_paid, 0) }}</td>
                        <td class="table-td text-right font-bold text-red-700">
                            {{ (float) $run->total_net_payable - (float) $run->total_paid > 0 ? '৳' . number_format((float) $run->total_net_payable - (float) $run->total_paid, 0) : '✓ Fully Paid' }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Journal Entry --}}
    @if ($run->journalEntry)
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                <h3 class="font-semibold text-indigo-800 text-sm">
                    Approval Journal Entry
                    <span class="font-mono text-indigo-600 ml-2">{{ $run->journalEntry->entry_number ?? '' }}</span>
                </h3>
                <span class="text-xs text-indigo-500">{{ $run->approved_at?->format('d M Y H:i') }}</span>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Account</th>
                        <th class="table-th">Code</th>
                        <th class="table-th">Description</th>
                        <th class="table-th text-right">Debit (৳)</th>
                        <th class="table-th text-right">Credit (৳)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($run->journalEntry->lines as $line)
                        <tr>
                            <td class="table-td text-sm font-medium text-gray-900">{{ $line->account?->name }}</td>
                            <td class="table-td font-mono text-xs text-gray-400">{{ $line->account?->code }}</td>
                            <td class="table-td text-xs text-gray-500">{{ $line->description }}</td>
                            <td
                                class="table-td text-right font-semibold {{ $line->debit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ $line->debit > 0 ? '৳' . number_format($line->debit, 2) : '—' }}
                            </td>
                            <td
                                class="table-td text-right font-semibold {{ $line->credit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ $line->credit > 0 ? '৳' . number_format($line->credit, 2) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="3" class="table-td font-bold">Totals</td>
                        <td class="table-td text-right font-bold text-indigo-700">
                            ৳{{ number_format($run->journalEntry->lines->sum('debit'), 2) }}
                        </td>
                        <td class="table-td text-right font-bold text-indigo-700">
                            ৳{{ number_format($run->journalEntry->lines->sum('credit'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Cancel Modal --}}
    @if ($showCancelModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Cancel Payroll Run</h3>
                <div>
                    <label class="label">Reason *</label>
                    <textarea wire:model="cancelReason" rows="3" class="input" placeholder="Why is this run being cancelled?"></textarea>
                    @error('cancelReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="cancel" class="btn-danger flex-1" wire:loading.attr="disabled"
                        wire:target="cancel">Cancel Run</button>
                    <button wire:click="$set('showCancelModal', false)" class="btn-secondary">Back</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Reverse Modal --}}
    @if ($showReverseModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Reverse Payroll Run</h3>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                    <strong>⚠ This will:</strong><br>
                    • Post a reversal journal entry (dated today)<br>
                    • Mark all slips as Reversed<br>
                    • All individual payments must already be reversed first<br>
                    • This action cannot be undone
                </div>
                <div>
                    <label class="label">Reversal Reason * <span class="text-xs font-normal text-gray-400">(min 10
                            characters)</span></label>
                    <textarea wire:model="reversalReason" rows="3" class="input"
                        placeholder="e.g. Incorrect salary components used — regenerating with corrected structures…"></textarea>
                    @error('reversalReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="reverse" class="btn-danger flex-1" wire:loading.attr="disabled"
                        wire:target="reverse">
                        <span wire:loading.remove>Post Reversal</span>
                        <span wire:loading>Processing…</span>
                    </button>
                    <button wire:click="$set('showReverseModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
