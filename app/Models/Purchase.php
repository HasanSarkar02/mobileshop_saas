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
}