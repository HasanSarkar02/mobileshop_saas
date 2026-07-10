<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\AccountBalanceChecker;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecordSupplierPaymentAction
{
    public function __construct(
        private readonly AccountingService    $accounting,
        private readonly AccountBalanceChecker $balanceChecker,
    ) {}

    public function execute(Shop $shop, Supplier $supplier, array $data, User $actor): SupplierPayment
    {
        return DB::transaction(function () use ($shop, $supplier, $data, $actor) {

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            // ── Check payment account has sufficient balance ────────────────────
            $check = $this->balanceChecker->checkDebit(
                (int) $data['payment_account_id'],
                $amount
            );

            if (! $check['allowed']) {
                throw new RuntimeException($check['message']);
            }

            // ── Create payment record ──────────────────────────────────────────
            $paymentNumber = $this->nextPaymentNumber($shop);

            $payment = SupplierPayment::create([
                'shop_id'            => $shop->id,
                'branch_id'          => $data['branch_id'],
                'supplier_id'        => $supplier->id,
                'payment_account_id' => $data['payment_account_id'],
                'payment_number'     => $paymentNumber,
                'amount'             => $amount,
                'payment_date'       => $data['payment_date'],
                'payment_method'     => $data['payment_method'] ?? 'cash',
                'reference_number'   => $data['reference_number'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'created_by'         => $actor->id,
            ]);

            // ── Journal: Dr Accounts Payable (2000) / Cr Payment Account ──────
            $apAccount = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('code', '2000')
                ->firstOrFail();

            $pa    = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $payGl = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

            $journalEntry = $this->accounting->postEntry(
                shop:        $shop,
                description: "Supplier payment — {$supplier->name} ({$paymentNumber})",
                lines: [
                    ['account_id' => $apAccount->id, 'debit'  => $amount,
                     'description' => "AP cleared — {$supplier->name}"],
                    ['account_id' => $payGl->id,     'credit' => $amount,
                     'description' => "Paid via {$pa->name}"],
                ],
                entryDate: new \DateTime($data['payment_date']),
                reference: $payment,
                branchId:  $data['branch_id'],
                actor:     $actor,
            );

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            // ── Update supplier denormalized balance ───────────────────────────
            $supplier->decrement('current_balance', $amount);

            // ── FIFO allocation to purchases ───────────────────────────────────
            // Apply payment to oldest unpaid/partial purchases first
            $this->allocatePaymentToPurchases($supplier, $shop->id, $amount);

            if (isset($check['warning'])) {
                session()->flash('balance_warning', $check['warning']);
            }

            return $payment->fresh(['supplier', 'paymentAccount', 'createdBy']);
        });
    }

    /**
     * Allocate a payment amount to outstanding purchases in FIFO order.
     * Updates amount_paid and payment_status on each purchase.
     *
     * Example:
     *   Purchase A: ৳50,000 (unpaid)
     *   Purchase B: ৳30,000 (unpaid)
     *   Payment:    ৳65,000
     *   → Purchase A: paid (৳50,000 applied)
     *   → Purchase B: partial (৳15,000 applied, ৳15,000 remaining)
     */
    private function allocatePaymentToPurchases(
        Supplier $supplier,
        int      $shopId,
        float    $paymentAmount,
    ): void {
        $unpaidPurchases = Purchase::withoutGlobalScopes()
            ->where('supplier_id', $supplier->id)
            ->where('shop_id', $shopId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $paymentAmount;

        foreach ($unpaidPurchases as $purchase) {
            if ($remaining <= 0.005) break; // stop when allocation exhausted

            $alreadyPaid  = (float) $purchase->amount_paid;
            $outstanding  = (float) $purchase->total_amount - $alreadyPaid;

            if ($outstanding <= 0) continue;

            if ($remaining >= $outstanding) {
                // Payment fully covers this purchase
                $purchase->update([
                    'amount_paid'    => $purchase->total_amount,
                    'payment_status' => 'paid',
                ]);
                $remaining -= $outstanding;
            } else {
                // Payment partially covers this purchase
                $purchase->update([
                    'amount_paid'    => round($alreadyPaid + $remaining, 2),
                    'payment_status' => 'partial',
                ]);
                $remaining = 0;
            }
        }
    }

    private function nextPaymentNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "sup_pay_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "sup_pay_{$year}")
            ->value('current_value');
        return sprintf('SPY-%s-%05d', $year, $seq);
    }
}