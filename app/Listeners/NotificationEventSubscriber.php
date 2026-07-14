<?php

namespace App\Listeners;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Events\ExpenseApproved;
use App\Events\ExpensePendingApproval;
use App\Events\ExpenseRejected;
use App\Events\SaleConfirmed;
use App\Events\SaleVoided;
use App\Events\TreasuryApproved;
use App\Events\TreasuryPendingApproval;
use App\Events\TreasuryRejected;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\RecipientResolver;
use Illuminate\Events\Dispatcher;

class NotificationEventSubscriber
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly RecipientResolver $recipients,
    ) {}

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
            ]
        );
    }

    public function handleExpenseApproved(ExpenseApproved $event): void
    {
        $expense = $event->expense;

        if (! $expense->created_by || $expense->created_by === $event->actor->id) {
            return;
        }

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

        if (! $expense->created_by) {
            return;
        }

        $this->notifications->dispatch(
            NotificationEventType::ExpenseRejected,
            $event->shop,
            $this->recipients->byUsers([$expense->createdBy]),
            [
                'title' => 'Your expense was rejected',
                'body' => $event->reason,
                'reference' => $expense,
                'branch_id' => $expense->branch_id,
            ]
        );
    }

    public function handleSaleConfirmed(SaleConfirmed $event): void
    {
        // Deliberately low-noise: sales are extremely high-frequency and the
        // Owner already sees them on the dashboard. Grouped into one running
        // "today's sales" notification per branch instead of one row per sale.
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
            [
                'title' => 'Sale voided',
                'body' => "{$sale->sale_number} voided by {$event->actor->name}: {$event->reason}",
                'reference' => $sale,
                'branch_id' => $sale->branch_id,
            ]
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
            ]
        );
    }

    public function handleTreasuryApproved(TreasuryApproved $event): void
    {
        $txn = $event->transaction;

        if (! $txn->created_by || $txn->created_by === $event->actor->id) {
            return;
        }

        $this->notifications->dispatch(
            NotificationEventType::TreasuryApproved,
            $event->shop,
            $this->recipients->byUsers([$txn->createdBy]),
            [
                'title' => 'Treasury transaction approved',
                'body' => "{$txn->transaction_number} approved by {$event->actor->name}",
                'reference' => $txn,
                'branch_id' => $txn->branch_id,
            ]
        );
    }

    public function handleTreasuryRejected(TreasuryRejected $event): void
    {
        $txn = $event->transaction;

        if (! $txn->created_by) {
            return;
        }

        $this->notifications->dispatch(
            NotificationEventType::TreasuryRejected,
            $event->shop,
            $this->recipients->byUsers([$txn->createdBy]),
            [
                'title' => 'Treasury transaction rejected',
                'body' => $event->reason,
                'reference' => $txn,
                'branch_id' => $txn->branch_id,
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
    }
}