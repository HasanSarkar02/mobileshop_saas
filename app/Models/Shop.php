<?php

namespace App\Models;

use App\Enums\ShopStatus;
use App\Enums\UserType;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'slug', 'email', 'phone', 'address', 'logo',
    'business_type', 'currency', 'timezone', 'status',
    'vat_enabled', 'vat_registration_number', 'default_vat_rate',
    'trial_ends_at', 'subscription_ends_at', 'is_active', 'settings',
    'subscription_plan_id', 'books_locked_through', 'onboarding_completed_at','expense_approval_threshold',
])]
class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status'                  => ShopStatus::class,
            'trial_ends_at'           => 'datetime',
            'subscription_ends_at'    => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'books_locked_through'    => 'date',
            'is_active'               => 'boolean',
            'vat_enabled'             => 'boolean',
            'default_vat_rate'        => 'decimal:2',
            'settings'                => 'array',
            'expense_approval_threshold' => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

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

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isOnTrial(): bool
    {
        return $this->status === ShopStatus::Trial && $this->trial_ends_at?->isFuture();
    }
}