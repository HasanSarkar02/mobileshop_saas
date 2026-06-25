<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id', 'year', 'month', 'status',
    'total_gross', 'total_deductions', 'total_net',
    'created_by', 'approved_by', 'approved_at', 'paid_at',
])]
class PayrollRun extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'status'         => PayrollStatus::class,
            'total_gross'    => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net'      => 'decimal:2',
            'approved_at'    => 'datetime',
            'paid_at'        => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function monthName(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
    }
}