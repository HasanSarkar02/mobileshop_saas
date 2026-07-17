<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Subscription Plans</h2>
        <button wire:click="openCreate" class="btn-primary">+ New Plan</button>
    </div>

    {{-- Plan Form --}}
    @if ($showForm)
        <div class="card p-6 border-2 border-indigo-200 space-y-4">
            <h3 class="font-semibold text-gray-900">{{ $editingId ? 'Edit Plan' : 'New Plan' }}</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label text-xs">Plan Name *</label>
                    <input wire:model.live="name" type="text" class="input @error('name') input-error @enderror"
                        placeholder="e.g. Pro">
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label text-xs">Slug (URL-safe) *</label>
                    <input wire:model="slug" type="text" class="input" placeholder="pro">
                </div>
                <div>
                    <label class="label text-xs">Sort Order</label>
                    <input wire:model="sortOrder" type="number" min="0" class="input">
                </div>
                <div>
                    <label class="label text-xs">Monthly Price (৳) *</label>
                    <input wire:model="monthlyPrice" type="number" step="0.01" min="0"
                        class="input @error('monthlyPrice') input-error @enderror">
                    @error('monthlyPrice')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label text-xs">Yearly Price (৳) <span class="text-gray-400">optional</span></label>
                    <input wire:model="yearlyPrice" type="number" step="0.01" min="0" class="input"
                        placeholder="Leave blank to disable">
                </div>
                <div>
                    <label class="label text-xs">Max Branches</label>
                    <input wire:model="maxBranches" type="number" min="1" class="input">
                </div>
                <div>
                    <label class="label text-xs">Max Employees</label>
                    <input wire:model="maxEmployees" type="number" min="1" class="input">
                </div>
                <div>
                    <label class="label text-xs">Max Products (0 = unlimited)</label>
                    <input wire:model="maxProducts" type="number" min="0" class="input">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="isActive" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm text-gray-700">Active (visible/assignable)</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="label text-xs">Features Included</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 mt-1">
                    @foreach ($availableFeatures as $key => $label)
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" value="{{ $key }}" wire:model="features"
                                class="rounded border-gray-300 text-indigo-600">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="save" class="btn-primary btn-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Update Plan' : 'Create Plan' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <button wire:click="$set('showForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @endif

    {{-- Plans Grid --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($this->plans as $plan)
            <div class="card p-5 space-y-3 {{ !$plan->is_active ? 'opacity-60' : '' }}"
                wire:key="plan-{{ $plan->id }}">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-bold text-gray-900 text-lg">{{ $plan->name }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ $plan->slug }}</div>
                    </div>
                    <div class="flex gap-2">
                        <span class="badge {{ $plan->is_active ? 'badge-green' : 'badge-gray' }} text-xs">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Monthly</span>
                        <span class="font-bold text-indigo-700">৳{{ number_format($plan->monthly_price, 0) }}/mo</span>
                    </div>
                    @if ($plan->yearly_price)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Yearly</span>
                            <span
                                class="font-bold text-indigo-600">৳{{ number_format($plan->yearly_price, 0) }}/yr</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Branches</span>
                        <span>{{ $plan->max_branches }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Employees</span>
                        <span>{{ $plan->max_employees }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Products</span>
                        <span>{{ $plan->max_products ?: 'Unlimited' }}</span>
                    </div>
                </div>

                @if ($plan->features)
                    <div class="flex flex-wrap gap-1">
                        @foreach ($plan->features as $feature)
                            <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">
                                {{ $availableFeatures[$feature] ?? $feature }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        <strong>{{ $plan->active_subscriptions_count }}</strong> active subscribers
                    </span>
                    <button wire:click="openEdit({{ $plan->id }})"
                        class="text-xs text-indigo-600 hover:underline font-medium">
                        Edit
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-3 card p-10 text-center text-gray-400">
                No plans yet.
                <button wire:click="openCreate" class="text-indigo-600 hover:underline ml-1">Create first plan
                    →</button>
            </div>
        @endforelse
    </div>
</div>
