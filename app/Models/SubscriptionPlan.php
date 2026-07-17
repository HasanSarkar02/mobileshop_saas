<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'monthly_price', 'yearly_price',
    'max_branches', 'max_employees', 'max_products',
    'features', 'is_active', 'sort_order',
])]
class SubscriptionPlan extends Model
{
    protected function casts(): array
    {
        return [
            'features'      => 'array',
            'monthly_price' => 'decimal:2',
            'yearly_price'  => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class, 'plan_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class, 'plan_id')
            ->whereIn('status', ['trial', 'active']);
    }
}