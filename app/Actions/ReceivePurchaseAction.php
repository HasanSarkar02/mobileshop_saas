<?php

namespace App\Actions;

use App\Enums\ProductTrackingType;
use App\Enums\UnitStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseLineItem;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceivePurchaseAction
{
    public function __construct(
        private readonly AccountingService $accountingService,
    ) {}

    /**
     * @param  array{
     *     supplier_id: int, branch_id: int, purchase_date: string,
     *     lines: array<int, array{
     *         product_variant_id: int, unit_cost: float, quantity: int,
     *         manufacturer_warranty_months?: int, shop_warranty_days?: int,
     *         serial_numbers?: array<int, array{serial_number: string, secondary_serial_number?: string}>
     *     }>
     * }  $data
     */
    public function execute(Shop $shop, array $data, ?User $actor = null): Purchase
    {
        return DB::transaction(function () use ($shop, $data, $actor) {
            $branch = Branch::where('shop_id', $shop->id)->findOrFail($data['branch_id']);
            $totalAmount = 0;

            $purchase = Purchase::create([
                'shop_id' => $shop->id,
                'branch_id' => $branch->id,
                'supplier_id' => $data['supplier_id'],
                'reference_number' => $this->nextReferenceNumber($shop),
                'purchase_date' => $data['purchase_date'],
                'total_amount' => 0,
                'created_by' => $actor?->id,
            ]);

            foreach ($data['lines'] as $line) {
                $variant = ProductVariant::with('product')->findOrFail($line['product_variant_id']);
                $lineTotal = $line['unit_cost'] * $line['quantity'];
                $totalAmount += $lineTotal;

                $lineItem = PurchaseLineItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $lineTotal,
                ]);

                if ($variant->product->tracking_type === ProductTrackingType::Serialized) {
                    $this->receiveSerializedUnits($shop, $branch, $variant, $lineItem, $line);
                } else {
                    $this->receiveBulkStock($shop, $branch, $variant, $line['quantity'], $line['unit_cost']);
                }
            }

            $purchase->update(['total_amount' => $totalAmount]);

            $this->postPurchaseJournalEntry($shop, $branch, $purchase, $totalAmount, $actor);

            return $purchase->fresh('lineItems');
        });
    }

    private function receiveSerializedUnits(Shop $shop, Branch $branch, ProductVariant $variant, PurchaseLineItem $lineItem, array $line): void
    {
        $serials = $line['serial_numbers'] ?? [];

        if (count($serials) !== $line['quantity']) {
            throw new InvalidArgumentException('Number of serial numbers entered does not match the quantity purchased.');
        }

        foreach ($serials as $serial) {
            $this->assertSerialNumberNotActive($serial['serial_number']);

            ProductUnit::create([
                'shop_id' => $shop->id,
                'branch_id' => $branch->id,
                'product_variant_id' => $variant->id,
                'serial_number' => $serial['serial_number'],
                'secondary_serial_number' => $serial['secondary_serial_number'] ?? null,
                'cost_price' => $line['unit_cost'],
                'purchase_line_item_id' => $lineItem->id,
                'status' => UnitStatus::InStock,
                'manufacturer_warranty_months' => $line['manufacturer_warranty_months'] ?? 0,
                'shop_warranty_days' => $line['shop_warranty_days'] ?? 0,
                'is_archived' => false,
            ]);
        }
    }

    private function receiveBulkStock(Shop $shop, Branch $branch, ProductVariant $variant, int $quantity, float $unitCost): void
    {
        $stock = BranchStock::firstOrCreate(
            ['shop_id' => $shop->id, 'branch_id' => $branch->id, 'product_variant_id' => $variant->id],
            ['quantity' => 0, 'average_cost' => 0]
        );

        $newQuantity = $stock->quantity + $quantity;
        $newAverageCost = $newQuantity > 0
            ? (($stock->quantity * $stock->average_cost) + ($quantity * $unitCost)) / $newQuantity
            : 0;

        $stock->update(['quantity' => $newQuantity, 'average_cost' => $newAverageCost]);
    }

    /**
     * Deliberately generic error — never reveals WHICH shop the IMEI is
     * already registered at. That's a cross-tenant information leak the
     * stress test caught: an Owner shouldn't learn that a specific phone
     * exists at a competitor's shop.
     */
    private function assertSerialNumberNotActive(string $serialNumber): void
    {
        $exists = ProductUnit::withoutGlobalScopes()
            ->where('serial_number', $serialNumber)
            ->where('is_archived', false)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException(
                'This IMEI/serial number is already registered as active inventory. Verify the number, or contact support if this is a legitimate trade-in.'
            );
        }
    }

    private function postPurchaseJournalEntry(Shop $shop, Branch $branch, Purchase $purchase, float $totalAmount, ?User $actor): void
    {
        $inventoryAccount = Account::where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();
        $payableAccount = Account::where('shop_id', $shop->id)->where('code', '2000')->firstOrFail();

        $this->accountingService->postEntry(
            shop: $shop,
            description: "Purchase {$purchase->reference_number}",
            lines: [
                ['account_id' => $inventoryAccount->id, 'debit' => $totalAmount],
                ['account_id' => $payableAccount->id, 'credit' => $totalAmount],
            ],
            entryDate: $purchase->purchase_date,
            reference: $purchase,
            branchId: $branch->id,
            actor: $actor,
        );
    }

    private function nextReferenceNumber(Shop $shop): string
    {
        $year = now()->format('Y');

        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "purchase_{$year}"]
        );

        $sequence = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "purchase_{$year}")
            ->value('current_value');

        return sprintf('PO-%s-%05d', $year, $sequence);
    }
}