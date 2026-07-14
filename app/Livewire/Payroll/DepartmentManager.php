<?php

namespace App\Livewire\Payroll;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Departments')]
class DepartmentManager extends Component
{
    use \App\Traits\HasAuthorization;

    // Form state
    public bool   $showForm       = false;
    public ?int   $editingId      = null;
    public string $name           = '';
    public string $code           = '';
    public string $description    = '';
    public int    $parentId       = 0;
    public int    $headUserId     = 0;

    public function mount(): void
    {
        $this->requirePermission('payroll.manage_departments');
    }

    #[Computed]
    public function departments(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::where('shop_id', Auth::user()->shop_id)
            ->with(['parent', 'head'])
            ->withCount('salaryStructures')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->where('user_type', 'employee')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $dept = Department::where('shop_id', Auth::user()->shop_id)->findOrFail($id);
        $this->editingId   = $id;
        $this->name        = $dept->name;
        $this->code        = $dept->code ?? '';
        $this->description = $dept->description ?? '';
        $this->parentId    = $dept->parent_department_id ?? 0;
        $this->headUserId  = $dept->head_user_id ?? 0;
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'code' => [
                'nullable', 'string', 'max:30',
                \Illuminate\Validation\Rule::unique('departments', 'code')
                    ->where('shop_id', Auth::user()->shop_id)
                    ->ignore($this->editingId),
            ],
        ]);

        // Prevent circular parent
        if ($this->editingId && $this->parentId === $this->editingId) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'A department cannot be its own parent.']);
            return;
        }

        $data = [
            'shop_id'               => Auth::user()->shop_id,
            'name'                  => $this->name,
            'code'                  => $this->code ?: null,
            'description'           => $this->description ?: null,
            'parent_department_id'  => $this->parentId ?: null,
            'head_user_id'          => $this->headUserId ?: null,
            'is_active'             => true,
        ];

        if ($this->editingId) {
            Department::where('shop_id', Auth::user()->shop_id)
                ->findOrFail($this->editingId)
                ->update($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Department updated.']);
        } else {
            Department::create($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Department created.']);
        }

        $this->resetForm();
        unset($this->departments);
    }

    public function toggleActive(int $id): void
    {
        $dept = Department::where('shop_id', Auth::user()->shop_id)->findOrFail($id);
        $dept->update(['is_active' => ! $dept->is_active]);
        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => $dept->is_active ? "{$dept->name} deactivated." : "{$dept->name} activated.",
        ]);
        unset($this->departments);
    }

    public function delete(int $id): void
    {
        $dept = Department::where('shop_id', Auth::user()->shop_id)
            ->withCount('salaryStructures')
            ->findOrFail($id);

        if ($dept->salary_structures_count > 0) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => "Cannot delete: {$dept->salary_structures_count} employee(s) are in this department."]);
            return;
        }

        $dept->delete(); // soft delete
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Department deleted.']);
        unset($this->departments);
    }

    private function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->code        = '';
        $this->description = '';
        $this->parentId    = 0;
        $this->headUserId  = 0;
        $this->showForm    = false;
    }

    public function render()
    {
        return view('livewire.payroll.department-manager');
    }
}