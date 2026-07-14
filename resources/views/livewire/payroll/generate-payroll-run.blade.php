<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Generate Payroll Run</h2>
            <p class="text-sm text-gray-500 mt-0.5">Step {{ $step }} of 3</p>
        </div>
        <a href="{{ route('payroll.index') }}" wire:navigate class="btn-secondary btn-sm">← Cancel</a>
    </div>

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2">
        @foreach ([1 => 'Scope', 2 => 'Preview', 3 => 'Done'] as $num => $label)
            <div class="flex items-center gap-2">
                <div
                    class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $step >= $num ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                    {{ $step > $num ? '✓' : $num }}
                </div>
                <span class="text-sm {{ $step >= $num ? 'text-indigo-700 font-medium' : 'text-gray-400' }}">
                    {{ $label }}
                </span>
            </div>
            @if ($num < 3)
                <div class="flex-1 h-px {{ $step > $num ? 'bg-indigo-400' : 'bg-gray-200' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- ── STEP 1: SCOPE ── --}}
    @if ($step === 1)
        <div class="card p-6 space-y-5">
            <h3 class="font-semibold text-gray-900">Select Payroll Scope</h3>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Year *</label>
                    <select wire:model="year" class="input">
                        @foreach ($this->yearOptions as $y => $label)
                            <option value="{{ $y }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('year')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Month *</label>
                    <select wire:model="month" class="input">
                        @foreach ($this->monthOptions as $m => $mLabel)
                            <option value="{{ $m }}">{{ $mLabel }}</option>
                        @endforeach
                    </select>
                    @error('month')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Branch <span class="text-xs font-normal text-gray-400">(leave blank for
                            all)</span></label>
                    <select wire:model="branchId" class="input">
                        <option value="0">All Branches</option>
                        @foreach ($this->branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Department <span
                            class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <select wire:model="departmentId" class="input">
                        <option value="0">All Departments</option>
                        @foreach ($this->departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Employment Type <span
                            class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <select wire:model="employmentType" class="input">
                        <option value="">All Types</option>
                        @foreach (\App\Enums\EmploymentType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Description / Note</label>
                    <input wire:model="description" type="text" class="input"
                        placeholder="Optional note for this run…">
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-800">
                ℹ Payroll will be generated for all employees with an <strong>active salary structure</strong>
                that covers the selected period within the specified scope.
            </div>

            <button wire:click="goToPreview" class="btn-primary" wire:loading.attr="disabled" wire:target="goToPreview">
                <span wire:loading.remove>Preview Employees →</span>
                <span wire:loading>Loading…</span>
            </button>
        </div>
    @endif

    {{-- ── STEP 2: PREVIEW ── --}}
    @if ($step === 2)
        {{-- Warnings --}}
        @if (!empty($warnings))
            <div class="card p-4 bg-amber-50 border-amber-300 space-y-1">
                <div class="font-semibold text-amber-800 text-sm">⚠ Warnings</div>
                @foreach ($warnings as $w)
                    <div class="text-xs text-amber-700">{{ $w }}</div>
                @endforeach
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">
                        {{ count($previewEmployees) }} Employee(s) —
                        {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Review the list. Salary amounts will be computed from each employee's active structure.
                    </p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Employee</th>
                            <th class="table-th">Designation</th>
                            <th class="table-th">Department</th>
                            <th class="table-th">Policy</th>
                            <th class="table-th">Type</th>
                            <th class="table-th text-center">Work Days</th>
                            <th class="table-th">Flags</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($previewEmployees as $emp)
                            <tr class="hover:bg-gray-50 {{ !empty($emp['warnings']) ? 'bg-amber-50' : '' }}">
                                <td class="table-td font-semibold text-gray-900">{{ $emp['name'] }}</td>
                                <td class="table-td text-gray-500 text-sm">{{ $emp['designation'] }}</td>
                                <td class="table-td text-gray-500 text-sm">{{ $emp['department'] }}</td>
                                <td class="table-td text-gray-500 text-xs">{{ $emp['policy'] }}</td>
                                <td class="table-td text-xs">{{ $emp['employment_type'] }}</td>
                                <td class="table-td text-center">{{ $emp['working_days'] }}</td>
                                <td class="table-td text-xs">
                                    @foreach ($emp['warnings'] as $flag)
                                        <span class="badge badge-yellow text-xs">{{ $flag }}</span>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex gap-3">
            <button wire:click="$set('step', 1)" class="btn-secondary">← Back</button>
            <button wire:click="generate" class="btn-primary" wire:loading.attr="disabled" wire:target="generate">
                <span wire:loading.remove wire:target="generate">
                    ✓ Generate Payroll for {{ count($previewEmployees) }} Employees
                </span>
                <span wire:loading wire:target="generate" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            class="opacity-25" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                    Generating…
                </span>
            </button>
        </div>
    @endif

    {{-- ── STEP 3: DONE ── --}}
    @if ($step === 3)
        <div class="card p-8 text-center space-y-4">
            <div class="text-5xl">✅</div>
            <h3 class="text-xl font-bold text-green-700">Payroll Generated Successfully</h3>
            @if (!empty($warnings))
                <div class="text-left bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-1">
                    <div class="font-semibold text-amber-800 text-sm mb-2">Warnings during generation:</div>
                    @foreach ($warnings as $w)
                        <div class="text-xs text-amber-700">{{ $w }}</div>
                    @endforeach
                </div>
            @endif
            <div class="flex gap-3 justify-center">
                @if ($generatedRunId)
                    <a href="{{ route('payroll.run.show', $generatedRunId) }}" wire:navigate class="btn-primary">
                        View Run & Submit for Approval →
                    </a>
                @endif
                <a href="{{ route('payroll.generate') }}" wire:navigate class="btn-secondary">
                    Generate Another
                </a>
            </div>
        </div>
    @endif

</div>
