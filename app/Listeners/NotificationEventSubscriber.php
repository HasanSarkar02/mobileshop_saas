<?php

namespace App\Listeners;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Events\CustomerCreditLimitReached;
use App\Events\EmployeeInvited;
use App\Events\ExpenseApproved;
use App\Events\ExpensePendingApproval;
use App\Events\ExpenseRejected;
use App\Events\ExpenseVoided;
use App\Events\FpSettlementRecorded;
use App\Events\ImpersonationStarted;
use App\Events\PayrollDraftReady;
use App\Events\PayrollPaid;
use App\Events\PurchaseReceived;
use App\Events\PurchaseReturnProcessed;
use App\Events\ReturnProcessed;
use App\Events\SalaryOverdrawn;
use App\Events\SaleConfirmed;
use App\Events\SaleVoided;
use App\Events\StockLow;
use App\Events\StockTransferInitiated;
use App\Events\StockTransferReceived;
use App\Events\SupplierBalanceHigh;
use App\Events\TreasuryApproved;
use App\Events\TreasuryPendingApproval;
use App\Events\TreasuryRejected;
use App\Events\TreasuryReversed;
use App\Events\UsedPhoneAcquired;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\RecipientResolver;
use Illuminate\Events\Dispatcher;

class NotificationEventSubscriber
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly RecipientResolver $recipients,
    ) {}

    // ── Phase 1 (unchanged) ─────────────────────────────────────────────────

    public function handleExpensePendingApproval(ExpensePendingApproval $event): void
    {
        $expense = $event->expense;

        $this->notifications->dispatch(
            NotificationEventType::ExpensePendingApproval,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::ExpensesApprove->value, $expense->branch_id),
            [
                'title' => 'Expense needs approval',
                'body' => '৳' . number_format((float) $expense->amount, 2) . " — {$expense->description}",
                'reference' => $expense,
                'branch_id' => $expense->branch_id,
                'group_key' => "expense_pending:{$event->shop->id}",
                'placeholders' => ['amount' => '৳' . number_format((float) $expense->amount, 2), 'branch' => $expense->branch?->name, 'date' => $expense->expense_date?->format('d M Y')],
            ],
            ['amount' => (float) $expense->amount],
        );
    }

    public function handleExpenseApproved(ExpenseApproved $event): void
    {
        $expense = $event->expense;
        if (! $expense->created_by || $expense->created_by === $event->actor->id) return;

        $this->notifications->dispatch(
            NotificationEventType::ExpenseApproved,
            $event->shop,
            $this->recipients->byUsers([$expense->createdBy]),
            [
                'title' => 'Your expense was approved',
                'body' => '৳' . number_format((float) $expense->amount, 2) . " — {$expense->description}",
                'reference' => $expense,
                'branch_id' => $expense->branch_id,
            ]
        );
    }

    public function handleExpenseRejected(ExpenseRejected $event): void
    {
        $expense = $event->expense;
        if (! $expense->created_by) return;

        $this->notifications->dispatch(
            NotificationEventType::ExpenseRejected,
            $event->shop,
            $this->recipients->byUsers([$expense->createdBy]),
            ['title' => 'Your expense was rejected', 'body' => $event->reason, 'reference' => $expense, 'branch_id' => $expense->branch_id]
        );
    }

    public function handleSaleConfirmed(SaleConfirmed $event): void
    {
        $sale = $event->sale;

        $this->notifications->dispatch(
            NotificationEventType::SaleConfirmed,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::SalesView->value, $sale->branch_id),
            [
                'title' => 'New sale confirmed',
                'body' => "{$sale->sale_number} — ৳" . number_format((float) $sale->grand_total, 2),
                'reference' => $sale,
                'branch_id' => $sale->branch_id,
                'group_key' => "sale_confirmed:{$sale->branch_id}:" . now()->format('Y-m-d'),
                'group_cooldown_minutes' => 1440,
                'placeholders' => ['invoice_no' => $sale->sale_number, 'sale_total' => '৳' . number_format((float) $sale->grand_total, 2), 'branch' => $sale->branch?->name],
            ]
        );
    }

    public function handleSaleVoided(SaleVoided $event): void
    {
        $sale = $event->sale;

        $this->notifications->dispatch(
            NotificationEventType::SaleVoided,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::SalesVoid->value, $sale->branch_id),
            ['title' => 'Sale voided', 'body' => "{$sale->sale_number} voided by {$event->actor->name}: {$event->reason}", 'reference' => $sale, 'branch_id' => $sale->branch_id]
        );
    }

    public function handleTreasuryPendingApproval(TreasuryPendingApproval $event): void
    {
        $txn = $event->transaction;

        $this->notifications->dispatch(
            NotificationEventType::TreasuryPendingApproval,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::TreasuryApprove->value, $txn->branch_id),
            [
                'title' => 'Treasury transaction needs approval',
                'body' => "{$txn->transaction_type->label()} — ৳" . number_format((float) $txn->amount, 2),
                'reference' => $txn,
                'branch_id' => $txn->branch_id,
                'group_key' => "treasury_pending:{$event->shop->id}",
                'placeholders' => ['amount' => '৳' . number_format((float) $txn->amount, 2), 'branch' => $txn->branch?->name, 'date' => $txn->transaction_date?->format('d M Y')],
            ],
            ['amount' => (float) $txn->amount],
        );
    }

    public function handleTreasuryApproved(TreasuryApproved $event): void
    {
        $txn = $event->transaction;
        if (! $txn->created_by || $txn->created_by === $event->actor->id) return;

        $this->notifications->dispatch(
            NotificationEventType::TreasuryApproved,
            $event->shop,
            $this->recipients->byUsers([$txn->createdBy]),
            ['title' => 'Treasury transaction approved', 'body' => "{$txn->transaction_number} approved by {$event->actor->name}", 'reference' => $txn, 'branch_id' => $txn->branch_id]
        );
    }

    public function handleTreasuryRejected(TreasuryRejected $event): void
    {
        $txn = $event->transaction;
        if (! $txn->created_by) return;

        $this->notifications->dispatch(
            NotificationEventType::TreasuryRejected,
            $event->shop,
            $this->recipients->byUsers([$txn->createdBy]),
            ['title' => 'Treasury transaction rejected', 'body' => $event->reason, 'reference' => $txn, 'branch_id' => $txn->branch_id]
        );
    }

    // ── Phase 2 ─────────────────────────────────────────────────────────────

    public function handleReturnProcessed(ReturnProcessed $event): void
    {
        $cn = $event->creditNote;

        $this->notifications->dispatch(
            NotificationEventType::ReturnProcessed,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::SalesRefund->value, $cn->branch_id),
            [
                'title' => 'Return processed',
                'body' => "{$cn->credit_note_number} — refund ৳" . number_format((float) $cn->refund_amount, 2),
                'reference' => $cn,
                'branch_id' => $cn->branch_id,
            ]
        );
    }

    public function handlePurchaseReceived(PurchaseReceived $event): void
    {
        $purchase = $event->purchase;

        $this->notifications->dispatch(
            NotificationEventType::PurchaseReceived,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::PurchasesView->value, $purchase->branch_id),
            [
                'title' => 'Purchase received',
                'body' => "{$purchase->reference_number} — ৳" . number_format((float) $purchase->total_amount, 2),
                'reference' => $purchase,
                'branch_id' => $purchase->branch_id,
                'group_key' => "purchase_received:{$purchase->branch_id}:" . now()->format('Y-m-d'),
                'group_cooldown_minutes' => 1440,
            ]
        );
    }

    public function handleSupplierBalanceHigh(SupplierBalanceHigh $event): void
    {
        $supplier = $event->supplier;

        $this->notifications->dispatch(
            NotificationEventType::SupplierBalanceHigh,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::SuppliersManage->value),
            [
                'title' => 'Supplier balance exceeds credit limit',
                'body' => "{$supplier->name}: ৳" . number_format((float) $supplier->current_balance, 2) .
                    ' (limit ৳' . number_format((float) $supplier->credit_limit, 2) . ')',
                'reference' => $supplier,
                'group_key' => "supplier_balance_high:{$supplier->id}",
                'group_cooldown_minutes' => 1440,
                'placeholders' => ['supplier_name' => $supplier->name, 'amount' => '৳' . number_format((float) $supplier->current_balance, 2)],
            ]
        );
    }

    public function handlePurchaseReturnProcessed(PurchaseReturnProcessed $event): void
    {
        $return = $event->purchaseReturn;

        $this->notifications->dispatch(
            NotificationEventType::PurchaseReturnProcessed,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::PurchasesView->value, $return->branch_id),
            [
                'title' => 'Purchase return processed',
                'body' => "{$return->return_number} — ৳" . number_format((float) $return->total_amount, 2),
                'reference' => $return,
                'branch_id' => $return->branch_id,
            ]
        );
    }

    public function handleStockLow(StockLow $event): void
    {
        $this->notifications->dispatch(
            NotificationEventType::StockLow,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::ProductsView->value, $event->branchId),
            [
                'title' => 'Stock running low',
                'body' => "{$event->variant->attributes_label} ({$event->variant->sku}) — only {$event->remainingQuantity} left",
                'reference' => $event->variant,
                'branch_id' => $event->branchId,
                'group_key' => "stock_low:{$event->branchId}:{$event->variant->id}",
                'group_cooldown_minutes' => 1440,
            ]
        );
    }

    public function handleCustomerCreditLimitReached(CustomerCreditLimitReached $event): void
    {
        $customer = $event->customer;

        $this->notifications->dispatch(
            NotificationEventType::CustomerCreditLimitReached,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::CustomersRecordDuePayment->value),
            [
                'title' => 'Customer approaching credit limit',
                'body' => "{$customer->name}: ৳" . number_format((float) $customer->current_balance, 2) .
                    ' of ৳' . number_format((float) $customer->credit_limit, 2) . ' limit',
                'reference' => $customer,
                'group_key' => "customer_credit_limit:{$customer->id}",
                'group_cooldown_minutes' => 4320,
                'placeholders' => ['customer_name' => $customer->name, 'amount' => '৳' . number_format((float) $customer->current_balance, 2)],
            ]
        );
    }

    public function handleStockTransferInitiated(StockTransferInitiated $event): void
    {
        $transfer = $event->transfer;

        $this->notifications->dispatch(
            NotificationEventType::StockTransferInitiated,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::StockConfirmTransfer->value, $transfer->to_branch_id),
            [
                'title' => 'Stock transfer incoming',
                'body' => "Transfer #{$transfer->id} from {$transfer->fromBranch?->name} to {$transfer->toBranch?->name}",
                'reference' => $transfer,
                'branch_id' => $transfer->to_branch_id,
            ]
        );
    }

    public function handleStockTransferReceived(StockTransferReceived $event): void
    {
        $transfer = $event->transfer;

        $this->notifications->dispatch(
            NotificationEventType::StockTransferReceived,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::StockTransfer->value, $transfer->from_branch_id),
            [
                'title' => 'Stock transfer confirmed',
                'body' => "Transfer #{$transfer->id} received at {$transfer->toBranch?->name}",
                'reference' => $transfer,
                'branch_id' => $transfer->from_branch_id,
            ]
        );
    }

    public function handleExpenseVoided(ExpenseVoided $event): void
    {
        $expense = $event->expense;

        $this->notifications->dispatch(
            NotificationEventType::ExpenseVoided,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::ExpensesApprove->value, $expense->branch_id),
            ['title' => 'Expense voided', 'body' => "{$expense->description}: {$event->reason}", 'reference' => $expense, 'branch_id' => $expense->branch_id]
        );
    }

    public function handlePayrollDraftReady(PayrollDraftReady $event): void
    {
        $run = $event->run;

        $this->notifications->dispatch(
            NotificationEventType::PayrollDraftReady,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::PayrollManage->value),
            ['title' => 'Payroll draft ready', 'body' => $run->monthName() . ' — ৳' . number_format((float) $run->total_net, 2) . ' net', 'reference' => $run]
        );
    }

    public function handlePayrollPaid(PayrollPaid $event): void
    {
        $run = $event->run;

        $this->notifications->dispatch(
            NotificationEventType::PayrollPaid,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::PayrollManage->value),
            ['title' => 'Payroll paid', 'body' => $run->monthName() . ' — ৳' . number_format((float) $run->total_net, 2) . ' paid by ' . $event->actor->name, 'reference' => $run]
        );
    }

    public function handleSalaryOverdrawn(SalaryOverdrawn $event): void
    {
        $this->notifications->dispatch(
            NotificationEventType::SalaryOverdrawn,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::PayrollManage->value),
            [
                'title' => 'Employee salary overdrawn',
                'body' => "{$event->employee->name} drew ৳" . number_format($event->overdrawAmount, 2) . ' more than their gross salary.',
                'reference' => $event->employee,
                'group_key' => "salary_overdrawn:{$event->employee->id}:" . now()->format('Y-m'),
                'group_cooldown_minutes' => 40320,
                'placeholders' => ['employee_name' => $event->employee->name, 'amount' => '৳' . number_format($event->overdrawAmount, 2)],
            ]
        );
    }

    public function handleTreasuryReversed(TreasuryReversed $event): void
    {
        $txn = $event->original;
        if (! $txn->created_by) return;

        $this->notifications->dispatch(
            NotificationEventType::TreasuryReversed,
            $event->shop,
            $this->recipients->byUsers([$txn->createdBy]),
            ['title' => 'Treasury transaction reversed', 'body' => "{$txn->transaction_number} was reversed by {$event->actor->name}", 'reference' => $event->reversal, 'branch_id' => $txn->branch_id]
        );
    }

    public function handleFpSettlementRecorded(FpSettlementRecorded $event): void
    {
        $settlement = $event->settlement;

        $this->notifications->dispatch(
            NotificationEventType::FpSettlementRecorded,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::FinancePartnersRecordPayment->value),
            ['title' => 'Finance partner settlement recorded', 'body' => "{$settlement->partner?->name} — ৳" . number_format((float) $settlement->net_amount, 2), 'reference' => $settlement]
        );
    }

    public function handleUsedPhoneAcquired(UsedPhoneAcquired $event): void
    {
        $acq = $event->acquisition;

        $this->notifications->dispatch(
            NotificationEventType::UsedPhoneAcquired,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::ProductsView->value, $acq->branch_id),
            ['title' => 'Used phone acquired', 'body' => "{$acq->model_description} — ৳" . number_format((float) $acq->purchase_price, 2), 'reference' => $acq, 'branch_id' => $acq->branch_id]
        );
    }

    public function handleEmployeeInvited(EmployeeInvited $event): void
    {
        $this->notifications->dispatch(
            NotificationEventType::EmployeeInvited,
            $event->shop,
            $this->recipients->byPermission($event->shop, PermissionEnum::EmployeesManage->value),
            ['title' => 'Employee invited', 'body' => "{$event->employee->name} was invited to join the team.", 'reference' => $event->employee]
        );
    }

    public function handleImpersonationStarted(ImpersonationStarted $event): void
    {
        $this->notifications->dispatch(
            NotificationEventType::ImpersonationStarted,
            $event->shop,
            $this->recipients->owner($event->shop),
            [
                'title' => 'Support session started on your account',
                'body' => "A Super Admin ({$event->superAdmin->name}) started a support session on {$event->target->name}'s account.",
                'placeholders' => ['date' => now()->format('d M Y H:i')],
            ]
        );
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ExpensePendingApproval::class, [self::class, 'handleExpensePendingApproval']);
        $events->listen(ExpenseApproved::class, [self::class, 'handleExpenseApproved']);
        $events->listen(ExpenseRejected::class, [self::class, 'handleExpenseRejected']);
        $events->listen(SaleConfirmed::class, [self::class, 'handleSaleConfirmed']);
        $events->listen(SaleVoided::class, [self::class, 'handleSaleVoided']);
        $events->listen(TreasuryPendingApproval::class, [self::class, 'handleTreasuryPendingApproval']);
        $events->listen(TreasuryApproved::class, [self::class, 'handleTreasuryApproved']);
        $events->listen(TreasuryRejected::class, [self::class, 'handleTreasuryRejected']);

        $events->listen(ReturnProcessed::class, [self::class, 'handleReturnProcessed']);
        $events->listen(PurchaseReceived::class, [self::class, 'handlePurchaseReceived']);
        $events->listen(SupplierBalanceHigh::class, [self::class, 'handleSupplierBalanceHigh']);
        $events->listen(PurchaseReturnProcessed::class, [self::class, 'handlePurchaseReturnProcessed']);
        $events->listen(StockLow::class, [self::class, 'handleStockLow']);
        $events->listen(CustomerCreditLimitReached::class, [self::class, 'handleCustomerCreditLimitReached']);
        $events->listen(StockTransferInitiated::class, [self::class, 'handleStockTransferInitiated']);
        $events->listen(StockTransferReceived::class, [self::class, 'handleStockTransferReceived']);
        $events->listen(ExpenseVoided::class, [self::class, 'handleExpenseVoided']);
        $events->listen(PayrollDraftReady::class, [self::class, 'handlePayrollDraftReady']);
        $events->listen(PayrollPaid::class, [self::class, 'handlePayrollPaid']);
        $events->listen(SalaryOverdrawn::class, [self::class, 'handleSalaryOverdrawn']);
        $events->listen(TreasuryReversed::class, [self::class, 'handleTreasuryReversed']);
        $events->listen(FpSettlementRecorded::class, [self::class, 'handleFpSettlementRecorded']);
        $events->listen(UsedPhoneAcquired::class, [self::class, 'handleUsedPhoneAcquired']);
        $events->listen(EmployeeInvited::class, [self::class, 'handleEmployeeInvited']);
        $events->listen(ImpersonationStarted::class, [self::class, 'handleImpersonationStarted']);
    }
}