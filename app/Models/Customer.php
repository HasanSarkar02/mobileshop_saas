<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'shop_id', 'customer_type', 'name', 'phone', 'phone_alt', 'email',
    'address', 'district', 'thana', 'date_of_birth', 'gender', 'occupation',
    'id_type', 'id_number', 'photo_path', 'id_front_path', 'id_back_path',
    'credit_limit', 'current_balance', 'total_purchase_amount', 'total_paid_amount',
    'notes', 'is_active', 'created_by',
])]
class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'customer_type'         => CustomerType::class,
            'date_of_birth'         => 'date',
            'credit_limit'          => 'decimal:2',
            'current_balance'       => 'decimal:2',
            'total_purchase_amount' => 'decimal:2',
            'total_paid_amount'     => 'decimal:2',
            'is_active'             => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'customer_type', 'credit_limit', 'address'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('customer');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function guarantor(): HasOne
    {
        return $this->hasOne(CustomerGuarantor::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class)->latest();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    public function hasReachedCreditLimit(): bool
    {
        if ($this->credit_limit <= 0) {
            return false; // unlimited credit
        }

        return $this->current_balance >= $this->credit_limit;
    }

    public function availableCredit(): float
    {
        if ($this->credit_limit <= 0) {
            return PHP_FLOAT_MAX;
        }

        return max(0, $this->credit_limit - $this->current_balance);
    }

    public function isOverdue(): bool
    {
        return $this->current_balance > 0;
    }

    // ── Static helpers for POS ────────────────────────────────────────────────

    /**
     * Get or create the generic "Walk-in Customer" for a shop.
     * Used in POS when no specific customer is selected.
     * Each shop has exactly one walk-in customer record.
     */
    public static function getWalkInForShop(int $shopId): self
    {
        return static::withoutGlobalScopes()
            ->firstOrCreate(
                ['shop_id' => $shopId, 'customer_type' => CustomerType::WalkIn->value],
                ['name' => 'Walk-in Customer', 'phone' => '00000000000', 'is_active' => true],
            );
    }

    /**
     * Quick search — used by POS's customer lookup.
     */
    public static function searchForPos(int $shopId, string $query): \Illuminate\Database\Eloquent\Collection
    {
        return static::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('customer_type', '!=', CustomerType::WalkIn->value)
            ->where(fn($q) =>
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
            )
            ->select(['id', 'name', 'phone', 'customer_type', 'current_balance', 'credit_limit'])
            ->limit(8)
            ->get();
    }
}