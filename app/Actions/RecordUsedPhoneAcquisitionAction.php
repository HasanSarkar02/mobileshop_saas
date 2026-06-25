<?php

namespace App\Actions;

use App\Enums\PhoneCondition;
use App\Enums\UnitStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\UsedPhoneAcquisition;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordUsedPhoneAcquisitionAction
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {}

    /**
     * @param  array{
     *   branch_id: int,
     *   seller_name: string, seller_phone?: string, seller_nid?: string, seller_address?: string,
     *   imei_1: string, imei_2?: string,
     *   model_description: string,
     *   product_variant_id: int|null,
     *   condition: string,
     *   condition_notes?: string,
     *   accessories?: string,
     *   purchase_price: float,
     *   expected_sell_price?: float,
     *   payment_account_id: int,
     *   trade_in_sale_id?: int|null,
     *   notes?: string,
     * }  $data
     */
    public function execute(Shop $shop, array $data, User $actor): UsedPhoneAcquisition
    {
        return DB::transaction(function () use ($shop, $data, $actor) {

            // ── 1. Validate IMEI uniqueness ────────────────────────────────────
            $exists = ProductUnit::withoutGlobalScopes()
                ->where('serial_number', $data['imei_1'])
                ->where('is_archived', false)
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException(
                    "IMEI {$data['imei_1']} is already registered as active inventory. " .
                    "If this is a legitimate trade-in, contact support."
                );
            }

            // ── 2. Get or auto-create ProductUnit ─────────────────────────────
            $productUnit = null;

            if (! empty($data['product_variant_id'])) {
                // Linked to catalog variant — use it directly
                $variant = ProductVariant::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->findOrFail($data['product_variant_id']);
            } else {
                // Not linked — auto-create a variant under a "Used Phones" product
                // so the phone can still be sold via POS
                $variant = $this->getOrCreateUsedPhoneVariant(
                    $shop,
                    $data['model_description'],
                    (float) ($data['expected_sell_price'] ?? $data['purchase_price'] * 1.15),
                );
            }

            $productUnit = ProductUnit::create([
                'shop_id'                      => $shop->id,
                'branch_id'                    => $data['branch_id'],
                'product_variant_id'           => $variant->id,
                'serial_number'                => $data['imei_1'],
                'secondary_serial_number'      => $data['imei_2'] ?? null,
                'cost_price'                   => $data['purchase_price'],
                'status'                       => UnitStatus::InStock,
                'is_archived'                  => false,
                'shop_warranty_days'           => 0,
                'manufacturer_warranty_months' => 0,
            ]);

            // ── 3. Create acquisition record ───────────────────────────────────
            $number = $this->nextAcquisitionNumber($shop);

            $acquisition = UsedPhoneAcquisition::create([
                'shop_id'              => $shop->id,
                'branch_id'            => $data['branch_id'],
                'acquisition_number'   => $number,
                'seller_name'          => $data['seller_name'],
                'seller_phone'         => $data['seller_phone'] ?? null,
                'seller_nid'           => $data['seller_nid'] ?? null,
                'seller_address'       => $data['seller_address'] ?? null,
                'imei_1'               => $data['imei_1'],
                'imei_2'               => $data['imei_2'] ?? null,
                'model_description'    => $data['model_description'],
                'product_variant_id'   =>  $variant->id ?? null,
                'product_unit_id'      => $productUnit?->id,
                'condition'            => $data['condition'],
                'condition_notes'      => $data['condition_notes'] ?? null,
                'accessories'          => $data['accessories'] ?? null,
                'purchase_price'       => $data['purchase_price'],
                'expected_sell_price'  => $data['expected_sell_price'] ?? 0,
                'payment_account_id'   => $data['payment_account_id'],
                'trade_in_sale_id'     => $data['trade_in_sale_id'] ?? null,
                'notes'                => $data['notes'] ?? null,
                'created_by'           => $actor->id,
            ]);

            // ── 4. Accounting entry ────────────────────────────────────────────
            // Dr Inventory Asset (phone enters stock)
            // Cr Cash/Bank (payment to seller) — NOT Accounts Payable (immediate payment)
            $invAcc  = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();
            $payAcc  = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $glAcc   = Account::withoutGlobalScopes()->findOrFail($payAcc->account_id);

            $this->accounting->postEntry(
                shop: $shop,
                description: "Used phone acquisition {$number} — {$data['model_description']}",
                lines: [
                    ['account_id' => $invAcc->id, 'debit'  => $data['purchase_price'],
                     'description' => "Inventory: {$data['model_description']}"],
                    ['account_id' => $glAcc->id,  'credit' => $data['purchase_price'],
                     'description' => "Payment to seller: {$data['seller_name']}"],
                ],
                reference: $acquisition,
                branchId: $data['branch_id'],
                actor: $actor,
            );

            return $acquisition->fresh(['variant', 'productUnit', 'paymentAccount']);
        });
    }

    private function nextAcquisitionNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "used_phone_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "used_phone_{$year}")
            ->value('current_value');
        return sprintf('UPA-%s-%05d', $year, $seq);
    }

    private function getOrCreateUsedPhoneVariant(Shop $shop, string $modelDescription, float $sellPrice): ProductVariant
    {
        // Find or create the shop's "Used Phones" catch-all product
        $product = \App\Models\Product::withoutGlobalScopes()
            ->firstOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Used Phones (Unlinked)'],
                [
                    'shop_id'       => $shop->id,
                    'tracking_type' => 'serialized',
                    'description'   => 'Auto-created for used phone acquisitions not linked to catalog',
                    'is_active'     => true,
                ]
            );

        // Each unlinked acquisition gets its own variant with a unique SKU
        $sku = 'USED-' . strtoupper(substr(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9 ]/', '', $modelDescription)), 0, 20)) . '-' . now()->format('His');

        return ProductVariant::create([
            'shop_id'          => $shop->id,
            'product_id'       => $product->id,
            'attributes_label' => $modelDescription,
            'sku'              => $sku,
            'selling_price'    => $sellPrice,
            'is_active'        => true,
        ]);
    }
}