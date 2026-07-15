<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'slug' => 'starter', 'monthly_price' => 499,
             'yearly_price' => 4999, 'max_branches' => 1, 'max_employees' => 3, 'max_products' => 200,
             'features' => json_encode(['pos','sales','purchases','expenses']), 'sort_order' => 1],
            ['name' => 'Pro',     'slug' => 'pro',     'monthly_price' => 999,
             'yearly_price' => 9999, 'max_branches' => 3, 'max_employees' => 15, 'max_products' => 2000,
             'features' => json_encode(['pos','sales','purchases','expenses','payroll','treasury','service','reports']), 'sort_order' => 2],
            ['name' => 'Enterprise','slug'=>'enterprise','monthly_price'=>1999,
             'yearly_price' => 19999, 'max_branches' => 10, 'max_employees' => 50, 'max_products' => 0,
             'features' => json_encode(['all']), 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(['slug' => $plan['slug']], array_merge($plan, [
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}