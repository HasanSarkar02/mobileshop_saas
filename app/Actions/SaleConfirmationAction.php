<?php

namespace App\Actions;

use App\Enums\CustomerType;
use App\Enums\FPReceivableStatus;
use App\Enums\SaleStatus;
use App\Enums\UnitStatus;
use App\Models\Account;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\FinancePartnerReceivable;
use App\Models\PaymentAccount;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CustomerLedgerService;
use App\Services\UnitStatusTransitioner;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleConfirmationAction
{
    public function __construct(
        private readonly AccountingService    $accounting,
        private readonly CustomerLedgerService $ledger,
        private readonly UnitStatusTransitioner $transitioner,
    ) {}

    /**
     * Execute a complete sale — inventory, accounting, ledger — all in one atomic transaction.
     * If anything fails, the whole thing rolls back. No partial states.
     *
     * @param  array{
     *   branch_id: int,
     *   customer_id: int|null,
     *   items: array<int, array{
     *     product_variant_id: int,
     *     product_unit_id: int|null,
     *     product_name: string,
     *     variant_label: string|null,
     *     sku: string,
     *     serial_number: string|null,
     *     quantity: int,
     *     unit_price: float,
     *     original_price: float,
     *     cost_price: float,
     *     discount_type: string,
     *     discount_value: float,
     *     discount_amount: float,
     *     line_subtotal: float,
     *     vat_rate: float,
     *     vat_amount: float,
     *     line_total: float,
     *   }>,
     *   payments: array<int, array{
     *     type: string,
     *     payment_account_id: int|null,
     *     finance_partner_id: int|null,
     *     amount: float,
     *     reference: string|null,
     *   }>,
     *   order_discount_type: string,
     *   order_discount_value: float,
     *   due_collection_amount: float,
     *   due_collection_account_id: int|null,
     *   notes: string|null,
     * }  $data
     */
    public function execute(Shop $shop, array $data, User $actor): Sale
    {
        return DB::transaction(function () use ($shop, $data, $actor) {

            // ── 1. Resolve customer ────────────────────────────────────────────
            $customer = ($data['customer_id'] ?? null)
                ? Customer::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->findOrFail($data['customer_id'])
                : Customer::getWalkInForShop($shop->id);

            // ── Credit limit check ─────────────────────────────────────────────
            if ($customer && $customer->customer_type->value !== 'walk_in') {
                $creditPayment = collect($data['payments'])
                    ->where('type', 'customer_credit')
                    ->sum('amount');

                if ($creditPayment > 0 && $customer->credit_limit > 0) {
                    $newBalance = (float) $customer->current_balance + $creditPayment;
                    if ($newBalance > (float) $customer->credit_limit) {
                        throw new \RuntimeException(
                            "Credit limit exceeded for {$customer->name}. " .
                            "Limit: ৳" . number_format($customer->credit_limit, 2) . ", " .
                            "Current balance: ৳" . number_format($customer->current_balance, 2) . ", " .
                            "This credit: ৳" . number_format($creditPayment, 2) . ". " .
                            "Available credit: ৳" . number_format(max(0, $customer->credit_limit - $customer->current_balance), 2)
                        );
                    }
                }
            }

            // ── 2. Lock IMEI units (race-condition protection) ─────────────────
            $units = [];
            foreach ($data['items'] as $item) {
                if (! empty($item['product_unit_id'])) {
                    $unit = ProductUnit::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)
                        ->lockForUpdate()
                        ->findOrFail($item['product_unit_id']);

                    if ($unit->status !== UnitStatus::InStock) {
                        throw new RuntimeException(
                            "Unit [{$unit->serial_number}] is no longer available — it may have just been sold by another cashier."
                        );
                    }

                    $units[$item['product_unit_id']] = $unit;
                }
            }

            // ── 3. Compute totals ──────────────────────────────────────────────
            $subtotal          = 0.0;
            $itemDiscountTotal = 0.0;
            $vatTotal          = 0.0;
            $costTotal         = 0.0;

            foreach ($data['items'] as $item) {
                $subtotal          += (float) $item['line_subtotal'];
                $itemDiscountTotal += (float) $item['discount_amount'];
                $vatTotal          += (float) $item['vat_amount'];
                $costTotal         += (float) $item['cost_price'] * (int) $item['quantity'];
            }

            $orderDiscountAmount = $this->calculateOrderDiscount(
                $subtotal - $itemDiscountTotal,
                $data['order_discount_type'] ?? 'none',
                (float) ($data['order_discount_value'] ?? 0),
            );

            $totalDiscountAmount = $itemDiscountTotal + $orderDiscountAmount;
            $grandTotal          = $subtotal - $totalDiscountAmount + $vatTotal;
            $grossProfit         = $grandTotal - $vatTotal - $costTotal;

            // ── 4. Validate payment total ──────────────────────────────────────
            $paymentTotal = collect($data['payments'])
                ->sum(fn ($p) => (float) ($p['amount'] ?? 0));

            if (abs($paymentTotal - $grandTotal) > 0.01) {
                throw new RuntimeException(
                    "Payment total (৳" . number_format($paymentTotal, 2) . ") does not match sale total (৳" . number_format($grandTotal, 2) . ")."
                );
            }

            // ── 5. Baki only for non-walk-in customers ─────────────────────────
            $hasBakiPayment = collect($data['payments'])
                ->contains(fn ($p) => ($p['type'] ?? '') === 'customer_credit');

            if ($hasBakiPayment && $customer->customer_type === CustomerType::WalkIn) {
                throw new RuntimeException('Walk-in customers cannot have credit/baki sales. Please select a registered customer.');
            }

            // ── 6. Create Sale record ──────────────────────────────────────────
            $saleNumber = $this->nextSaleNumber($shop);

            $sale = Sale::create([
                'shop_id'               => $shop->id,
                'branch_id'             => $data['branch_id'],
                'sale_number'           => $saleNumber,
                'customer_id'           => $customer->id,
                'cashier_id'            => $actor->id,
                'status'                => SaleStatus::Confirmed,
                'subtotal'              => $subtotal,
                'order_discount_type'   => $data['order_discount_type'] ?? 'none',
                'order_discount_value'  => $data['order_discount_value'] ?? 0,
                'item_discount_amount'  => $itemDiscountTotal,
                'order_discount_amount' => $orderDiscountAmount,
                'total_discount_amount' => $totalDiscountAmount,
                'vat_amount'            => $vatTotal,
                'grand_total'           => $grandTotal,
                'total_cost'            => $costTotal,
                'gross_profit'          => $grossProfit,
                'due_collection_amount' => (float) ($data['due_collection_amount'] ?? 0),
                'notes'                 => $data['notes'] ?? null,
                'created_by'            => $actor->id,
                'confirmed_at'          => now(),
            ]);

            // ── 7. Create SaleItems + process inventory ────────────────────────
            foreach ($data['items'] as $item) {
                $lineSubtotal = (float) $item['line_subtotal'];
                $discountAmt  = (float) $item['discount_amount'];
                $vatAmt       = (float) $item['vat_amount'];
                $lineTotal    = $lineSubtotal - $discountAmt + $vatAmt;
                $costForLine  = (float) $item['cost_price'] * (int) $item['quantity'];
                $profit       = ($lineSubtotal - $discountAmt) - $costForLine;

                SaleItem::create([
                    'sale_id'            => $sale->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'product_unit_id'    => $item['product_unit_id'] ?? null,
                    'product_name'       => $item['product_name'],
                    'variant_label'      => $item['variant_label'] ?? null,
                    'sku'                => $item['sku'],
                    'serial_number'      => $item['serial_number'] ?? null,
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    'original_price'     => $item['original_price'],
                    'cost_price'         => $item['cost_price'],
                    'discount_type'      => $item['discount_type'] ?? 'none',
                    'discount_value'     => $item['discount_value'] ?? 0,
                    'discount_amount'    => $discountAmt,
                    'line_subtotal'      => $lineSubtotal,
                    'vat_rate'           => $item['vat_rate'] ?? 0,
                    'vat_amount'         => $vatAmt,
                    'line_total'         => $lineTotal,
                    'profit_amount'      => $profit,
                ]);

                if (! empty($item['product_unit_id'])) {
                    // Serialized: transition the locked unit to Sold
                    $this->transitioner->transition($units[$item['product_unit_id']], UnitStatus::Sold, $sale);
                } else {
                    // Non-serialized: atomic decrement (unsignedInteger means MySQL prevents negative)
                    $affected = BranchStock::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)
                        ->where('branch_id', $data['branch_id'])
                        ->where('product_variant_id', $item['product_variant_id'])
                        ->where('quantity', '>=', $item['quantity'])
                        ->decrement('quantity', $item['quantity']);

                    if ($affected === 0) {
                        throw new RuntimeException(
                            "Insufficient stock for \"{$item['product_name']}\". The quantity was changed by another cashier."
                        );
                    }
                }
            }

            // ── 8. Create SalePayments + build journal lines ───────────────────
            $journalLines = [];
            $bakiTotal    = 0.0;

            foreach ($data['payments'] as $payment) {
                $pAmount = (float) ($payment['amount'] ?? 0);
                if ($pAmount <= 0) continue;

                $pType = $payment['type'] ?? '';

                SalePayment::create([
                    'sale_id'            => $sale->id,
                    'payment_type'       => $pType,
                    'payment_account_id' => $payment['payment_account_id'] ?? null,
                    'finance_partner_id' => $payment['finance_partner_id'] ?? null,
                    'amount'             => $pAmount,
                    'reference_number'   => $payment['reference'] ?? null,
                ]);

                if ($pType === 'finance_partner' && ! empty($payment['finance_partner_id'])) {
                    // EMI receivable — debit AR-Finance Partners
                    $arFP = Account::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)->where('code', '1110')->firstOrFail();
                    $journalLines[] = ['account_id' => $arFP->id, 'debit' => $pAmount,
                                       'description' => 'Finance Partner Receivable'];

                    FinancePartnerReceivable::create([
                        'shop_id'            => $shop->id,
                        'sale_id'            => $sale->id,
                        'finance_partner_id' => $payment['finance_partner_id'],
                        'total_amount'       => $pAmount,
                        'settled_amount'     => 0,
                        'status'             => FPReceivableStatus::Pending,
                    ]);

                } elseif ($pType === 'customer_credit') {
                    // Baki — debit AR-Customers
                    $arCust = Account::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)->where('code', '1100')->firstOrFail();
                    $journalLines[] = ['account_id' => $arCust->id, 'debit' => $pAmount,
                                       'description' => 'Customer Credit/Baki'];
                    $bakiTotal += $pAmount;

                } else {
                    // Cash/Bank/MFS — debit the payment account's GL
                    $payAccount = PaymentAccount::withoutGlobalScopes()->findOrFail($payment['payment_account_id']);
                    $glAccount  = Account::withoutGlobalScopes()->findOrFail($payAccount->account_id);
                    $journalLines[] = ['account_id' => $glAccount->id, 'debit' => $pAmount,
                                       'description' => $payAccount->name];
                }
            }

            // ── 9. Revenue credit lines ────────────────────────────────────────
            $salesRev = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '4000')->firstOrFail();
            $journalLines[] = ['account_id' => $salesRev->id, 'credit' => $subtotal, 'description' => 'Sales Revenue'];

            if ($totalDiscountAmount > 0) {
                $discAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '4020')->firstOrFail();
                $journalLines[] = ['account_id' => $discAcc->id, 'debit' => $totalDiscountAmount,
                                   'description' => 'Sales Discount'];
            }

            if ($vatTotal > 0) {
                $vatAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '2010')->firstOrFail();
                $journalLines[] = ['account_id' => $vatAcc->id, 'credit' => $vatTotal,
                                   'description' => 'Output VAT'];
            }

            // COGS
            if ($costTotal > 0) {
                $cogsAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '5000')->firstOrFail();
                $invAcc  = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();
                $journalLines[] = ['account_id' => $cogsAcc->id, 'debit'  => $costTotal, 'description' => 'COGS'];
                $journalLines[] = ['account_id' => $invAcc->id,  'credit' => $costTotal, 'description' => 'Inventory'];
            }

            $this->accounting->postEntry(
                shop: $shop,
                description: "Sale {$saleNumber}",
                lines: $journalLines,
                reference: $sale,
                branchId: $data['branch_id'],
                actor: $actor,
            );

            // ── 10. Customer ledger (baki) ─────────────────────────────────────
            if ($bakiTotal > 0) {
                $this->ledger->recordSaleCredit($customer, $bakiTotal, $sale, $actor);
            }

            // ── 11. Due collection (separate from the sale itself) ─────────────
            $dueAmount    = (float) ($data['due_collection_amount'] ?? 0);
            $dueAccountId = $data['due_collection_account_id'] ?? null;

            if ($dueAmount > 0 && $dueAccountId && $customer->customer_type !== CustomerType::WalkIn) {
                $collectionAccount = PaymentAccount::withoutGlobalScopes()->findOrFail($dueAccountId);
                $this->ledger->recordPayment(
                    $customer, $dueAmount, $collectionAccount,
                    "Due collection during sale {$saleNumber}", $actor,
                );
            }

            try {
                app(\App\Services\SmsService::class)->sendSaleReceipt(
                    $shop, $customer, $sale
                );
            } catch (\Throwable) {
                // SMS failure never blocks a confirmed sale
            }

            return $sale->fresh(['items', 'payments', 'customer', 'branch', 'cashier',
                                  'financePartnerReceivable.financePartner']);
        });
    }

    private function calculateOrderDiscount(float $base, string $type, float $value): float
    {
        return match ($type) {
            'percentage' => round($base * $value / 100, 2),
            'flat'       => min($value, $base),
            default      => 0.0,
        };
    }

    private function nextSaleNumber(Shop $shop): string
    {
        $year = now()->format('Y');

        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "sale_{$year}"]
        );

        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "sale_{$year}")
            ->value('current_value');

        return sprintf('INV-%s-%05d', $year, $seq);
    }
}