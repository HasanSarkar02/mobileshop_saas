<?php

namespace App\Services;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\Customer;
use App\Models\CustomerDueFollowUp;
use App\Models\CustomerTransaction;
use App\Models\Shop;
use App\Models\User;

class CustomerFollowUpService
{
    /**
     * Manual entry (staff). Closes whatever follow-up is currently open for
     * this customer — without touching its recorded content — then creates
     * a fresh row. Guarantees at most one "active" follow-up per customer at
     * a time, while every prior entry stays in history untouched.
     */
    public function create(Customer $customer, Shop $shop, array $data, ?User $actor = null): CustomerDueFollowUp
    {
        $this->closeOpenFollowUps($shop->id, $customer->id);

        return CustomerDueFollowUp::create([
            'shop_id'               => $shop->id,
            'customer_id'           => $customer->id,
            'created_by'            => $actor?->id,
            'followup_type'         => $data['followup_type'],
            'followup_date'         => $data['followup_date'],
            'status'                => $data['status'],
            'promised_payment_date' => $data['promised_payment_date'] ?? null,
            'promised_amount'       => $data['promised_amount'] ?? null,
            'next_followup_date'    => $data['next_followup_date'] ?? null,
            'customer_response'     => $data['customer_response'] ?? null,
            'internal_note'         => $data['internal_note'] ?? null,
        ]);
    }

    /** Reschedule/edit the currently open follow-up. Auto-closes it if the new status isn't open. */
    public function update(CustomerDueFollowUp $followUp, array $data): CustomerDueFollowUp
    {
        $status = FollowUpStatus::from($data['status']);

        $followUp->update([
            'next_followup_date'    => $data['next_followup_date'] ?? null,
            'promised_payment_date' => $data['promised_payment_date'] ?? null,
            'promised_amount'       => $data['promised_amount'] ?? null,
            'status'                => $status->value,
            'customer_response'     => $data['customer_response'] ?? null,
            'internal_note'         => $data['internal_note'] ?? null,
            'completed_at'          => $status->isOpen() ? null : ($followUp->completed_at ?? now()),
        ]);

        return $followUp;
    }

    /**
     * Fired on every CustomerPaymentRecorded (POS due-collection, profile
     * page — same event, same handling). Rules:
     *  - Balance reaches zero  → close every open follow-up as Paid. Done.
     *  - Balance under the shop's ignore threshold → close open follow-ups,
     *    create nothing. Still on the ledger; owner can write it off later.
     *  - Otherwise → close whatever was open (history preserved) and start
     *    a fresh system-generated one, ~1 month out, status PartiallyPaid.
     */
    public function handlePaymentRecorded(CustomerTransaction $transaction, Customer $customer, Shop $shop): void
    {
        $customer = Customer::withoutGlobalScopes()->lockForUpdate()->findOrFail($customer->id);
        $balance  = (float) $customer->current_balance;

        if ($balance <= 0) {
            CustomerDueFollowUp::withoutGlobalScopes()
                ->where('shop_id', $customer->shop_id)
                ->where('customer_id', $customer->id)
                ->whereNull('completed_at')
                ->update(['status' => FollowUpStatus::Paid->value, 'completed_at' => now()]);
            return;
        }

        $threshold = (float) ($shop->due_followup_ignore_threshold ?? 0);

        if ($threshold > 0 && $balance <= $threshold) {
            $this->closeOpenFollowUps($customer->shop_id, $customer->id);
            return;
        }

        $this->closeOpenFollowUps($customer->shop_id, $customer->id);

        CustomerDueFollowUp::withoutGlobalScopes()->create([
            'shop_id'            => $customer->shop_id,
            'customer_id'        => $customer->id,
            'created_by'         => null, // system-generated
            'followup_type'      => FollowUpType::Other->value,
            'followup_date'      => now(),
            'status'             => FollowUpStatus::PartiallyPaid->value,
            'next_followup_date' => now()->addMonthNoOverflow(),
            'internal_note'      => sprintf(
                'Auto-scheduled — payment of ৳%s received, ৳%s still due.',
                number_format((float) $transaction->amount, 2),
                number_format($balance, 2),
            ),
        ]);
    }

    private function closeOpenFollowUps(int $shopId, int $customerId): void
    {
        CustomerDueFollowUp::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('customer_id', $customerId)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }
}