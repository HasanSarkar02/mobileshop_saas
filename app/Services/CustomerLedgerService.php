<?php

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Events\CustomerPaymentRecorded;

class CustomerLedgerService
{
    public function __construct(
        private readonly AccountingService $accountingService,
    ) {}

    /**
     * Record an opening balance when migrating an existing shop.
     * Journal: Dr AR-Customers / Cr Opening Balance Equity
     */
    public function recordOpeningBalance(
        Customer $customer,
        float $amount,
        ?User $actor = null,
    ): CustomerTransaction {
        return DB::transaction(function () use ($customer, $amount, $actor) {
            $transaction = $this->record(
                customer: $customer,
                type: CustomerTransactionType::OpeningBalance,
                amount: $amount,
                direction: 'debit',
                notes: 'Opening balance migration',
                actor: $actor,
            );

            $shop = $customer->shop;
            $arAccount = $this->getArAccount($shop->id);
            $openingEquity = Account::where('shop_id', $shop->id)->where('code', '3020')->firstOrFail();

            $this->accountingService->postEntry(
                shop: $shop,
                description: "Opening balance for customer {$customer->name}",
                lines: [
                    ['account_id' => $arAccount->id, 'debit'  => $amount],
                    ['account_id' => $openingEquity->id, 'credit' => $amount],
                ],
                reference: $customer,
                actor: $actor,
            );

            return $transaction;
        });
    }

    /**
     * Record a credit/baki sale — called by POS (Step 13).
     * Journal: Dr AR-Customers / Cr Sales Revenue (posted by POS separately)
     * Here we ONLY update the subsidiary ledger (no double accounting).
     */
    public function recordSaleCredit(
        Customer $customer,
        float $amount,
        Model $sale,
        ?User $actor = null,
    ): CustomerTransaction {
        return $this->record(
            customer: $customer,
            type: CustomerTransactionType::SaleCredit,
            amount: $amount,
            direction: 'debit',
            reference: $sale,
            notes: "Credit sale",
            actor: $actor,
        );
    }

    /**
     * Customer comes in and pays their due.
     * Journal: Dr Payment Account GL / Cr AR-Customers
     */
    public function recordPayment(
        Customer $customer,
        float $amount,
        PaymentAccount $paymentAccount,
        ?string $notes = null,
        ?User $actor = null,
    ): CustomerTransaction {
        return DB::transaction(function () use ($customer, $amount, $paymentAccount, $notes, $actor) {
            $transaction = $this->record(
                customer: $customer,
                type: CustomerTransactionType::PaymentReceived,
                amount: $amount,
                direction: 'credit',
                notes: $notes,
                actor: $actor,
            );

            $shop = $customer->shop;
            $arAccount = $this->getArAccount($shop->id);
            $cashAccount = Account::findOrFail($paymentAccount->account_id);

            $this->accountingService->postEntry(
                shop: $shop,
                description: "Customer payment — {$customer->name}",
                lines: [
                    ['account_id' => $cashAccount->id, 'debit'  => $amount],
                    ['account_id' => $arAccount->id,   'credit' => $amount],
                ],
                reference: $customer,
                actor: $actor,
            );

            // $customer->refresh();
            // DB::afterCommit(fn () => event(new CustomerPaymentRecorded($transaction, $customer, $shop)));

            return $transaction;
        });
    }

    /**
     * Return reduces what a customer owes.
     * Called by Returns module (Step 15). No GL entry here — return action posts its own entry.
     */
    public function recordReturnCredit(
        Customer $customer,
        float $amount,
        Model $creditNote,
        ?User $actor = null,
    ): CustomerTransaction {
        return $this->record(
            customer: $customer,
            type: CustomerTransactionType::ReturnCredit,
            amount: $amount,
            direction: 'credit',
            reference: $creditNote,
            notes: 'Return credit',
            actor: $actor,
        );
    }

    /**
     * Write off a customer's bad debt.
     * Journal: Dr Bad Debt Expense / Cr AR-Customers
     */
    public function writeOff(
        Customer $customer,
        float $amount,
        ?string $notes = null,
        ?User $actor = null,
    ): CustomerTransaction {
        return DB::transaction(function () use ($customer, $amount, $notes, $actor) {
            $transaction = $this->record(
                customer: $customer,
                type: CustomerTransactionType::WriteOff,
                amount: $amount,
                direction: 'credit',
                notes: $notes ?? 'Bad debt write-off',
                actor: $actor,
            );

            $shop = $customer->shop;
            $arAccount = $this->getArAccount($shop->id);
            $badDebtAccount = Account::where('shop_id', $shop->id)->where('code', '6050')->firstOrFail();

            $this->accountingService->postEntry(
                shop: $shop,
                description: "Bad debt write-off — {$customer->name}",
                lines: [
                    ['account_id' => $badDebtAccount->id, 'debit'  => $amount],
                    ['account_id' => $arAccount->id,      'credit' => $amount],
                ],
                reference: $customer,
                actor: $actor,
            );

            return $transaction;
        });
    }

    // ── Core recording engine ─────────────────────────────────────────────────

    private function record(
        Customer $customer,
        CustomerTransactionType $type,
        float $amount,
        string $direction,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $actor = null,
    ): CustomerTransaction {
        // lockForUpdate prevents race conditions when two requests update the same customer balance
        $customer = Customer::withoutGlobalScopes()->lockForUpdate()->findOrFail($customer->id);

        $newBalance = $direction === 'debit'
            ? $customer->current_balance + $amount
            : $customer->current_balance - $amount;

        $newBalance = max(0, $newBalance); // balance never goes negative

        $transaction = CustomerTransaction::create([
            'shop_id'          => $customer->shop_id,
            'customer_id'      => $customer->id,
            'transaction_type' => $type->value,
            'amount'           => $amount,
            'direction'        => $direction,
            'running_balance'  => $newBalance,
            'reference_type'   => $reference?->getMorphClass(),
            'reference_id'     => $reference?->getKey(),
            'notes'            => $notes,
            'created_by'       => $actor?->id,
        ]);

        // Update cached aggregates on customer
        $updates = ['current_balance' => $newBalance];

        if ($type === CustomerTransactionType::SaleCredit) {
            $updates['total_purchase_amount'] = $customer->total_purchase_amount + $amount;
        }

        if ($type === CustomerTransactionType::PaymentReceived) {
            $updates['total_paid_amount'] = $customer->total_paid_amount + $amount;
        }

        $customer->update($updates);

        return $transaction;
    }

    private function getArAccount(int $shopId): Account
    {
        return Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('code', '1100')
            ->firstOrFail();
    }
}