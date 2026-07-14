<div class="max-w-4xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Salary Setup — {{ $user->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Configure payroll policy, components, and payment method for this employee.
            </p>
        </div>
        <a href="{{ route('employees.show', $user) }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    {{-- Active Structure Summary --}}
    @if ($this->activeStructure)
        <div class="card p-4 bg-green-50 border-green-200">
            <div class="flex items-center gap-4 text-sm">
                <span class="text-green-700 font-medium">✓ Active Structure</span>
                <span class="text-green-600">Policy: {{ $this->activeStructure->policy?->name }}</span>
                <span class="text-green-600">From: {{ $this->activeStructure->effective_from->format('d M Y') }}</span>
                @if ($this->activeStructure->department)
                    <span class="text-green-600">Dept: {{ $this->activeStructure->department->name }}</span>
                @endif
            </div>
        </div>
    @else
        <div class="card p-4 bg-amber-50 border-amber-200">
            <p class="text-sm text-amber-800">
                ⚠ No active salary structure found. This employee cannot be included in payroll until one is configured.
            </p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">

        {{-- Basic Setup --}}
        <div class="card p-6 space-y-4">
            <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-2">Policy & Employment</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Payroll Policy *</label>
                    <select wire:model.live="policyId" class="input @error('policyId') input-error @enderror">
                        <option value="0">Select policy…</option>
                        @foreach ($this->policies as $policy)
                            <option value="{{ $policy->id }}">
                                {{ $policy->name }}
                                {{ $policy->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('policyId')
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
                <div>
                    <label class="label">Department</label>
                    <select wire:model="departmentId" class="input">
                        <option value="0">No department</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Designation</label>
                    <input wire:model="designation" type="text" class="input"
                        placeholder="e.g. Senior Sales Executive">
                </div>
                <div>
                    <label class="label">Effective From *</label>
                    <input wire:model="effectiveFrom" type="date"
                        class="input @error('effectiveFrom') input-error @enderror">
                    @error('effectiveFrom')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Monthly Working Days *</label>
                    <input wire:model="monthlyWorkingDays" type="number" min="1" max="31"
                        class="input @error('monthlyWorkingDays') input-error @enderror">
                    @error('monthlyWorkingDays')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Weekly Off Days</label>
                    <input wire:model="weeklyOffDays" type="number" min="0" max="3" class="input">
                </div>
                <div>
                    <label class="label">Overtime Rate (৳/hour)</label>
                    <input wire:model="overtimeRate" type="number" min="0" step="0.01" class="input"
                        placeholder="Optional">
                </div>
            </div>
        </div>

        {{-- Payment Setup --}}
        <div class="card p-6 space-y-4">
            <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-2">Payment Method</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Default Payment Account</label>
                    <select wire:model.live="paymentAccountId" class="input">
                        <option value="0">Not set (choose at payment time)</option>
                        @foreach ($this->paymentAccounts as $pa)
                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Payment Method</label>
                    <select wire:model.live="paymentMethod" class="input">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                @if ($paymentMethod === 'bank_transfer')
                    <div>
                        <label class="label">Bank Name</label>
                        <input wire:model="bankName" type="text" class="input" placeholder="e.g. BRAC Bank">
                    </div>
                    <div>
                        <label class="label">Account Number</label>
                        <input wire:model="bankAccountNumber" type="text" class="input font-mono">
                    </div>
                    <div>
                        <label class="label">Routing Number</label>
                        <input wire:model="bankRoutingNumber" type="text" class="input font-mono">
                    </div>
                @endif
            </div>
        </div>

        {{-- Component Overrides --}}
        @if (!empty($componentOverrides))
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Salary Components</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Values shown are from the selected policy.
                            Override any value for this specific employee.
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-400">Estimated Gross</div>
                        <div class="font-bold text-indigo-700 text-lg">
                            ৳{{ number_format($this->grossSalaryPreview, 0) }}
                        </div>
                    </div>
                </div>

                @foreach (['earning' => '💰 Earnings', 'deduction' => '📤 Deductions'] as $type => $label)
                    @php
                        $typeComps = collect($componentOverrides)->filter(fn($c) => $c['component_type'] === $type);
                    @endphp
                    @if ($typeComps->isNotEmpty())
                        <div class="px-5 py-2 bg-gray-50 border-b border-gray-100">
                            <span
                                class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $label }}</span>
                        </div>
                        @foreach ($componentOverrides as $compId => $comp)
                            @if ($comp['component_type'] !== $type)
                                @continue
                            @endif
                            <div class="px-5 py-3 border-b border-gray-50 hover:bg-gray-50"
                                wire:key="co-{{ $compId }}">
                                <div class="flex items-center gap-4 flex-wrap">
                                    <div class="min-w-[140px]">
                                        <div class="font-medium text-sm text-gray-900">{{ $comp['name'] }}</div>
                                        <div class="font-mono text-xs text-indigo-400">{{ $comp['code'] }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <select wire:model="componentOverrides.{{ $compId }}.calculation_type"
                                            class="input text-xs py-1 w-28">
                                            <option value="fixed">Fixed</option>
                                            <option value="percentage">%</option>
                                            <option value="formula">Formula</option>
                                        </select>
                                        <input wire:model="componentOverrides.{{ $compId }}.value"
                                            type="number" step="0.01" min="0"
                                            class="input text-sm w-28 font-semibold" placeholder="0.00">
                                        @if ($comp['calculation_type'] === 'percentage')
                                            <span class="text-xs text-gray-400">% of
                                                {{ $comp['percentage_of'] }}</span>
                                        @endif
                                    </div>
                                    @if ((float) $comp['value'] !== (float) $comp['policy_value'])
                                        <span class="text-xs text-amber-500 font-medium">
                                            ⚡ Override (policy: ৳{{ number_format($comp['policy_value'], 0) }})
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-300">Policy default</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endforeach
            </div>
        @elseif($policyId)
            <div class="card p-6 text-center text-gray-400 text-sm">
                This policy has no components configured yet.
                <a href="{{ route('payroll.policies') }}" wire:navigate class="text-indigo-600 hover:underline ml-1">
                    Configure policy →
                </a>
            </div>
        @endif

        {{-- Submit --}}
        <div class="flex gap-3 pb-8">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Salary Structure</span>
                <span wire:loading>Saving…</span>
            </button>
            <a href="{{ route('employees.show', $user) }}" wire:navigate class="btn-secondary">Cancel</a>
        </div>

    </form>
</div>
