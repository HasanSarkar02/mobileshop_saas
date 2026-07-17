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

    // Read-only display
    public string  $productName   = '';
    public string  $trackingType  = 'non_serialized';
    public float   $currentStock  = 0;
    public float   $damagedStock  = 0;
    public float   $reservedStock = 0;

    #[On('open-stock-adjustment')]
    public function open(array $data): void
    {
        $this->requirePermission('inventory.edit');

        $this->variantId      = $data['variant_id'];
        $this->unitId         = $data['unit_id'] ?? null;
        $this->branchId       = $data['branch_id'] ?? Auth::user()->branch_id ?? 0;
        $this->adjustmentType = $data['type'] ?? 'damaged';
        $this->productName    = $data['product_name'] ?? '';
        $this->trackingType   = $data['tracking_type'] ?? 'non_serialized';
        $this->quantity       = '1';
        $this->reason         = '';
        $this->alreadyDamaged = false;

        $this->loadCurrentStock();
        $this->show = true;
    }

    private function loadCurrentStock(): void
    {
        if ($this->trackingType === 'non_serialized' && $this->variantId && $this->branchId) {
            $stock = BranchStock::withoutGlobalScopes()
                ->where('product_variant_id', $this->variantId)
                ->where('branch_id', $this->branchId)
                ->first();

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

        $shop  = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $actor = Auth::user();

        try {
            if ($this->trackingType === 'serialized' && $this->unitId) {
                $unit = ProductUnit::withoutGlobalScopes()->findOrFail($this->unitId);

                match($this->adjustmentType) {
                    'damaged'    => $damageAction->executeSerialized($shop, $unit, $this->reason, $actor),
                    'written_off'=> $writeOffAction->executeSerialized($shop, $unit, $this->reason, $actor),
                    default      => throw new \RuntimeException('Invalid adjustment type for serialized unit.'),
                };
            } else {
                $variant = ProductVariant::withoutGlobalScopes()->findOrFail($this->variantId);
                $branch  = Branch::findOrFail($this->branchId);
                $qty     = (float) $this->quantity;

                match($this->adjustmentType) {
                    'damaged'    => $damageAction->executeNonSerialized($shop, $variant, $branch, $qty, $this->reason, $actor),
                    'written_off'=> $writeOffAction->executeNonSerialized($shop, $variant, $branch, $qty, $this->reason, $this->alreadyDamaged, $actor),
                    'reserved'   => $reserveAction->reserve($shop, $variant, $this->branchId, $qty, $this->reason, $actor),
                    'unreserved' => $reserveAction->release($shop, $variant, $this->branchId, $qty, $this->reason, $actor),
                    default      => throw new \RuntimeException('Invalid adjustment type.'),
                };
            }

            $this->show = false;
            $this->dispatch('stock-adjusted');
            $this->dispatch('notify', ['type' => 'success',
                'message' => ucfirst($this->adjustmentType) . ' recorded successfully.']);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment-modal');
    }
}