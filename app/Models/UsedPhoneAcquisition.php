<?php

namespace App\Models;

use App\Enums\PhoneCondition;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'branch_id', 'acquisition_number',
    'seller_name', 'seller_phone', 'seller_nid', 'seller_address',
    'imei_1', 'imei_2', 'model_description', 'product_variant_id', 'product_unit_id',
    'condition', 'condition_notes', 'accessories',
    'purchase_price', 'expected_sell_price', 'payment_account_id',
    'trade_in_sale_id', 'notes', 'created_by',
])]
class UsedPhoneAcquisition extends Model
{
    use BelongsToBranch;

    protected function casts(): array
    {
        return [
            'condition'           => PhoneCondition::class,
            'purchase_price'      => 'decimal:2',
            'expected_sell_price' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function productUnit(): BelongsTo { return $this->belongsTo(ProductUnit::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function tradeInSale(): BelongsTo { return $this->belongsTo(Sale::class, 'trade_in_sale_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}