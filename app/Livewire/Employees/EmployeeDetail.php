<?php

namespace App\Livewire\Employees;

use App\Enums\PermissionEnum;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\PermissionRegistrar;
use \App\Traits\HasAuthorization;

#[Layout('components.layouts.app')]
#[Title('Employee Detail')]
class EmployeeDetail extends Component
{
    use HasAuthorization;
    public User $employee;

    public string $activeTab = 'overview';

    // Permission management
    public array  $selectedPermissions = [];
    public string $selectedRole        = '';
    public bool   $permissionsChanged  = false;

    public function mount(User $employee): void
    {
        $this->requirePermission('employees.view');

        // Security: must belong to same shop
        if ($employee->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $this->employee = $employee->load([
            'roles',
            'permissions',
            'branch',
            'employeeProfile',
            'activeSalaryStructure.policy',
            'activeSalaryStructure.department',
        ]);

        $this->selectedRole = $employee->roles->first()?->name ?? '';

        // Load current direct permissions
        $this->selectedPermissions = $employee->permissions
            ->pluck('name')
            ->toArray();
    }

    #[Computed]
    public function allRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::where('shop_id', Auth::user()->shop_id)
                   ->where('name', '!=', 'Owner')
                   ->get();
    }

    #[Computed]
    public function permissionsByGroup(): array
    {
        $rolePermissions = $this->getRolePermissions();

        return collect(PermissionEnum::cases())
            ->groupBy(fn ($p) => $p->group())
            ->map(fn ($perms) => $perms->map(fn ($p) => [
                'name'    => $p->value,
                'label'   => $p->label(),
                'fromRole' => in_array($p->value, $rolePermissions),
                'direct'   => in_array($p->value, $this->selectedPermissions),
            ])->values())
            ->toArray();
    }

    private function getRolePermissions(): array
    {
        if (! $this->selectedRole) return [];

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($shop->id);

        $role = Role::where('name', $this->selectedRole)
                    ->where('shop_id', $shop->id)
                    ->first();

        return $role?->permissions->pluck('name')->toArray() ?? [];
    }

    #[Computed]
    public function monthSales(): array
    {
        $sales = Sale::where('cashier_id', $this->employee->id)
            ->where('status', 'confirmed')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->selectRaw('COUNT(*) as count, SUM(grand_total) as revenue, SUM(gross_profit) as profit')
            ->first();

        return [
            'count'   => $sales->count ?? 0,
            'revenue' => (float) ($sales->revenue ?? 0),
            'profit'  => (float) ($sales->profit ?? 0),
        ];
    }

    public function updatedSelectedRole(): void
    {
        $this->permissionsChanged = true;
    }

    public function updatedSelectedPermissions(): void
    {
        $this->permissionsChanged = true;
    }

    public function savePermissions(): void
    {
        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($shop->id);

        // Sync role
        if ($this->selectedRole) {
            $this->employee->syncRoles([$this->selectedRole]);
        } else {
            $this->employee->syncRoles([]);
        }

        // Sync direct permissions (additional ones beyond role)
        $this->employee->syncPermissions($this->selectedPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->permissionsChanged = false;
        $this->employee->refresh()->load('roles', 'permissions');

        $this->dispatch('notify', ['type' => 'success',
            'message' => "Permissions updated for {$this->employee->name}."]);
    }

    public function resendInvite(): void
    {
        app(\App\Services\UserInviter::class)->invite(
            $this->employee,
            "an employee of " . Auth::user()->shop?->name
        );
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Invite email resent.']);
    }

    public function render()
    {
        return view('livewire.employees.employee-detail');
    }
}