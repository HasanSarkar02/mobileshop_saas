<?php

namespace App\Livewire\Purchases;

use App\Actions\ReceivePurchaseAction;
use App\Enums\ProductTrackingType;
use App\Models\Branch;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('New Purchase')]
class CreatePurchase extends Component
{
    use HasAuthorization;
    public int $supplierId = 0;
    public int $branchId = 0;
    public string $purchaseDate = '';

    // Each line: product_variant_id, variant_label, unit_cost, quantity,
    //             tracking_type, manufacturer_warranty_months, shop_warranty_days,
    //             serial_numbers: [{serial_number, secondary_serial_number}]
    public array $lines = [];

    // Per-line search state (not sent to server on submit)
    public array $searches = [];
    public array $searchResults = [];

    public float $totalAmount = 0;

    public function mount(): void
    {
        $this->requirePermission('purchases.manage');
        $this->purchaseDate = now()->format('Y-m-d');
        $this->branchId = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );
        $this->addLine();
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'product_variant_id' => 0,
            'variant_label' => '',
            'unit_cost' => '',
            'quantity' => 1,
            'tracking_type' => 'non_serialized',
            'manufacturer_warranty_months' => 12,
            'shop_warranty_days' => 7,
            'serial_numbers' => [],
        ];
        $this->searches[] = '';
        $this->searchResults[] = [];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 1) {
            $this->dispatch('notify', type: 'error', message: 'At least one line is required.');
            return;
        }
        array_splice($this->lines, $index, 1);
        array_splice($this->searches, $index, 1);
        array_splice($this->searchResults, $index, 1);
        $this->recalcTotal();
    }

    public function searchProduct(int $lineIndex, string $query): void
    {
        $this->searches[$lineIndex] = $query;

        if (strlen(trim($query)) < 2) {
            $this->searchResults[$lineIndex] = [];
            return;
        }

        $shopId = Auth::user()->shop_id;

        $this->searchResults[$lineIndex] = ProductVariant::with('product.brand')
            ->whereHas('product', fn($q) =>
                $q->where('shop_id', $shopId)
                  ->where('is_active', true)
                  ->where('name', 'like', "%{$query}%")
            )
            ->where('is_active', true)
            ->limit(8)
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'label' => trim(
                    ($v->product->brand?->name ?? '') . ' ' .
                    $v->product->name .
                    ($v->attributes_label ? ' — ' . $v->attributes_label : '')
                ),
                'sku' => $v->sku,
                'tracking_type' => $v->product->tracking_type->value,
            ])
            ->toArray();
    }

    public function selectVariant(int $lineIndex, int $variantId, string $label, string $trackingType): void
    {
        $this->lines[$lineIndex]['product_variant_id'] = $variantId;
        $this->lines[$lineIndex]['variant_label'] = $label;
        $this->lines[$lineIndex]['tracking_type'] = $trackingType;
        $this->searches[$lineIndex] = $label;
        $this->searchResults[$lineIndex] = [];
        $this->syncSerialSlots($lineIndex);
        $this->recalcTotal();
    }

    public function updatedLines(mixed $value, string $key): void
    {
        // key format: "0.quantity" or "1.unit_cost"
        [$idx, $field] = array_pad(explode('.', $key, 2), 2, null);
        $idx = (int) $idx;

        if ($field === 'quantity') {
            $this->syncSerialSlots($idx);
        }

        if (in_array($field, ['quantity', 'unit_cost'])) {
            $this->recalcTotal();
        }
    }

    private function syncSerialSlots(int $idx): void
    {
        if ($this->lines[$idx]['tracking_type'] !== 'serialized') {
            $this->lines[$idx]['serial_numbers'] = [];
            return;
        }

        $qty = max(1, (int) ($this->lines[$idx]['quantity'] ?? 1));
        $existing = $this->lines[$idx]['serial_numbers'];
        $slots = [];

        for ($i = 0; $i < $qty; $i++) {
            $slots[] = $existing[$i] ?? ['serial_number' => '', 'secondary_serial_number' => ''];
        }

        $this->lines[$idx]['serial_numbers'] = $slots;
    }

    private function recalcTotal(): void
    {
        $this->totalAmount = collect($this->lines)->sum(
            fn($l) => ((float) ($l['unit_cost'] ?? 0)) * ((int) ($l['quantity'] ?? 1))
        );
    }

    public function validateImei(int $lineIdx, int $serialIdx): void
    {
        $serial = trim($this->lines[$lineIdx]['serial_numbers'][$serialIdx]['serial_number'] ?? '');

        if (empty($serial)) return;

        // Validate format (14-15 digits)
        if (! preg_match('/^\d{14,15}$/', $serial)) {
            $this->addError("lines.{$lineIdx}.serial_numbers.{$serialIdx}.serial_number", 'IMEI must be 14–15 digits.');
            return;
        }

        // Check for duplicates within this purchase form
        $count = 0;
        foreach ($this->lines as $li => $line) {
            foreach ($line['serial_numbers'] ?? [] as $si => $s) {
                if (trim($s['serial_number']) === $serial && !($li === $lineIdx && $si === $serialIdx)) {
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->addError("lines.{$lineIdx}.serial_numbers.{$serialIdx}.serial_number", 'Duplicate IMEI in this purchase.');
            return;
        }

        // Check against database (active units only)
        $exists = ProductUnit::withoutGlobalScopes()
            ->where('serial_number', $serial)
            ->where('is_archived', false)
            ->exists();

        if ($exists) {
            $this->addError("lines.{$lineIdx}.serial_numbers.{$serialIdx}.serial_number", 'This IMEI is already registered as active inventory.');
        }
    }

    public function save(ReceivePurchaseAction $action): void
    {
        $this->validate([
            'supplierId' => 'required|integer|min:1',
            'branchId' => 'required|integer|min:1',
            'purchaseDate' => 'required|date|before_or_equal:today',
            'lines' => 'required|array|min:1',
            'lines.*.product_variant_id' => 'required|integer|min:1',
            'lines.*.unit_cost' => 'required|numeric|min:0.01',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'supplierId.min' => 'Please select a supplier.',
            'branchId.min' => 'Please select a branch.',
            'lines.*.product_variant_id.min' => 'Please select a product for every line.',
            'lines.*.unit_cost.min' => 'Unit cost must be greater than zero.',
        ]);

        // Validate each IMEI for serialized lines
        foreach ($this->lines as $li => $line) {
            if ($line['tracking_type'] !== 'serialized') continue;

            $expectedQty = (int) $line['quantity'];
            $provided = collect($line['serial_numbers'])->filter(fn($s) => trim($s['serial_number']) !== '');

            if ($provided->count() !== $expectedQty) {
                $this->addError("lines.{$li}.serial_numbers", "Please enter all {$expectedQty} IMEI number(s) for this item.");
                return;
            }

            foreach ($line['serial_numbers'] as $si => $s) {
                if (! preg_match('/^\d{14,15}$/', trim($s['serial_number']))) {
                    $this->addError("lines.{$li}.serial_numbers.{$si}.serial_number", 'Invalid IMEI format (14–15 digits required).');
                    return;
                }
            }
        }

        $shop = Auth::user()->shop;

        try {
            $purchase = $action->execute($shop, [
                'supplier_id' => $this->supplierId,
                'branch_id' => $this->branchId,
                'purchase_date' => $this->purchaseDate,
                'lines' => collect($this->lines)->map(fn($l) => [
                    'product_variant_id' => (int) $l['product_variant_id'],
                    'unit_cost' => (float) $l['unit_cost'],
                    'quantity' => (int) $l['quantity'],
                    'manufacturer_warranty_months' => (int) ($l['manufacturer_warranty_months'] ?? 0),
                    'shop_warranty_days' => (int) ($l['shop_warranty_days'] ?? 0),
                    'serial_numbers' => $l['serial_numbers'] ?? [],
                ])->toArray(),
            ], Auth::user());

            $this->dispatch('notify', type: 'success', message: "{$purchase->reference_number} recorded — {$purchase->lineItems->sum('quantity')} units received.");
            $this->redirect(route('purchases.show', $purchase->id), navigate: true);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.purchases.create-purchase', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->get(),
        ]);
    }
}