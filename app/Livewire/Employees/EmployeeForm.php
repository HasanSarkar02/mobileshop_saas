<?php

namespace App\Livewire\Employees;

use App\Actions\CreateEmployeeAction;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use \App\Traits\HasAuthorization;

#[Layout('components.layouts.app')]
#[Title('Employee')]
class EmployeeForm extends Component
{
    use HasAuthorization;
    public ?User $employee = null;

    public string $name     = '';
    public string $email    = '';
    public string $phone    = '';
    public int    $branchId = 0;
    public string $role     = '';

    public function mount(?User $employee = null): void
    {
        $this->requirePermission('employees.manage');

        if ($employee && $employee->exists) {
            $this->employee = $employee;
            $this->name     = $employee->name;
            $this->email    = $employee->email;
            $this->phone    = $employee->phone ?? '';
            $this->branchId = $employee->branch_id ?? 0;
            $this->role     = $employee->roles->first()?->name ?? '';
        }
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
                     ->where('is_active', true)->get();
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::where('shop_id', Auth::user()->shop_id)
                   ->where('name', '!=', 'Owner')
                   ->get();
    }

    public function save(CreateEmployeeAction $action): void
    {
        $isNew = ! $this->employee?->exists;

        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                $isNew
                    ? \Illuminate\Validation\Rule::unique('users')
                    : \Illuminate\Validation\Rule::unique('users')->ignore($this->employee->id),
            ],
            'phone' => 'nullable|string|max:20',
            'role'  => 'required|string',
        ]);

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        if ($isNew) {
            // New employee → send invite
            try {
                $action->execute($shop, [
                    'name'      => $this->name,
                    'email'     => $this->email,
                    'phone'     => $this->phone ?: null,
                    'branch_id' => $this->branchId ?: null,
                    'role'      => $this->role,
                ]);

                $this->dispatch('notify', ['type' => 'success',
                    'message' => "Invite sent to {$this->email}."]);
                $this->redirect(route('employees.index'), navigate: true);

            } catch (\Exception $e) {
                $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            // Edit existing employee
            $oldRole   = $this->employee->roles->first()?->name;
            $oldBranch = $this->employee->branch_id;
            $this->employee->update([
                'name'      => $this->name,
                'phone'     => $this->phone ?: null,
                'branch_id' => $this->branchId ?: null,
            ]);

            // Update role if changed
            if ($this->role && $this->employee->roles->first()?->name !== $this->role) {
                app(\Spatie\Permission\PermissionRegistrar::class)
                    ->setPermissionsTeamId($shop->id);
                $this->employee->syncRoles([$this->role]);
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($this->employee)
                ->withProperties([
                    'name'       => $this->name,
                    'phone'      => $this->phone,
                    'branch_id'  => $this->branchId ?: null,
                    'old_branch' => $oldBranch,
                    'role'       => $this->role,
                    'old_role'   => $oldRole,
                ])
                ->log('employee.updated');

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Employee updated.']);
            $this->redirect(route('employees.show', $this->employee), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.employees.employee-form');
    }
}