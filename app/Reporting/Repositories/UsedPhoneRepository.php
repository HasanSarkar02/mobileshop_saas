<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UsedPhoneRepository extends BaseReportRepository
{
    public function summary(int $shopId, ?int $branchId = null): object
    {
        $base = DB::table('used_phone_acquisitions')
            ->where('shop_id', $shopId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $total = (clone $base)->selectRaw('
            COUNT(*)                              AS total_count,
            COALESCE(SUM(purchase_price), 0)      AS total_spent,
            COALESCE(SUM(expected_sell_price), 0) AS expected_revenue
        ')->first();

        // Revenue from confirmed, non-returned sales of used phones
        $unitIds = (clone $base)->whereNotNull('product_unit_id')->pluck('product_unit_id');

        $soldRevenue = DB::table('sale_items')
            ->whereIn('product_unit_id', $unitIds)
            ->whereHas('sale', fn ($q) =>
                $q->where('status', 'confirmed')->where('return_processed', false)
            )
            ->selectRaw('
                COUNT(DISTINCT product_unit_id) AS sold_count,
                COALESCE(SUM(line_total), 0)    AS revenue
            ')
            ->first();

        // Cost of sold units specifically
        $costOfSold = DB::table('used_phone_acquisitions')
            ->whereIn('product_unit_id', $unitIds)
            ->whereHas('productUnit', fn ($q) => $q->where('status', 'sold'))
            ->sum('purchase_price');

        $inventoryValue = DB::table('used_phone_acquisitions')
            ->where('shop_id', $shopId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('productUnit', fn ($q) => $q->where('status', 'in_stock'))
            ->sum('purchase_price');

        return (object) [
            'total_count'     => (int)   ($total->total_count ?? 0),
            'total_spent'     => (float) ($total->total_spent ?? 0),
            'expected_revenue'=> (float) ($total->expected_revenue ?? 0),
            'sold_count'      => (int)   ($soldRevenue->sold_count ?? 0),
            'total_revenue'   => (float) ($soldRevenue->revenue ?? 0),
            'net_profit'      => (float) ($soldRevenue->revenue ?? 0) - (float) $costOfSold,
            'inventory_value' => (float) $inventoryValue,
        ];
    }

    public function list(ReportFilter $filter): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return DB::table('used_phone_acquisitions')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'used_phone_acquisitions.product_variant_id')
            ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('product_units', 'product_units.id', '=', 'used_phone_acquisitions.product_unit_id')
            ->leftJoin('branches', 'branches.id', '=', 'used_phone_acquisitions.branch_id')
            ->where('used_phone_acquisitions.shop_id', $filter->shopId)
            ->when($filter->branchId, fn ($q) => $q->where('used_phone_acquisitions.branch_id', $filter->branchId))
            ->whereBetween('used_phone_acquisitions.created_at', [
                $filter->dateRange->from,
                $filter->dateRange->to,
            ])
            ->selectRaw('
                used_phone_acquisitions.*,
                COALESCE(products.name, used_phone_acquisitions.model_description) AS catalog_name,
                product_units.status AS unit_status,
                branches.name AS branch_name
            ')
            ->orderByDesc('used_phone_acquisitions.created_at')
            ->paginate($filter->perPage);
    }

    public function conditionBreakdown(int $shopId): Collection
    {
        return DB::table('used_phone_acquisitions')
            ->where('shop_id', $shopId)
            ->selectRaw('
                `condition`,
                COUNT(*) AS count,
                COALESCE(SUM(purchase_price), 0) AS total_spent,
                COALESCE(SUM(expected_sell_price), 0) AS expected_revenue
            ')
            ->groupBy('condition')
            ->orderByRaw('count DESC')
            ->get();
    }
}