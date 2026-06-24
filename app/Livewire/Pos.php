<?php

namespace App\Livewire;

use App\Actions\SaleConfirmationAction;
use App\Enums\CustomerType;
use App\Enums\ProductTrackingType;
use App\Enums\UnitStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\FinancePartner;
use App\Models\PaymentAccount;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.pos')]
#[Title('POS')]
class Pos extends Component
{
    // ── Cart ─────────────────────────────────────────────────────────────────
    public array $cart = [];

    // ── Product search ────────────────────────────────────────────────────────
    public string $productSearch  = '';
    public array  $productResults = [];

    // ── Barcode / IMEI scanner ────────────────────────────────────────────────
    public string $barcodeInput = '';

    // ── Unit picker (serialized) ──────────────────────────────────────────────
    public bool   $showUnitPicker    = false;
    public int    $pendingVariantIdx = -1;
    public string $unitSearchQuery   = '';
    public array  $availableUnits    = [];

    // ── Customer ──────────────────────────────────────────────────────────────
    public ?int   $customerId      = null;
    public array  $customerDisplay = [];
    public string $customerSearch  = '';
    public array  $customerResults = [];

    // ── Quick add customer ────────────────────────────────────────────────────
    public bool   $showQuickCustomer = false;
    public string $qcName            = '';
    public string $qcPhone           = '';

    // ── Order discount ────────────────────────────────────────────────────────
    public string $orderDiscountType  = 'none';
    public string $orderDiscountValue = '0';
    public bool   $showDiscountPanel  = false;

    // ── Payments ──────────────────────────────────────────────────────────────
    public array $paymentLines = [];

    // ── Due collection ────────────────────────────────────────────────────────
    public string $dueCollectionAmount    = '0';
    public int    $dueCollectionAccountId = 0;
    public bool   $showDueCollection      = false;

    // ── Notes ─────────────────────────────────────────────────────────────────
    public string $saleNotes = '';

    // ── State ─────────────────────────────────────────────────────────────────
    public ?int $completedSaleId = null;
    public int  $currentBranchId;
    public bool $vatEnabled;
    public float $vatRate;

    // ── Search dropdown visibility
    public bool $showProductDropdown  = false;
    public bool $showCustomerDropdown = false;

    // ── Hold Sale ─────────────────────────────────────────────────────────────
    public bool $showHeldSales = false;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $user = Auth::user();
        $shop = $user->shop()->withoutGlobalScopes()->findOrFail($user->shop_id);

        $this->vatEnabled = (bool) $shop->vat_enabled;
        $this->vatRate    = (float) $shop->default_vat_rate;

        $this->currentBranchId = $user->branch_id
            ?? Branch::where('shop_id', $shop->id)->where('is_main', true)->value('id')
            ?? 0;

        // Default: cash payment line with first available cash account
        $defaultCash = PaymentAccount::where('is_active', true)
            ->where('provider', 'cash')
            ->first();

        $this->paymentLines = [[
            'type'               => 'cash',
            'payment_account_id' => $defaultCash?->id ?? 0,
            'finance_partner_id' => null,
            'amount'             => '',
            'reference'          => '',
        ]];
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->orderBy('provider')->orderBy('name')->get();
    }

    #[Computed]
    public function financePartners(): \Illuminate\Database\Eloquent\Collection
    {
        return FinancePartner::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function totals(): array
    {
        $subtotal = 0.0;
        $itemDiscount = 0.0;
        $vatTotal = 0.0;
        $costTotal = 0.0;

        foreach ($this->cart as $item) {
            $subtotal     += (float) ($item['line_subtotal'] ?? 0);
            $itemDiscount += (float) ($item['discount_amount'] ?? 0);
            $vatTotal     += (float) ($item['vat_amount'] ?? 0);
            $costTotal    += (float) ($item['cost_price'] ?? 0) * (int) ($item['quantity'] ?? 1);
        }

        $baseForOrderDiscount = $subtotal - $itemDiscount;
        $orderDiscount = match ($this->orderDiscountType) {
            'percentage' => round($baseForOrderDiscount * (float) $this->orderDiscountValue / 100, 2),
            'flat'       => min((float) $this->orderDiscountValue, $baseForOrderDiscount),
            default      => 0.0,
        };

        $totalDiscount = $itemDiscount + $orderDiscount;
        $grandTotal    = $subtotal - $totalDiscount + $vatTotal;

        $salePaid = collect($this->paymentLines)
            ->sum(fn ($p) => (float) ($p['amount'] ?? 0));

        $remaining  = max(0.0, $grandTotal - $salePaid);
        $change     = max(0.0, $salePaid - $grandTotal);
        $profit     = ($subtotal - $totalDiscount) - $costTotal;

        return compact(
            'subtotal', 'itemDiscount', 'orderDiscount', 'totalDiscount',
            'vatTotal', 'grandTotal', 'salePaid', 'remaining', 'change',
            'costTotal', 'profit',
        );
    }

    // ── Product Search ────────────────────────────────────────────────────────

    public function updatedProductSearch(): void
    {
        if (strlen(trim($this->productSearch)) < 2) {
            $this->productResults = [];
            $this->showProductDropdown = false;
            return;
        }

        $shopId = Auth::user()->shop_id;
        $q      = $this->productSearch;

        $this->productResults = ProductVariant::with('product.brand')
            ->whereHas('product', fn ($pq) =>
                $pq->where('shop_id', $shopId)
                   ->where('is_active', true)
                   ->where('name', 'like', "%{$q}%")
            )
            ->orWhere(fn ($sq) =>
                $sq->where('sku', 'like', "%{$q}%")
                   ->whereHas('product', fn($pq) =>
                       $pq->where('shop_id', $shopId)->where('is_active', true)
                   )
            )
            ->where('is_active', true)
            ->limit(8)
            ->get()
            ->map(fn ($v) => [
                'id'            => $v->id,
                'label'         => trim(
                    ($v->product->brand?->name ?? '') . ' ' .
                    $v->product->name .
                    ($v->attributes_label ? ' — ' . $v->attributes_label : '')
                ),
                'sku'           => $v->sku,
                'selling_price' => $v->selling_price,
                'tracking_type' => $v->product->tracking_type->value,
                'product_name'  => $v->product->name,
            ])
            ->toArray();

        $this->showProductDropdown = ! empty($this->productResults);
    }

    public function processBarcode(): void
    {
        $code = trim($this->barcodeInput);
        $this->barcodeInput = '';

        if (empty($code)) return;

        $shopId = Auth::user()->shop_id;

        // Try IMEI / serial first
        $unit = ProductUnit::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where(fn ($q) => $q->where('serial_number', $code)->orWhere('secondary_serial_number', $code))
            ->where('status', UnitStatus::InStock->value)
            ->where('is_archived', false)
            ->with('variant.product.brand')
            ->first();

        if ($unit) {
            $this->addUnitToCart($unit);
            $this->dispatch('notify', type: 'success', message: "Added: {$unit->serial_number}");
            return;
        }

        // Try SKU
        $variant = ProductVariant::where('sku', $code)->where('is_active', true)->with('product.brand')->first();
        if ($variant && $variant->product->tracking_type === ProductTrackingType::NonSerialized) {
            $this->addVariantToCart($variant->id);
            return;
        }

        $this->dispatch('notify', type: 'error', message: "Not found: {$code}");
    }

    public function selectVariantFromSearch(int $variantId, string $trackingType): void
    {
        $this->productSearch  = '';
        $this->productResults = [];

        if ($trackingType === 'serialized') {
            $this->openUnitPicker($variantId);
        } else {
            $this->addVariantToCart($variantId);
        }
    }

    public function openUnitPicker(int $variantId): void
    {
        $shopId    = Auth::user()->shop_id;
        $branchId  = $this->currentBranchId;

        $units = ProductUnit::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('product_variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->where('status', UnitStatus::InStock->value)
            ->where('is_archived', false)
            ->get()
            ->map(fn ($u) => [
                'id'            => $u->id,
                'serial_number' => $u->serial_number,
                'secondary'     => $u->secondary_serial_number,
                'cost_price'    => $u->cost_price,
            ])
            ->toArray();

        if (count($units) === 1) {
            // Only one unit → auto-select
            $unit = ProductUnit::withoutGlobalScopes()->with('variant.product.brand')->find($units[0]['id']);
            $this->addUnitToCart($unit);
            return;
        }

        if (count($units) === 0) {
            $this->dispatch('notify', type: 'error', message: 'No units in stock at this branch for this variant.');
            return;
        }

        $this->pendingVariantIdx = count($this->cart); // will be appended
        $this->availableUnits    = $units;
        $this->showUnitPicker    = true;
    }

    public function selectUnitFromPicker(int $unitId): void
    {
        $this->showUnitPicker = false;
        $unit = ProductUnit::withoutGlobalScopes()->with('variant.product.brand')->findOrFail($unitId);
        $this->addUnitToCart($unit);
    }

    private function addUnitToCart(ProductUnit $unit): void
    {
        // Check if already in cart
        foreach ($this->cart as $item) {
            if (($item['product_unit_id'] ?? null) === $unit->id) {
                $this->dispatch('notify', type: 'error', message: 'This unit is already in the cart.');
                return;
            }
        }

        $v = $unit->variant;
        $p = $v->product;

        $this->cart[] = $this->buildCartItem([
            'product_variant_id' => $v->id,
            'product_unit_id'    => $unit->id,
            'product_name'       => $p->name,
            'variant_label'      => $v->attributes_label,
            'sku'                => $v->sku,
            'serial_number'      => $unit->serial_number,
            'tracking_type'      => 'serialized',
            'quantity'           => 1,
            'unit_price'         => (float) $v->selling_price,
            'original_price'     => (float) $v->selling_price,
            'cost_price'         => (float) $unit->cost_price,
        ]);
    }

    private function addVariantToCart(int $variantId): void
    {
        $variant = ProductVariant::with('product.brand')->findOrFail($variantId);
        $product = $variant->product;

        // Check existing cart item for same variant (increment quantity for non-serialized)
        foreach ($this->cart as $idx => $item) {
            if ($item['product_variant_id'] === $variantId && empty($item['product_unit_id'])) {
                $this->cart[$idx]['quantity']++;
                $this->recalcItem($idx);
                return;
            }
        }

        // Check available stock
        $stock = BranchStock::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->where('branch_id', $this->currentBranchId)
            ->where('product_variant_id', $variantId)
            ->first();

        if (! $stock || $stock->quantity < 1) {
            $this->dispatch('notify', type: 'error', message: 'No stock available for this item at this branch.');
            return;
        }

        $this->cart[] = $this->buildCartItem([
            'product_variant_id' => $variant->id,
            'product_unit_id'    => null,
            'product_name'       => $product->name,
            'variant_label'      => $variant->attributes_label,
            'sku'                => $variant->sku,
            'serial_number'      => null,
            'tracking_type'      => 'non_serialized',
            'quantity'           => 1,
            'unit_price'         => (float) $variant->selling_price,
            'original_price'     => (float) $variant->selling_price,
            'cost_price'         => (float) ($stock->average_cost ?? 0),
        ]);
    }

    private function buildCartItem(array $base): array
    {
        $item = array_merge($base, [
            'discount_type'  => 'none',
            'discount_value' => '0',
            'vat_rate'       => $this->vatEnabled ? $this->vatRate : 0,
        ]);

        return $this->calculateItemTotals($item);
    }

    private function calculateItemTotals(array $item): array
    {
        $qty   = (int) $item['quantity'];
        $price = (float) $item['unit_price'];
        $cost  = (float) $item['cost_price'];

        $lineSubtotal = $price * $qty;

        $discountAmount = match ($item['discount_type']) {
            'percentage' => round($lineSubtotal * (float) $item['discount_value'] / 100, 2),
            'flat'       => min((float) $item['discount_value'], $lineSubtotal),
            default      => 0.0,
        };

        $netBeforeVat = $lineSubtotal - $discountAmount;
        $vatAmount    = $this->vatEnabled
            ? round($netBeforeVat * (float) $item['vat_rate'] / 100, 2)
            : 0.0;
        $lineTotal = $netBeforeVat + $vatAmount;
        $profit    = $netBeforeVat - ($cost * $qty);

        return array_merge($item, [
            'discount_amount' => $discountAmount,
            'line_subtotal'   => $lineSubtotal,
            'vat_amount'      => $vatAmount,
            'line_total'      => $lineTotal,
            'profit'          => $profit,
            'is_below_cost'   => $price < $cost,
        ]);
    }

    // ── Cart Operations ───────────────────────────────────────────────────────

    public function removeItem(int $idx): void
    {
        array_splice($this->cart, $idx, 1);
    }

    public function updateQuantity(int $idx, int $delta): void
    {
        if (! isset($this->cart[$idx])) return;
        if (! empty($this->cart[$idx]['product_unit_id'])) return; // serialized units = always qty 1

        $newQty = max(1, (int) $this->cart[$idx]['quantity'] + $delta);
        $this->cart[$idx]['quantity'] = $newQty;
        $this->recalcItem($idx);
    }

    public function updatedCart(mixed $value, string $key): void
    {
        [$idx, $field] = array_pad(explode('.', $key, 2), 2, null);
        if (in_array($field, ['unit_price', 'quantity', 'discount_type', 'discount_value'])) {
            $this->recalcItem((int) $idx);
        }
    }

    private function recalcItem(int $idx): void
    {
        if (isset($this->cart[$idx])) {
            $this->cart[$idx] = $this->calculateItemTotals($this->cart[$idx]);
        }
    }

    // ── Customer ──────────────────────────────────────────────────────────────

    public function updatedCustomerSearch(): void
    {
        if (strlen(trim($this->customerSearch)) < 2) {
            $this->customerResults = [];
            $this->showCustomerDropdown = false;
            return;
        }

        $q      = $this->customerSearch;
        $shopId = Auth::user()->shop_id;

        $this->customerResults = Customer::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('customer_type', '!=', CustomerType::WalkIn->value)
            ->where(fn ($cq) =>
                $cq->where('name', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
            )
            ->select(['id', 'name', 'phone', 'customer_type', 'current_balance', 'credit_limit'])
            ->limit(8)
            ->get()
            ->toArray();

        $this->showCustomerDropdown = ! empty($this->customerResults);
    }

    public function selectCustomer(int $id): void
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($id);

        $this->customerId = $id;
        $this->customerSearch = '';
        $this->customerResults = [];
        $this->customerDisplay = [
            'name'            => $customer->name,
            'phone'           => $customer->phone,
            'type'            => $customer->customer_type->label(),
            'current_balance' => (float) $customer->current_balance,
            'credit_limit'    => (float) $customer->credit_limit,
        ];

        // Show due collection if customer has balance
        if ($customer->current_balance > 0) {
            $this->showDueCollection = true;
        }
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerDisplay = [];
        $this->showDueCollection = false;
        $this->dueCollectionAmount = '0';
        $this->dueCollectionAccountId = 0;
    }

    public function quickAddCustomer(): void
    {
        $this->validate([
            'qcName'  => 'required|string|max:255',
            'qcPhone' => 'required|string|max:20',
        ]);

        $customer = Customer::create([
            'shop_id'       => Auth::user()->shop_id,
            'customer_type' => CustomerType::Regular->value,
            'name'          => $this->qcName,
            'phone'         => $this->qcPhone,
            'is_active'     => true,
            'created_by'    => Auth::id(),
        ]);

        $this->selectCustomer($customer->id);
        $this->showQuickCustomer = false;
        $this->qcName = $this->qcPhone = '';
        $this->dispatch('notify', type: 'success', message: "Customer \"{$customer->name}\" added and selected.");
    }

    // ── Payment Lines ─────────────────────────────────────────────────────────

    public function addPaymentLine(): void
    {
        $this->paymentLines[] = [
            'type'               => 'cash',
            'payment_account_id' => PaymentAccount::where('is_active', true)->where('provider', 'cash')->value('id'),
            'finance_partner_id' => null,
            'amount'             => '',
            'reference'          => '',
        ];
    }

    public function removePaymentLine(int $idx): void
    {
        if (count($this->paymentLines) <= 1) {
            $this->dispatch('notify', type: 'error', message: 'At least one payment line is required.');
            return;
        }
        array_splice($this->paymentLines, $idx, 1);
    }

    public function fillRemaining(int $idx): void
    {
        $remaining = $this->totals['remaining'];
        $this->paymentLines[$idx]['amount'] = $remaining > 0 ? (string) number_format($remaining, 2, '.', '') : '';
    }

    public function updatedPaymentLines(mixed $value, string $key): void
    {
        // When type changes, reset account/partner selection
        if (str_ends_with($key, '.type')) {
            $idx = (int) explode('.', $key)[0];
            $this->paymentLines[$idx]['payment_account_id'] = null;
            $this->paymentLines[$idx]['finance_partner_id'] = null;
        }
    }

    // ── Confirm Sale ──────────────────────────────────────────────────────────

    public function confirmSale(SaleConfirmationAction $action): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty.');
            return;
        }

        $totals = $this->totals;

        if (abs($totals['remaining']) > 0.01) {
            $this->dispatch('notify', type: 'error',
                message: "Payment doesn't match: ৳" . number_format(abs($totals['remaining']), 2) . ' remaining.');
            return;
        }

        // Baki payment requires a registered customer
        $hasBaki = collect($this->paymentLines)
            ->filter(fn ($p) => ($p['amount'] ?? 0) > 0)
            ->contains(fn ($p) => $p['type'] === 'customer_credit');

        if ($hasBaki && ! $this->customerId) {
            $this->dispatch('notify', type: 'error', message: 'Please select a customer for credit/baki sales.');
            return;
        }

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $sale = $action->execute($shop, [
                'branch_id'                => $this->currentBranchId,
                'customer_id'              => $this->customerId,
                'items'                    => $this->cart,
                'payments'                 => collect($this->paymentLines)
                    ->filter(fn ($p) => (float) ($p['amount'] ?? 0) > 0)
                    ->values()
                    ->toArray(),
                'order_discount_type'      => $this->orderDiscountType,
                'order_discount_value'     => (float) $this->orderDiscountValue,
                'due_collection_amount'    => (float) $this->dueCollectionAmount,
                'due_collection_account_id' => $this->dueCollectionAccountId ?: null,
                'notes'                    => $this->saleNotes ?: null,
            ], Auth::user());

            $this->completedSaleId = $sale->id;

        } catch (\RuntimeException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function startNewSale(): void
    {
        $this->reset([
            'cart', 'productSearch', 'productResults', 'barcodeInput',
            'customerId', 'customerDisplay', 'customerSearch', 'customerResults',
            'orderDiscountType', 'orderDiscountValue', 'showDiscountPanel',
            'dueCollectionAmount', 'dueCollectionAccountId', 'showDueCollection',
            'saleNotes', 'completedSaleId', 'showQuickCustomer',
        ]);

        $defaultCash = PaymentAccount::where('is_active', true)->where('provider', 'cash')->first();
        $this->paymentLines = [[
            'type'               => 'cash',
            'payment_account_id' => $defaultCash?->id ?? 0,
            'finance_partner_id' => null,
            'amount'             => '',
            'reference'          => '',
        ]];
    }

    public function closeProductSearch(): void
    {
        $this->productResults = [];
        $this->showProductDropdown = false;
        $this->productSearch = '';
    }

    public function closeCustomerSearch(): void
    {
        $this->customerResults = [];
        $this->showCustomerDropdown = false;
        $this->customerSearch = '';
    }


    

    // ── Hold Sale ─────────────────────────────────────────────────────────────

    public function holdSale(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty.');
            return;
        }

        $key        = 'held_sales_' . Auth::id();
        $heldSales  = session()->get($key, []);

        $heldSales[] = [
            'id'                  => uniqid('hold_', true),
            'cart'                => $this->cart,
            'customer_id'         => $this->customerId,
            'customer_display'    => $this->customerDisplay,
            'order_discount_type' => $this->orderDiscountType,
            'order_discount_value'=> $this->orderDiscountValue,
            'notes'               => $this->saleNotes,
            'held_at'             => now()->format('H:i'),
            'item_count'          => count($this->cart),
            'total'               => $this->totals['grandTotal'],
            'customer_name'       => $this->customerDisplay['name'] ?? 'Walk-in',
        ];

        session()->put($key, $heldSales);
        $this->startNewSale();
        $this->dispatch('notify', type: 'success', message: 'Sale held. Cart saved — you can resume it anytime.');
    }

    public function resumeHeldSale(string $heldId): void
    {
        $key       = 'held_sales_' . Auth::id();
        $heldSales = session()->get($key, []);
        $held      = collect($heldSales)->firstWhere('id', $heldId);

        if (! $held) {
            $this->dispatch('notify', type: 'error', message: 'Held sale not found.');
            return;
        }

        if (! empty($this->cart)) {
            // Auto-hold current cart before resuming
            $this->holdSale();
        }

        $this->cart                = $held['cart'];
        $this->customerId          = $held['customer_id'];
        $this->customerDisplay     = $held['customer_display'] ?? [];
        $this->orderDiscountType   = $held['order_discount_type'] ?? 'none';
        $this->orderDiscountValue  = $held['order_discount_value'] ?? '0';
        $this->saleNotes           = $held['notes'] ?? '';

        // Remove from held list
        $remaining = collect($heldSales)->reject(fn ($h) => $h['id'] === $heldId)->values()->toArray();
        session()->put($key, $remaining);

        $this->showHeldSales = false;
        $this->dispatch('notify', type: 'success', message: 'Sale resumed.');
    }

    public function discardHeldSale(string $heldId): void
    {
        $key       = 'held_sales_' . Auth::id();
        $heldSales = session()->get($key, []);
        $remaining = collect($heldSales)->reject(fn ($h) => $h['id'] === $heldId)->values()->toArray();
        session()->put($key, $remaining);
        $this->dispatch('notify', type: 'success', message: 'Held sale discarded.');
    }

    #[Computed]
    public function heldSales(): array
    {
        return session()->get('held_sales_' . Auth::id(), []);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.pos');
    }
}