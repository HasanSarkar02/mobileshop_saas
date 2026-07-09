<div class="max-w-lg mx-auto">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">{{ $supplier?->exists ? 'Edit Supplier' : 'New Supplier' }}</h2>
        </div>
        <form wire:submit="save" class="p-6 space-y-4">
            <div>
                <label class="label">Supplier Name *</label>
                <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                    placeholder="e.g. Samsung Bangladesh Distributor">
                @error('name')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Phone</label>
                    <input wire:model="phone" type="tel" class="input" placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="label">Email</label>
                    <input wire:model="email" type="email" class="input" placeholder="contact@supplier.com">
                </div>
            </div>
            <div>
                <label class="label">Address</label>
                <textarea wire:model="address" rows="3" class="input" placeholder="Supplier address…"></textarea>
            </div>
            {{-- Bank Details Section --}}
            <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Bank & Payment Details
                    <span class="text-xs font-normal text-gray-400">(optional — for bank transfers)</span>
                </h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label text-xs">Bank Name</label>
                        <input wire:model="bankName" type="text" class="input text-sm"
                            placeholder="e.g. Islami Bank, Dutch-Bangla">
                    </div>
                    <div>
                        <label class="label text-xs">Account Number</label>
                        <input wire:model="bankAccountNumber" type="text" class="input text-sm font-mono"
                            placeholder="Bank account number">
                    </div>
                    <div>
                        <label class="label text-xs">Bank Branch Name</label>
                        <input wire:model="bankBranchName" type="text" class="input text-sm"
                            placeholder="e.g. Motijheel Branch">
                    </div>
                    <div>
                        <label class="label text-xs">Routing Number</label>
                        <input wire:model="bankRoutingNumber" type="text" class="input text-sm font-mono"
                            placeholder="9-digit routing number">
                    </div>
                    <div>
                        <label class="label text-xs">Payment Terms</label>
                        <select wire:model="paymentTerms" class="input text-sm">
                            <option value="">Select…</option>
                            <option value="COD">COD (Cash on Delivery)</option>
                            <option value="Advance">Advance Payment</option>
                            <option value="Net 7">Net 7 Days</option>
                            <option value="Net 15">Net 15 Days</option>
                            <option value="Net 30">Net 30 Days</option>
                            <option value="Net 45">Net 45 Days</option>
                            <option value="Net 60">Net 60 Days</option>
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Credit Limit (৳)</label>
                        <input wire:model="creditLimit" type="number" min="0" step="1000"
                            class="input text-sm" placeholder="0 = unlimited">
                        <p class="text-xs text-gray-400 mt-0.5">Max credit allowed from this supplier</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $supplier?->exists ? 'Update' : 'Create' }} Supplier</span>
                    <span wire:loading>Saving…</span>
                </button>
                <a href="{{ route('suppliers.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
