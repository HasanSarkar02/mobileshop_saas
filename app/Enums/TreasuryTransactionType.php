<?php

namespace App\Enums;

enum TreasuryTransactionType: string
{
    // ── Internal Transfers ────────────────────────────────────────────────────
    case AccountTransfer  = 'account_transfer';
    case BranchTransfer   = 'branch_transfer';
    case BankDeposit      = 'bank_deposit';
    case BankWithdrawal   = 'bank_withdrawal';
    case WalletCashout    = 'wallet_cashout';

    // ── Equity & Capital ──────────────────────────────────────────────────────
    case OwnerCapital       = 'owner_capital';
    case OwnerDrawings      = 'owner_drawings';
    case PartnerInvestment  = 'partner_investment';
    case PartnerWithdrawal  = 'partner_withdrawal';

    // ── Adjustments ───────────────────────────────────────────────────────────
    case CashOver       = 'cash_over';
    case CashShort      = 'cash_short';
    case OpeningBalance = 'opening_balance';

    // ── Bank & Finance ────────────────────────────────────────────────────────
    case BankCharge      = 'bank_charge';
    case InterestIncome  = 'interest_income';
    case InterestExpense = 'interest_expense';
    case LoanReceipt     = 'loan_receipt';
    case LoanRepayment   = 'loan_repayment';

    // ── Petty Cash ────────────────────────────────────────────────────────────
    case PettyCashIssue  = 'petty_cash_issue';
    case PettyCashReturn = 'petty_cash_return';

    public function label(): string
    {
        return match ($this) {
            self::AccountTransfer  => 'Account to Account Transfer',
            self::BranchTransfer   => 'Branch to Branch Transfer',
            self::BankDeposit      => 'Bank Deposit (Cash → Bank)',
            self::BankWithdrawal   => 'Bank Withdrawal (Bank → Cash)',
            self::WalletCashout    => 'Wallet Cashout (bKash/Nagad → Cash)',
            self::OwnerCapital     => 'Owner Capital Injection',
            self::OwnerDrawings    => 'Owner Drawings / Withdrawal',
            self::PartnerInvestment=> 'Partner Investment',
            self::PartnerWithdrawal=> 'Partner Withdrawal',
            self::CashOver         => 'Cash Over (Physical Surplus)',
            self::CashShort        => 'Cash Short (Physical Deficit)',
            self::OpeningBalance   => 'Opening Balance Setup',
            self::BankCharge       => 'Bank Charge / Fee',
            self::InterestIncome   => 'Interest Income',
            self::InterestExpense  => 'Interest Expense',
            self::LoanReceipt      => 'Loan Received',
            self::LoanRepayment    => 'Loan Repayment',
            self::PettyCashIssue   => 'Petty Cash Issue',
            self::PettyCashReturn  => 'Petty Cash Return',
        };
    }

    public function category(): TreasuryTransactionCategory
    {
        return match ($this) {
            self::AccountTransfer,
            self::BranchTransfer,
            self::BankDeposit,
            self::BankWithdrawal,
            self::WalletCashout    => TreasuryTransactionCategory::InternalTransfer,

            self::OwnerCapital,
            self::OwnerDrawings,
            self::PartnerInvestment,
            self::PartnerWithdrawal=> TreasuryTransactionCategory::Equity,

            self::CashOver,
            self::CashShort,
            self::OpeningBalance   => TreasuryTransactionCategory::Adjustment,

            self::BankCharge,
            self::InterestIncome,
            self::InterestExpense,
            self::LoanReceipt,
            self::LoanRepayment    => TreasuryTransactionCategory::BankFinance,

            self::PettyCashIssue,
            self::PettyCashReturn  => TreasuryTransactionCategory::PettyCash,
        };
    }

    /** Types that ALWAYS require approval regardless of amount or actor */
    public function alwaysRequiresApproval(): bool
    {
        return in_array($this, [
            self::OwnerCapital,
            self::OwnerDrawings,
            self::PartnerInvestment,
            self::PartnerWithdrawal,
            self::OpeningBalance,
            self::LoanReceipt,
            self::LoanRepayment,
        ]);
    }

    /**
     * Which fields this type needs.
     * Used by the form to show/hide fields correctly.
     */
    public function needsFromAccount(): bool
    {
        return in_array($this, [
            self::AccountTransfer, self::BranchTransfer,
            self::BankDeposit, self::WalletCashout,
            self::OwnerDrawings, self::PartnerWithdrawal,
            self::CashOver, self::CashShort,
            self::BankCharge, self::InterestExpense, self::LoanRepayment,
            self::PettyCashIssue,
        ]);
    }

    public function needsToAccount(): bool
    {
        return in_array($this, [
            self::AccountTransfer, self::BranchTransfer,
            self::BankWithdrawal, self::WalletCashout,
            self::BankDeposit,
            self::OwnerCapital, self::PartnerInvestment,
            self::OpeningBalance,
            self::InterestIncome, self::LoanReceipt,
            self::PettyCashReturn,
        ]);
    }

    public function needsFee(): bool
    {
        return in_array($this, [
            self::WalletCashout,
            self::AccountTransfer,
            self::BankDeposit,
            self::LoanRepayment, // fee_amount = interest
        ]);
    }

    public function needsThirdParty(): bool
    {
        return in_array($this, [
            self::OwnerCapital,
            self::PartnerInvestment,
            self::PartnerWithdrawal,
            self::LoanReceipt,
            self::LoanRepayment,
        ]);
    }

    public function feeLabel(): string
    {
        return match ($this) {
            self::LoanRepayment => 'Interest Amount (৳)',
            default             => 'Fee / Charge (৳)',
        };
    }

    /** Icon for UI */
    public function icon(): string
    {
        return match ($this->category()) {
            TreasuryTransactionCategory::InternalTransfer => '↔',
            TreasuryTransactionCategory::Equity           => '💰',
            TreasuryTransactionCategory::Adjustment       => '⚖',
            TreasuryTransactionCategory::BankFinance      => '🏦',
            TreasuryTransactionCategory::PettyCash        => '🪙',
        };
    }

    /** GL debit account code (for types where from_account is not used) */
    /**
     * GL account code debited for types where from_account is a non-payment GL.
     * These must match TreasuryJournalBuilder exactly.
     * NULL = debit goes to a PaymentAccount GL (handled dynamically in builder).
     */
    public function debitGlCode(): ?string
    {
        return match ($this) {
            self::OwnerDrawings     => '3010', // Owner's Drawings (already in COA)
            self::PartnerWithdrawal => '3030', // Partner Capital (new)
            self::CashShort         => '6088', // Cash Short / Misc Loss (new)
            self::BankCharge        => '6085', // Bank Charges & Fees (new)
            self::InterestExpense   => '6087', // Interest Expense (new)
            default                 => null,
        };
    }

    /**
     * GL account code credited for types where to_account is a non-payment GL.
     * These must match TreasuryJournalBuilder exactly.
     * NULL = credit goes to a PaymentAccount GL (handled dynamically in builder).
     */
    public function creditGlCode(): ?string
    {
        return match ($this) {
            self::OwnerCapital      => '3000', // Owner's Equity (already in COA)
            self::PartnerInvestment => '3030', // Partner Capital (new)
            self::CashOver          => '4050', // Miscellaneous Income (new)
            self::OpeningBalance    => '3020', // Opening Balance Equity (already in COA)
            self::InterestIncome    => '4040', // Interest Income (new)
            self::LoanReceipt       => '2100', // Short-term Loans Payable (new)
            default                 => null,
        };
    }
}