<?php

namespace App\Models;

use App\Enums\ShopStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'slug', 'email', 'phone', 'address', 'logo',
    'business_type', 'currency', 'timezone', 'status',
    'vat_enabled', 'vat_registration_number', 'default_vat_rate',
    'trial_ends_at', 'subscription_ends_at', 'is_active', 'settings',
])]
class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ShopStatus::class,
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'vat_enabled' => 'boolean',
            'default_vat_rate' => 'decimal:2',
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function mainBranch(): HasOne
    {
        return $this->hasOne(Branch::class)->where('is_main', true);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('user_type', UserType::Owner->value);
    }

    public function isOnTrial(): bool
    {
        return $this->status === ShopStatus::Trial && $this->trial_ends_at?->isFuture();
    }
}