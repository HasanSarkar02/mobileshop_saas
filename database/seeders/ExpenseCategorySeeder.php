<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        ['name' => 'Rent & Utilities', 'code' => '6000', 'children' => [
            ['name' => 'Shop Rent',     'code' => '6000'],
            ['name' => 'Electricity',   'code' => '6010'],
            ['name' => 'Water Bill',    'code' => '6010'],
            ['name' => 'Gas Bill',      'code' => '6010'],
        ]],
        ['name' => 'Communication', 'code' => '6030', 'children' => [
            ['name' => 'Internet',      'code' => '6030'],
            ['name' => 'Mobile Bill',   'code' => '6030'],
        ]],
        ['name' => 'Transport', 'code' => '6030', 'children' => [
            ['name' => 'Fuel / CNG',    'code' => '6030'],
            ['name' => 'Courier / Delivery', 'code' => '6030'],
        ]],
        ['name' => 'Marketing',     'code' => '6030', 'children' => [
            ['name' => 'Social Media Ads', 'code' => '6030'],
            ['name' => 'Print / Banner',   'code' => '6030'],
        ]],
        ['name' => 'Office & Supplies', 'code' => '6030', 'children' => [
            ['name' => 'Stationery',    'code' => '6030'],
            ['name' => 'Cleaning',      'code' => '6030'],
        ]],
        ['name' => 'Repairs',       'code' => '6030'],
        ['name' => 'Bank Charges',  'code' => '6030'],
        ['name' => 'Salary',        'code' => '6020'],
        ['name' => 'Miscellaneous', 'code' => '6030'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $cat) {
            $parent = ExpenseCategory::withoutGlobalScopes()->firstOrCreate(
                ['shop_id' => null, 'parent_id' => null, 'name' => $cat['name']],
                ['gl_account_code' => $cat['code'], 'is_system' => true, 'is_active' => true]
            );

            foreach ($cat['children'] ?? [] as $child) {
                ExpenseCategory::withoutGlobalScopes()->firstOrCreate(
                    ['shop_id' => null, 'parent_id' => $parent->id, 'name' => $child['name']],
                    ['gl_account_code' => $child['code'], 'is_system' => true, 'is_active' => true]
                );
            }
        }
    }
}