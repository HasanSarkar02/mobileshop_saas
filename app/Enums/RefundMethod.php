<?php

namespace App\Enums;

enum RefundMethod: string
{
    case OriginalPayment = 'original_payment'; // refund via original method
    case StoreCredit     = 'store_credit';     // credit to customer account
    case Exchange        = 'exchange';          // apply toward new sale

    public function label(): string
    {
        return match ($this) {
            self::OriginalPayment => 'Refund to original payment method',
            self::StoreCredit     => 'Store credit (add to customer account)',
            self::Exchange        => 'Exchange (apply toward new sale)',
        };
    }
}