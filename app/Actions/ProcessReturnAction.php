<?php

namespace App\Actions;

use App\Enums\CreditNoteStatus;
use App\Enums\CustomerTransactionType;
use App\Enums\FPReceivableStatus;
use App\Enums\RefundMethod;
use App\Enums\ReturnCondition;
use App\Enums\UnitStatus;
use App\Models\Account;
use App\Models\BranchStock;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CustomerTransaction;
use App\Models\FinancePartnerReceivable;
use App\Models\PaymentAccount;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\UnitStatusTransitioner;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessReturnAction
{
    public function __construct(
        private readonly AccountingService      $accounting,
        private readonly UnitStatusTransitioner $transitioner,
    ) {}

    public function execute(Sale $sale, array $data, User $actor): CreditNote
    {
        if ($sale->status->value !== 'confirmed') {
            throw new RuntimeException('Only confirmed sales can be returned.');
        }

        return DB::transaction(function () use ($sale, $data, $actor) {
            $shop         = $sale->shop()->withoutGlobalScopes()->findOrFail($sale->shop_id);
            $refundMethod = RefundMethod::from($data['refund_method']);

            $sale->load(['items', 'payments', 'customer', 'financePartnerReceivable']);

            // ── 1. Build return lines using user-specified refund amounts ──────
            $returnLines   = [];
            $totalRefund   = 0.0;
            $totalCostRestored = 0.0;

            foreach ($data['items'] as $line) {
                $saleItem = $sale->items->firstWhere('id', $line['sale_item_id']);
                if (! $saleItem) {
                    throw new RuntimeException("Sale item #{$line['sale_item_id']} not found.");
                }

                $refundAmount = (float) ($line['refund_amount'] ?? $saleItem->line_total);
                $refundAmount = min($refundAmount, (float) $saleItem->line_total); // cap at original
                $lineCost     = (float) $saleItem->cost_price * (int) $line['quantity'];

                $returnLines[] = array_merge($line, [
                    'sale_item'     => $saleItem,
                    'refund_amount' => $refundAmount,
                    'line_cost'     => $lineCost,
                ]);

                $totalRefund += $refundAmount;
                if ($line['restock']) {
                    $totalCostRestored += $lineCost;
                }
            }

            if ($totalRefund <= 0) {
                throw new RuntimeException('Total refund amount must be greater than zero.');
            }

            // ── 2. Create CreditNote ───────────────────────────────────────────
            $cnNumber = $this->nextCreditNoteNumber($shop);

            $creditNote = CreditNote::create([
                'shop_id'                   => $shop->id,
                'branch_id'                 => $sale->branch_id,
                'credit_note_number'        => $cnNumber,
                'original_sale_id'          => $sale->id,
                'customer_id'               => $sale->customer_id,
                'status'                    => CreditNoteStatus::Completed,
                'refund_method'             => $refundMethod->value,
                'items_total'               => $totalRefund,
                'refund_amount'             => $totalRefund,
                'restock_value'             => $totalCostRestored,
                'refund_payment_account_id' => $data['refund_payment_account_id'] ?? null,
                'refund_reference'          => $data['refund_reference'] ?? null,
                'reason'                    => $data['reason'],
                'notes'                     => $data['notes'] ?? null,
                'created_by'                => $actor->id,
            ]);

            // ── 3. Create items + process inventory ────────────────────────────
            foreach ($returnLines as $line) {
                $saleItem  = $line['sale_item'];
                $condition = ReturnCondition::from($line['condition']);

                CreditNoteItem::create([
                    'credit_note_id'        => $creditNote->id,
                    'original_sale_item_id' => $saleItem->id,
                    'product_variant_id'    => $saleItem->product_variant_id,
                    'product_unit_id'       => $saleItem->product_unit_id,
                    'product_name'          => $saleItem->product_name,
                    'variant_label'         => $saleItem->variant_label,
                    'sku'                   => $saleItem->sku,
                    'serial_number'         => $saleItem->serial_number,
                    'quantity'              => $line['quantity'],
                    'unit_price'            => $saleItem->unit_price,
                    'unit_cost'             => $saleItem->cost_price,
                    'line_total'            => $line['refund_amount'],
                    'condition'             => $condition->value,
                    'restock'               => $line['restock'],
                    'restock_branch_id'     => $line['restock_branch_id'],
                    'condition_notes'       => $line['condition_notes'] ?? null,
                ]);

                if ($saleItem->product_unit_id) {
                    $unit = ProductUnit::withoutGlobalScopes()->findOrFail($saleItem->product_unit_id);
                    $this->transitioner->transition($unit, UnitStatus::ReturnedPendingInspection, $creditNote);
                    $unit->refresh();

                    if ($line['restock'] && $condition->shouldRestock()) {
                        $this->transitioner->transition($unit, UnitStatus::InStock);
                        $unit->update([
                            'branch_id'   => $line['restock_branch_id'],
                            'is_archived' => false,
                            'sold_at'     => null,
                        ]);
                    } else {
                        $this->transitioner->transition($unit, UnitStatus::Damaged);
                    }
                } elseif ($line['restock']) {
                    BranchStock::withoutGlobalScopes()
                        ->firstOrCreate(
                            [
                                'shop_id'            => $shop->id,
                                'branch_id'          => $line['restock_branch_id'],
                                'product_variant_id' => $saleItem->product_variant_id,
                            ],
                            ['quantity' => 0, 'average_cost' => $saleItem->cost_price]
                        )
                        ->increment('quantity', $line['quantity']);
                }
            }

            // ── 4. Build journal entries ───────────────────────────────────────
            $salesReturnAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '4010')->firstOrFail();

            $journalLines = [
                [
                    'account_id'  => $salesReturnAcc->id,
                    'debit'       => $totalRefund,
                    'description' => "Return {$cnNumber}",
                ],
            ];

            // COGS reversal for restocked items only
            if ($totalCostRestored > 0) {
                $cogsAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '5000')->firstOrFail();
                $invAcc  = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();

                $journalLines[] = ['account_id' => $invAcc->id,  'debit'  => $totalCostRestored,
                                   'description' => 'Inventory restored'];
                $journalLines[] = ['account_id' => $cogsAcc->id, 'credit' => $totalCostRestored,
                                   'description' => 'COGS reversal'];
            }

            // Credit side — proportional to original payment mix
            $journalLines = array_merge(
                $journalLines,
                $this->buildBalancedCreditLines($sale, $totalRefund, $refundMethod, $data, $shop)
            );

            $this->accounting->postEntry(
                shop: $shop,
                description: "Return {$cnNumber} — original sale {$sale->sale_number}",
                lines: $journalLines,
                reference: $creditNote,
                branchId: $sale->branch_id,
                actor: $actor,
            );

            // ── 5. Customer ledger update ──────────────────────────────────────
            $this->updateCustomerLedger($sale, $totalRefund, $creditNote, $actor);

            // ── 6. Finance partner receivable ──────────────────────────────────
            $this->handleFinancePartnerOnReturn($sale);
            $sale->update(['return_processed' => true]);

            return $creditNote->fresh(['items', 'originalSale', 'customer']);
        });
    }

    /**
     * Build credit lines that ALWAYS balance the debit side.
     * Handles all payment types including finance_partner and customer_credit.
     */
    private function buildBalancedCreditLines(
        Sale $sale,
        float $totalRefund,
        RefundMethod $refundMethod,
        array $data,
        Shop $shop,
    ): array {
        $lines = [];

        if ($refundMethod === RefundMethod::StoreCredit || $refundMethod === RefundMethod::Exchange) {
            $advancesAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '2020')->firstOrFail();
            $lines[] = [
                'account_id'  => $advancesAcc->id,
                'credit'      => $totalRefund,
                'description' => $refundMethod === RefundMethod::StoreCredit
                    ? 'Store credit issued to customer'
                    : 'Exchange credit pending new sale',
            ];
            return $lines;
        }

        // OriginalPayment — distribute refund proportionally across payment methods
        $saleTotal = max((float) $sale->grand_total, 0.01);
        $ratio     = $totalRefund / $saleTotal;

        // Check if finance partner receivable is already settled
        $fpReceivable  = $sale->financePartnerReceivable;
        $fpAlreadyPaid = $fpReceivable && in_array($fpReceivable->status?->value, ['settled', 'partial']);

        $allocatedCredit = 0.0;
        $lastLineIdx     = null;

        foreach ($sale->payments as $idx => $payment) {
            $share = round((float) $payment->amount * $ratio, 2);
            if ($share <= 0) continue;

            $lastLineIdx = $idx;

            if ($payment->payment_type === 'finance_partner') {
                if ($fpAlreadyPaid) {
                    // Partner already paid us → we now owe them
                    $dueToFpAcc = Account::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)->where('code', '2040')->firstOrFail();
                    $lines[] = [
                        'account_id'  => $dueToFpAcc->id,
                        'credit'      => $share,
                        'description' => 'Due to finance partner (return after settlement)',
                    ];
                } else {
                    // Not yet paid → reduce what they owe us
                    $arFpAcc = Account::withoutGlobalScopes()
                        ->where('shop_id', $shop->id)->where('code', '1110')->firstOrFail();
                    $lines[] = [
                        'account_id'  => $arFpAcc->id,
                        'credit'      => $share,
                        'description' => 'Finance partner receivable cancelled',
                    ];
                }
            } elseif ($payment->payment_type === 'customer_credit') {
                // Reduce customer due balance
                $arCustAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '1100')->firstOrFail();
                $lines[] = [
                    'account_id'  => $arCustAcc->id,
                    'credit'      => $share,
                    'description' => 'Customer credit reduced',
                ];
            } else {
                // Cash / Bank / MFS
                $pa    = PaymentAccount::withoutGlobalScopes()
                    ->findOrFail($payment->payment_account_id);
                $glAcc = Account::withoutGlobalScopes()->findOrFail($pa->account_id);
                $lines[] = [
                    'account_id'  => $glAcc->id,
                    'credit'      => $share,
                    'description' => "Refund via {$pa->name}",
                ];
            }

            $allocatedCredit += $share;
        }

        // Fix rounding difference on last line to ensure balance
        if (! empty($lines) && abs($allocatedCredit - $totalRefund) > 0.001) {
            $diff = round($totalRefund - $allocatedCredit, 2);
            if ($diff !== 0.0 && $lastLineIdx !== null) {
                $lastCredit = end($lines);
                $lastCredit['credit'] = round($lastCredit['credit'] + $diff, 2);
                $lines[count($lines) - 1] = $lastCredit;
            }
        }

        return $lines;
    }

    private function updateCustomerLedger(Sale $sale, float $totalRefund, CreditNote $creditNote, User $actor): void
    {
        $bakiPayment = $sale->payments->firstWhere('payment_type', 'customer_credit');
        if (! $bakiPayment) return;

        $saleTotal   = max((float) $sale->grand_total, 0.01);
        $bakiRatio   = (float) $bakiPayment->amount / $saleTotal;
        $bakiCredit  = min(round($totalRefund * $bakiRatio, 2), (float) $bakiPayment->amount);
        if ($bakiCredit <= 0) return;

        $customer = $sale->customer()->withoutGlobalScopes()->lockForUpdate()->findOrFail($sale->customer_id);
        $newBal   = max(0, (float) $customer->current_balance - $bakiCredit);

        CustomerTransaction::create([
            'shop_id'          => $sale->shop_id,
            'customer_id'      => $customer->id,
            'transaction_type' => CustomerTransactionType::ReturnCredit->value,
            'amount'           => $bakiCredit,
            'direction'        => 'credit',
            'running_balance'  => $newBal,
            'reference_type'   => CreditNote::class,
            'reference_id'     => $creditNote->id,
            'notes'            => "Return credit — {$creditNote->credit_note_number}",
            'created_by'       => $actor->id,
        ]);

        $customer->update(['current_balance' => $newBal]);
    }

    private function handleFinancePartnerOnReturn(Sale $sale): void
    {
        $fpReceivable = FinancePartnerReceivable::withoutGlobalScopes()
            ->where('sale_id', $sale->id)
            ->whereIn('status', [FPReceivableStatus::Pending->value, FPReceivableStatus::Partial->value])
            ->first();

        if ($fpReceivable) {
            $fpReceivable->update(['status' => FPReceivableStatus::Cancelled->value]);
        }
    }

    private function nextCreditNoteNumber(Shop $shop): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shop->id, "credit_note_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shop->id)
            ->where('counter_key', "credit_note_{$year}")
            ->value('current_value');
        return sprintf('CN-%s-%05d', $year, $seq);
    }
}