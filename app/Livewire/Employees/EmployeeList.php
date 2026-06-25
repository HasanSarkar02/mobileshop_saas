<?php

namespace App\Livewire\Employees;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use \App\Traits\HasAuthorization;

#[Layout('components.layouts.app')]
#[Title('Employees')]
class EmployeeList extends Component
{
    use WithPagination;
    use HasAuthorization;

    #[Url(as: 'q')]
    public string $search  = '';

    #[Url]
    public string $branchFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }

    #[Computed]
    public function stats(): array
    {
        $employees = User::where('user_type', UserType::Employee->value);

        return [
            'total'    => (clone $employees)->count(),
            'active'   => (clone $employees)->where('is_active', true)->count(),
            'inactive' => (clone $employees)->where('is_active', false)->count(),
        ];
    }

    public function mount(): void
    {
        $this->requirePermission('employees.view');
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('notify', ['type' => 'success',
            'message' => $user->is_active ? "{$user->name} deactivated." : "{$user->name} activated."]);
    }

    public function render()
    {
        $employees = User::where('user_type', UserType::Employee->value)
            ->with(['employeeProfile', 'branch', 'roles'])
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%")
            )
            ->when($this->branchFilter, fn ($q) =>
                $this->branchFilter === 'any'
                    ? $q->whereNull('branch_id')
                    : $q->where('branch_id', $this->branchFilter)
            )
            ->latest()
            ->paginate(20);

        $branches = \App\Models\Branch::where('is_active', true)->get();

        return view('livewire.employees.employee-list', compact('employees', 'branches'));
    }
    
}