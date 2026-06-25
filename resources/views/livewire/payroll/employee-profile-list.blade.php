<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h2 class="font-bold text-gray-900">Employee Salary Profiles</h2>
        <div class="flex gap-2">
            <button wire:click="$toggle('showLocalEmpForm')" class="btn-secondary btn-sm">
                + Add Local Employee (No Account)
            </button>
            <a href="{{ route('payroll.index') }}" wire:navigate class="btn-secondary btn-sm">← Payroll</a>
        </div>
    </div>

    <div wire:show="showLocalEmpForm" class="card p-5 border-amber-200 bg-amber-50">
        <h4 class="font-semibold text-amber-900 mb-3">Add Local Employee</h4>
        <p class="text-xs text-amber-700 mb-3">
            This employee will appear in payroll but cannot log into the system.
            Use for cashiers, helpers, or staff without a computer.
        </p>
        <div class="flex gap-3">
            <div class="flex-1">
                <label class="label text-xs">Name *</label>
                <input wire:model="localEmpName" type="text" class="input text-sm" placeholder="Employee name">
                @error('localEmpName')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="w-44">
                <label class="label text-xs">Phone</label>
                <input wire:model="localEmpPhone" type="tel" class="input text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button wire:click="createLocalEmployee" class="btn-success btn-sm">Add</button>
                <button wire:click="$set('showLocalEmpForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    </div>

    @if ($this->employees->isEmpty())
        <div class="card p-10 text-center text-gray-400 text-sm">
            No employees found. Add employees first.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->employees as $emp)
                @php $profile = $emp->employeeProfile; @endphp
                <div class="card overflow-hidden" wire:key="emp-{{ $emp->id }}">
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-900">{{ $emp->name }}</span>
                                @if ($profile?->designation)
                                    <span class="text-xs text-gray-500">— {{ $profile->designation }}</span>
                                @endif
                                @if ($emp->branch)
                                    <span class="badge badge-gray text-xs">{{ $emp->branch->name }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $emp->email }}</div>
                            @if ($profile)
                                <div class="flex flex-wrap gap-4 mt-2 text-sm">
                                    <span>Base: <strong>৳{{ number_format($profile->base_salary, 0) }}</strong></span>
                                    @if ($profile->house_allowance > 0)
                                        <span class="text-gray-500">+ House
                                            ৳{{ number_format($profile->house_allowance, 0) }}</span>
                                    @endif
                                    @if ($profile->transport_allowance > 0)
                                        <span class="text-gray-500">+ Transport
                                            ৳{{ number_format($profile->transport_allowance, 0) }}</span>
                                    @endif
                                    <span class="font-semibold text-indigo-600">
                                        Gross: ৳{{ number_format($profile->grossSalary(), 0) }}
                                    </span>
                                </div>
                                @php
                                    $activeAdvances = \App\Models\SalaryAdvance::withoutGlobalScopes()
                                        ->where('user_id', $emp->id)
                                        ->where('status', 'active')
                                        ->get();
                                @endphp
                                @if ($activeAdvances->isNotEmpty())
                                    <div class="text-xs text-amber-600 mt-1">
                                        Advance balance:
                                        ৳{{ number_format($activeAdvances->sum('balance_remaining'), 0) }}
                                        (৳{{ number_format($activeAdvances->sum('monthly_deduction'), 0) }}/month
                                        deduction)
                                    </div>
                                @endif
                            @else
                                <div class="text-xs text-amber-500 mt-1">⚠ No salary profile configured</div>
                            @endif
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button wire:click="openForm({{ $emp->id }})" class="btn-secondary btn-sm">
                                {{ $profile ? 'Edit Salary' : 'Set Salary' }}
                            </button>
                            <button wire:click="openAdvanceForm({{ $emp->id }})" class="btn-secondary btn-sm">
                                Give Advance
                            </button>
                        </div>
                    </div>

                    {{-- Salary Edit Form --}}
                    @if ($showForm && $editUserId === $emp->id)
                        <div class="border-t border-indigo-200 bg-indigo-50 p-5 space-y-4">
                            <h4 class="font-medium text-indigo-900">Salary Profile</h4>
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="label text-xs">Designation</label>
                                    <input wire:model="designation" type="text" class="input text-sm"
                                        placeholder="e.g. Sales Manager">
                                </div>
                                <div>
                                    <label class="label text-xs">Joining Date</label>
                                    <input wire:model="joiningDate" type="date" class="input text-sm">
                                </div>
                                <div>
                                    <label class="label text-xs">Base Salary (৳) *</label>
                                    <input wire:model="baseSalary" type="number" min="0" step="0.01"
                                        class="input text-sm font-semibold @error('baseSalary') input-error @enderror">
                                    @error('baseSalary')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="label text-xs">House Allowance (৳)</label>
                                    <input wire:model="houseAllowance" type="number" min="0" step="0.01"
                                        class="input text-sm">
                                </div>
                                <div>
                                    <label class="label text-xs">Transport Allowance (৳)</label>
                                    <input wire:model="transportAllowance" type="number" min="0" step="0.01"
                                        class="input text-sm">
                                </div>
                                <div>
                                    <label class="label text-xs">Other Allowance (৳)</label>
                                    <input wire:model="otherAllowance" type="number" min="0" step="0.01"
                                        class="input text-sm">
                                </div>
                                <div>
                                    <label class="label text-xs">Pay From Account</label>
                                    <select wire:model="salaryPayAccId" class="input text-sm">
                                        <option value="0">Default</option>
                                        @foreach ($this->paymentAccounts as $pa)
                                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($baseSalary && (float) $baseSalary > 0)
                                <div class="text-sm text-indigo-700 font-medium">
                                    Gross Salary:
                                    ৳{{ number_format((float) $baseSalary + (float) $houseAllowance + (float) $transportAllowance + (float) $otherAllowance, 2) }}
                                </div>
                            @endif
                            <div class="flex gap-2">
                                <button wire:click="saveProfile" class="btn-primary btn-sm">Save</button>
                                <button wire:click="$set('showForm', false)"
                                    class="btn-secondary btn-sm">Cancel</button>
                            </div>
                        </div>
                    @endif

                    {{-- Advance Form --}}
                    @if ($showAdvanceForm && $advanceUserId === $emp->id)
                        <div class="border-t border-amber-200 bg-amber-50 p-5 space-y-4">
                            <h4 class="font-medium text-amber-900">Give Salary Advance</h4>
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="label text-xs">Amount (৳) *</label>
                                    <input wire:model="advanceAmount" type="number" min="1" step="0.01"
                                        class="input text-sm @error('advanceAmount') input-error @enderror">
                                    @error('advanceAmount')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Monthly Deduction (৳)</label>
                                    <input wire:model="monthlyDeduction" type="number" min="0"
                                        step="0.01" class="input text-sm" placeholder="0 = no auto-deduction">
                                </div>
                                <div>
                                    <label class="label text-xs">Date *</label>
                                    <input wire:model="advanceDate" type="date"
                                        class="input text-sm @error('advanceDate') input-error @enderror">
                                    @error('advanceDate')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Pay From *</label>
                                    <select wire:model="advancePayAccId"
                                        class="input text-sm @error('advancePayAccId') input-error @enderror">
                                        <option value="0">Select…</option>
                                        @foreach ($this->paymentAccounts as $pa)
                                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('advancePayAccId')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label text-xs">Purpose</label>
                                    <input wire:model="advancePurpose" type="text" class="input text-sm"
                                        placeholder="Medical, emergency, festival…">
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="giveAdvance" class="btn-success btn-sm"
                                    wire:loading.attr="disabled">
                                    Give Advance
                                </button>
                                <button wire:click="$set('showAdvanceForm', false)"
                                    class="btn-secondary btn-sm">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
