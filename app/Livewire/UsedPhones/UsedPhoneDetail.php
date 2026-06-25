<?php

namespace App\Livewire\UsedPhones;

use App\Models\SaleItem;
use App\Models\UsedPhoneAcquisition;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Used Phone Detail')]
class UsedPhoneDetail extends Component
{
    public UsedPhoneAcquisition $acquisition;

    public function mount(UsedPhoneAcquisition $acquisition): void
    {
        $this->acquisition = $acquisition->load([
            'variant.product',
            'productUnit.branch',
            'paymentAccount',
            'branch',
            'createdBy',
            'tradeInSale.customer',
        ]);
    }

    #[Computed]
    public function saleRecord(): ?SaleItem
    {
        if (! $this->acquisition->product_unit_id) return null;

        return SaleItem::whereHas('sale', fn ($q) =>
            $q->where('status', 'confirmed')
        )
        ->where('product_unit_id', $this->acquisition->product_unit_id)
        ->with('sale.customer', 'sale.cashier')
        ->first();
    }

    #[Computed]
    public function profit(): ?float
    {
        $saleItem = $this->saleRecord;
        if (! $saleItem) return null;

        return (float) $saleItem->line_total - (float) $this->acquisition->purchase_price;
    }

    public function render()
    {
        return view('livewire.used-phones.used-phone-detail', [
            'acquisition' => $this->acquisition,
            'saleRecord'  => $this->saleRecord,
            'profit'      => $this->profit,
        ]);
    }
}