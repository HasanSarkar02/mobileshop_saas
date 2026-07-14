<?php

namespace App\Livewire\Payroll;

use App\Enums\EmploymentType;
use App\Models\PayrollComponent;
use App\Models\PayrollPolicy;
use App\Models\PayrollPolicyComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payroll Policies')]
class PayrollPolicyManager extends Component
{
    use \App\Traits\HasAuthorization;

    #[Url]
    public ?int $viewingPolicyId = null;

    public bool   $showForm       = false;
    public ?int   $editingId      = null;
    public string $name           = '';
    public string $code           = '';
    public string $description    = '';
    public string $employmentType = 'monthly';
    public bool   $isDefault      = false;

    // Component assignment (for policy being viewed)
    public array $policyComponents = [];

    public function mount(): void
    {
        $this->requirePermission('payroll.manage_components');
    }

    #[Computed]
    public function policies(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollPolicy::where('shop_id', Auth::user()->shop_id)
            ->withCount('salaryStructures')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function viewingPolicy(): ?PayrollPolicy
    {
        if (! $this->viewingPolicyId) return null;

        return PayrollPolicy::where('shop_id', Auth::user()->shop_id)
            ->with(['components' => fn ($q) => $q->orderByPivot('sequence')])
            ->find($this->viewingPolicyId);
    }

    #[Computed]
    public function allComponents(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollComponent::withoutGlobalScopes()
            ->where(fn ($q) => $q->whereNull('shop_id')
                ->orWhere('shop_id', Auth::user()->shop_id))
            ->where('is_active', true)
            ->orderBy('component_type')
            ->orderBy('sequence')
            ->get();
    }

    #[Computed]
    public function employmentTypes(): array
    {
        return EmploymentType::cases();
    }

    public function selectPolicy(int $id): void
    {
        $this->viewingPolicyId = $id;
        $this->loadPolicyComponents($id);
        unset($this->viewingPolicy);
    }

    private function loadPolicyComponents(int $policyId): void
    {
        $policy = PayrollPolicy::where('shop_id', Auth::user()->shop_id)
            ->find($policyId);

        if (! $policy) return;

        // Load pivot rows directly — avoids auto-guessed FK names
        $pivotRows = DB::table('payroll_policy_components')
            ->where('policy_id', $policyId)
            ->get()
            ->keyBy('component_id');

        $this->policyComponents = $this->allComponents->map(function ($comp) use ($pivotRows) {
            $pivot = $pivotRows->get($comp->id);

            return [
                'component_id'     => $comp->id,
                'name'             => $comp->name,
                'code'             => $comp->code,
                'component_type'   => $comp->component_type->value,
                'is_system'        => $comp->is_system,
                'included'         => (bool) $pivot,
                'calculation_type' => $pivot?->calculation_type ?? $comp->calculation_type->value,
                'default_value'    => (float) ($pivot?->default_value ?? $comp->default_value ?? 0),
                'percentage_of'    => $pivot?->percentage_of ?? $comp->percentage_of ?? '',
                'formula'          => $pivot?->formula ?? $comp->formula ?? '',
                'is_required'      => (bool) ($pivot?->is_required ?? false),
                'sequence'         => (int) ($pivot?->sequence ?? $comp->sequence ?? 100),
            ];
        })->values()->toArray();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $policy = PayrollPolicy::where('shop_id', Auth::user()->shop_id)->findOrFail($id);
        $this->editingId      = $id;
        $this->name           = $policy->name;
        $this->code           = $policy->code;
        $this->description    = $policy->description ?? '';
        $this->employmentType = $policy->employment_type;
        $this->isDefault      = $policy->is_default;
        $this->showForm       = true;
    }

    public function savePolicy(): void
    {
        $this->validate([
            'name'           => 'required|string|max:150',
            'code'           => 'required|string|max:50|regex:/^[A-Z0-9_]+$/',
            'employmentType' => 'required|string',
        ]);

        DB::transaction(function () {
            // If setting this as default, unset others
            if ($this->isDefault) {
                PayrollPolicy::where('shop_id', Auth::user()->shop_id)
                    ->where('id', '!=', $this->editingId ?? 0)
                    ->update(['is_default' => false]);
            }

            $data = [
                'shop_id'         => Auth::user()->shop_id,
                'name'            => $this->name,
                'code'            => strtoupper($this->code),
                'description'     => $this->description ?: null,
                'employment_type' => $this->employmentType,
                'is_default'      => $this->isDefault,
                'is_active'       => true,
            ];

            if ($this->editingId) {
                PayrollPolicy::where('shop_id', Auth::user()->shop_id)
                    ->findOrFail($this->editingId)
                    ->update($data);
                $this->dispatch('notify', ['type' => 'success', 'message' => 'Policy updated.']);
            } else {
                $policy = PayrollPolicy::create($data);
                $this->viewingPolicyId = $policy->id;
                $this->loadPolicyComponents($policy->id);
                $this->dispatch('notify', ['type' => 'success',
                    'message' => 'Policy created. Now assign components below.']);
            }
        });

        $this->resetForm();
        unset($this->policies, $this->viewingPolicy);
    }

    public function savePolicyComponents(): void
    {
        if (! $this->viewingPolicyId) return;

        $policy = PayrollPolicy::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($this->viewingPolicyId);

        DB::transaction(function () use ($policy) {
            // Detach all using correct FK
            DB::table('payroll_policy_components')
                ->where('policy_id', $policy->id)
                ->delete();

            foreach ($this->policyComponents as $comp) {
                if (! $comp['included']) continue;

                DB::table('payroll_policy_components')->insert([
                    'policy_id'        => $policy->id,
                    'component_id'     => $comp['component_id'],
                    'calculation_type' => $comp['calculation_type'],
                    'default_value'    => (float) $comp['default_value'],
                    'percentage_of'    => $comp['percentage_of'] ?: null,
                    'formula'          => $comp['formula'] ?: null,
                    'is_required'      => $comp['is_required'] ? 1 : 0,
                    'sequence'         => (int) $comp['sequence'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        });

        unset($this->viewingPolicy);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Policy components saved.']);
    }

    public function setDefault(int $id): void
    {
        DB::transaction(function () use ($id) {
            PayrollPolicy::where('shop_id', Auth::user()->shop_id)
                ->update(['is_default' => false]);

            PayrollPolicy::where('shop_id', Auth::user()->shop_id)
                ->where('id', $id)
                ->update(['is_default' => true]);
        });

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Default policy updated.']);
        unset($this->policies);
    }

    private function resetForm(): void
    {
        $this->editingId      = null;
        $this->name           = '';
        $this->code           = '';
        $this->description    = '';
        $this->employmentType = 'monthly';
        $this->isDefault      = false;
        $this->showForm       = false;
    }

    public function render()
    {
        return view('livewire.payroll.payroll-policy-manager', [
            'policies'       => $this->policies,
            'viewingPolicy'  => $this->viewingPolicy,
            'allComponents'  => $this->allComponents,
            'employmentTypes'=> $this->employmentTypes,
        ]);
    }
}