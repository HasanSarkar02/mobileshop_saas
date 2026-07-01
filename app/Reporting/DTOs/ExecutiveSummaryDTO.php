<?php

namespace App\Reporting\DTOs;

final class ExecutiveSummaryDTO
{
    public function __construct(
        public readonly SalesSummaryDTO    $sales,
        public readonly CashPositionDTO    $cash,
        public readonly InventorySummaryDTO $inventory,
        // Pending items requiring owner action
        public readonly int   $pendingExpenseApprovals,
        public readonly int   $pendingServiceTickets,
        public readonly int   $openServiceTickets,
        public readonly float $serviceAmountDue,
        public readonly float $fpPendingSettlements,
        public readonly float $payrollAmountDue,
        // Top performers
        public readonly array $topProducts,
        public readonly array $topCustomers,
        public readonly array $topEmployees,
        // Branch breakdown
        public readonly array $branchSales,
        // Recent activity
        public readonly array $recentSales,
    ) {}
}