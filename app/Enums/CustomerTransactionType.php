<?php

namespace App\Enums;

enum CustomerTransactionType: string
{
    case OpeningBalance   = 'opening_balance';
    case SaleCredit       = 'sale_credit';       // baki sale — adds to what customer owes
    case PaymentReceived  = 'payment_received';  // customer pays — reduces what they owe
    case ReturnCredit     = 'return_credit';     // product return reduces balance
    case WriteOff         = 'write_off';         // bad debt
    case Adjustment       = 'adjustment';        // manual correction

    public function isDebit(): bool
    {
        // Debit = customer owes MORE (increases balance)
        return in_array($this, [self::OpeningBalance, self::SaleCredit, self::Adjustment]);
    }

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance  => 'Opening Balance',
            self::SaleCredit      => 'Credit Sale',
            self::PaymentReceived => 'Payment Received',
            self::ReturnCredit    => 'Return Credit',
            self::WriteOff        => 'Write-off',
            self::Adjustment      => 'Adjustment',
        };
    }
}