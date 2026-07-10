<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable(['shop_id', 'branch_id', 'supplier_id', 'reference_number', 'purchase_date', 'total_amount', 'payment_status', 'created_by'])]
class Purchase extends Model
{
    use BelongsToBranch;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PurchaseLineItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function delete()
    {
        if ($this->lineItems()->exists()) {
            throw new RuntimeException('Cannot delete a purchase that has already received inventory — it would break cost traceability.');
        }

        return parent::delete();
    }

    public function returns(): HasMany
    {
        return $this->hasMany(\App\Models\PurchaseReturn::class);
    }

    public function effectiveTotalAmount(): float
    {
        $returned = $this->returns()
            ->where('settlement_type', 'credit_note')
            ->sum('total_amount');
        return max(0, (float) $this->total_amount - (float) $returned);
    }

    public function totalReturned(): float
    {
        return (float) $this->returns()->sum('total_amount');
    }
}