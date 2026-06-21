<?php

namespace Database\Seeders;

use App\Enums\ProductTrackingType;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCatalogSeeder extends Seeder
{
    private const BRANDS = [
        'Samsung', 'Apple', 'Xiaomi', 'Oppo', 'Vivo', 'Realme',
        'Itel', 'Symphony', 'Walton', 'Tecno', 'Infinix', 'Nokia',
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $brand) {
            Brand::withoutGlobalScopes()->firstOrCreate(['shop_id' => null, 'name' => $brand]);
        }

        Category::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => null, 'parent_id' => null, 'name' => 'Smartphones'],
            ['default_tracking_type' => ProductTrackingType::Serialized]
        );

        Category::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => null, 'parent_id' => null, 'name' => 'Feature Phones'],
            ['default_tracking_type' => ProductTrackingType::Serialized]
        );

        Category::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => null, 'parent_id' => null, 'name' => 'SIM Cards'],
            ['default_tracking_type' => ProductTrackingType::NonSerialized]
        );

        $accessories = Category::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => null, 'parent_id' => null, 'name' => 'Accessories'],
            ['default_tracking_type' => ProductTrackingType::NonSerialized]
        );

        foreach (['Cases & Covers', 'Chargers & Cables', 'Earphones & Headsets', 'Screen Protectors', 'Power Banks'] as $name) {
            Category::withoutGlobalScopes()->firstOrCreate(
                ['shop_id' => null, 'parent_id' => $accessories->id, 'name' => $name],
                ['default_tracking_type' => ProductTrackingType::NonSerialized]
            );
        }
    }
}