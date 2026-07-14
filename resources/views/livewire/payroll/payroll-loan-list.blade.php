<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Loans & Advances</h2>
            <p class="text-sm text-gray-500 mt-0.5">Disburse salary advances and loans. Auto-recovered in payroll.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="btn-primary">
            {{ $showForm ? '✕ Cancel' : '+ Disburse Loan / Advance' }}
        </button>
    </div>

    {{-- Stats --}}
    @php $stats = $this->stats; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Active Loans</div>
            <div class="text-2xl font-bold text-red-700">{{ $stats->total_active }}</div>
        </div>
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">Total Outstanding</div>
            <div class="text-2xl font-bold text-amber-700">৳{{ number_format($stats->total_outstanding, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-blue-50">
            <div class="text-xs font-semibold text-blue-500 uppercase tracking-wider mb-1">Total Disbursed</div>
            <div class="text-xl font-bold text-blue-700">৳{{ number_format($stats->total_disbursed, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-xs font-semibold text-green-500 uppercase tracking-wider mb-1">Fully Recovered</div>
            <div class="text-2xl font-bold text-green-700">{{ $stats->fully_recovered }}</div>
        </div>
    </div>

    {{-- Disburse Form --}}
    @if ($showForm)
        <div class="card p-6 border-2 border-indigo-200 space-y-4">
            <h3 class="font-semibold text-gray-900">New Loan / Advance Disbursement</h3>
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-sm text-indigo-800">
                💡 <strong>Accounting:</strong> Dr Salary Advance Receivable (1150) / Cr Payment Account.
                Recovery is automatic when payroll is processed.
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label">Employee *</label>
                    <select wire:model="selectedUserId" class="input @error('selectedUserId') input-error @enderror">
                        <option value="0">Select employee…</option>
                        @foreach ($this->employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedUserId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Type *</label>
                    <select wire:model="loanType" class="input">
                        <option value="advance">Salary Advance</option>
                        <option value="loan">Formal Loan</option>
                    </select>
                </div>
                <div>
                    <label class="label">Total Amount (৳) *</label>
                    <input wire:model.live="amount" type="number" min="1" step="0.01"
                        class="input @error('amount') input-error @enderror" placeholder="0.00">
                    @error('amount')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Monthly Deduction (৳) *</label>
                    <input wire:model="monthlyDeduction" type="number" min="1" step="0.01"
                        class="input @error('monthlyDeduction') input-error @enderror" placeholder="0.00">
                    @error('monthlyDeduction')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    @if ($amount && $monthlyDeduction && (float) $monthlyDeduction > 0)
                        <p class="text-xs text-indigo-500 mt-0.5">
                            ~{{ ceil((float) $amount / (float) $monthlyDeduction) }} months to recover
                        </p>
                    @endif
                </div>
                <div>
                    <label class="label">Disbursement Date *</label>
                    <input wire:model="disbursementDate" type="date"
                        class="input @error('disbursementDate') input-error @enderror">
                    @error('disbursementDate')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Pay From *</label>
                    <select wire:model="paymentAccountId"
                        class="input @error('paymentAccountId') input-error @enderror">
                        <option value="0">Select account…</option>
                        @foreach ($this->paymentAccounts as $pa)
                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                        @endforeach
                    </select>
                    @error('paymentAccountId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Purpose / Notes</label>
                    <input wire:model="purpose" type="text" class="input"
                        placeholder="Medical emergency, home repair, etc.">
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="disburse" class="btn-primary btn-sm" wire:loading.attr="disabled"
                    wire:target="disburse">
                    <span wire:loading.remove wire:target="disburse">
                        Disburse ৳{{ number_format((float) ($amount ?: 0), 0) }}
                    </span>
                    <span wire:loading wire:target="disburse">Processing…</span>
                </button>
                <button wire:click="$set('showForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex items-center gap-3 flex-wrap">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search employee…"
            class="input max-w-xs text-sm">
        <div class="flex gap-1">
            @foreach (['active' => 'Active', 'fully_recovered' => 'Recovered', 'waived' => 'Waived', '' => 'All'] as $val => $label)
                <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $statusFilter === $val ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Loan List --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Loan No.</th>
                        <th class="table-th">Employee</th>
                        <th class="table-th">Type</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th text-right">Outstanding</th>
                        <th class="table-th text-right">Monthly</th>
                        <th class="table-th">Disbursed</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->loans as $loan)
                        <tr class="hover:bg-gray-50" wire:key="loan-{{ $loan->id }}">
                            <td class="table-td font-mono text-indigo-600 text-sm">
                                {{ $loan->loan_number }}
                            </td>
                            <td class="table-td font-medium text-gray-900">
                                {{ $loan->user?->name }}
                            </td>
                            <td class="table-td">
                                <span
                                    class="badge {{ $loan->loan_type === 'advance' ? 'badge-blue' : 'badge-indigo' }} text-xs">
                                    {{ ucfirst($loan->loan_type) }}
                                </span>
                            </td>
                            <td class="table-td text-right font-semibold">
                                ৳{{ number_format($loan->total_amount, 0) }}
                            </td>
                            <td class="table-td text-right">
                                <span
                                    class="{{ $loan->outstanding_balance > 0 ? 'font-bold text-red-600' : 'text-gray-300' }}">
                                    {{ $loan->outstanding_balance > 0 ? '৳' . number_format($loan->outstanding_balance, 0) : '—' }}
                                </span>
                            </td>
                            <td class="table-td text-right text-gray-600">
                                ৳{{ number_format($loan->monthly_deduction, 0) }}/mo
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                {{ $loan->disbursement_date->format('d M Y') }}
                                <div class="text-gray-400">{{ $loan->disbursementAccount?->name }}</div>
                            </td>
                            <td class="table-td">
                                @php
                                    $statusBadge = match ($loan->status->value) {
                                        'active' => 'badge-red',
                                        'fully_recovered' => 'badge-green',
                                        'waived' => 'badge-yellow',
                                        default => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }} text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $loan->status->value)) }}
                                </span>
                                @if ($loan->purpose)
                                    <div class="text-xs text-gray-400 mt-0.5 max-w-[120px] truncate">
                                        {{ $loan->purpose }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td">
                                @if ($loan->status->value === 'active' && $loan->outstanding_balance > 0)
                                    <button wire:click="openWaiver({{ $loan->id }})"
                                        class="text-xs text-amber-500 hover:underline font-medium">
                                        Waive Balance
                                    </button>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-td text-center text-gray-400 py-10">
                                No {{ $statusFilter ?: '' }} loans found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->loans->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $this->loans->links() }}</div>
        @endif
    </div>

    {{-- Waiver Modal --}}
    @if ($showWaiverModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Waive Loan Balance</h3>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                    ⚠ Waiving the outstanding balance is irreversible.
                    The remaining amount will be written off and removed from payroll deductions.
                    A journal entry will be posted: Dr Misc Expense / Cr Advance Receivable.
                </div>
                <div>
                    <label class="label">Waiver Reason * <span class="text-xs font-normal text-gray-400">(min 10
                            characters)</span></label>
                    <textarea wire:model="waiverReason" rows="3" class="input"
                        placeholder="e.g. Employee resigned, balance written off per management decision…"></textarea>
                    @error('waiverReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="waive" class="btn-danger flex-1" wire:loading.attr="disabled"
                        wire:target="waive">
                        <span wire:loading.remove wire:target="waive">Waive Balance</span>
                        <span wire:loading wire:target="waive">Processing…</span>
                    </button>
                    <button wire:click="$set('showWaiverModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
