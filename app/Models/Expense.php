<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'shop_id', 'branch_id', 'expense_category_id', 'payment_account_id',
    'reference_number', 'amount', 'expense_date', 'description',
    'receipt_path', 'status', 'notes', 'created_by', 'approved_by', 'approved_at',
])]
class Expense extends Model
{
    use BelongsToBranch, LogsActivity;

    protected function casts(): array
    {
        return [
            'status'       => ExpenseStatus::class,
            'amount'       => 'decimal:2',
            'expense_date' => 'date',
            'approved_at'  => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'amount', 
                'status', 
                'expense_category_id', 
                'payment_account_id', 
                'expense_date', 
                'description', 
                'notes', 
                'approved_by'
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('expense');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiptUrl(): ?string
    {
        return $this->receipt_path ? Storage::url($this->receipt_path) : null;
    }
}