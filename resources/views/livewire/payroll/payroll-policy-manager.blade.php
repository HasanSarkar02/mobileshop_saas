<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Payroll Policies</h2>
            <p class="text-sm text-gray-500 mt-0.5">Policies define which components apply to a group of employees.</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">+ New Policy</button>
    </div>

    {{-- Policy Form --}}
    @if ($showForm)
        <div class="card p-6 border-2 border-indigo-200 space-y-4">
            <h3 class="font-semibold text-gray-900">{{ $editingId ? 'Edit Policy' : 'New Policy' }}</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Policy Name *</label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                        placeholder="e.g. Standard Monthly Staff">
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Code * <span
                            class="text-xs font-normal text-gray-400">(uppercase)</span></label>
                    <input wire:model="code" type="text" class="input @error('code') input-error @enderror"
                        placeholder="STANDARD_MONTHLY" style="text-transform:uppercase">
                    @error('code')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Employment Type *</label>
                    <select wire:model="employmentType" class="input">
                        @foreach ($this->employmentTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="isDefault" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm text-gray-700">Set as default policy</span>
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Description</label>
                    <input wire:model="description" type="text" class="input" placeholder="Optional description…">
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="savePolicy" class="btn-primary btn-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Update Policy' : 'Create Policy' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <button wire:click="$set('showForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-5">

        {{-- Policy List --}}
        <div class="space-y-3">
            @forelse($this->policies as $policy)
                <div class="card p-4 cursor-pointer transition-all
                    {{ $viewingPolicyId === $policy->id ? 'border-2 border-indigo-500' : 'hover:border-indigo-200 border-2 border-transparent' }}"
                    wire:click="selectPolicy({{ $policy->id }})" wire:key="policy-{{ $policy->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 flex items-center gap-2">
                                {{ $policy->name }}
                                @if ($policy->is_default)
                                    <span class="badge badge-indigo text-xs">Default</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $policy->code }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ \App\Enums\EmploymentType::from($policy->employment_type)->label() }}
                                · {{ $policy->salary_structures_count }} employee(s)
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 shrink-0">
                            <button wire:click.stop="openEdit({{ $policy->id }})"
                                class="text-xs text-indigo-600 hover:underline">Edit</button>
                            @if (!$policy->is_default)
                                <button wire:click.stop="setDefault({{ $policy->id }})"
                                    class="text-xs text-gray-400 hover:underline">Set Default</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-6 text-center text-gray-400 text-sm">
                    No policies yet.
                    <button wire:click="openCreate" class="text-indigo-600 hover:underline ml-1 block mt-1">
                        Create your first policy →
                    </button>
                </div>
            @endforelse
        </div>

        {{-- Component Assignment --}}
        <div class="lg:col-span-2">
            @if ($viewingPolicy)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $viewingPolicy->name }} — Components</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Check components to include. Set default values for
                                this policy.</p>
                        </div>
                        <button wire:click="savePolicyComponents" class="btn-primary btn-sm"
                            wire:loading.attr="disabled" wire:target="savePolicyComponents">
                            <span wire:loading.remove wire:target="savePolicyComponents">Save Components</span>
                            <span wire:loading wire:target="savePolicyComponents">Saving…</span>
                        </button>
                    </div>

                    @foreach (['earning' => '💰 Earnings', 'deduction' => '📤 Deductions'] as $type => $label)
                        <div class="px-5 py-2 bg-gray-50 border-b border-gray-100">
                            <span
                                class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $label }}</span>
                        </div>
                        @foreach ($policyComponents as $idx => $comp)
                            @if ($comp['component_type'] !== $type)
                                @continue
                            @endif
                            <div class="px-5 py-3 border-b border-gray-50 hover:bg-gray-50"
                                wire:key="pc-{{ $comp['component_id'] }}">
                                <div class="flex items-start gap-3">
                                    <input wire:model.live="policyComponents.{{ $idx }}.included"
                                        type="checkbox" class="mt-1 rounded border-gray-300 text-indigo-600">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-sm text-gray-900">{{ $comp['name'] }}</span>
                                            <span class="font-mono text-xs text-indigo-400">{{ $comp['code'] }}</span>
                                            @if ($comp['is_system'])
                                                <span class="badge badge-gray text-xs">System</span>
                                            @endif
                                        </div>
                                        @if ($policyComponents[$idx]['included'])
                                            <div class="mt-2 grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                                <div>
                                                    <label class="text-xs text-gray-500">Calculation</label>
                                                    <select
                                                        wire:model.live="policyComponents.{{ $idx }}.calculation_type"
                                                        class="input text-xs py-1">
                                                        <option value="fixed">Fixed</option>
                                                        <option value="percentage">Percentage</option>
                                                        <option value="formula">Formula</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="text-xs text-gray-500">
                                                        @if ($policyComponents[$idx]['calculation_type'] === 'percentage')
                                                            Percentage (%)
                                                        @else
                                                            Default Value (৳)
                                                        @endif
                                                    </label>
                                                    <input
                                                        wire:model="policyComponents.{{ $idx }}.default_value"
                                                        type="number" step="0.01" min="0"
                                                        class="input text-xs py-1">
                                                </div>
                                                @if ($policyComponents[$idx]['calculation_type'] === 'percentage')
                                                    <div>
                                                        <label class="text-xs text-gray-500">% Of (Code)</label>
                                                        <input
                                                            wire:model="policyComponents.{{ $idx }}.percentage_of"
                                                            type="text" class="input text-xs py-1 uppercase"
                                                            placeholder="BASIC">
                                                    </div>
                                                @endif
                                                <div>
                                                    <label class="text-xs text-gray-500">Sequence</label>
                                                    <input wire:model="policyComponents.{{ $idx }}.sequence"
                                                        type="number" min="1" class="input text-xs py-1">
                                                </div>
                                                <div class="flex items-end">
                                                    <label class="flex items-center gap-1 cursor-pointer">
                                                        <input
                                                            wire:model="policyComponents.{{ $idx }}.is_required"
                                                            type="checkbox"
                                                            class="rounded border-gray-300 text-indigo-600">
                                                        <span class="text-xs text-gray-600">Required</span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="card p-10 text-center text-gray-400">
                    <div class="text-4xl mb-3">👈</div>
                    Select a policy from the left to manage its components.
                </div>
            @endif
        </div>
    </div>
</div>
