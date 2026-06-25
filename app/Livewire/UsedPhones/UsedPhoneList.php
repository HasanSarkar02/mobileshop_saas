<?php

namespace App\Livewire\UsedPhones;

use App\Models\SaleItem;
use App\Models\UsedPhoneAcquisition;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Used Phones')]
class UsedPhoneList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $soldFilter = ''; // '' | 'sold' | 'in_stock'

    #[Computed]
    public function metrics(): array
    {
        $shopId = auth()->user()->shop_id;

        $totalSpent = UsedPhoneAcquisition::where('shop_id', $shopId)->sum('purchase_price');
        $totalCount = UsedPhoneAcquisition::where('shop_id', $shopId)->count();

        // Get revenue from sold used phones
        $unitIds = UsedPhoneAcquisition::where('shop_id', $shopId)
            ->whereNotNull('product_unit_id')
            ->pluck('product_unit_id');

        $totalRevenue = SaleItem::whereIn('product_unit_id', $unitIds)
            ->whereHas('sale', fn ($q) => $q->where('status', 'confirmed'))
            ->sum('line_total');

        $soldCount = SaleItem::whereIn('product_unit_id', $unitIds)
            ->whereHas('sale', fn ($q) => $q->where('status', 'confirmed'))
            ->distinct('product_unit_id')->count();

        $inventoryValue = UsedPhoneAcquisition::where('shop_id', $shopId)
            ->whereHas('productUnit', fn ($q) => $q->where('status', 'in_stock'))
            ->sum('purchase_price');

        return [
            'total_count'     => $totalCount,
            'total_spent'     => (float) $totalSpent,
            'total_revenue'   => (float) $totalRevenue,
            'net_profit'      => (float) $totalRevenue - (float) $totalSpent,
            'sold_count'      => $soldCount,
            'inventory_value' => (float) $inventoryValue,
        ];
    }

    public function render()
    {
        $acquisitions = UsedPhoneAcquisition::with(['variant.product', 'productUnit', 'paymentAccount', 'branch'])
            ->when($this->search, fn ($q) =>
                $q->where('imei_1', 'like', "%{$this->search}%")
                  ->orWhere('model_description', 'like', "%{$this->search}%")
                  ->orWhere('seller_name', 'like', "%{$this->search}%")
                  ->orWhere('seller_phone', 'like', "%{$this->search}%")
                  ->orWhere('acquisition_number', 'like', "%{$this->search}%")
            )
            ->when($this->soldFilter === 'sold', fn ($q) =>
                $q->whereHas('productUnit', fn ($uq) => $uq->where('status', 'sold'))
            )
            ->when($this->soldFilter === 'in_stock', fn ($q) =>
                $q->whereHas('productUnit', fn ($uq) => $uq->where('status', 'in_stock'))
            )
            ->latest()
            ->paginate(20);

        return view('livewire.used-phones.used-phone-list', compact('acquisitions'));
    }
}