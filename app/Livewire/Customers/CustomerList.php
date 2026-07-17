<?php

namespace App\Livewire\Customers;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Traits\HasAuthorization;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Customers')]
class CustomerList extends Component
{
    use HasAuthorization;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $balance = ''; // 'with_due' | 'clear'

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }
    public function updatingBalance(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->requirePermission('customers.view');
    }
    
    #[Computed]
    public function stats(): array
    {
        return [
            'total'        => Customer::where('customer_type', '!=', CustomerType::WalkIn->value)->count(),
            'with_due'     => Customer::where('current_balance', '>', 0)->count(),
            'total_due'    => Customer::sum('current_balance'),
        ];
    }

    public function render()
    {
        $customers = Customer::with('guarantor')
            ->where('customer_type', '!=', CustomerType::WalkIn->value)
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->type, fn($q) => $q->where('customer_type', $this->type))
            ->when($this->balance === 'with_due', fn($q) => $q->where('current_balance', '>', 0))
            ->when($this->balance === 'clear', fn($q) => $q->where('current_balance', '<=', 0))
            ->latest()
            ->paginate(20);

        return view('livewire.customers.customer-list', compact('customers'));
    }
}