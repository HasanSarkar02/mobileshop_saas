<?php

namespace App\Reporting\Services;

use App\Reporting\DTOs\ReportFilter;
use App\Reporting\DTOs\SalesSummaryDTO;
use App\Reporting\Repositories\SalesRepository;
use Illuminate\Support\Collection;

class SalesReportService
{
    public function __construct(private readonly SalesRepository $repo) {}

    public function summary(ReportFilter $filter): SalesSummaryDTO
    {
        $current  = $this->repo->aggregate($filter);
        $previous = $this->repo->aggregate($filter->forPreviousPeriod());
        $net      = (float) $current->net_revenue;
        $profit   = (float) $current->gross_profit;

        return new SalesSummaryDTO(
            orderCount:      (int)   $current->order_count,
            grossRevenue:    (float) $current->gross_revenue,
            totalDiscount:   (float) $current->total_discount,
            vatAmount:       (float) $current->vat_amount,
            netRevenue:      $net,
            totalCost:       (float) $current->total_cost,
            grossProfit:     $profit,
            profitMarginPct: $net > 0 ? round($profit / $net * 100, 1) : 0,
            avgOrderValue:   (float) $current->avg_order_value,
            totalReturns:    (float) $current->return_amount,
            returnCount:     (int)   $current->return_count,
            prevNetRevenue:  (float) $previous->net_revenue,
            prevGrossProfit: (float) $previous->gross_profit,
            prevOrderCount:  (int)   $previous->order_count,
        );
    }

    public function dailyTrend(ReportFilter $filter): Collection
    {
        return $this->repo->dailyTrend($filter);
    }

    public function byPaymentMethod(ReportFilter $filter): Collection
    {
        return $this->repo->byPaymentMethod($filter);
    }

    public function topProducts(ReportFilter $filter, int $limit = 20): Collection
    {
        return $this->repo->topProducts($filter, $limit);
    }

    public function topCustomers(ReportFilter $filter, int $limit = 20): Collection
    {
        return $this->repo->topCustomers($filter, $limit);
    }

    public function byEmployee(ReportFilter $filter): Collection
    {
        return $this->repo->byEmployee($filter);
    }

    public function byBranch(ReportFilter $filter): Collection
    {
        return $this->repo->byBranch($filter);
    }

    public function byHour(ReportFilter $filter): Collection
    {
        return $this->repo->byHour($filter);
    }
}