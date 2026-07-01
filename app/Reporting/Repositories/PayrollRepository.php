<?php

namespace App\Reporting\Repositories;

use Illuminate\Support\Facades\DB;

class PayrollRepository extends BaseReportRepository
{
    public function pendingPayrollAmount(int $shopId): float
    {
        return (float) DB::table('payroll_runs')
            ->where('shop_id', $shopId)
            ->where('status', 'approved')
            ->sum('total_net');
    }

    public function currentMonthDrawsSummary(int $shopId): object
    {
        return DB::table('salary_draws')
            ->where('shop_id', $shopId)
            ->where('for_year', now()->year)
            ->where('for_month', now()->month)
            ->selectRaw('
                COUNT(DISTINCT user_id) AS employee_count,
                COALESCE(SUM(amount), 0) AS total_drawn
            ')
            ->first();
    }
}