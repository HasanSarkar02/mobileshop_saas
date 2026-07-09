<div class="max-w-2xl mx-auto space-y-5">
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Edit Draft</h2>
            <div class="text-sm text-gray-500 font-mono mt-0.5">
                {{ $transaction->transaction_number }} ·
                {{ $transaction->transaction_type->label() }}
            </div>
        </div>
        <a href="{{ route('treasury.show', $transaction) }}" wire:navigate class="btn-secondary btn-sm">← Cancel</a>
    </div>

    <div class="card p-4 bg-amber-50 border-amber-200">
        <p class="text-sm text-amber-800">
            <strong>Draft mode:</strong> You can edit amounts, dates, description and notes.
            Accounts and transaction type cannot be changed — create a new transaction if needed.
        </p>
    </div>

    <form wire:submit="save" class="card p-6 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Amount (৳) *</label>
                <input wire:model="amount" type="number" step="0.01" min="0.01"
                    class="input font-semibold text-lg @error('amount') input-error @enderror">
                @error('amount')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            @if ($transaction->transaction_type->needsFee())
                <div>
                    <label class="label">{{ $transaction->transaction_type->feeLabel() }}</label>
                    <input wire:model="feeAmount" type="number" step="0.01" min="0" class="input">
                    @error('feeAmount')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
            @endif
            <div>
                <label class="label">Transaction Date *</label>
                <input wire:model="transactionDate" type="date"
                    class="input @error('transactionDate') input-error @enderror">
                @error('transactionDate')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            @if ($transaction->transaction_type->needsThirdParty())
                <div>
                    <label class="label">Third Party Name</label>
                    <input wire:model="thirdPartyName" type="text" class="input">
                </div>
            @endif
            <div>
                <label class="label">Reference Number</label>
                <input wire:model="referenceNumber" type="text" class="input">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Description *</label>
                <input wire:model="description" type="text"
                    class="input @error('description') input-error @enderror">
                @error('description')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="label">Notes</label>
                <textarea wire:model="notes" rows="2" class="input"></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Additional Attachment</label>
                <input wire:model="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-gray-50 file:text-gray-700 cursor-pointer">
                @error('attachment')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading>Saving…</span>
            </button>
            <a href="{{ route('treasury.show', $transaction) }}" wire:navigate class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
