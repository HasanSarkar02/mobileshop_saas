<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Supplier')]


class SupplierForm extends Component
{
    public ?Supplier $supplier = null;

    public string $bankName          = '';
    public string $bankAccountNumber = '';
    public string $bankBranchName    = '';
    public string $bankRoutingNumber = '';
    public string $paymentTerms      = '';
    public string $creditLimit       = '0';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:500')]
    public string $address = '';

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier && $supplier->exists) {
            $this->supplier = $supplier;
            $this->fill([
                'name' => $supplier->name,
                'phone' => $supplier->phone ?? '',
                'email' => $supplier->email ?? '',
                'address' => $supplier->address ?? '',
            ]);
            $this->bankName          = $supplier->bank_name ?? '';
            $this->bankAccountNumber = $supplier->bank_account_number ?? '';
            $this->bankBranchName    = $supplier->bank_branch_name ?? '';
            $this->bankRoutingNumber = $supplier->bank_routing_number ?? '';
            $this->paymentTerms      = $supplier->payment_terms ?? '';
            $this->creditLimit       = (string) ($supplier->credit_limit ?? 0);
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'bank_name'           => $this->bankName ?: null,
            'bank_account_number' => $this->bankAccountNumber ?: null,
            'bank_branch_name'    => $this->bankBranchName ?: null,
            'bank_routing_number' => $this->bankRoutingNumber ?: null,
            'payment_terms'       => $this->paymentTerms ?: null,
            'credit_limit'        => (float) $this->creditLimit,
        ];

        if ($this->supplier?->exists) {
            $this->supplier->update($data);
            $this->dispatch('notify', type: 'success', message: 'Supplier updated.');
        } else {
            Supplier::create(['shop_id' => Auth::user()->shop_id, ...$data]);
            $this->dispatch('notify', type: 'success', message: 'Supplier created.');
            $this->redirect(route('suppliers.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.suppliers.supplier-form');
    }
}