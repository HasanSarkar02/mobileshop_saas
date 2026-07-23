<?php

namespace App\Enums;

/**
 * One case per distinct thing that can happen in the ERP a user might want to
 * know about. Each case carries its own category/priority/default channels/
 * action-required policy — mirrors how TreasuryTransactionType carries its
 * own category() and alwaysRequiresApproval().
 *
 * Phase 2 adds: SupplierPaymentDue, PayrollReminderDue, LoanRepaymentDue —
 * all three are scheduled/reminder-driven (see ReminderCheckers), not fired
 * from an Action via DB::afterCommit.
 */
enum NotificationEventType: string
{
    // Sales
    case SaleConfirmed = 'sale_confirmed';
    case SaleVoided = 'sale_voided';
    case ReturnProcessed = 'return_processed';
    case SaleReceipt = 'sale_receipt';

    // Purchases / Suppliers
    case PurchaseReceived = 'purchase_received';
    case PurchaseReturnProcessed = 'purchase_return_processed';
    case SupplierBalanceHigh = 'supplier_balance_high';
    case SupplierPaymentDue = 'supplier_payment_due';

    // Customers
    case CustomerCreditLimitReached = 'customer_credit_limit_reached';
    case CustomerDueReminder = 'customer_due_reminder';
    case CustomerPaymentReminderSms = 'customer_payment_reminder_sms';
    case CustomerPaymentReceived = 'customer_payment_received';

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
    case PayrollReminderDue = 'payroll_reminder_due';

    // Treasury
    case TreasuryPendingApproval = 'treasury_pending_approval';
    case TreasuryApproved = 'treasury_approved';
    case TreasuryRejected = 'treasury_rejected';
    case TreasuryReversed = 'treasury_reversed';
    case LoanRepaymentDue = 'loan_repayment_due';

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
            self::SaleReceipt => 'Sale Receipt',
            self::ReturnProcessed => 'Return processed',
            self::PurchaseReceived => 'Purchase received',
            self::PurchaseReturnProcessed => 'Purchase return processed',
            self::SupplierBalanceHigh => 'Supplier balance high',
            self::SupplierPaymentDue => 'Supplier payment due',
            self::CustomerCreditLimitReached => 'Customer credit limit reached',
            self::CustomerDueReminder => 'Customer due reminder',
            self::CustomerPaymentReminderSms => 'Customer payment reminder (SMS)',
            self::CustomerPaymentReceived => 'Customer payment received',
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
            self::PayrollReminderDue => 'Payroll reminder',
            self::TreasuryPendingApproval => 'Treasury transaction pending approval',
            self::TreasuryApproved => 'Treasury transaction approved',
            self::TreasuryRejected => 'Treasury transaction rejected',
            self::TreasuryReversed => 'Treasury transaction reversed',
            self::LoanRepaymentDue => 'Loan repayment reminder',
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
            self::SaleConfirmed,self::SaleReceipt, self::SaleVoided => NotificationCategory::Sales,
            self::ReturnProcessed => NotificationCategory::Returns,
            self::PurchaseReceived, self::PurchaseReturnProcessed => NotificationCategory::Purchases,
            self::SupplierBalanceHigh, self::SupplierPaymentDue => NotificationCategory::Suppliers,
            self::CustomerCreditLimitReached, self::CustomerDueReminder, => NotificationCategory::Customers,
            self::CustomerPaymentReminderSms => NotificationCategory::Customers,self::CustomerPaymentReceived => NotificationCategory::Customers,
            self::FpReceivableOverdue, self::FpSettlementRecorded => NotificationCategory::FinancePartners,
            self::StockLow, self::StockTransferInitiated, self::StockTransferReceived => NotificationCategory::Inventory,
            self::WarrantyExpiringSoon => NotificationCategory::Warranty,
            self::ServiceTicketReady, self::ServiceTicketOverdue => NotificationCategory::Service,
            self::ExpensePendingApproval, self::ExpenseApproved, self::ExpenseRejected, self::ExpenseVoided => NotificationCategory::Expenses,
            self::PayrollDraftReady, self::PayrollPaid, self::SalaryOverdrawn, self::PayrollReminderDue => NotificationCategory::Payroll,
            self::TreasuryPendingApproval, self::TreasuryApproved, self::TreasuryRejected, self::TreasuryReversed, self::LoanRepaymentDue => NotificationCategory::Treasury,
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
            self::PeriodLockApproaching,
            self::SupplierPaymentDue,
            self::LoanRepaymentDue => NotificationPriority::High,
            self::SaleVoided,
            self::TreasuryRejected,
            self::ExpenseRejected,
            self::ExpenseVoided,
            self::StockLow,
            self::ServiceTicketOverdue,
            self::SupplierBalanceHigh,
            self::PayrollReminderDue => NotificationPriority::Normal,
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
            self::ServiceTicketReady => [
                NotificationChannel::InApp, NotificationChannel::Sms,
            ],
            self::CustomerPaymentReminderSms => [
                NotificationChannel::Sms,
            ],
            self::SaleReceipt => [
                NotificationChannel::Sms,
            ],
            self::ImpersonationStarted, self::SalaryOverdrawn => [
                NotificationChannel::InApp, NotificationChannel::Popup, NotificationChannel::Email,
            ],
            self::SupplierPaymentDue, self::PayrollReminderDue, self::LoanRepaymentDue, self::FpReceivableOverdue => [
                NotificationChannel::InApp, NotificationChannel::Email,
            ],
            self::CustomerPaymentReceived => [
                NotificationChannel::Sms,
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
            self::SupplierPaymentDue,
            self::LoanRepaymentDue,
            self::PayrollReminderDue,
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
            self::SupplierPaymentDue => 'View supplier',
            self::PayrollReminderDue => 'Go to Payroll',
            self::LoanRepaymentDue => 'View treasury',
            default => null,
        };
    }
}