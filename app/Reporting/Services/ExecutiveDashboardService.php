<?php

namespace App\Reporting\Services;

use App\Reporting\DTOs\CashPositionDTO;
use App\Reporting\DTOs\ExecutiveSummaryDTO;
use App\Reporting\DTOs\InventorySummaryDTO;
use App\Reporting\DTOs\ReportFilter;
use App\Reporting\DTOs\SalesSummaryDTO;
use App\Reporting\Enums\ReportPeriod;
use App\Reporting\Repositories\CustomerRepository;
use App\Reporting\Repositories\ExpenseRepository;
use App\Reporting\Repositories\FinancialRepository;
use App\Reporting\Repositories\InventoryRepository;
use App\Reporting\Repositories\PayrollRepository;
use App\Reporting\Repositories\SalesRepository;
use App\Reporting\Repositories\ServiceRepository;

class ExecutiveDashboardService
{
    public function __construct(
        private readonly SalesRepository     $sales,
        private readonly InventoryRepository $inventory,
        private readonly FinancialRepository $financial,
        private readonly CustomerRepository  $customer,
        private readonly ServiceRepository   $service,
        private readonly ExpenseRepository   $expense,
        private readonly PayrollRepository   $payroll,
    ) {}

    /**
     * The ONE method the Dashboard calls. Returns a fully-typed DTO.
     * No aggregation logic in the Livewire component.
     */
    public function summary(int $shopId, ReportPeriod $period, ?int $branchId = null): ExecutiveSummaryDTO
    {
        $filter     = new ReportFilter(
            shopId:    $shopId,
            dateRange: $period->toDateRange(),
            branchId:  $branchId,
            period:    $period,
        );
        $prevFilter = $filter->forPreviousPeriod();

        // ── Sales ──────────────────────────────────────────────────────────────
        $current  = $this->sales->aggregate($filter);
        $previous = $this->sales->aggregate($prevFilter);

        $netRevenue  = (float) $current->net_revenue;
        $grossProfit = (float) $current->gross_profit;
        $margin      = $netRevenue > 0 ? round($grossProfit / $netRevenue * 100, 1) : 0;

        $salesDTO = new SalesSummaryDTO(
            orderCount:      (int)   $current->order_count,
            grossRevenue:    (float) $current->gross_revenue,
            totalDiscount:   (float) $current->total_discount,
            vatAmount:       (float) $current->vat_amount,
            netRevenue:      $netRevenue,
            totalCost:       (float) $current->total_cost,
            grossProfit:     $grossProfit,
            profitMarginPct: $margin,
            avgOrderValue:   (float) $current->avg_order_value,
            totalReturns:    (float) $current->return_amount,
            returnCount:     (int)   $current->return_count,
            prevNetRevenue:  (float) $previous->net_revenue,
            prevGrossProfit: (float) $previous->gross_profit,
            prevOrderCount:  (int)   $previous->order_count,
        );

        // ── Cash position ──────────────────────────────────────────────────────
        $accounts   = $this->financial->cashPositionByAccount($shopId);
        $byProvider = $accounts->groupBy('provider')
            ->map(fn ($g) => (float) $g->sum('balance'))
            ->toArray();
        $byAccount  = $accounts->mapWithKeys(fn ($a) => [$a->name => (float) $a->balance])->toArray();

        $suppPayable = $this->financial->supplierPayables($shopId);
        $custStats   = $this->customer->stats($shopId);
        $fpPending   = $this->financial->accountBalances($shopId, $filter)
            ->firstWhere('code', '1110');

        $cashDTO = new CashPositionDTO(
            totalBalance:          (float) $accounts->sum('balance'),
            byProvider:            $byProvider,
            byAccount:             $byAccount,
            customerReceivables:   (float) ($custStats->total_outstanding ?? 0),
            fpReceivables:         (float) ($fpPending->net_balance ?? 0),
            supplierPayables:      (float) ($suppPayable->total ?? 0),
        );

        // ── Inventory ──────────────────────────────────────────────────────────
        $invValue  = $this->inventory->totalValue($shopId, $branchId);
        $lowStock  = $this->inventory->lowStockItems($shopId, $branchId);

        $inventoryDTO = new InventorySummaryDTO(
            totalSkus:            0, // count variants with stock > 0
            totalSerializedUnits: (int) $invValue->serialized_units,
            inStockUnits:         (int) $invValue->serialized_units,
            lowStockSkus:         $lowStock->count(),
            outOfStockSkus:       0,
            totalInventoryValue:  (float) $invValue->total_value,
            serializedValue:      (float) $invValue->serialized_value,
            nonSerializedValue:   (float) $invValue->non_serialized_value,
            lowStockItems:        $lowStock->take(5)->toArray(),
        );

        // ── Service ───────────────────────────────────────────────────────────
        $svcStats  = $this->service->stats($shopId, $branchId);
        $svcRevenue = $this->service->revenueInPeriod($filter);

        // ── Finance partners ──────────────────────────────────────────────────
        $fpBalance = (float) ($fpPending->net_balance ?? 0);

        return new ExecutiveSummaryDTO(
            sales:                    $salesDTO,
            cash:                     $cashDTO,
            inventory:                $inventoryDTO,
            pendingExpenseApprovals:  $this->expense->pendingApprovalCount($shopId),
            pendingServiceTickets:    (int) ($svcStats->active ?? 0),
            openServiceTickets:       (int) ($svcStats->active ?? 0),
            serviceAmountDue:         (float) ($svcStats->total_due ?? 0),
            fpPendingSettlements:     $fpBalance,
            payrollAmountDue:         $this->payroll->pendingPayrollAmount($shopId),
            topProducts:              $this->sales->topProducts($filter, 5)->toArray(),
            topCustomers:             $this->sales->topCustomers($filter, 5)->toArray(),
            topEmployees:             $this->sales->byEmployee($filter)->take(5)->toArray(),
            branchSales:              $this->sales->byBranch($filter)->toArray(),
            recentSales:              $this->sales->recentSales($shopId, 8)->toArray(),
        );
    }
}