<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository extends BaseReportRepository
{
    /** Total value: serialized units (cost_price) + non-serialized (qty × avg_cost) */
    public function totalValue(int $shopId, ?int $branchId = null): object
    {
        $serialized = DB::table('product_units')
            ->where('shop_id', $shopId)
            ->where('status', 'in_stock')
            ->where('is_archived', false)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('
                COUNT(*) AS unit_count,
                COALESCE(SUM(cost_price), 0) AS value
            ')
            ->first();

        $nonSerialized = DB::table('branch_stocks')
            ->join('product_variants', 'product_variants.id', '=', 'branch_stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('branch_stocks.shop_id', $shopId)
            ->where('products.tracking_type', 'non_serialized')
            ->where('branch_stocks.quantity', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_stocks.branch_id', $branchId))
            ->selectRaw('
                COALESCE(SUM(branch_stocks.quantity), 0) AS total_qty,
                COALESCE(SUM(branch_stocks.quantity * branch_stocks.average_cost), 0) AS value
            ')
            ->first();

        return (object) [
            'serialized_units'       => (int)   ($serialized->unit_count ?? 0),
            'serialized_value'       => (float) ($serialized->value ?? 0),
            'non_serialized_qty'     => (int)   ($nonSerialized->total_qty ?? 0),
            'non_serialized_value'   => (float) ($nonSerialized->value ?? 0),
            'total_value'            => (float) ($serialized->value ?? 0) + (float) ($nonSerialized->value ?? 0),
        ];
    }

    /** SKUs below minimum threshold — "low stock" */
    public function lowStockItems(int $shopId, ?int $branchId = null, int $threshold = 3): Collection
    {
        // Non-serialized variants with qty <= threshold
        return DB::table('branch_stocks')
            ->join('product_variants', 'product_variants.id', '=', 'branch_stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('branch_stocks.shop_id', $shopId)
            ->where('products.tracking_type', 'non_serialized')
            ->where('products.is_active', true)
            ->where('branch_stocks.quantity', '<=', $threshold)
            ->when($branchId, fn ($q) => $q->where('branch_stocks.branch_id', $branchId))
            ->selectRaw('
                products.name                               AS product_name,
                COALESCE(brands.name, "—")                 AS brand,
                product_variants.sku,
                product_variants.selling_price,
                branch_stocks.quantity,
                branch_stocks.average_cost,
                (branch_stocks.quantity * branch_stocks.average_cost) AS stock_value
            ')
            ->orderBy('branch_stocks.quantity')
            ->get();
    }

    /** IMEI-level stock counts by status */
    public function imeiStatusCounts(int $shopId, ?int $branchId = null): Collection
    {
        return DB::table('product_units')
            ->where('shop_id', $shopId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('status, COUNT(*) AS count')
            ->groupBy('status')
            ->get();
    }

    /** Stock movement within a period (purchases in, sales out, returns) */
    public function stockMovement(ReportFilter $filter): Collection
    {
        $purchases = DB::table('purchase_line_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_line_items.purchase_id')
            ->join('product_variants', 'product_variants.id', '=', 'purchase_line_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('purchases.shop_id', $filter->shopId)
            ->whereBetween('purchases.purchase_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->when($filter->branchId, fn ($q) => $q->where('purchases.branch_id', $filter->branchId))
            ->selectRaw('"purchase" AS movement_type,
                products.name AS product_name, product_variants.sku,
                SUM(purchase_line_items.quantity) AS qty,
                SUM(purchase_line_items.line_total) AS value')
            ->groupBy('products.id', 'products.name', 'product_variants.sku')
            ->get();

        return $purchases; 
    }

    /** Inventory by variant for full stock valuation report */
    public function stockValuation(ReportFilter $filter): Collection
    {
        $serialized = DB::table('product_units')
            ->join('product_variants', 'product_variants.id', '=', 'product_units.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('product_units.shop_id', $filter->shopId)
            ->where('product_units.status', 'in_stock')
            ->where('product_units.is_archived', false)
            ->when($filter->branchId, fn ($q) => $q->where('product_units.branch_id', $filter->branchId))
            ->when($filter->categoryId, fn ($q) => $q->where('products.category_id', $filter->categoryId))
            ->when($filter->brandId, fn ($q) => $q->where('products.brand_id', $filter->brandId))
            ->selectRaw('
                products.name AS product_name,
                COALESCE(brands.name, "—") AS brand,
                COALESCE(categories.name, "—") AS category,
                product_variants.sku,
                product_variants.selling_price,
                COUNT(product_units.id) AS qty,
                AVG(product_units.cost_price) AS avg_cost,
                SUM(product_units.cost_price) AS total_cost_value,
                (COUNT(product_units.id) * product_variants.selling_price) AS retail_value,
                "serialized" AS tracking_type
            ')
            ->groupBy('products.id', 'products.name', 'brands.name', 'categories.name',
                      'product_variants.id', 'product_variants.sku', 'product_variants.selling_price')
            ->get();

        $nonSerialized = DB::table('branch_stocks')
            ->join('product_variants', 'product_variants.id', '=', 'branch_stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('branch_stocks.shop_id', $filter->shopId)
            ->where('products.tracking_type', 'non_serialized')
            ->where('branch_stocks.quantity', '>', 0)
            ->when($filter->branchId, fn ($q) => $q->where('branch_stocks.branch_id', $filter->branchId))
            ->selectRaw('
                products.name AS product_name,
                COALESCE(brands.name, "—") AS brand,
                COALESCE(categories.name, "—") AS category,
                product_variants.sku,
                product_variants.selling_price,
                SUM(branch_stocks.quantity) AS qty,
                AVG(branch_stocks.average_cost) AS avg_cost,
                SUM(branch_stocks.quantity * branch_stocks.average_cost) AS total_cost_value,
                (SUM(branch_stocks.quantity) * product_variants.selling_price) AS retail_value,
                "non_serialized" AS tracking_type
            ')
            ->groupBy('products.id', 'products.name', 'brands.name', 'categories.name',
                      'product_variants.id', 'product_variants.sku', 'product_variants.selling_price')
            ->get();

        return $serialized->concat($nonSerialized)->sortByDesc('total_cost_value')->values();
    }
}