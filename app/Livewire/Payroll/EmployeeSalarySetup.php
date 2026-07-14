<?php

namespace App\Livewire\Payroll;

use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeSalaryStructure;
use App\Models\PaymentAccount;
use App\Models\PayrollPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Salary Setup')]
class EmployeeSalarySetup extends Component
{
    use \App\Traits\HasAuthorization;

    public User $user;

    // Structure fields
    public int    $policyId            = 0;
    public int    $departmentId        = 0;
    public string $designation         = '';
    public string $employmentType      = 'monthly';
    public string $effectiveFrom       = '';
    public int    $paymentAccountId    = 0;
    public string $paymentMethod       = 'cash';
    public string $bankName            = '';
    public string $bankAccountNumber   = '';
    public string $bankRoutingNumber   = '';
    public int    $monthlyWorkingDays  = 26;
    public int    $weeklyOffDays       = 1;
    public string $overtimeRate        = '';

    // Component overrides indexed by component_id
    // Each entry: ['included' => bool, 'calculation_type' => ..., 'value' => ...]
    public array  $componentOverrides  = [];
    public ?int   $editingStructureId  = null;

    public function mount(User $user): void
    {
        $this->requirePermission('payroll.manage_structure');

        if ($user->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $this->user          = $user;
        $this->effectiveFrom = now()->startOfMonth()->format('Y-m-d');

        // Load existing active structure if present
        $existing = EmployeeSalaryStructure::where('user_id', $user->id)
            ->where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        if ($existing) {
            $this->loadStructure($existing);
        }
    }

    private function loadStructure(EmployeeSalaryStructure $structure): void
    {
        $this->editingStructureId  = $structure->id;
        $this->policyId            = $structure->policy_id;
        $this->departmentId        = $structure->department_id ?? 0;
        $this->designation         = $structure->designation ?? '';
        $this->employmentType      = $structure->employment_type->value;
        $this->effectiveFrom       = $structure->effective_from->format('Y-m-d');
        $this->paymentAccountId    = $structure->payment_account_id ?? 0;
        $this->paymentMethod       = $structure->payment_method ?? 'cash';
        $this->bankName            = $structure->bank_name ?? '';
        $this->bankAccountNumber   = $structure->bank_account_number ?? '';
        $this->bankRoutingNumber   = $structure->bank_routing_number ?? '';
        $this->monthlyWorkingDays  = $structure->monthly_working_days;
        $this->weeklyOffDays       = $structure->weekly_off_days;
        $this->overtimeRate        = $structure->overtime_rate ?? '';

        $this->loadComponentOverrides($structure);
    }

    private function loadComponentOverrides(EmployeeSalaryStructure $structure): void
    {
        $policy = PayrollPolicy::with([
            'components' => fn ($q) => $q->orderByPivot('sequence'),
        ])->find($this->policyId);

        if (! $policy) return;

        $existingOverrides = EmployeeSalaryComponent::where('salary_structure_id', $structure->id)
            ->with('component')
            ->get()
            ->keyBy('component_id');

        $this->componentOverrides = [];

        foreach ($policy->components as $comp) {
            $override = $existingOverrides->get($comp->id);

            $this->componentOverrides[$comp->id] = [
                'component_id'     => $comp->id,
                'name'             => $comp->name,
                'code'             => $comp->code,
                'component_type'   => $comp->component_type->value,
                'sequence'         => $comp->pivot->sequence,
                'policy_value'     => (float) $comp->pivot->default_value,
                'policy_calc_type' => $comp->pivot->calculation_type,
                'policy_pct_of'    => $comp->pivot->percentage_of,
                'has_override'     => (bool) $override,
                'calculation_type' => $override?->calculation_type
                    ?? $comp->pivot->calculation_type,
                'value'            => (float) ($override?->value
                    ?? $comp->pivot->default_value
                    ?? 0),
                'percentage_of'    => $override?->percentage_of
                    ?? $comp->pivot->percentage_of
                    ?? '',
                'formula'          => $override?->formula
                    ?? $comp->pivot->formula
                    ?? '',
            ];
        }
    }

    public function updatedPolicyId(): void
    {
        // If editing an existing structure, reload with overrides
        if ($this->editingStructureId) {
            $structure = EmployeeSalaryStructure::find($this->editingStructureId);
            if ($structure) {
                $this->loadComponentOverrides($structure);
                return;
            }
        }

        // Fresh policy — no existing structure yet
        if (! $this->policyId) {
            $this->componentOverrides = [];
            return;
        }

        $policy = PayrollPolicy::with([
            'components' => fn ($q) => $q->orderByPivot('sequence'),
        ])->find($this->policyId);

        if (! $policy) {
            $this->componentOverrides = [];
            return;
        }

        $this->componentOverrides = [];

        foreach ($policy->components as $comp) {
            $this->componentOverrides[$comp->id] = [
                'component_id'     => $comp->id,
                'name'             => $comp->name,
                'code'             => $comp->code,
                'component_type'   => $comp->component_type->value,
                'sequence'         => $comp->pivot->sequence,
                'policy_value'     => (float) $comp->pivot->default_value,
                'policy_calc_type' => $comp->pivot->calculation_type,
                'policy_pct_of'    => $comp->pivot->percentage_of,
                'has_override'     => false,
                'calculation_type' => $comp->pivot->calculation_type,
                'value'            => (float) ($comp->pivot->default_value ?? 0),
                'percentage_of'    => $comp->pivot->percentage_of ?? '',
                'formula'          => $comp->pivot->formula ?? '',
            ];
        }
    }

    #[Computed]
    public function policies(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollPolicy::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get();
    }

    #[Computed]
    public function departments(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function employmentTypes(): array
    {
        return EmploymentType::cases();
    }

    #[Computed]
    public function activeStructure(): ?EmployeeSalaryStructure
    {
        return EmployeeSalaryStructure::where('user_id', $this->user->id)
            ->where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->with(['policy', 'department'])
            ->latest('effective_from')
            ->first();
    }

    #[Computed]
    public function grossSalaryPreview(): float
    {
        return collect($this->componentOverrides)
            ->filter(fn ($c) => $c['component_type'] === 'earning')
            ->sum(fn ($c) => (float) ($c['value'] ?? 0));
    }

    public function save(): void
    {
        $this->validate([
            'policyId'           => 'required|integer|min:1',
            'effectiveFrom'      => 'required|date',
            'monthlyWorkingDays' => 'required|integer|min:1|max:31',
        ], [
            'policyId.min' => 'Please select a payroll policy.',
        ]);

        DB::transaction(function () {
            // Deactivate current structure
            EmployeeSalaryStructure::where('user_id', $this->user->id)
                ->where('shop_id', Auth::user()->shop_id)
                ->where('is_active', true)
                ->where('id', '!=', $this->editingStructureId ?? 0)
                ->update(['is_active' => false, 'effective_to' => now()->toDateString()]);

            $structureData = [
                'shop_id'              => Auth::user()->shop_id,
                'user_id'              => $this->user->id,
                'policy_id'            => $this->policyId,
                'department_id'        => $this->departmentId ?: null,
                'designation'          => $this->designation ?: null,
                'employment_type'      => $this->employmentType,
                'effective_from'       => $this->effectiveFrom,
                'effective_to'         => null,
                'payment_account_id'   => $this->paymentAccountId ?: null,
                'payment_method'       => $this->paymentMethod,
                'bank_name'            => $this->bankName ?: null,
                'bank_account_number'  => $this->bankAccountNumber ?: null,
                'bank_routing_number'  => $this->bankRoutingNumber ?: null,
                'monthly_working_days' => $this->monthlyWorkingDays,
                'weekly_off_days'      => $this->weeklyOffDays,
                'overtime_rate'        => $this->overtimeRate ?: null,
                'is_active'            => true,
                'created_by'           => Auth::id(),
            ];

            if ($this->editingStructureId) {
                EmployeeSalaryStructure::findOrFail($this->editingStructureId)
                    ->update($structureData);
                $structure = EmployeeSalaryStructure::findOrFail($this->editingStructureId);
            } else {
                $structure = EmployeeSalaryStructure::create($structureData);
                $this->editingStructureId = $structure->id;
            }

            // Save component overrides
            foreach ($this->componentOverrides as $compId => $override) {
                $policyDefault = $this->componentDefaultFromPolicy($compId);

                $valueChanged = (float) $override['value'] !== (float) $policyDefault;
                $typeChanged  = $override['calculation_type'] !== $override['policy_calc_type'];

                if ($valueChanged || $typeChanged) {
                    EmployeeSalaryComponent::updateOrCreate(
                        ['salary_structure_id' => $structure->id, 'component_id' => $compId],
                        [
                            'calculation_type' => $override['calculation_type'],
                            'value'            => (float) $override['value'],
                            'percentage_of'    => $override['percentage_of'] ?: null,
                            'formula'          => $override['formula'] ?: null,
                            'is_active'        => true,
                        ]
                    );
                } else {
                    // Remove override — use policy default
                    EmployeeSalaryComponent::where('salary_structure_id', $structure->id)
                        ->where('component_id', $compId)
                        ->delete();
                }
            }
        });

        unset($this->activeStructure);
        $this->dispatch('notify', ['type' => 'success',
            'message' => "Salary structure for {$this->user->name} saved."]);
    }

    private function componentDefaultFromPolicy(int $componentId): float
    {
        if (! $this->policyId) return 0;

        $policy = PayrollPolicy::with(['components'])->find($this->policyId);
        $comp   = $policy?->components->find($componentId);

        return (float) ($comp?->pivot?->default_value ?? 0);
    }

    public function render()
    {
        return view('livewire.payroll.employee-salary-setup', [
            'policies'          => $this->policies,
            'departments'       => $this->departments,
            'paymentAccounts'   => $this->paymentAccounts,
            'employmentTypes'   => $this->employmentTypes,
            'activeStructure'   => $this->activeStructure,
            'grossSalaryPreview'=> $this->grossSalaryPreview,
        ]);
    }
}