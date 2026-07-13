<?php

namespace App\Enums;

enum NotificationEventType: string
{
    // Sales
    case SaleConfirmed = 'sale_confirmed';
    case SaleVoided = 'sale_voided';
    case ReturnProcessed = 'return_processed';

    // Purchases / Suppliers
    case PurchaseReceived = 'purchase_received';
    case PurchaseReturnProcessed = 'purchase_return_processed';
    case SupplierBalanceHigh = 'supplier_balance_high';

    // Customers
    case CustomerCreditLimitReached = 'customer_credit_limit_reached';
    case CustomerDueReminder = 'customer_due_reminder';

    // Finance Partners
    case FpReceivableOverdue = 'fp_receivable_overdue';
    case FpSettlementRecorded = 'fp_settlement_recorded';

    // Inventory
    case StockLow = 'stock_low';
    case StockTransferInitiated = 'stock_transfer_initiated';
    case StockTransferReceived = 'stock_transfer_received';

    // Warranty / Service
    case WarrantyExpiringSoon = 'warranty_expiring_soon';
    case ServiceTicketReady = 'service_ticket_ready';
    case ServiceTicketOverdue = 'service_ticket_overdue';

    // Expenses
    case ExpensePendingApproval = 'expense_pending_approval';
    case ExpenseApproved = 'expense_approved';
    case ExpenseRejected = 'expense_rejected';
    case ExpenseVoided = 'expense_voided';

    // Payroll
    case PayrollDraftReady = 'payroll_draft_ready';
    case PayrollPaid = 'payroll_paid';
    case SalaryOverdrawn = 'salary_overdrawn';

    // Treasury
    case TreasuryPendingApproval = 'treasury_pending_approval';
    case TreasuryApproved = 'treasury_approved';
    case TreasuryRejected = 'treasury_rejected';
    case TreasuryReversed = 'treasury_reversed';

    // Accounting
    case PeriodLockApproaching = 'period_lock_approaching';

    // Employees
    case EmployeeInvited = 'employee_invited';
    case EmployeeDeactivated = 'employee_deactivated';

    // Used Phones
    case UsedPhoneAcquired = 'used_phone_acquired';

    // System / Security
    case SystemAnnouncement = 'system_announcement';
    case ImpersonationStarted = 'impersonation_started';

    public function label(): string
    {
        return match ($this) {
            self::SaleConfirmed => 'Sale confirmed',
            self::SaleVoided => 'Sale voided',
            self::ReturnProcessed => 'Return processed',
            self::PurchaseReceived => 'Purchase received',
            self::PurchaseReturnProcessed => 'Purchase return processed',
            self::SupplierBalanceHigh => 'Supplier balance high',
            self::CustomerCreditLimitReached => 'Customer credit limit reached',
            self::CustomerDueReminder => 'Customer due reminder',
            self::FpReceivableOverdue => 'Finance partner receivable overdue',
            self::FpSettlementRecorded => 'Finance partner settlement recorded',
            self::StockLow => 'Stock running low',
            self::StockTransferInitiated => 'Stock transfer initiated',
            self::StockTransferReceived => 'Stock transfer received',
            self::WarrantyExpiringSoon => 'Warranty expiring soon',
            self::ServiceTicketReady => 'Service ticket ready for pickup',
            self::ServiceTicketOverdue => 'Service ticket overdue',
            self::ExpensePendingApproval => 'Expense pending approval',
            self::ExpenseApproved => 'Expense approved',
            self::ExpenseRejected => 'Expense rejected',
            self::ExpenseVoided => 'Expense voided',
            self::PayrollDraftReady => 'Payroll draft ready',
            self::PayrollPaid => 'Payroll paid',
            self::SalaryOverdrawn => 'Employee salary overdrawn',
            self::TreasuryPendingApproval => 'Treasury transaction pending approval',
            self::TreasuryApproved => 'Treasury transaction approved',
            self::TreasuryRejected => 'Treasury transaction rejected',
            self::TreasuryReversed => 'Treasury transaction reversed',
            self::PeriodLockApproaching => 'Accounting period lock approaching',
            self::EmployeeInvited => 'Employee invited',
            self::EmployeeDeactivated => 'Employee deactivated',
            self::UsedPhoneAcquired => 'Used phone acquired',
            self::SystemAnnouncement => 'System announcement',
            self::ImpersonationStarted => 'Impersonation session started',
        };
    }

    public function category(): NotificationCategory
    {
        return match ($this) {
            self::SaleConfirmed, self::SaleVoided => NotificationCategory::Sales,
            self::ReturnProcessed => NotificationCategory::Returns,
            self::PurchaseReceived, self::PurchaseReturnProcessed => NotificationCategory::Purchases,
            self::SupplierBalanceHigh => NotificationCategory::Suppliers,
            self::CustomerCreditLimitReached, self::CustomerDueReminder => NotificationCategory::Customers,
            self::FpReceivableOverdue, self::FpSettlementRecorded => NotificationCategory::FinancePartners,
            self::StockLow, self::StockTransferInitiated, self::StockTransferReceived => NotificationCategory::Inventory,
            self::WarrantyExpiringSoon => NotificationCategory::Warranty,
            self::ServiceTicketReady, self::ServiceTicketOverdue => NotificationCategory::Service,
            self::ExpensePendingApproval, self::ExpenseApproved, self::ExpenseRejected, self::ExpenseVoided => NotificationCategory::Expenses,
            self::PayrollDraftReady, self::PayrollPaid, self::SalaryOverdrawn => NotificationCategory::Payroll,
            self::TreasuryPendingApproval, self::TreasuryApproved, self::TreasuryRejected, self::TreasuryReversed => NotificationCategory::Treasury,
            self::PeriodLockApproaching => NotificationCategory::Accounting,
            self::EmployeeInvited, self::EmployeeDeactivated => NotificationCategory::Employees,
            self::UsedPhoneAcquired => NotificationCategory::UsedPhones,
            self::SystemAnnouncement => NotificationCategory::System,
            self::ImpersonationStarted => NotificationCategory::Security,
        };
    }

    public function defaultPriority(): NotificationPriority
    {
        return match ($this) {
            self::ImpersonationStarted, self::SalaryOverdrawn => NotificationPriority::Urgent,
            self::ExpensePendingApproval,
            self::TreasuryPendingApproval,
            self::CustomerCreditLimitReached,
            self::FpReceivableOverdue,
            self::PeriodLockApproaching => NotificationPriority::High,
            self::SaleVoided,
            self::TreasuryRejected,
            self::ExpenseRejected,
            self::ExpenseVoided,
            self::StockLow,
            self::ServiceTicketOverdue,
            self::SupplierBalanceHigh => NotificationPriority::Normal,
            default => NotificationPriority::Normal,
        };
    }

    /** @return array<int, NotificationChannel> */
    public function defaultChannels(): array
    {
        return match ($this) {
            self::ExpensePendingApproval, self::TreasuryPendingApproval => [
                NotificationChannel::InApp, NotificationChannel::Popup, NotificationChannel::Email,
            ],
            self::CustomerDueReminder, self::ServiceTicketReady => [
                NotificationChannel::InApp, NotificationChannel::Sms,
            ],
            self::ImpersonationStarted, self::SalaryOverdrawn => [
                NotificationChannel::InApp, NotificationChannel::Popup, NotificationChannel::Email,
            ],
            default => [NotificationChannel::InApp],
        };
    }

    public function actionRequired(): bool
    {
        return in_array($this, [
            self::ExpensePendingApproval,
            self::TreasuryPendingApproval,
            self::CustomerCreditLimitReached,
            self::FpReceivableOverdue,
            self::ServiceTicketOverdue,
            self::SalaryOverdrawn,
        ], true);
    }

    public function defaultActionLabel(): ?string
    {
        return match ($this) {
            self::ExpensePendingApproval, self::TreasuryPendingApproval => 'Review',
            self::CustomerCreditLimitReached => 'View customer',
            self::FpReceivableOverdue => 'View receivable',
            self::ServiceTicketOverdue => 'View ticket',
            self::SalaryOverdrawn => 'View employee',
            default => null,
        };
    }
}