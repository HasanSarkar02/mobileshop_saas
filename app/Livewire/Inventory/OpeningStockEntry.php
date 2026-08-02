<?php

namespace App\Livewire\Inventory;

use App\Actions\Inventory\RecordOpeningStockAction;
use App\Models\Branch;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Actions\Inventory\ReverseOpeningStockAction;
use App\Traits\ReversesOpeningStock;

#[Layout('components.layouts.app')]
#[Title('Opening Stock Entry')]
class OpeningStockEntry extends Component
{
    use \App\Traits\HasAuthorization;
    use ReversesOpeningStock;
    use WithFileUploads;

    // Applies to the whole scanning session
    public int $branchId = 0;

    // Continuous scan input (USB wedge or camera)
    public string $barcodeInput = '';

    // Manual search fallback
    public string $productSearch = '';
    public array  $searchResults = [];

    // The scan queue — nothing hits the DB until saveBatch()
    // non_serialized: ['key','variant_id','tracking_type','product_name','quantity','unit_cost']
    // serialized:     ['key','variant_id','tracking_type','product_name','serials' => [...]]
    public array $batchItems = [];

    // While set, every scanned code is treated as an IMEI for this variant
    public ?int $activeSerializedVariantId = null;

    public bool  $isSaving = false;
    public array $recentEntries = [];

    public bool   $showReverseModal = false;
    public ?int   $reversingEntryId = null;
    public string $reversalReason   = '';
    
    public function mount(): void
    {
        $this->requirePermission('inventory.create');

        if (! Auth::user()->isOwner()) {
            abort(403, 'Only the shop owner can record opening stock.');
        }

        $this->branchId = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );

        $this->loadRecentEntries();
    }

    private function loadRecentEntries(): void
    {
        $this->recentEntries = \App\Models\StockAdjustment::where('shop_id', Auth::user()->shop_id)
            ->whereIn('adjustment_type', ['opening_stock', 'opening_stock_reversal'])
            ->with(['variant.product', 'branch', 'createdBy', 'reversalOf.variant.product'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->toArray();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    #[Computed]
    public function batchTotals(): array
    {
        $lines = 0;
        $units = 0.0;
        $value = 0.0;

        foreach ($this->batchItems as $item) {
            $lines++;
            if ($item['tracking_type'] === 'serialized') {
                $units += count($item['serials']);
                $value += collect($item['serials'])->sum(fn ($s) => (float) ($s['cost_price'] ?? 0));
            } else {
                $units += (float) $item['quantity'];
                $value += (float) $item['quantity'] * (float) ($item['unit_cost'] ?: 0);
            }
        }

        return ['lines' => $lines, 'units' => $units, 'value' => $value];
    }

    // ── Manual search fallback ───────────────────────────────────────────────

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

    public function selectFromSearch(int $variantId, string $label, string $trackingType): void
    {
        if (! $this->requireBranch()) return;

        $this->productSearch = '';
        $this->searchResults = [];
        $this->addProductToBatch($variantId, $label, $trackingType);
    }

    // ── Barcode scanning — single entry point for USB + camera ─────────────────

    public function processBarcode(): void
    {
        $code = trim($this->barcodeInput);
        $this->barcodeInput = '';

        if ($code === '') return;
        if (! $this->requireBranch()) return;

        $shopId = Auth::user()->shop_id;

        // 1) Always try to resolve as a product barcode/SKU first, so the user
        //    can switch products mid-session just by scanning a new one.
        $variant = ProductVariant::byBarcode($code, $shopId)
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with('product.brand')
            ->first();

        if (! $variant) {
            $variant = ProductVariant::where('shop_id', $shopId)
                ->where('sku', $code)
                ->where('is_active', true)
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->with('product.brand')
                ->first();
        }

        if ($variant) {
            $label = trim(
                ($variant->product->brand?->name ?? '') . ' ' .
                $variant->product->name .
                ($variant->attributes_label ? ' — ' . $variant->attributes_label : '')
            );

            $this->addProductToBatch($variant->id, $label, $variant->product->tracking_type->value);
            return;
        }

        // 2) Not a known product barcode — if we're mid-way through scanning
        //    IMEIs for a serialized product, treat this as the next unit.
        if ($this->activeSerializedVariantId) {
            $this->addSerialToActiveGroup($code);
            return;
        }

        $this->dispatch('scan-feedback', ok: false);
        $this->dispatch('notify', type: 'error', message: "No product matches barcode/SKU \"{$code}\".");
    }

    private function requireBranch(): bool
    {
        if ($this->branchId < 1) {
            $this->dispatch('scan-feedback', ok: false);
            $this->dispatch('notify', type: 'error', message: 'Select a branch before scanning.');
            return false;
        }
        return true;
    }

    private function addProductToBatch(int $variantId, string $label, string $trackingType): void
    {
        if ($trackingType === 'serialized') {
            $this->activeSerializedVariantId = $variantId;

            if ($this->findBatchIndex($variantId) === null) {
                $this->batchItems[] = [
                    'key'           => (string) Str::uuid(),
                    'variant_id'    => $variantId,
                    'tracking_type' => 'serialized',
                    'product_name'  => $label,
                    'serials'       => [],
                ];
            }

            $this->dispatch('scan-feedback', ok: true);
            $this->dispatch('notify', type: 'success',
                message: "Scanning IMEIs for \"{$label}\" — scan each unit now.");
            return;
        }

        // Non-serialized: leaving any active IMEI-capture mode
        $this->activeSerializedVariantId = null;

        $idx = $this->findBatchIndex($variantId);
        if ($idx !== null) {
            $this->batchItems[$idx]['quantity'] = (float) $this->batchItems[$idx]['quantity'] + 1;
        } else {
            $this->batchItems[] = [
                'key'           => (string) Str::uuid(),
                'variant_id'    => $variantId,
                'tracking_type' => 'non_serialized',
                'product_name'  => $label,
                'quantity'      => 1,
                'unit_cost'     => '',
            ];
        }

        $this->dispatch('scan-feedback', ok: true);
    }

    private function findBatchIndex(int $variantId): ?int
    {
        foreach ($this->batchItems as $idx => $item) {
            if ($item['variant_id'] === $variantId) return $idx;
        }
        return null;
    }

    private function addSerialToActiveGroup(string $serial): void
    {
        if (! preg_match('/^\d{14,15}$/', $serial)) {
            $this->dispatch('scan-feedback', ok: false);
            $this->dispatch('notify', type: 'error',
                message: "\"{$serial}\" doesn't look like a valid IMEI (14–15 digits). Ignored.");
            return;
        }

        // Duplicate within this session
        foreach ($this->batchItems as $item) {
            if ($item['tracking_type'] !== 'serialized') continue;
            foreach ($item['serials'] as $s) {
                if ($s['serial_number'] === $serial) {
                    $this->dispatch('scan-feedback', ok: false);
                    $this->dispatch('notify', type: 'error', message: "IMEI {$serial} was already scanned in this batch.");
                    return;
                }
            }
        }

        // Duplicate already active in the system
        $exists = ProductUnit::withoutGlobalScopes()
            ->where('serial_number', $serial)
            ->where('is_archived', false)
            ->exists();

        if ($exists) {
            $this->dispatch('scan-feedback', ok: false);
            $this->dispatch('notify', type: 'error', message: "IMEI {$serial} is already registered as active inventory.");
            return;
        }

        $idx = $this->findBatchIndex($this->activeSerializedVariantId);
        if ($idx === null) return;

        $this->batchItems[$idx]['serials'][] = [
            'serial_number'           => $serial,
            'secondary_serial_number' => '',
            'cost_price'              => '',
            'warranty_months'         => 12,
        ];

        $this->dispatch('scan-feedback', ok: true);
    }

    public function finishSerializedGroup(): void
    {
        if ($this->activeSerializedVariantId) {
            $idx = $this->findBatchIndex($this->activeSerializedVariantId);
            // Drop the group if nothing was actually scanned into it
            if ($idx !== null && empty($this->batchItems[$idx]['serials'])) {
                unset($this->batchItems[$idx]);
                $this->batchItems = array_values($this->batchItems);
            }
        }
        $this->activeSerializedVariantId = null;
    }

    // ── Batch editing ────────────────────────────────────────────────────────

    public function removeBatchItem(string $key): void
    {
        $this->batchItems = array_values(array_filter(
            $this->batchItems, fn ($i) => $i['key'] !== $key
        ));

        $stillActive = collect($this->batchItems)
            ->contains(fn ($i) => $i['variant_id'] === $this->activeSerializedVariantId);
        if (! $stillActive) $this->activeSerializedVariantId = null;
    }

    public function removeSerial(string $groupKey, int $serialIdx): void
    {
        foreach ($this->batchItems as $idx => $item) {
            if ($item['key'] === $groupKey) {
                unset($this->batchItems[$idx]['serials'][$serialIdx]);
                $this->batchItems[$idx]['serials'] = array_values($this->batchItems[$idx]['serials']);
                return;
            }
        }
    }

    public function clearBatch(): void
    {
        $this->batchItems = [];
        $this->activeSerializedVariantId = null;
    }

    // ── Save batch ───────────────────────────────────────────────────────────

    public function saveBatch(RecordOpeningStockAction $action): void
    {
        if ($this->branchId < 1) {
            $this->dispatch('notify', type: 'error', message: 'Please select a branch.');
            return;
        }

        if (empty($this->batchItems)) {
            $this->dispatch('notify', type: 'error', message: 'Scan or add at least one product first.');
            return;
        }

        // Block on bad input rather than silently skipping lines
        foreach ($this->batchItems as $idx => $item) {
            if ($item['tracking_type'] === 'non_serialized') {
                if ((float) $item['quantity'] <= 0) {
                    $this->addError("batchItems.{$idx}.quantity", 'Quantity must be greater than zero.');
                    return;
                }
                if ($item['unit_cost'] === '' || (float) $item['unit_cost'] < 0) {
                    $this->addError("batchItems.{$idx}.unit_cost", 'Enter a unit cost (0 is allowed for free stock).');
                    return;
                }
            } elseif (empty($item['serials'])) {
                $this->dispatch('notify', type: 'error',
                    message: "\"{$item['product_name']}\" has no IMEIs scanned — remove it or scan units.");
                return;
            }
        }

        $this->isSaving = true;

        $shop   = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $branch = Branch::where('shop_id', $shop->id)->findOrFail($this->branchId);
        $actor  = Auth::user();

        $okLines      = 0;
        $failedKeys   = [];
        $failMessages = [];

        foreach ($this->batchItems as $item) {
            try {
                $variant = ProductVariant::withoutGlobalScopes()->findOrFail($item['variant_id']);

                if ($item['tracking_type'] === 'non_serialized') {
                    $action->executeNonSerialized(
                        $shop, $variant, $branch,
                        (float) $item['quantity'],
                        (float) $item['unit_cost'],
                        $actor
                    );
                    $okLines++;
                } else {
                    $created = $action->executeSerialized(
                        $shop, $variant, $branch, $item['serials'], $actor
                    );
                    $okLines++;

                    if ($created < count($item['serials'])) {
                        $skipped = count($item['serials']) - $created;
                        $failMessages[] = "{$item['product_name']}: {$skipped} IMEI(s) skipped as duplicates.";
                    }
                }
            } catch (\Exception $e) {
                $failedKeys[]   = $item['key'];
                $failMessages[] = "{$item['product_name']}: {$e->getMessage()}";
            }
        }

        $this->isSaving = false;

        if ($okLines > 0) {
            $this->dispatch('notify', type: 'success', message: "{$okLines} line(s) recorded as opening stock.");
        }
        foreach ($failMessages as $msg) {
            $this->dispatch('notify', type: 'error', message: $msg);
        }

        // Only drop lines that fully succeeded — nothing scanned is ever silently lost
        $this->batchItems = array_values(array_filter(
            $this->batchItems, fn ($i) => in_array($i['key'], $failedKeys, true)
        ));
        if (empty($this->batchItems)) {
            $this->activeSerializedVariantId = null;
        }

        $this->loadRecentEntries();
    }

    protected function afterReversal(): void
    {
        $this->loadRecentEntries();
    }
    
    public function render()
    {
        return view('livewire.inventory.opening-stock-entry');
    }
}