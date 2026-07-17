<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Suppliers')]
class SupplierList extends Component
{
    use \App\Traits\HasAuthorization;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->requirePermission('suppliers.manage');
    }
    
    public function delete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchases()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete — this supplier has purchase records.');
            return;
        }

        $supplier->delete();
        $this->dispatch('notify', type: 'success', message: 'Supplier deleted.');
    }

    public function render()
    {
        $suppliers = Supplier::when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->withCount('purchases')
            ->latest()
            ->paginate(20);

        return view('livewire.suppliers.supplier-list', compact('suppliers'));
    }
}