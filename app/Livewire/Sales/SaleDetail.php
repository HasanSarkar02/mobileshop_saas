<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Sale Detail')]
class SaleDetail extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $this->sale = $sale->load([
            'items.variant.product.brand',
            'items.productUnit',
            'payments.paymentAccount',
            'payments.financePartner',
            'customer',
            'branch',
            'cashier',
            'financePartnerReceivable.financePartner',
            'creditNotes.items',
        ]);
    }

    public function render()
    {
        return view('livewire.sales.sale-detail', ['sale' => $this->sale]);
    }
}