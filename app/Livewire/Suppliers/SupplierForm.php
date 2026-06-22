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