<?php

namespace App\Enums;

enum TreasuryTransactionCategory: string
{
    case InternalTransfer = 'internal_transfer';
    case Equity           = 'equity';
    case Adjustment       = 'adjustment';
    case BankFinance      = 'bank_finance';
    case PettyCash        = 'petty_cash';

    public function label(): string
    {
        return match ($this) {
            self::InternalTransfer => 'Internal Transfer',
            self::Equity           => 'Owner & Partners',
            self::Adjustment       => 'Cash Adjustments',
            self::BankFinance      => 'Bank & Loans',
            self::PettyCash        => 'Petty Cash',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::InternalTransfer => 'badge-blue',
            self::Equity           => 'badge-indigo',
            self::Adjustment       => 'badge-yellow',
            self::BankFinance      => 'badge-gray',
            self::PettyCash        => 'badge-green',
        };
    }

    /** Types grouped by category — for form dropdown */
    public function types(): array
    {
        return array_filter(
            TreasuryTransactionType::cases(),
            fn ($t) => $t->category() === $this
        );
    }
}