<div class="max-w-xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Pay Employee</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $slip->employee_name }} ·
                {{ $slip->payrollRun?->monthName() }}
            </p>
        </div>
        <a href="{{ route('payroll.slip.show', $slip) }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    {{-- Outstanding Summary --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-gray-50">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Net Payable</div>
            <div class="text-lg font-bold text-gray-800">৳{{ number_format($slip->net_payable, 2) }}</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-xs font-semibold text-green-500 uppercase tracking-wider mb-1">Already Paid</div>
            <div class="text-lg font-bold text-green-700">৳{{ number_format($slip->total_paid, 2) }}</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Balance Due</div>
            <div class="text-lg font-bold text-red-700">৳{{ number_format($slip->balance_payable, 2) }}</div>
        </div>
    </div>

    {{-- Previous Payments --}}
    @if ($slip->activePayments->isNotEmpty())
        <div class="card p-4 bg-gray-50 border-gray-200">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Previous Payments</h3>
            @foreach ($slip->activePayments as $pmt)
                <div class="flex items-center justify-between text-sm py-1">
                    <span class="text-gray-600">
                        {{ $pmt->payment_date->format('d M Y') }} via {{ $pmt->paymentAccount?->name }}
                    </span>
                    <span class="font-semibold text-green-600">৳{{ number_format($pmt->amount, 0) }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Payment Form --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900">Record Payment</h3>

        {{-- Balance Warning --}}
        <div wire:show="showBalanceWarning" class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-800">
            ⚠ {{ $balanceWarningText }}
        </div>

        {{-- Accounting info --}}
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-sm text-indigo-800">
            💡 Dr Salary Payable (2030) / Cr
            {{ $paymentAccountId ? $this->paymentAccounts->find($paymentAccountId)?->name : 'Payment Account' }}
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Pay From Account *</label>
                <select wire:model.live="paymentAccountId"
                    class="input @error('paymentAccountId') input-error @enderror">
                    <option value="0">Select account…</option>
                    @foreach ($this->paymentAccounts as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </select>
                @if ($paymentAccountId && $accountBalance > 0)
                    <p class="text-xs text-green-600 mt-0.5">
                        Balance: ৳{{ number_format($accountBalance, 2) }}
                    </p>
                @endif
                @error('paymentAccountId')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Payment Method *</label>
                <select wire:model="paymentMethod" class="input">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="rocket">Rocket</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <label class="label">Amount (৳) *</label>
                    <button type="button" wire:click="payFull" class="text-xs text-indigo-600 hover:underline mb-1">
                        Pay full ৳{{ number_format($slip->balance_payable, 0) }}
                    </button>
                </div>
                <input wire:model.live="amount" type="number" step="0.01" min="0.01"
                    max="{{ $slip->balance_payable }}"
                    class="input text-lg font-bold @error('amount') input-error @enderror">
                @error('amount')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Payment Date *</label>
                <input wire:model="paymentDate" type="date"
                    class="input @error('paymentDate') input-error @enderror">
                @error('paymentDate')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Reference / Transaction ID <span
                        class="text-xs font-normal text-gray-400">(optional)</span></label>
                <input wire:model="referenceNumber" type="text" class="input" placeholder="Bank ref, bKash TxID…">
            </div>
            <div>
                <label class="label">Notes <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                <input wire:model="notes" type="text" class="input" placeholder="Optional…">
            </div>
        </div>

        @php $isPartial = $amount && (float)$amount < (float)$slip->balance_payable; @endphp
        @if ($isPartial)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                ℹ This is a <strong>partial payment</strong> of ৳{{ number_format((float) $amount, 0) }}.
                Balance remaining after this payment:
                ৳{{ number_format((float) $slip->balance_payable - (float) $amount, 0) }}
            </div>
        @endif

        <div class="flex gap-3">
            <button wire:click="save" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    💸 Record Payment of ৳{{ number_format((float) ($amount ?: 0), 0) }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2 justify-center">
                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            class="opacity-25" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                    Processing…
                </span>
            </button>
            <a href="{{ route('payroll.slip.show', $slip) }}" wire:navigate class="btn-secondary">Cancel</a>
        </div>
    </div>

</div>
