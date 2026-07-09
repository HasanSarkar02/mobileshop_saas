<div class="max-w-3xl mx-auto space-y-5">
    <div class="card p-5 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">New Treasury Transaction</h2>
        <a href="{{ route('treasury.index') }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    <form wire:submit="save" class="space-y-5">

        {{-- Step 1: Category --}}
        <div class="card p-5 space-y-4">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">
                Step 1 — Select Category
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                @foreach ($this->typesByCategory as $catValue => $catData)
                    <button type="button" wire:click="$set('transactionCategory', '{{ $catValue }}')"
                        class="p-3 rounded-xl text-xs font-semibold text-center transition-all border-2
                            {{ $transactionCategory === $catValue
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                : 'border-gray-200 text-gray-600 hover:border-indigo-300' }}">
                        {{ $catData['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Step 2: Transaction Type --}}
        <div wire:show="transactionCategory !== ''" class="card p-5 space-y-4">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">
                Step 2 — Select Transaction Type
            </h3>
            @foreach ($this->typesByCategory as $catValue => $catData)
                <div wire:show="transactionCategory === '{{ $catValue }}'" class="space-y-2">
                    @foreach ($catData['types'] as $type)
                        <label
                            class="flex items-center gap-3 cursor-pointer group p-2.5 rounded-xl
                            {{ $transactionType === $type->value ? 'bg-indigo-50 border border-indigo-200' : 'hover:bg-gray-50 border border-transparent' }}">
                            <input wire:model.live="transactionType" type="radio" value="{{ $type->value }}"
                                class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $type->icon() }} {{ $type->label() }}
                                </div>
                                @if ($type->alwaysRequiresApproval())
                                    <div class="text-xs text-amber-600">⚠ Always requires Owner approval</div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Step 3: Transaction Details --}}
        <div wire:show="transactionType !== ''" class="card p-6 space-y-5">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">
                Step 3 — Transaction Details
            </h3>

            {{-- Approval Warning --}}
            @if ($this->willRequireApproval)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                    ⏳ This transaction will be sent for <strong>Owner approval</strong> before the accounting
                    journal is posted.
                    @if ($this->typeEnum?->alwaysRequiresApproval())
                        This transaction type always requires approval.
                    @else
                        Amount exceeds the approval threshold.
                    @endif
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">

                {{-- From Account --}}
                <div wire:show="needsFromAccount">
                    <label class="label">
                        From Account *
                        @if ($transactionType === 'owner_drawings')
                            <span class="text-xs font-normal text-gray-400">(account to withdraw from)</span>
                        @elseif($transactionType === 'bank_charge')
                            <span class="text-xs font-normal text-gray-400">(bank account charged)</span>
                        @elseif(in_array($transactionType, ['cash_over', 'cash_short']))
                            <span class="text-xs font-normal text-gray-400">(account to adjust)</span>
                        @endif
                    </label>
                    <select wire:model.live="fromAccountId" class="input">
                        <option value="0">Select account…</option>
                        @foreach ($this->paymentAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} ({{ ucfirst($acc->provider ?? 'other') }})
                            </option>
                        @endforeach
                    </select>

                    {{-- Real-time balance display ──────────────────────────── --}}
                    @if ($fromAccountBalance !== null)
                        @php
                            $amtNeeded = (float) ($amount ?: 0);
                            $short = $fromAccountBalance < $amtNeeded && $amtNeeded > 0;
                        @endphp
                        <p
                            class="text-xs mt-1 flex items-center gap-1
                            {{ $short ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                            @if ($short)
                                ⚠ Insufficient —
                            @else
                                ✓
                            @endif
                            Available: <strong>৳{{ number_format($fromAccountBalance, 2) }}</strong>
                            @if ($short)
                                · Short by ৳{{ number_format($amtNeeded - $fromAccountBalance, 2) }}
                            @endif
                        </p>
                    @endif
                </div>

                {{-- To Account --}}
                <div wire:show="needsToAccount">
                    <label class="label">
                        To Account *
                        @if ($transactionType === 'owner_capital')
                            <span class="text-xs font-normal text-gray-400">(account to deposit into)</span>
                        @elseif($transactionType === 'opening_balance')
                            <span class="text-xs font-normal text-gray-400">(account to set opening balance for)</span>
                        @endif
                    </label>
                    <select wire:model.live="toAccountId" class="input">
                        <option value="0">Select account…</option>
                        @foreach ($this->paymentAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} ({{ ucfirst($acc->provider ?? 'other') }})
                            </option>
                        @endforeach
                    </select>
                    @if ($toAccountBalance !== null)
                        <p class="text-xs text-gray-400 mt-1">
                            Current balance: ৳{{ number_format($toAccountBalance, 2) }}
                        </p>
                    @endif
                </div>

                {{-- Amount --}}
                <div>
                    <label class="label">
                        @if ($transactionType === 'loan_repayment')
                            Principal Amount (৳) *
                        @else
                            Amount (৳) *
                        @endif
                    </label>
                    <input wire:model.live="amount" type="number" step="0.01" min="0.01"
                        class="input font-semibold text-lg @error('amount') input-error @enderror" placeholder="0.00">
                    @error('amount')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fee --}}
                <div wire:show="needsFee">
                    <label class="label">{{ $feeLabelText }}</label>
                    <input wire:model.live="feeAmount" type="number" step="0.01" min="0"
                        class="input @error('feeAmount') input-error @enderror" placeholder="0.00">
                    @error('feeAmount')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    @if ((float) ($amount ?: 0) > 0 && (float) ($feeAmount ?: 0) > 0)
                        <p class="text-xs text-gray-400 mt-0.5">
                            Net
                            @if ($transactionType === 'loan_repayment')
                                total payment
                            @else
                                amount received
                            @endif:
                            <strong>৳{{ number_format($this->netAmount, 2) }}</strong>
                            @if ($transactionType === 'loan_repayment')
                                (Principal: ৳{{ number_format((float) $amount, 2) }} + Interest:
                                ৳{{ number_format((float) ($feeAmount ?: 0), 2) }})
                            @endif
                        </p>
                    @endif
                </div>

                {{-- Transaction Date --}}
                <div>
                    <label class="label">
                        @if ($transactionType === 'opening_balance')
                            As Of Date *
                        @else
                            Transaction Date *
                        @endif
                    </label>
                    <input wire:model="transactionDate" type="date"
                        class="input @error('transactionDate') input-error @enderror">
                    @error('transactionDate')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reference Number --}}
                <div>
                    <label class="label">
                        @if (in_array($transactionType, ['bank_deposit', 'bank_withdrawal', 'bank_charge']))
                            Bank Reference No.
                        @elseif($transactionType === 'loan_receipt')
                            Loan Agreement No.
                        @else
                            Reference Number
                        @endif
                        <span class="text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <input wire:model="referenceNumber" type="text" class="input"
                        placeholder="Cheque no., bank ref, etc.">
                </div>

                {{-- Third Party --}}
                <div wire:show="needsThirdParty">
                    <label class="label">
                        @if (in_array($transactionType, ['loan_receipt', 'loan_repayment']))
                            Lender / Bank Name *
                        @elseif(in_array($transactionType, ['partner_investment', 'partner_withdrawal']))
                            Partner Name *
                        @else
                            Third Party Name
                        @endif
                    </label>
                    <input wire:model="thirdPartyName" type="text" class="input"
                        placeholder="Name of lender, partner, institution…">
                </div>

                {{-- Branch --}}
                @if ($this->branches->count() > 1)
                    <div>
                        <label class="label">Branch *</label>
                        <select wire:model="branchId" class="input">
                            @foreach ($this->branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Description --}}
                <div class="sm:col-span-2">
                    <label class="label">Description *
                        <span class="text-xs font-normal text-gray-400">(narration / memo — required for audit
                            trail)</span>
                    </label>
                    <input wire:model="description" type="text"
                        class="input @error('description') input-error @enderror"
                        placeholder="Describe this transaction clearly…">
                    @error('description')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div class="sm:col-span-2">
                    <label class="label">Internal Notes <span
                            class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <textarea wire:model="notes" rows="2" class="input" placeholder="Additional context for accounting records…"></textarea>
                </div>

                {{-- Attachment --}}
                <div class="sm:col-span-2">
                    <label class="label">Attachment <span class="text-xs font-normal text-gray-400">(receipt, bank
                            slip, agreement — optional)</span></label>
                    @if ($attachment)
                        <div class="text-xs text-green-600 mb-1">✓ File selected:
                            {{ $attachment->getClientOriginalName() }}</div>
                    @endif
                    <input wire:model="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf"
                        class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-gray-50 file:text-gray-700 cursor-pointer">
                    <div wire:loading wire:target="attachment" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div wire:show="transactionType !== ''" class="flex gap-3 pb-8">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    @if ($willRequireApproval)
                        Submit for Approval
                    @else
                        Post Transaction
                    @endif
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            class="opacity-25" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                    Processing…
                </span>
            </button>
            <a href="{{ route('treasury.index') }}" wire:navigate class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
