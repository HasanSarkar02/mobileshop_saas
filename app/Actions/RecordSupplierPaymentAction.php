<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecordSupplierPaymentAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(Shop $shop, Supplier $supplier, array $data, User $actor): SupplierPayment
    {
        return DB::transaction(function () use ($shop, $supplier, $data, $actor) {

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
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
                'bank_name'          => $data['bank_name'] ?? null,
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

            return $payment->fresh(['supplier', 'paymentAccount', 'createdBy']);
        });
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