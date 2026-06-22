<div class="max-w-2xl mx-auto">
    @if ($success)
        <div class="card p-8 text-center space-y-4">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Shop Created!</h3>
            <p class="text-gray-500 text-sm">{{ $successMessage }}</p>
            <div class="flex gap-3 justify-center pt-2">
                <a href="{{ route('admin.shops.index') }}" wire:navigate class="btn-secondary">View All Shops</a>
                <button wire:click="createAnother" class="btn-primary">Create Another</button>
            </div>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">New Shop</h2>
                <p class="text-sm text-gray-500 mt-0.5">The owner will receive an email invite to set their password.
                </p>
            </div>
            <form wire:submit="save" class="p-6 space-y-5">
                {{-- Shop Info --}}
                <fieldset class="space-y-4">
                    <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Shop Details</legend>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Shop Name *</label>
                            <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                                placeholder="Rahman Mobile">
                            @error('name')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Business Type *</label>
                            <select wire:model="businessType" class="input">
                                <option value="mobile_shop">Mobile Shop</option>
                                <option value="electronics">Electronics</option>
                                <option value="general_retail">General Retail</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <textarea wire:model="address" rows="2" class="input" placeholder="Shop address…"></textarea>
                    </div>
                </fieldset>

                <hr class="border-gray-100">

                {{-- Owner Info --}}
                <fieldset class="space-y-4">
                    <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Owner Details</legend>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Owner Name *</label>
                            <input wire:model="ownerName" type="text"
                                class="input @error('ownerName') input-error @enderror" placeholder="Mohammad Rahman">
                            @error('ownerName')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Owner Phone</label>
                            <input wire:model="phone" type="tel" class="input" placeholder="01XXXXXXXXX">
                        </div>
                    </div>
                    <div>
                        <label class="label">Owner Email * <span class="text-xs font-normal text-gray-400">(invite will
                                be sent here)</span></label>
                        <input wire:model="email" type="email" class="input @error('email') input-error @enderror"
                            placeholder="owner@example.com">
                        @error('email')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                <hr class="border-gray-100">

                {{-- Trial & VAT --}}
                <fieldset class="space-y-4">
                    <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Plan & Tax</legend>
                    <div>
                        <label class="label">Trial Duration *</label>
                        <div class="flex items-center gap-2">
                            <input wire:model="trialDays" type="number" min="1" max="90"
                                class="input w-24">
                            <span class="text-sm text-gray-500">days</span>
                        </div>
                        @error('trialDays')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input wire:model.live="vatEnabled" type="checkbox" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 peer-checked:after:translate-x-4 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-transform">
                            </div>
                        </label>
                        <span class="text-sm text-gray-700">Enable VAT for this shop</span>
                    </div>
                    @if ($vatEnabled)
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">VAT Registration Number</label>
                                <input wire:model="vatRegistrationNumber" type="text" class="input"
                                    placeholder="VAT Reg. No.">
                            </div>
                            <div>
                                <label class="label">Default VAT Rate (%)</label>
                                <input wire:model="defaultVatRate" type="number" step="0.01" min="0"
                                    max="100" class="input" placeholder="15">
                            </div>
                        </div>
                    @endif
                </fieldset>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Create Shop & Send Invite</span>
                        <span wire:loading>Creating…</span>
                    </button>
                    <a href="{{ route('admin.shops.index') }}" wire:navigate class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    @endif
</div>
