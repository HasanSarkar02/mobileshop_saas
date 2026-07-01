<?php

namespace App\Reporting\DTOs;

final class InventorySummaryDTO
{
    public function __construct(
        public readonly int   $totalSkus,
        public readonly int   $totalSerializedUnits,
        public readonly int   $inStockUnits,
        public readonly int   $lowStockSkus,
        public readonly int   $outOfStockSkus,
        public readonly float $totalInventoryValue,
        public readonly float $serializedValue,
        public readonly float $nonSerializedValue,
        /** array of ['name', 'sku', 'qty', 'value'] */
        public readonly array $lowStockItems,
    ) {}
}