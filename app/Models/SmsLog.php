<?php
namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'shop_id', 'to_number', 'template', 'message', 'status',
    'provider_response', 'message_id', 'cost', 'reference_type',
    'reference_id', 'created_by',
])]
class SmsLog extends Model
{
    use BelongsToShop;

    public function reference(): MorphTo { return $this->morphTo(); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}