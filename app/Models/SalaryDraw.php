<?php
namespace App\Models;

use App\Enums\SalaryDrawType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'user_id', 'amount', 'payment_account_id',
    'draw_date', 'for_year', 'for_month', 'draw_type',
    'notes', 'payroll_item_id', 'created_by',
])]
class SalaryDraw extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'draw_type' => SalaryDrawType::class,
            'amount'    => 'decimal:2',
            'draw_date' => 'date',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}