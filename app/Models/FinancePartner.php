<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'name', 'contact_person', 'phone', 'email', 'processing_fee_percent', 'notes', 'is_active'])]
class FinancePartner extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected function casts(): array
    {
        return [
            'processing_fee_percent' => 'decimal:2',
            'is_active'              => 'boolean',
        ];
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(FinancePartnerReceivable::class);
    }

    public function pendingReceivablesTotal(): float
    {
        return (float) $this->receivables()
            ->whereIn('status', ['pending', 'partial'])
            ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - settled_amount'));
    }
}