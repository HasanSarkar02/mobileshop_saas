<?php

namespace App\Support;

class TenantContext
{
    private static ?int $shopId = null;
    private static ?int $branchId = null;

    public static function setShop(?int $shopId): void
    {
        self::$shopId = $shopId;
    }

    public static function shopId(): ?int
    {
        return self::$shopId;
    }

    public static function setBranch(?int $branchId): void
    {
        self::$branchId = $branchId;
    }

    public static function branchId(): ?int
    {
        return self::$branchId;
    }

    public static function clear(): void
    {
        self::$shopId = null;
        self::$branchId = null;
    }
}