<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SubscriptionPlan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Subscription Plans')]
class PlanManager extends Component
{
    public bool   $showForm    = false;
    public ?int   $editingId   = null;
    public string $name        = '';
    public string $slug        = '';
    public string $monthlyPrice= '';
    public string $yearlyPrice = '';
    public int    $maxBranches = 1;
    public int    $maxEmployees= 5;
    public int    $maxProducts = 500;
    public array  $features    = [];
    public int    $sortOrder   = 0;
    public bool   $isActive    = true;

    // Feature checkboxes
    public array $availableFeatures = [
        'pos' => 'Point of Sale', 'sales' => 'Sales Module',
        'purchases' => 'Purchases', 'expenses' => 'Expenses',
        'payroll' => 'Payroll', 'treasury' => 'Treasury',
        'service' => 'Service & Repair', 'reports' => 'Advanced Reports',
        'used_phones' => 'Used Phones', 'sms' => 'SMS Notifications',
        'multi_branch' => 'Multi-Branch', 'all' => 'All Features',
    ];

    #[Computed]
    public function plans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::withCount('activeSubscriptions')
            ->orderBy('sort_order')
            ->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId','name','slug','monthlyPrice','yearlyPrice',
            'maxBranches','maxEmployees','maxProducts','features','sortOrder']);
        $this->isActive  = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $this->editingId    = $id;
        $this->name         = $plan->name;
        $this->slug         = $plan->slug;
        $this->monthlyPrice = (string) $plan->monthly_price;
        $this->yearlyPrice  = (string) ($plan->yearly_price ?? '');
        $this->maxBranches  = $plan->max_branches;
        $this->maxEmployees = $plan->max_employees;
        $this->maxProducts  = $plan->max_products;
        $this->features     = $plan->features ?? [];
        $this->sortOrder    = $plan->sort_order;
        $this->isActive     = $plan->is_active;
        $this->showForm     = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'         => 'required|string|max:100',
            'slug'         => 'required|string|max:50',
            'monthlyPrice' => 'required|numeric|min:0',
            'maxBranches'  => 'required|integer|min:1',
        ]);

        $data = [
            'name'          => $this->name,
            'slug'          => \Illuminate\Support\Str::slug($this->slug),
            'monthly_price' => (float) $this->monthlyPrice,
            'yearly_price'  => $this->yearlyPrice ? (float) $this->yearlyPrice : null,
            'max_branches'  => $this->maxBranches,
            'max_employees' => $this->maxEmployees,
            'max_products'  => $this->maxProducts,
            'features'      => array_values($this->features),
            'sort_order'    => $this->sortOrder,
            'is_active'     => $this->isActive,
        ];

        if ($this->editingId) {
            SubscriptionPlan::findOrFail($this->editingId)->update($data);
            $msg = 'Plan updated.';
        } else {
            SubscriptionPlan::create($data);
            $msg = 'Plan created.';
        }

        $this->showForm = false;
        unset($this->plans);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function render()
    {
        return view('livewire.admin.plan-manager');
    }
}