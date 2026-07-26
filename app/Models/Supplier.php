<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'name', 'phone', 'email', 'address','notes', 'is_active', 'current_balance', 'bank_name', 'bank_account_number', 'bank_branch_name', 
    'bank_routing_number', 'payment_terms', 'credit_limit'])]
class Supplier extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'current_balance' => 'decimal:2',
            'credit_limit'    => 'decimal:2'
            ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function totalOutstanding(): float
    {
        return (float) $this->current_balance;
    }

    public function hasOverdueBalance(): bool
    {
        return $this->current_balance > 0;
    }
}