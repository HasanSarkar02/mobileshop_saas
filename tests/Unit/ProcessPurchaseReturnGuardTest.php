<?php

namespace Tests\Unit;

use App\Actions\ProcessPurchaseReturnAction;
use App\Models\Purchase;
use App\Models\User;
use RuntimeException;
use Tests\TestCase;

/**
 * Guard-level tests for ProcessPurchaseReturnAction::execute().
 *
 * These cover only the validation that runs BEFORE any database write
 * (settlement allow-list, paid+credit_note block, refund-account
 * requirement, credit-note oversize check). The full success paths
 * (journal posting, stock moves) need a MySQL-backed staging DB because
 * the action uses MySQL-specific upserts (shop_counters) — verify those
 * manually on staging, never on live production.
 */
class ProcessPurchaseReturnGuardTest extends TestCase
{
    /**
     * Regression: every money field the payment/return actions write via
     * fill()/update() MUST be mass-assignable. `amount_paid` was once missing
     * from Purchase::$fillable, so all payments silently recorded status
     * without amounts (Paid badge + ৳0 paid + full Due). DB-free.
     */
    public function test_purchase_money_fields_are_mass_assignable(): void
    {
        $purchase = new Purchase;
        foreach (['total_amount', 'amount_paid', 'payment_status'] as $field) {
            $this->assertTrue(
                $purchase->isFillable($field),
                "Purchase::{$field} must be fillable or writes are silently dropped."
            );
        }
    }

    private function action(): ProcessPurchaseReturnAction
    {
        return $this->app->make(ProcessPurchaseReturnAction::class);
    }

    /** Purchase stub with no DB — overrides the one query the guards need. */
    private function stubPurchase(string $status, float $effectiveTotal, float $amountPaid): Purchase
    {
        return new class($status, $effectiveTotal, $amountPaid) extends Purchase
        {
            public function __construct(
                private string $stubStatus,
                private float $stubEffective,
                private float $stubPaid,
            ) {
                // Deliberately NOT calling parent::__construct with attributes:
                // set raw attributes directly so no DB connection is ever needed.
                $this->mergeAttributesFromCachedCasts();
                $this->setRawAttributes([
                    'payment_status' => $this->stubStatus,
                    'amount_paid' => $this->stubPaid,
                ], true);
            }

            public function effectiveTotalAmount(): float
            {
                return $this->stubEffective;
            }
        };
    }

    private function item(float $unitCost, int $qty): array
    {
        return [
            'purchase_line_item_id' => 1,
            'product_variant_id' => 1,
            'product_unit_id' => null,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'condition' => 'good',
        ];
    }

    private function baseData(string $settlement, array $items, mixed $refundAccountId = 1): array
    {
        $data = [
            'return_date' => now()->format('Y-m-d'),
            'return_reason' => 'Defective batch for guard test',
            'settlement_type' => $settlement,
            'notes' => null,
            'items' => $items,
        ];
        if ($refundAccountId !== null) {
            $data['refund_account_id'] = $refundAccountId;
        }

        return $data;
    }

    public function test_invalid_settlement_type_rejected_before_payment_status_check(): void
    {
        // Even on a PAID purchase, an unknown type must report the type error —
        // proving the allow-list runs first.
        $purchase = $this->stubPurchase('paid', 1000.00, 1000.00);

        try {
            $this->action()->execute(
                $purchase,
                $this->baseData('replacement', [$this->item(100.00, 1)]),
                new User
            );
            $this->fail('Expected RuntimeException for invalid settlement type.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Invalid settlement type', $e->getMessage());
        }
    }

    public function test_missing_settlement_type_rejected(): void
    {
        $purchase = $this->stubPurchase('unpaid', 1000.00, 0.00);
        $data = $this->baseData('credit_note', [$this->item(100.00, 1)]);
        unset($data['settlement_type']);

        try {
            $this->action()->execute($purchase, $data, new User);
            $this->fail('Expected RuntimeException for missing settlement type.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Invalid settlement type', $e->getMessage());
        }
    }

    public function test_paid_purchase_with_credit_note_is_blocked(): void
    {
        $purchase = $this->stubPurchase('paid', 1000.00, 1000.00);

        try {
            $this->action()->execute(
                $purchase,
                $this->baseData('credit_note', [$this->item(100.00, 1)]),
                new User
            );
            $this->fail('Expected RuntimeException for credit note on paid purchase.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('fully paid', $e->getMessage());
            $this->assertStringContainsString('cash_refund', $e->getMessage());
        }
    }

    public function test_cash_refund_without_refund_account_is_rejected(): void
    {
        // On an UNPAID purchase too — proves cash_refund is not blocked on
        // unpaid/partial, it just needs the account.
        $purchase = $this->stubPurchase('unpaid', 1000.00, 0.00);

        try {
            $this->action()->execute(
                $purchase,
                $this->baseData('cash_refund', [$this->item(100.00, 1)], null),
                new User
            );
            $this->fail('Expected RuntimeException for missing refund account.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('refund account', $e->getMessage());
        }
    }

    public function test_oversize_credit_note_is_rejected(): void
    {
        // Outstanding = 1000 - 200 = 800; requesting 900 must fail.
        $purchase = $this->stubPurchase('unpaid', 1000.00, 200.00);

        try {
            $this->action()->execute(
                $purchase,
                $this->baseData('credit_note', [$this->item(900.00, 1)]),
                new User
            );
            $this->fail('Expected RuntimeException for oversize credit note.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('exceeds outstanding payable', $e->getMessage());
        }
    }

    public function test_credit_note_equal_to_outstanding_passes_the_guard(): void
    {
        // Outstanding = 800; requesting exactly 800 must NOT raise the oversize
        // error. It will fail later (no DB rows for shop/items in this
        // DB-free test) — any NON-oversize exception proves the guard passed.
        $purchase = $this->stubPurchase('unpaid', 1000.00, 200.00);

        try {
            $this->action()->execute(
                $purchase,
                $this->baseData('credit_note', [$this->item(800.00, 1)]),
                new User
            );
            // If it somehow succeeded without a DB, that also means guards passed.
            $this->assertTrue(true);
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString(
                'exceeds outstanding payable',
                $e->getMessage(),
                'Exact-outstanding credit note must not be treated as oversize.'
            );
        }
    }
}
