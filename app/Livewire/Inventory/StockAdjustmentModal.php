<?php
namespace App\Livewire\Inventory;

use App\Actions\Inventory\MarkStockDamagedAction;
use App\Actions\Inventory\WriteOffStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class StockAdjustmentModal extends Component
{
    use \App\Traits\HasAuthorization;

    public bool    $show          = false;
    public ?int    $variantId     = null;
    public ?int    $unitId        = null;   // for serialized
    public int     $branchId      = 0;
    public string  $adjustmentType= 'damaged'; // damaged | written_off | reserved | unreserved
    public string  $quantity      = '1';
    public string  $reason        = '';
    public bool    $alreadyDamaged= false;
    public string $heldForName  = '';
    public string $heldForPhone = '';
    public string $holdExpiresAt = '';

    // Read-only display
    public string  $productName   = '';
    public string  $trackingType  = 'non_serialized';
    public float   $currentStock  = 0;
    public float   $damagedStock  = 0;
    public float   $reservedStock = 0;

    #[On('open-stock-adjustment')]
    public function open(
        int     $variant_id,
        int     $branch_id      = 0,
        string  $type           = 'damaged',
        string  $product_name   = '',
        string  $tracking_type  = 'non_serialized',
        ?int    $unit_id        = null,
        
    ): void {
        $this->requirePermission('inventory.edit');

        $this->variantId      = $variant_id;
        $this->unitId         = $unit_id;
        $this->branchId       = $branch_id ?: (Auth::user()->branch_id ?? 0);
        $this->adjustmentType = $type;
        $this->productName    = $product_name;
        $this->trackingType   = $tracking_type;
        $this->quantity       = '1';
        $this->reason         = '';
        $this->alreadyDamaged = false;
        $this->heldForName  = '';
        $this->heldForPhone = '';
        $this->holdExpiresAt = now()->addDays(3)->format('Y-m-d');

        $this->loadCurrentStock();
        $this->show = true;
    }

    private function loadCurrentStock(): void
    {
        if ($this->trackingType === 'non_serialized' && $this->variantId) {
            $query = BranchStock::withoutGlobalScopes()
                ->where('shop_id', Auth::user()->shop_id)
                ->where('product_variant_id', $this->variantId);

            // If no specific branch, sum across all branches
            if ($this->branchId) {
                $stock = $query->where('branch_id', $this->branchId)->first();
            } else {
                // Owner with no branch — use main branch or first available
                $stock = $query->first();
                if ($stock) {
                    $this->branchId = $stock->branch_id;
                }
            }

            $this->currentStock  = (float) ($stock?->quantity ?? 0);
            $this->damagedStock  = (float) ($stock?->damaged_quantity ?? 0);
            $this->reservedStock = (float) ($stock?->reserved_quantity ?? 0);
        }
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function availableQty(): float
    {
        return max(0, $this->currentStock - $this->reservedStock);
    }

    public function save(
    MarkStockDamagedAction $damageAction,
    WriteOffStockAction    $writeOffAction,
    ReserveStockAction     $reserveAction,
): void {
    $this->validate([
        'quantity' => 'required|numeric|min:0.01',
        'reason'   => 'required|string|min:3|max:255',
    ]);

    if ($this->adjustmentType === 'reserved') {
        $this->validate([
            'heldForName'  => 'required|string|max:150',
            'heldForPhone' => 'nullable|string|max:30',
        ]);
    }

    if (! $this->branchId && $this->trackingType === 'non_serialized') {
        $this->dispatch('notify', ['type' => 'error', 'message' => 'Please select a branch.']);
        return;
    }

    $shop   = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
    $actor  = Auth::user();
    $branch = $this->branchId ? Branch::where('shop_id', $shop->id)->findOrFail($this->branchId) : null;

    try {
        if ($this->trackingType === 'serialized' && $this->unitId) {
            $unit = ProductUnit::withoutGlobalScopes()->findOrFail($this->unitId);

            match($this->adjustmentType) {
                'damaged'     => $damageAction->executeSerialized($shop, $unit, $this->reason, $actor),
                'written_off' => $writeOffAction->executeSerialized($shop, $unit, $this->reason, $actor),
                'reserved'    => $reserveAction->reserveSerialized(
                                    $shop, $unit, $this->reason, $actor,
                                    $this->heldForName ?: null,
                                    $this->heldForPhone ?: null,
                                    $this->holdExpiresAt ? \Carbon\Carbon::parse($this->holdExpiresAt) : null,
                                ),
                'unreserved'  => $reserveAction->releaseSerialized($shop, $unit, $this->reason, $actor),
                default       => throw new \RuntimeException('Invalid adjustment type for serialized unit.'),
            };
        } else {
            $variant = ProductVariant::withoutGlobalScopes()->findOrFail($this->variantId);
            $qty     = (float) $this->quantity;

            match($this->adjustmentType) {
                'damaged'     => $damageAction->executeNonSerialized($shop, $variant, $branch, $qty, $this->reason, $actor),
                'written_off' => $writeOffAction->executeNonSerialized($shop, $variant, $branch, $qty, $this->reason, $this->alreadyDamaged, $actor),
                'reserved'    => $reserveAction->reserve($shop, $variant, $this->branchId, $qty, $this->reason, $actor , $this->heldForName ?: null, $this->heldForPhone ?: null,$this->holdExpiresAt ? \Carbon\Carbon::parse($this->holdExpiresAt) : null,),
                'unreserved'  => $reserveAction->release($shop, $variant, $this->branchId, $qty, $this->reason, $actor),
                default       => throw new \RuntimeException('Invalid adjustment type.'),
            };
        }

        $this->show = false;
        $this->dispatch('stock-adjusted');
        $this->dispatch('notify', ['type' => 'success',
            'message' => ucfirst(str_replace('_', ' ', $this->adjustmentType)) . ' recorded successfully.']);

    } catch (\Exception $e) {
        $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
    }
}

    public function updatedBranchId(): void
    {
        $this->loadCurrentStock();
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment-modal');
    }
}