<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['shop_id', 'branch_id', 'supplier_id', 'reference_number', 'purchase_date', 'total_amount', 'amount_paid', 'payment_status', 'created_by'])]
class Purchase extends Model
{
    use BelongsToBranch, LogsActivity;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'supplier_id', 
                'branch_id', 
                'reference_number', 
                'purchase_date', 
                'total_amount', 
                'payment_status'
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('purchase');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
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

    /** Credit-note returns reduce what we owe — this is the AP-relevant total. */
    public function totalCredited(): float
    {
        return (float) $this->returns()
            ->where('settlement_type', 'credit_note')
            ->sum('total_amount');
    }

    /**
     * Cash-refund returns: money came back into our wallet (see journal), so
     * they do NOT reduce the payable — shown informationally, never subtracted.
     */
    public function totalCashRefunded(): float
    {
        return (float) $this->returns()
            ->where('settlement_type', 'cash_refund')
            ->sum('total_amount');
    }

    public function effectiveTotalAmount(): float
    {
        return max(0, (float) $this->total_amount - $this->totalCredited());
    }

    /** What we still owe on this purchase: net payable minus what we paid. */
    public function outstandingAmount(): float
    {
        return $this->effectiveTotalAmount() - (float) $this->amount_paid;
    }

    /**
     * Cumulative returned quantity per purchase line (ALL settlements — a
     * cash-refunded unit is gone just as much as a credited one).
     *
     * @return array<int, int> purchase_line_item_id => returned qty
     */
    public function returnedQuantities(): array
    {
        return \Illuminate\Support\Facades\DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->where('pr.purchase_id', $this->id)
            ->groupBy('pri.purchase_line_item_id')
            ->selectRaw('pri.purchase_line_item_id as line_id, SUM(pri.quantity) as qty')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->line_id => (int) $r->qty])
            ->all();
    }

    /** Whether any line still has returnable quantity left. */
    public function hasReturnableQuantity(): bool
    {
        $returned = $this->returnedQuantities();
        foreach ($this->lineItems()->get(['id', 'quantity']) as $line) {
            if ((int) $line->quantity - (int) ($returned[$line->id] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    public function totalReturned(): float
    {
        return (float) $this->returns()->sum('total_amount');
    }
}