<?php

namespace App\Models;

use App\Enums\GuarantorRelation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'customer_id', 'shop_id', 'name', 'phone', 'phone_alt', 'address',
    'relation', 'nid_number', 'photo_path', 'nid_front_path', 'nid_back_path',
])]
class CustomerGuarantor extends Model
{
    protected function casts(): array
    {
        return ['relation' => GuarantorRelation::class];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }
}