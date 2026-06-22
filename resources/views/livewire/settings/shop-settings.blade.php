<div class="max-w-4xl mx-auto space-y-5">

    {{-- Tab Navigation --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @php
                $tabs = [
                    ['key' => 'profile', 'label' => 'Shop Profile'],
                    ['key' => 'payments', 'label' => 'Payment Accounts'],
                    ['key' => 'branches', 'label' => 'Branches'],
                    ['key' => 'vat', 'label' => 'VAT / Tax'],
                ];
            @endphp
            @foreach ($tabs as $tab)
                <button wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab['key']
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- ── PROFILE TAB ── --}}
        <div wire:show="activeTab === 'profile'" class="p-6 space-y-5">
            <h3 class="font-semibold text-gray-900">Shop Information</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="label">Shop Name *</label>
                    <input wire:model="shopName" type="text" class="input @error('shopName') input-error @enderror">
                    @error('shopName')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input wire:model="shopPhone" type="tel" class="input" placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="label">Email</label>
                    <input wire:model="shopEmail" type="email" class="input">
                    @error('shopEmail')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Address</label>
                    <textarea wire:model="shopAddress" rows="2" class="input"></textarea>
                </div>
                <div>
                    <label class="label">Timezone</label>
                    <select wire:model="timezone" class="input">
                        <option value="Asia/Dhaka">Asia/Dhaka (GMT+6)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                <div>
                    <label class="label">Currency</label>
                    <select wire:model="currency" class="input">
                        <option value="BDT">BDT — Bangladeshi Taka (৳)</option>
                        <option value="USD">USD — US Dollar ($)</option>
                    </select>
                </div>
            </div>
            <div class="pt-2">
                <button wire:click="saveProfile" class="btn-primary" data-loading:class="opacity-50 cursor-not-allowed">
                    Save Profile
                </button>
            </div>
        </div>

        {{-- ── PAYMENT ACCOUNTS TAB ── --}}
        <div wire:show="activeTab === 'payments'" class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">Payment Accounts</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Each account creates a GL ledger entry. Add all accounts your shop uses.
                    </p>
                </div>
                <button wire:click="openPaymentForm()" class="btn-primary btn-sm">
                    + Add Account
                </button>
            </div>

            {{-- Add / Edit Form --}}
            <div wire:show="showPaymentForm" class="border border-indigo-200 bg-indigo-50 rounded-xl p-5 space-y-4">
                <h4 class="font-medium text-indigo-900 text-sm">
                    {{ $editingPaymentId ? 'Edit' : 'New' }} Payment Account
                </h4>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Account Label *</label>
                        <input wire:model="paymentName" type="text" placeholder="e.g. bKash Business — 01711000000"
                            class="input @error('paymentName') input-error @enderror">
                        @error('paymentName')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Provider *</label>
                        <select wire:model.live="paymentProvider"
                            class="input @error('paymentProvider') input-error @enderror"
                            {{ $editingPaymentId ? 'disabled' : '' }}>
                            <option value="bank">Bank Account</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="upay">Upay</option>
                            <option value="card">Card Terminal</option>
                            <option value="other">Other</option>
                        </select>
                        @if ($editingPaymentId)
                            <p class="text-xs text-gray-400 mt-0.5">Provider cannot be changed after creation.</p>
                        @endif
                    </div>
                    <div>
                        <label class="label">Account Number</label>
                        <input wire:model="paymentAccountNumber" type="text"
                            placeholder="{{ in_array($paymentProvider, ['bkash', 'nagad', 'rocket', 'upay']) ? '01XXXXXXXXX' : 'ACC-XXXXXXXX' }}"
                            class="input">
                    </div>
                    @if ($paymentProvider === 'bank')
                        <div class="sm:col-span-2">
                            <label class="label">Bank Name</label>
                            <input wire:model="paymentBankName" type="text" placeholder="e.g. Dutch-Bangla Bank"
                                class="input">
                        </div>
                    @endif
                    @if (!$editingPaymentId)
                        <div>
                            <label class="label">Branch <span class="text-gray-400 font-normal">(optional — blank = all
                                    branches)</span></label>
                            <select wire:model="paymentBranchId" class="input">
                                <option value="">All branches (shop-wide)</option>
                                @foreach ($this->branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="flex gap-2 pt-1">
                    <button wire:click="savePaymentAccount" class="btn-primary btn-sm" data-loading:class="opacity-50">
                        {{ $editingPaymentId ? 'Update' : 'Add Account' }}
                    </button>
                    <button wire:click="$set('showPaymentForm', false)" class="btn-secondary btn-sm">
                        Cancel
                    </button>
                </div>
            </div>

            {{-- Existing Accounts --}}
            @php
                $providerLabels = [
                    'cash' => ['label' => 'Cash', 'color' => 'badge-green'],
                    'bank' => ['label' => 'Bank', 'color' => 'badge-blue'],
                    'bkash' => ['label' => 'bKash', 'color' => 'badge-red'],
                    'nagad' => ['label' => 'Nagad', 'color' => 'badge-yellow'],
                    'rocket' => ['label' => 'Rocket', 'color' => 'badge-blue'],
                    'upay' => ['label' => 'Upay', 'color' => 'badge-gray'],
                    'card' => ['label' => 'Card', 'color' => 'badge-gray'],
                    'other' => ['label' => 'Other', 'color' => 'badge-gray'],
                ];
            @endphp

            <div class="space-y-2">
                @forelse($this->paymentAccounts as $payment)
                    <div class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl hover:bg-gray-50">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-900 text-sm">{{ $payment->name }}</span>
                                <span
                                    class="badge {{ $providerLabels[$payment->provider]['color'] ?? 'badge-gray' }}">
                                    {{ $providerLabels[$payment->provider]['label'] ?? $payment->provider }}
                                </span>
                                @if ($payment->branch)
                                    <span class="badge badge-gray">{{ $payment->branch->name }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-3">
                                @if ($payment->account_number)
                                    <span>{{ $payment->account_number }}</span>
                                @endif
                                @if ($payment->bank_name)
                                    <span>{{ $payment->bank_name }}</span>
                                @endif
                                <span class="font-mono text-indigo-400">GL: {{ $payment->account?->code }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($payment->provider !== 'cash')
                                <button wire:click="openPaymentForm({{ $payment->id }})"
                                    class="text-xs text-indigo-600 hover:underline font-medium">
                                    Edit
                                </button>
                            @endif
                            <button wire:click="deactivatePaymentAccount({{ $payment->id }})"
                                wire:confirm="Remove {{ $payment->name }}? If it has transactions, it will be deactivated instead of deleted."
                                class="text-xs text-red-500 hover:underline font-medium">
                                Remove
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-gray-400 text-sm">
                        No payment accounts yet.
                        <button wire:click="openPaymentForm()" class="text-indigo-600 hover:underline ml-1">
                            Add your first account
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── BRANCHES TAB ── --}}
        <div wire:show="activeTab === 'branches'" class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">Branches</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Each new branch gets its own Cash-in-Hand GL account automatically.
                    </p>
                </div>
                <button wire:click="openBranchForm()" class="btn-primary btn-sm">
                    + Add Branch
                </button>
            </div>

            {{-- Branch Form --}}
            <div wire:show="showBranchForm" class="border border-indigo-200 bg-indigo-50 rounded-xl p-5 space-y-4">
                <h4 class="font-medium text-indigo-900 text-sm">
                    {{ $editingBranchId ? 'Edit Branch' : 'New Branch' }}
                </h4>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Branch Name *</label>
                        <input wire:model="branchName" type="text" placeholder="e.g. Gulshan Branch"
                            class="input @error('branchName') input-error @enderror">
                        @error('branchName')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Branch Code *
                            <span class="text-gray-400 font-normal">(short, unique)</span>
                        </label>
                        <input wire:model="branchCode" type="text" placeholder="e.g. GL, DH-2" maxlength="20"
                            class="input uppercase @error('branchCode') input-error @enderror">
                        @error('branchCode')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input wire:model="branchPhone" type="tel" class="input" placeholder="01XXXXXXXXX">
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <input wire:model="branchAddress" type="text" class="input"
                            placeholder="Branch address">
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <button wire:click="saveBranch" class="btn-primary btn-sm" data-loading:class="opacity-50">
                        {{ $editingBranchId ? 'Update' : 'Add Branch' }}
                    </button>
                    <button wire:click="$set('showBranchForm', false)" class="btn-secondary btn-sm">
                        Cancel
                    </button>
                </div>
            </div>

            {{-- Branches List --}}
            <div class="space-y-2">
                @forelse($this->branches as $branch)
                    <div class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl hover:bg-gray-50">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-900 text-sm">{{ $branch->name }}</span>
                                <span
                                    class="font-mono text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ $branch->code }}</span>
                                @if ($branch->is_main)
                                    <span class="badge badge-blue">Main</span>
                                @endif
                                <span class="{{ $branch->is_active ? 'badge-green' : 'badge-gray' }} badge">
                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            @if ($branch->address || $branch->phone)
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ collect([$branch->phone, $branch->address])->filter()->implode(' · ') }}
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="openBranchForm({{ $branch->id }})"
                                class="text-xs text-indigo-600 hover:underline font-medium">
                                Edit
                            </button>
                            @if (!$branch->is_main)
                                <button wire:click="toggleBranch({{ $branch->id }})"
                                    class="text-xs {{ $branch->is_active ? 'text-red-500' : 'text-green-600' }} hover:underline font-medium">
                                    {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-center py-8 text-sm text-gray-400">No branches found.</p>
                @endforelse
            </div>
        </div>

        {{-- ── VAT TAB ── --}}
        <div wire:show="activeTab === 'vat'" class="p-6 space-y-5">
            <h3 class="font-semibold text-gray-900">VAT / Tax Settings</h3>
            <p class="text-sm text-gray-500">
                When enabled, VAT will appear as a line item on invoices and be tracked in a separate GL account.
            </p>

            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input wire:model.live="vatEnabled" type="checkbox" class="sr-only peer">
                    <div
                        class="w-9 h-5 bg-gray-200 rounded-full peer
                        peer-checked:bg-indigo-600
                        peer-checked:after:translate-x-4
                        after:content-[''] after:absolute after:top-0.5 after:left-0.5
                        after:bg-white after:rounded-full after:h-4 after:w-4
                        after:transition-transform">
                    </div>
                </label>
                <span class="text-sm font-medium text-gray-700">
                    {{ $vatEnabled ? 'VAT enabled' : 'VAT disabled' }}
                </span>
            </div>

            <div wire:show="vatEnabled" class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">VAT Registration Number</label>
                        <input wire:model="vatRegistrationNumber" type="text" placeholder="BIN / VAT Reg. No."
                            class="input @error('vatRegistrationNumber') input-error @enderror">
                        @error('vatRegistrationNumber')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Default VAT Rate (%)</label>
                        <div class="flex items-center gap-2">
                            <input wire:model="defaultVatRate" type="number" step="0.01" min="0"
                                max="100" placeholder="15"
                                class="input @error('defaultVatRate') input-error @enderror">
                            <span class="text-sm text-gray-500 shrink-0">%</span>
                        </div>
                        @error('defaultVatRate')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="text-xs text-gray-400">
                    Standard VAT rate in Bangladesh is 15%. Consult your accountant for your specific rate.
                </p>
            </div>

            <div class="pt-2">
                <button wire:click="saveVat" class="btn-primary" data-loading:class="opacity-50">
                    Save VAT Settings
                </button>
            </div>
        </div>
    </div>
</div>
