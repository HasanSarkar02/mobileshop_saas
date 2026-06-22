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
