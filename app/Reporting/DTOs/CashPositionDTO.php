<?php

namespace App\Reporting\DTOs;

final class CashPositionDTO
{
    public function __construct(
        /** Total across ALL payment accounts */
        public readonly float $totalBalance,
        /** Keyed by provider: ['cash' => 5000, 'bkash' => 12000, ...] */
        public readonly array $byProvider,
        /** Keyed by account name for granular breakdown */
        public readonly array $byAccount,
        /** Customer dues outstanding */
        public readonly float $customerReceivables,
        /** Finance partner dues outstanding */
        public readonly float $fpReceivables,
        /** Supplier payables outstanding */
        public readonly float $supplierPayables,
    ) {}

    public function netPosition(): float
    {
        return $this->totalBalance
            + $this->customerReceivables
            + $this->fpReceivables
            - $this->supplierPayables;
    }
}