<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ExpenseRepository extends BaseReportRepository
{
    public function aggregate(ReportFilter $filter): object
    {
        return $this->applyExpenseFilters(DB::table('expenses'), $filter)
            ->selectRaw('
                COUNT(*) AS count,
                COALESCE(SUM(amount), 0) AS total
            ')
            ->first();
    }

    public function pendingApprovalCount(int $shopId): int
    {
        return DB::table('expenses')
            ->where('shop_id', $shopId)
            ->where('status', 'pending')
            ->count();
    }

    public function byCategory(ReportFilter $filter): Collection
    {
        return DB::table('expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->leftJoin('expense_categories AS parent', 'parent.id', '=', 'expense_categories.parent_id')
            ->where('expenses.shop_id', $filter->shopId)
            ->where('expenses.status', 'approved')
            ->whereBetween('expenses.expense_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->when($filter->branchId, fn ($q) => $q->where('expenses.branch_id', $filter->branchId))
            ->selectRaw('
                COALESCE(parent.name, expense_categories.name) AS parent_category,
                expense_categories.name AS category,
                COUNT(*) AS count,
                COALESCE(SUM(expenses.amount), 0) AS total
            ')
            ->groupBy('expense_categories.id', 'expense_categories.name',
                      'parent.id', 'parent.name')
            ->orderByRaw('total DESC')
            ->get();
    }
}