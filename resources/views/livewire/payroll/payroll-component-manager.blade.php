<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Payroll Components</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                System components are global. You can add custom components for your shop.
            </p>
        </div>
        <button wire:click="openCreate" class="btn-primary">+ Custom Component</button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-gray-200">
        @foreach (['earnings' => 'Earnings', 'deductions' => 'Deductions'] as $key => $label)
            <button wire:click="$set('activeTab', '{{ $key }}')"
                class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
                    {{ $activeTab === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Custom Component Form --}}
    @if ($showForm)
        <div class="card p-6 border-2 border-indigo-200 space-y-4">
            <h3 class="font-semibold text-gray-900">
                {{ $editingId ? 'Edit Custom Component' : 'New Custom Component' }}
            </h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label">Component Name *</label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                        placeholder="e.g. Night Shift Allowance">
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Code * <span class="text-xs font-normal text-gray-400">(uppercase, no
                            spaces)</span></label>
                    <input wire:model="code" type="text" class="input @error('code') input-error @enderror"
                        placeholder="NIGHT_SHIFT" style="text-transform:uppercase">
                    @error('code')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Calculation Type *</label>
                    <select wire:model.live="calculationType" class="input">
                        <option value="fixed">Fixed Amount</option>
                        <option value="percentage">Percentage of Another</option>
                        <option value="formula">Formula</option>
                    </select>
                </div>

                {{-- Fixed amount --}}
                @if ($calculationType === 'fixed')
                    <div>
                        <label class="label">Default Amount (৳)</label>
                        <input wire:model="defaultValue" type="number" step="0.01" min="0" class="input"
                            placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-0.5">Can be overridden per employee.</p>
                    </div>
                @endif

                {{-- Percentage --}}
                @if ($showPercentageOf)
                    <div>
                        <label class="label">Percentage Of (Component Code) *</label>
                        <select wire:model="percentageOf" class="input">
                            <option value="">Select component…</option>
                            @foreach ($this->allEarningCodes as $code => $compName)
                                <option value="{{ $code }}">{{ $compName }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Percentage (%) *</label>
                        <input wire:model="defaultValue" type="number" step="0.01" min="0" max="100"
                            class="input" placeholder="50">
                    </div>
                @endif

                {{-- Formula --}}
                @if ($showFormula)
                    <div class="sm:col-span-2">
                        <label class="label">Formula *</label>
                        <input wire:model="formula" type="text" class="input" placeholder="e.g. BASIC * 0.5 + 500">
                        <p class="text-xs text-gray-400 mt-0.5">
                            Use component codes: BASIC, HRA, TRANSPORT, etc. Supports: + - * / ()
                        </p>
                    </div>
                @endif

                <div>
                    <label class="label">Sequence</label>
                    <input wire:model="sequence" type="number" min="1" class="input" placeholder="100">
                    <p class="text-xs text-gray-400 mt-0.5">Display order. Lower = first.</p>
                </div>
                <div>
                    <label class="label">GL Account Code <span class="text-xs font-normal text-gray-400">(optional
                            override)</span></label>
                    <input wire:model="glAccountCode" type="text" class="input" placeholder="e.g. 6022">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Description</label>
                    <input wire:model="description" type="text" class="input"
                        placeholder="Optional notes about this component">
                </div>

                {{-- Toggles --}}
                <div class="sm:col-span-3 flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="isTaxable" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm text-gray-700">Taxable</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="isRecurring" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm text-gray-700">Recurring (appears every month)</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="save" class="btn-primary btn-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Update' : 'Create Component' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <button wire:click="$set('showForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @endif

    {{-- System Components (read-only) --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">
                System {{ $activeTab === 'earnings' ? 'Earnings' : 'Deductions' }}
                <span class="text-xs font-normal text-gray-400 ml-1">(global, read-only)</span>
            </h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="table-th">Name</th>
                    <th class="table-th">Code</th>
                    <th class="table-th">Calculation</th>
                    <th class="table-th">Default</th>
                    <th class="table-th text-center">Taxable</th>
                    <th class="table-th text-center">Recurring</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($this->globalComponents as $comp)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-medium text-gray-900">{{ $comp->name }}</td>
                        <td class="table-td font-mono text-xs text-indigo-600">{{ $comp->code }}</td>
                        <td class="table-td text-sm text-gray-600 capitalize">
                            @if ($comp->calculation_type->value === 'percentage')
                                {{ $comp->default_value }}% of {{ $comp->percentage_of }}
                            @elseif($comp->calculation_type->value === 'formula')
                                Formula: <code class="text-xs bg-gray-100 px-1 rounded">{{ $comp->formula }}</code>
                            @else
                                Fixed
                            @endif
                        </td>
                        <td class="table-td text-gray-500">
                            {{ $comp->default_value > 0 ? '৳' . number_format($comp->default_value, 0) : '—' }}
                        </td>
                        <td class="table-td text-center">
                            {{ $comp->is_taxable ? '✓' : '—' }}
                        </td>
                        <td class="table-td text-center">
                            <span class="badge {{ $comp->is_recurring ? 'badge-green' : 'badge-yellow' }} text-xs">
                                {{ $comp->is_recurring ? 'Recurring' : 'One-time' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="table-td text-center text-gray-400 py-6">
                            No system {{ $activeTab }} defined.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Shop-Specific Components --}}
    @if ($this->shopComponents->isNotEmpty() || true)
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                <h3 class="font-semibold text-indigo-800 text-sm">
                    Custom {{ $activeTab === 'earnings' ? 'Earnings' : 'Deductions' }}
                    <span class="text-xs font-normal text-indigo-500 ml-1">(shop-specific, editable)</span>
                </h3>
            </div>
            @if ($this->shopComponents->isEmpty())
                <div class="p-6 text-center text-gray-400 text-sm">
                    No custom components yet.
                    <button wire:click="openCreate" class="text-indigo-600 hover:underline ml-1">
                        Add one →
                    </button>
                </div>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Name</th>
                            <th class="table-th">Code</th>
                            <th class="table-th">Calculation</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($this->shopComponents as $comp)
                            <tr class="hover:bg-gray-50 {{ !$comp->is_active ? 'opacity-50' : '' }}"
                                wire:key="scomp-{{ $comp->id }}">
                                <td class="table-td font-medium text-gray-900">{{ $comp->name }}</td>
                                <td class="table-td font-mono text-xs text-indigo-600">{{ $comp->code }}</td>
                                <td class="table-td text-sm text-gray-600">
                                    @if ($comp->calculation_type->value === 'percentage')
                                        {{ $comp->default_value }}% of {{ $comp->percentage_of }}
                                    @elseif($comp->calculation_type->value === 'formula')
                                        Formula
                                    @else
                                        Fixed ৳{{ number_format($comp->default_value, 0) }}
                                    @endif
                                </td>
                                <td class="table-td">
                                    <span class="badge {{ $comp->is_active ? 'badge-green' : 'badge-gray' }} text-xs">
                                        {{ $comp->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="table-td">
                                    <div class="flex items-center gap-3">
                                        <button wire:click="openEdit({{ $comp->id }})"
                                            class="text-xs text-indigo-600 hover:underline font-medium">Edit</button>
                                        <button wire:click="toggleShopComponent({{ $comp->id }})"
                                            class="text-xs {{ $comp->is_active ? 'text-amber-500' : 'text-green-500' }} hover:underline font-medium">
                                            {{ $comp->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
</div>
