<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
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

    #[Url]
    public string $dueOnly = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDueOnly(): void { $this->resetPage(); }

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

    /**
     * Shop-wide totals — deliberately independent of the current search/filter
     * so the cards always reflect the true position, not just the visible page.
     */
    #[Computed]
    public function stats(): object
    {
        return (object) [
            'total_suppliers'    => Supplier::count(),
            'total_payable'      => (float) Supplier::where('current_balance', '>', 0)->sum('current_balance'),
            'suppliers_with_due' => Supplier::where('current_balance', '>', 0)->count(),
        ];
    }

    public function render()
    {
        $suppliers = Supplier::when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->dueOnly, fn($q) => $q->where('current_balance', '>', 0))
            ->withCount('purchases')
            ->orderByDesc('current_balance')
            ->latest()
            ->paginate(20);

        return view('livewire.suppliers.supplier-list', compact('suppliers'));
    }
}