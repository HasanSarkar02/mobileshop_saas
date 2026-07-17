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
    use \App\Traits\HasAuthorization;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $soldFilter = '';
    
    public function mount(): void
    {
        $this->requirePermission('used_phones.view');
    }

    #[Computed]
    public function metrics(): array
    {
        $shopId = auth()->user()->shop_id;

        // All acquisition unit IDs for this shop
        $unitIds = UsedPhoneAcquisition::where('shop_id', $shopId)
            ->whereNotNull('product_unit_id')
            ->pluck('product_unit_id')
            ->filter()
            ->toArray();

        $totalCount = UsedPhoneAcquisition::where('shop_id', $shopId)->count();
        $totalSpent = (float) UsedPhoneAcquisition::where('shop_id', $shopId)->sum('purchase_price');

        // Revenue: only confirmed sales that have NOT been returned or voided
        $soldItems = SaleItem::whereIn('product_unit_id', $unitIds)
            ->whereHas('sale', fn ($q) =>
                $q->where('status', 'confirmed')    // exclude voided
                  ->where('return_processed', false) // exclude returned
            )
            ->get(['product_unit_id', 'line_total', 'cost_price']);

        $totalRevenue = (float) $soldItems->sum('line_total');
        $soldUnitIds  = $soldItems->pluck('product_unit_id')->filter()->unique()->toArray();
        $soldCount    = count($soldUnitIds);

        // Net profit = revenue from sold phones − what we paid for those specific phones
        // NOT total_spent (which includes phones still in inventory)
        $costOfSoldUnits = (float) UsedPhoneAcquisition::where('shop_id', $shopId)
            ->whereIn('product_unit_id', $soldUnitIds)
            ->sum('purchase_price');

        // Inventory value = current purchase_price of phones that are IN STOCK right now
        // This automatically updates after void (unit back to in_stock) or
        // return (unit back to in_stock or damaged)
        $inventoryValue = (float) UsedPhoneAcquisition::where('shop_id', $shopId)
            ->whereHas('productUnit', fn ($q) =>
                $q->where('status', 'in_stock')
            )
            ->sum('purchase_price');

        return [
            'total_count'     => $totalCount,
            'total_spent'     => $totalSpent,
            'total_revenue'   => $totalRevenue,
            'net_profit'      => $totalRevenue - $costOfSoldUnits, // profit on sold units only
            'sold_count'      => $soldCount,
            'inventory_value' => $inventoryValue,
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