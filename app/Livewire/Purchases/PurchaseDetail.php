<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Purchase Detail')]
class PurchaseDetail extends Component
{
    public Purchase $purchase;

    public function mount(Purchase $purchase): void
    {
        $this->purchase = $purchase->load([
            'supplier',
            'branch',
            'createdBy',
            'lineItems.variant.product.brand',
            'lineItems.units',
        ]);
    }

    public function render()
    {
        return view('livewire.purchases.purchase-detail', ['purchase' => $this->purchase]);
    }
}