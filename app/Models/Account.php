<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'branch_id', 'code', 'name', 'type', 'subtype', 'parent_id', 'is_system', 'is_header', 'is_active', 'description'])]
class Account extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_system' => 'boolean',
            'is_header' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Current balance, respecting the account's normal balance side.
     */
    public function balance(): float
    {
        $sums = $this->lines()
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return $this->type->normalBalance() === 'debit'
            ? (float) $sums->total_debit - (float) $sums->total_credit
            : (float) $sums->total_credit - (float) $sums->total_debit;
    }

    public function delete()
    {
        if ($this->is_system) {
            throw new \RuntimeException("Cannot delete the system account [{$this->name}]. Deactivate it instead.");
        }

        if ($this->lines()->exists()) {
            throw new \RuntimeException("Cannot delete account [{$this->name}] — it has transaction history. Deactivate it instead.");
        }

        return parent::delete();
    }

    public function isPostable(): bool
    {
        return ! $this->is_header && $this->is_active;
    }
}