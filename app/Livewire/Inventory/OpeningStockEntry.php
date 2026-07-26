<?php

namespace App\Livewire\Inventory;

use App\Actions\Inventory\RecordOpeningStockAction;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Opening Stock Entry')]
class OpeningStockEntry extends Component
{
    use \App\Traits\HasAuthorization;
    use WithFileUploads;

    // Step 1 — select product + branch
    public int    $branchId   = 0;
    public int    $variantId  = 0;
    public string $productSearch = '';

    // Step 2 — non-serialized
    public string $quantity  = '';
    public string $unitCost  = '';

    // Step 2 — serialized (dynamic rows)
    public array  $imeiRows  = [
        ['serial_number' => '', 'secondary_serial_number' => '', 'cost_price' => '', 'warranty_months' => ''],
    ];

    // State
    public string $trackingType   = '';
    public string $productName    = '';
    public bool   $showForm       = false;
    public array  $searchResults  = [];
    public array  $recentEntries  = [];

    public function mount(): void
    {
        $this->requirePermission('inventory.create');

        // Owner only — opening stock is a one-time sensitive operation
        if (! Auth::user()->isOwner()) {
            abort(403, 'Only the shop owner can record opening stock.');
        }

        $this->loadRecentEntries();
    }

    private function loadRecentEntries(): void
    {
        $this->recentEntries = \App\Models\StockAdjustment::where('shop_id', Auth::user()->shop_id)
            ->where('adjustment_type', 'opening_stock')
            ->with(['variant.product', 'branch', 'createdBy'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->toArray();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    public function searchProduct(): void
    {
        $term   = trim($this->productSearch);
        $shopId = Auth::user()->shop_id;

        if (strlen($term) < 2) { $this->searchResults = []; return; }

        $this->searchResults = ProductVariant::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where(fn ($q) =>
                $q->where('sku', 'like', "%{$term}%")
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"))
            )
            ->with('product')
            ->limit(8)->get()
            ->map(fn ($v) => [
                'variant_id'    => $v->id,
                'label'         => $v->product->name . ($v->attributes_label ? ' — '.$v->attributes_label : ''),
                'sku'           => $v->sku,
                'tracking_type' => $v->product->tracking_type->value,
            ])->toArray();
    }

    public function selectVariant(int $variantId, string $label, string $trackingType): void
    {
        $this->variantId      = $variantId;
        $this->productName    = $label;
        $this->trackingType   = $trackingType;
        $this->productSearch  = $label;
        $this->searchResults  = [];
        $this->showForm       = true;
        $this->quantity       = '';
        $this->unitCost       = '';
        $this->imeiRows       = [
            ['serial_number'=>'','secondary_serial_number'=>'','cost_price'=>'','warranty_months'=>''],
        ];
    }

    public function addImeiRow(): void
    {
        $this->imeiRows[] = ['serial_number'=>'','secondary_serial_number'=>'','cost_price'=>'','warranty_months'=>''];
    }

    public function removeImeiRow(int $idx): void
    {
        unset($this->imeiRows[$idx]);
        $this->imeiRows = array_values($this->imeiRows);
    }

    public function save(RecordOpeningStockAction $action): void
    {
        $this->validate([
            'branchId'  => 'required|integer|min:1',
            'variantId' => 'required|integer|min:1',
        ]);

        $shop    = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $branch  = Branch::where('shop_id', $shop->id)->findOrFail($this->branchId);
        $variant = ProductVariant::withoutGlobalScopes()->findOrFail($this->variantId);
        $actor   = Auth::user();

        try {
            if ($this->trackingType === 'serialized') {
                $serials = array_filter($this->imeiRows, fn ($r) => !empty(trim($r['serial_number'])));

                if (empty($serials)) {
                    $this->dispatch('notify', ['type'=>'error','message'=>'Enter at least one IMEI.']);
                    return;
                }

                $created = $action->executeSerialized($shop, $variant, $branch, array_values($serials), $actor);

                $this->dispatch('notify', ['type'=>'success',
                    'message'=>"{$created} IMEI(s) registered as opening stock."]);
            } else {
                $this->validate([
                    'quantity' => 'required|numeric|min:0.01',
                    'unitCost' => 'required|numeric|min:0',
                ]);

                $action->executeNonSerialized(
                    $shop, $variant, $branch,
                    (float) $this->quantity,
                    (float) $this->unitCost,
                    $actor
                );

                $this->dispatch('notify', ['type'=>'success',
                    'message'=>"Opening stock of {$this->quantity} units recorded."]);
            }

            // Reset for next entry
            $this->showForm      = false;
            $this->variantId     = 0;
            $this->productSearch = '';
            $this->productName   = '';
            $this->trackingType  = '';
            $this->loadRecentEntries();

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type'=>'error','message'=>$e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory.opening-stock-entry');
    }
}