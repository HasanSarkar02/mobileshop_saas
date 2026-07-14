<?php

namespace App\Livewire\Payroll;

use App\Enums\ComponentCalculationType;
use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payroll Components')]
class PayrollComponentManager extends Component
{
    use \App\Traits\HasAuthorization;

    #[Url]
    public string $activeTab = 'earnings';

    public bool   $showForm         = false;
    public ?int   $editingId        = null;
    public string $name             = '';
    public string $code             = '';
    public string $componentType    = 'earning';
    public string $calculationType  = 'fixed';
    public string $defaultValue     = '0';
    public string $percentageOf     = '';
    public string $formula          = '';
    public bool   $isTaxable        = false;
    public bool   $isRecurring      = true;
    public int    $sequence         = 100;
    public string $glAccountCode    = '';
    public string $description      = '';

    // Reactive flags for conditional form fields
    public bool $showPercentageOf = false;
    public bool $showFormula      = false;

    public function mount(): void
    {
        $this->requirePermission('payroll.manage_components');
    }

    public function updatedCalculationType(): void
    {
        $this->showPercentageOf = $this->calculationType === 'percentage';
        $this->showFormula      = $this->calculationType === 'formula';
    }

    #[Computed]
    public function globalComponents(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollComponent::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->where('component_type', $this->activeTab === 'earnings' ? 'earning' : 'deduction')
            ->orderBy('sequence')
            ->get();
    }

    #[Computed]
    public function shopComponents(): \Illuminate\Database\Eloquent\Collection
    {
        return PayrollComponent::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->where('component_type', $this->activeTab === 'earnings' ? 'earning' : 'deduction')
            ->orderBy('sequence')
            ->get();
    }

    #[Computed]
    public function allEarningCodes(): array
    {
        return PayrollComponent::withoutGlobalScopes()
            ->where(fn ($q) => $q->whereNull('shop_id')
                ->orWhere('shop_id', Auth::user()->shop_id))
            ->where('component_type', 'earning')
            ->where('is_active', true)
            ->orderBy('sequence')
            ->pluck('name', 'code')
            ->toArray();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->componentType = $this->activeTab === 'earnings' ? 'earning' : 'deduction';
        $this->showForm      = true;
    }

    public function openEdit(int $id): void
    {
        $comp = PayrollComponent::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id) // only shop-specific ones are editable
            ->findOrFail($id);

        $this->editingId       = $id;
        $this->name            = $comp->name;
        $this->code            = $comp->code;
        $this->componentType   = $comp->component_type->value;
        $this->calculationType = $comp->calculation_type->value;
        $this->defaultValue    = (string) ($comp->default_value ?? 0);
        $this->percentageOf    = $comp->percentage_of ?? '';
        $this->formula         = $comp->formula ?? '';
        $this->isTaxable       = $comp->is_taxable;
        $this->isRecurring     = $comp->is_recurring;
        $this->sequence        = $comp->sequence;
        $this->glAccountCode   = $comp->gl_account_code ?? '';
        $this->description     = $comp->description ?? '';

        $this->showPercentageOf = $this->calculationType === 'percentage';
        $this->showFormula      = $this->calculationType === 'formula';
        $this->showForm         = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'          => 'required|string|max:150',
            'code'          => 'required|string|max:50|regex:/^[A-Z0-9_]+$/',
            'defaultValue'  => 'nullable|numeric|min:0',
            'sequence'      => 'required|integer|min:1|max:999',
        ], [
            'code.regex' => 'Code must be uppercase letters, numbers, and underscores only.',
        ]);

        // Check code uniqueness within shop
        $existsQuery = PayrollComponent::withoutGlobalScopes()
            ->where(fn ($q) => $q->whereNull('shop_id')
                ->orWhere('shop_id', Auth::user()->shop_id))
            ->where('code', strtoupper($this->code));

        if ($this->editingId) {
            $existsQuery->where('id', '!=', $this->editingId);
        }

        if ($existsQuery->exists()) {
            $this->addError('code', "Component code '{$this->code}' already exists.");
            return;
        }

        $data = [
            'shop_id'          => Auth::user()->shop_id,
            'name'             => $this->name,
            'code'             => strtoupper($this->code),
            'component_type'   => $this->componentType,
            'calculation_type' => $this->calculationType,
            'default_value'    => $this->calculationType === 'fixed' ? (float) $this->defaultValue : null,
            'percentage_of'    => $this->calculationType === 'percentage' ? strtoupper($this->percentageOf) : null,
            'formula'          => $this->calculationType === 'formula' ? $this->formula : null,
            'is_taxable'       => $this->isTaxable,
            'is_recurring'     => $this->isRecurring,
            'is_system'        => false,
            'affects_gross'    => $this->componentType === 'earning',
            'sequence'         => $this->sequence,
            'gl_account_code'  => $this->glAccountCode ?: null,
            'description'      => $this->description ?: null,
            'is_active'        => true,
        ];

        if ($this->editingId) {
            PayrollComponent::withoutGlobalScopes()
                ->where('shop_id', Auth::user()->shop_id)
                ->findOrFail($this->editingId)
                ->update($data);
            $msg = 'Component updated.';
        } else {
            PayrollComponent::create($data);
            $msg = 'Custom component created.';
        }

        $this->resetForm();
        unset($this->shopComponents);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function toggleShopComponent(int $id): void
    {
        $comp = PayrollComponent::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->findOrFail($id);

        $comp->update(['is_active' => ! $comp->is_active]);
        unset($this->shopComponents);
        $this->dispatch('notify', ['type' => 'success',
            'message' => "{$comp->name} " . ($comp->is_active ? 'deactivated' : 'reactivated') . "."]);
    }

    private function resetForm(): void
    {
        $this->editingId       = null;
        $this->name            = '';
        $this->code            = '';
        $this->calculationType = 'fixed';
        $this->defaultValue    = '0';
        $this->percentageOf    = '';
        $this->formula         = '';
        $this->isTaxable       = false;
        $this->isRecurring     = true;
        $this->sequence        = 100;
        $this->glAccountCode   = '';
        $this->description     = '';
        $this->showPercentageOf= false;
        $this->showFormula     = false;
        $this->showForm        = false;
    }

    public function render()
    {
        return view('livewire.payroll.payroll-component-manager');
    }
}