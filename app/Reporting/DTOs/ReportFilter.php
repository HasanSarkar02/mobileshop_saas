<?php

namespace App\Reporting\DTOs;

use App\Reporting\Enums\ReportPeriod;

final class ReportFilter
{
    public function __construct(
        public readonly int        $shopId,
        public readonly DateRange  $dateRange,
        public readonly ?int       $branchId          = null,
        public readonly ?int       $employeeId        = null,
        public readonly ?int       $customerId        = null,
        public readonly ?int       $supplierId        = null,
        public readonly ?int       $productId         = null,
        public readonly ?int       $categoryId        = null,
        public readonly ?int       $brandId           = null,
        public readonly ?int       $financePartnerId  = null,
        public readonly ?string    $paymentMethod     = null,
        public readonly ?string    $status            = null,
        public readonly ?string    $groupBy           = null,
        public readonly int        $perPage           = 50,
        public readonly ReportPeriod $period          = ReportPeriod::Custom,
    ) {}

    public static function forShop(int $shopId, ReportPeriod $period = ReportPeriod::ThisMonth): self
    {
        return new self(
            shopId:    $shopId,
            dateRange: $period->toDateRange(),
            period:    $period,
        );
    }

    public static function forShopAndDateRange(int $shopId, string $from, string $to): self
    {
        return new self(
            shopId:    $shopId,
            dateRange: DateRange::custom($from, $to),
            period:    ReportPeriod::Custom,
        );
    }

    /** Derive a comparison filter covering the immediately preceding period */
    public function forPreviousPeriod(): self
    {
        $prev = $this->dateRange->previousPeriod();

        return new self(
            shopId:           $this->shopId,
            dateRange:        $prev,
            branchId:         $this->branchId,
            employeeId:       $this->employeeId,
            customerId:       $this->customerId,
            supplierId:       $this->supplierId,
            productId:        $this->productId,
            categoryId:       $this->categoryId,
            brandId:          $this->brandId,
            financePartnerId: $this->financePartnerId,
            paymentMethod:    $this->paymentMethod,
            status:           $this->status,
            period:           ReportPeriod::Custom,
        );
    }

    /** Cache key — uniquely identifies this filter combination */
    public function cacheKey(string $prefix = ''): string
    {
        return $prefix . '_' . md5(serialize([
            $this->shopId, $this->branchId, $this->employeeId,
            $this->customerId, $this->supplierId, $this->productId,
            $this->categoryId, $this->brandId, $this->financePartnerId,
            $this->paymentMethod, $this->status,
            $this->dateRange->cacheKey(),
        ]));
    }
}