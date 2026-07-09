<div class="max-w-2xl mx-auto">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">New Expense</h2>
        </div>
        <form wire:submit="save" class="p-6 space-y-4">
            @if (session('balance_warning'))
                <div class="card p-4 bg-amber-50 border-amber-300">
                    <div class="text-sm font-medium text-amber-800">
                        ⚠ {{ session('balance_warning') }}
                    </div>
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Category *</label>
                    <select wire:model="categoryId" class="input @error('categoryId') input-error @enderror">
                        <option value="0">Select category…</option>
                        @foreach ($this->categories as $cat)
                            <option value="{{ $cat->id }}">
                                {{ $cat->parent_id ? '  › ' : '' }}{{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoryId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Expense Date *</label>
                    <input wire:model="expenseDate" type="date"
                        class="input @error('expenseDate') input-error @enderror">
                    @error('expenseDate')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Description *</label>
                    <input wire:model="description" type="text"
                        class="input @error('description') input-error @enderror"
                        placeholder="e.g. Monthly shop rent — June 2026">
                    @error('description')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Amount (৳) *</label>
                    <input wire:model="amount" type="number" step="0.01" min="0.01"
                        class="input font-semibold @error('amount') input-error @enderror" placeholder="0.00">
                    @error('amount')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Paid From *</label>
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
                <div>
                    <label class="label">Branch</label>
                    <select wire:model="branchId" class="input">
                        @foreach ($this->branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Reference Number</label>
                    <input wire:model="referenceNumber" type="text" class="input"
                        placeholder="Invoice / receipt number">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Notes</label>
                    <textarea wire:model="notes" rows="2" class="input" placeholder="Additional notes…"></textarea>
                </div>

                {{-- Receipt Upload --}}
                <div class="sm:col-span-2">
                    <label class="label">Receipt Photo (optional)</label>
                    @if ($receipt)
                        <img src="{{ $receipt->temporaryUrl() }}" class="h-24 rounded-xl object-cover mb-2 border">
                    @endif
                    <input wire:model="receipt" type="file" accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-gray-50 file:text-gray-700 cursor-pointer">
                    @error('receipt')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="receipt" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save Expense</span>
                    <span wire:loading>Saving…</span>
                </button>
                <a href="{{ route('expenses.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
