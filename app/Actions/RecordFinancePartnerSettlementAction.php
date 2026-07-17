<?php

namespace App\Actions;

use App\Enums\FPReceivableStatus;
use App\Events\FpSettlementRecorded;
use App\Models\Account;
use App\Models\FinancePartnerReceivable;
use App\Models\FinancePartnerSettlement;
use App\Models\FinancePartnerSettlementAllocation;
use App\Models\PaymentAccount;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RecordFinancePartnerSettlementAction
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {}

    /**
     * @param  array{
     *   finance_partner_id: int,
     *   payment_account_id: int,
     *   reference_number: string|null,
     *   gross_amount: float,
     *   fee_deducted: float,
     *   settlement_date: string,
     *   notes: string|null,
     *   allocations: array<int, array{receivable_id: int, amount: float}>,
     * }  $data
     */
    public function execute(Shop $shop, array $data, User $actor): FinancePartnerSettlement
    {
        return DB::transaction(function () use ($shop, $data, $actor) {

            $netAmount   = (float) $data['gross_amount'] - (float) $data['fee_deducted'];
            $totalAlloc  = collect($data['allocations'])
                ->sum(fn ($a) => (float) $a['amount']);

            if ($totalAlloc > $netAmount + 0.01) {
                throw new InvalidArgumentException(
                    "Total allocation (৳" . number_format($totalAlloc, 2) . ") " .
                    "exceeds net settlement amount (৳" . number_format($netAmount, 2) . ")."
                );
            }

            // ── 1. Create settlement header ────────────────────────────────────
            $settlement = FinancePartnerSettlement::create([
                'shop_id'            => $shop->id,
                'finance_partner_id' => $data['finance_partner_id'],
                'payment_account_id' => $data['payment_account_id'],
                'reference_number'   => $data['reference_number'] ?: null,
                'gross_amount'       => $data['gross_amount'],
                'fee_deducted'       => $data['fee_deducted'],
                'net_amount'         => $netAmount,
                'allocated_amount'   => 0,
                'settlement_date'    => $data['settlement_date'],
                'notes'              => $data['notes'] ?: null,
                'created_by'         => $actor->id,
            ]);

            // ── 2. Apply allocations to receivables ────────────────────────────
            $totalActuallyAllocated = 0.0;

            foreach ($data['allocations'] as $alloc) {
                $allocAmount = (float) $alloc['amount'];
                if ($allocAmount <= 0) continue;

                // Lock the receivable row to prevent race conditions
                $receivable = FinancePartnerReceivable::withoutGlobalScopes()
                    ->where('id', $alloc['receivable_id'])
                    ->where('shop_id', $shop->id) // security: must belong to this shop
                    ->whereIn('status', [
                        FPReceivableStatus::Pending->value,
                        FPReceivableStatus::Partial->value,
                    ])
                    ->lockForUpdate()
                    ->first();

                if (! $receivable) {
                    throw new RuntimeException(
                        "Receivable #{$alloc['receivable_id']} is not available for allocation."
                    );
                }

                $maxAlloc = (float) $receivable->total_amount - (float) $receivable->settled_amount;
                if ($allocAmount > $maxAlloc + 0.01) {
                    throw new InvalidArgumentException(
                        "Allocation of ৳" . number_format($allocAmount, 2) .
                        " exceeds pending amount (৳" . number_format($maxAlloc, 2) .
                        ") for receivable #{$receivable->id}."
                    );
                }

                FinancePartnerSettlementAllocation::create([
                    'settlement_id' => $settlement->id,
                    'receivable_id' => $receivable->id,
                    'amount'        => $allocAmount,
                ]);

                $newSettledAmount = (float) $receivable->settled_amount + $allocAmount;
                $fullySettled     = abs($newSettledAmount - (float) $receivable->total_amount) < 0.01;

                $receivable->update([
                    'settled_amount' => $newSettledAmount,
                    'status'         => $fullySettled
                        ? FPReceivableStatus::Settled->value
                        : FPReceivableStatus::Partial->value,
                ]);

                $totalActuallyAllocated += $allocAmount;
            }

            $settlement->update(['allocated_amount' => $totalActuallyAllocated]);

            // ── 3. Post journal entries ────────────────────────────────────────
            $arFpAccount      = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '1110')->firstOrFail();
            $payAccount       = PaymentAccount::withoutGlobalScopes()
                ->findOrFail($data['payment_account_id']);
            $glPayAccount     = Account::withoutGlobalScopes()
                ->findOrFail($payAccount->account_id);

            $journalLines = [
                // Net amount received — debit the bank/cash account
                [
                    'account_id'  => $glPayAccount->id,
                    'debit'       => $netAmount,
                    'description' => "Settlement from {$settlement->partner?->name}",
                ],
                // Reduce the finance partner receivable
                [
                    'account_id'  => $arFpAccount->id,
                    'credit'      => (float) $data['gross_amount'],
                    'description' => 'Clearing finance partner receivable',
                ],
            ];

            // Fee deduction (if any) → expense
            if ((float) $data['fee_deducted'] > 0) {
                $feeAccount = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('code', '6070')
                    ->firstOrFail();

                $journalLines[] = [
                    'account_id'  => $feeAccount->id,
                    'debit'       => (float) $data['fee_deducted'],
                    'description' => 'Finance partner processing fee',
                ];
            }

            $this->accounting->postEntry(
                shop: $shop,
                description: "Finance partner settlement — {$settlement->partner?->name} Ref: " .
                             ($data['reference_number'] ?? 'N/A'),
                lines: $journalLines,
                entryDate: new \DateTime($data['settlement_date']),
                reference: $settlement,
                actor: $actor,
            );
            DB::afterCommit(fn () => event(new FpSettlementRecorded($settlement, $shop)));

            return $settlement->fresh(['partner', 'paymentAccount', 'allocations.receivable.sale.customer']);
        });
    }
}