<?php

namespace App\Reporting\DTOs;

final class ProfitLossDTO
{
    public function __construct(
        // Revenue
        public readonly float $salesRevenue,
        public readonly float $serviceRevenue,
        public readonly float $salesReturns,         // contra
        public readonly float $salesDiscounts,       // contra
        public readonly float $netRevenue,

        // Cost of Sales
        public readonly float $costOfGoodsSold,
        public readonly float $costOfServiceParts,
        public readonly float $grossProfit,
        public readonly float $grossMarginPct,

        // Operating Expenses (from GL 6xxx accounts)
        public readonly array $expensesByAccount,    // ['Rent' => 15000, 'Salary' => 80000, ...]
        public readonly float $totalOperatingExpenses,

        // Operating Profit
        public readonly float $operatingProfit,
        public readonly float $operatingMarginPct,

        // Other
        public readonly float $otherIncome,
        public readonly float $otherExpenses,
        public readonly float $netProfit,
        public readonly float $netMarginPct,

        // Meta
        public readonly string $periodLabel,
        public readonly ?self  $previousPeriod = null,
    ) {}

    public function profitVsPrevious(): ?float
    {
        if (! $this->previousPeriod) return null;
        return $this->netProfit - $this->previousPeriod->netProfit;
    }
}