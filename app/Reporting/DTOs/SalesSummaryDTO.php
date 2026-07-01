<?php

namespace App\Reporting\DTOs;

final class SalesSummaryDTO
{
    public function __construct(
        public readonly int   $orderCount,
        public readonly float $grossRevenue,
        public readonly float $totalDiscount,
        public readonly float $vatAmount,
        public readonly float $netRevenue,
        public readonly float $totalCost,
        public readonly float $grossProfit,
        public readonly float $profitMarginPct,
        public readonly float $avgOrderValue,
        public readonly float $totalReturns,
        public readonly int   $returnCount,
        // comparison vs previous period
        public readonly ?float $prevNetRevenue  = null,
        public readonly ?float $prevGrossProfit = null,
        public readonly ?int   $prevOrderCount  = null,
    ) {}

    public function revenueChangePercent(): ?float
    {
        if (! $this->prevNetRevenue || $this->prevNetRevenue == 0) return null;
        return round(($this->netRevenue - $this->prevNetRevenue) / $this->prevNetRevenue * 100, 1);
    }

    public function profitChangePercent(): ?float
    {
        if (! $this->prevGrossProfit || $this->prevGrossProfit == 0) return null;
        return round(($this->grossProfit - $this->prevGrossProfit) / $this->prevGrossProfit * 100, 1);
    }

    public function orderChangePercent(): ?float
    {
        if (! $this->prevOrderCount || $this->prevOrderCount == 0) return null;
        return round(($this->orderCount - $this->prevOrderCount) / $this->prevOrderCount * 100, 1);
    }
}