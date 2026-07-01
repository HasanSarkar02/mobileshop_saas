<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesRepository extends BaseReportRepository
{
    /**
     * Core sales aggregation — the single query all sales widgets call.
     * Returns a stdClass with named properties so services can map to DTOs.
     */
    public function aggregate(ReportFilter $filter): object
    {
        $result = $this->applySaleFilters(
            DB::table('sales'),
            $filter
        )
        ->selectRaw('
            COUNT(*)                                               AS order_count,
            COALESCE(SUM(subtotal), 0)                            AS gross_revenue,
            COALESCE(SUM(total_discount_amount), 0)               AS total_discount,
            COALESCE(SUM(vat_amount), 0)                          AS vat_amount,
            COALESCE(SUM(grand_total), 0)                         AS net_revenue,
            COALESCE(SUM(total_cost), 0)                          AS total_cost,
            COALESCE(SUM(gross_profit), 0)                        AS gross_profit,
            COALESCE(AVG(grand_total), 0)                         AS avg_order_value
        ')
        ->first();

        // Returns (separate query — credit notes dated in the period)
        $returns = DB::table('credit_notes')
            ->where('shop_id', $filter->shopId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$filter->dateRange->from, $filter->dateRange->to])
            ->when($filter->branchId, fn ($q) => $q->where('branch_id', $filter->branchId))
            ->selectRaw('COUNT(*) as return_count, COALESCE(SUM(refund_amount), 0) as return_amount')
            ->first();

        $result->return_count  = (int)   ($returns->return_count ?? 0);
        $result->return_amount = (float) ($returns->return_amount ?? 0);

        return $result;
    }

    /** Revenue grouped by day — for trend charts */
    public function dailyTrend(ReportFilter $filter): Collection
    {
        return $this->applySaleFilters(DB::table('sales'), $filter)
            ->selectRaw('
                DATE(confirmed_at) AS sale_date,
                COUNT(*) AS orders,
                COALESCE(SUM(grand_total), 0) AS revenue,
                COALESCE(SUM(gross_profit), 0) AS profit
            ')
            ->groupByRaw('DATE(confirmed_at)')
            ->orderBy('sale_date')
            ->get();
    }

    /** Revenue by payment method (provider-level) */
    public function byPaymentMethod(ReportFilter $filter): Collection
    {
        return DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_accounts', 'payment_accounts.id', '=', 'sale_payments.payment_account_id')
            ->where('sales.shop_id', $filter->shopId)
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.confirmed_at', [$filter->dateRange->from, $filter->dateRange->to])
            ->when($filter->branchId, fn ($q) => $q->where('sales.branch_id', $filter->branchId))
            ->selectRaw('
                payment_accounts.provider AS provider,
                COUNT(DISTINCT sales.id) AS sale_count,
                SUM(sale_payments.amount) AS total_amount
            ')
            ->groupBy('payment_accounts.provider')
            ->orderByRaw('total_amount DESC')
            ->get();
    }

    /** Top-selling products by revenue */
    public function topProducts(ReportFilter $filter, int $limit = 10): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('sales.shop_id', $filter->shopId)
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.confirmed_at', [$filter->dateRange->from, $filter->dateRange->to])
            ->when($filter->branchId, fn ($q) => $q->where('sales.branch_id', $filter->branchId))
            ->when($filter->categoryId, fn ($q) => $q->where('products.category_id', $filter->categoryId))
            ->when($filter->brandId, fn ($q) => $q->where('products.brand_id', $filter->brandId))
            ->selectRaw('
                products.name                                        AS product_name,
                COALESCE(brands.name, "—")                          AS brand_name,
                product_variants.sku                                 AS sku,
                SUM(sale_items.quantity)                             AS qty_sold,
                COALESCE(SUM(sale_items.line_total), 0)             AS revenue,
                COALESCE(SUM(sale_items.profit_amount), 0)          AS profit
            ')
            ->groupBy('products.id', 'products.name', 'brands.name', 'product_variants.sku')
            ->orderByRaw('revenue DESC')
            ->limit($limit)
            ->get();
    }

    /** Top customers by revenue */
    public function topCustomers(ReportFilter $filter, int $limit = 10): Collection
    {
        return DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.shop_id', $filter->shopId)
            ->where('sales.status', 'confirmed')
            ->where('customers.customer_type', '!=', 'walk_in')
            ->whereBetween('sales.confirmed_at', [$filter->dateRange->from, $filter->dateRange->to])
            ->when($filter->branchId, fn ($q) => $q->where('sales.branch_id', $filter->branchId))
            ->selectRaw('
                customers.id,
                customers.name,
                customers.phone,
                COUNT(sales.id)                          AS order_count,
                COALESCE(SUM(sales.grand_total), 0)      AS revenue,
                COALESCE(SUM(sales.gross_profit), 0)     AS profit,
                customers.current_balance                AS outstanding_due
            ')
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.current_balance')
            ->orderByRaw('revenue DESC')
            ->limit($limit)
            ->get();
    }

    /** Sales by cashier/employee */
    public function byEmployee(ReportFilter $filter): Collection
    {
        return DB::table('sales')
            ->join('users', 'users.id', '=', 'sales.cashier_id')
            ->where('sales.shop_id', $filter->shopId)
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.confirmed_at', [$filter->dateRange->from, $filter->dateRange->to])
            ->when($filter->branchId, fn ($q) => $q->where('sales.branch_id', $filter->branchId))
            ->selectRaw('
                users.id,
                users.name,
                COUNT(sales.id)                         AS order_count,
                COALESCE(SUM(sales.grand_total), 0)     AS revenue,
                COALESCE(SUM(sales.gross_profit), 0)    AS profit
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByRaw('revenue DESC')
            ->get();
    }

    /** Sales grouped by branch for comparison */
    public function byBranch(ReportFilter $filter): Collection
    {
        return DB::table('sales')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->where('sales.shop_id', $filter->shopId)
            ->where('sales.status', 'confirmed')
            ->whereBetween('sales.confirmed_at', [$filter->dateRange->from, $filter->dateRange->to])
            ->selectRaw('
                branches.id,
                branches.name,
                COUNT(sales.id)                         AS order_count,
                COALESCE(SUM(sales.grand_total), 0)     AS revenue,
                COALESCE(SUM(sales.gross_profit), 0)    AS profit
            ')
            ->groupBy('branches.id', 'branches.name')
            ->orderByRaw('revenue DESC')
            ->get();
    }

    /** For hourly sales heatmap */
    public function byHour(ReportFilter $filter): Collection
    {
        return $this->applySaleFilters(DB::table('sales'), $filter)
            ->selectRaw('
                HOUR(confirmed_at)              AS hour_of_day,
                COUNT(*)                        AS orders,
                COALESCE(SUM(grand_total), 0)   AS revenue
            ')
            ->groupByRaw('HOUR(confirmed_at)')
            ->orderBy('hour_of_day')
            ->get();
    }

    public function recentSales(int $shopId, int $limit = 10): Collection
    {
        return DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('users', 'users.id', '=', 'sales.cashier_id')
            ->where('sales.shop_id', $shopId)
            ->where('sales.status', 'confirmed')
            ->selectRaw('
                sales.id, sales.sale_number, sales.grand_total,
                sales.confirmed_at, customers.name AS customer_name,
                users.name AS cashier_name, sales.return_processed
            ')
            ->orderByDesc('sales.confirmed_at')
            ->limit($limit)
            ->get();
    }
}