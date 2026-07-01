<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class BaseReportRepository
{
    /**
     * Apply common filters shared across all sale-related queries.
     * Centralises filter logic — never duplicate WHERE clauses.
     */
    protected function applySaleFilters(Builder $query, ReportFilter $filter): Builder
    {
        $query->where('sales.shop_id', $filter->shopId)
              ->where('sales.status', 'confirmed')
              ->whereBetween('sales.confirmed_at', [
                  $filter->dateRange->from,
                  $filter->dateRange->to,
              ]);

        if ($filter->branchId) {
            $query->where('sales.branch_id', $filter->branchId);
        }

        if ($filter->employeeId) {
            $query->where('sales.cashier_id', $filter->employeeId);
        }

        if ($filter->customerId) {
            $query->where('sales.customer_id', $filter->customerId);
        }

        return $query;
    }

    protected function applyExpenseFilters(Builder $query, ReportFilter $filter): Builder
    {
        $query->where('expenses.shop_id', $filter->shopId)
              ->where('expenses.status', 'approved')
              ->whereBetween('expenses.expense_date', [
                  $filter->dateRange->from->toDateString(),
                  $filter->dateRange->to->toDateString(),
              ]);

        if ($filter->branchId) {
            $query->where('expenses.branch_id', $filter->branchId);
        }

        return $query;
    }

    protected function changePercent(?float $previous, float $current): ?float
    {
        if (! $previous || $previous == 0) return null;
        return round(($current - $previous) / $previous * 100, 1);
    }
}