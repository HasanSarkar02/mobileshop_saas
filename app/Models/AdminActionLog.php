<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['admin_id', 'action', 'subject_type', 'subject_id', 'shop_id', 'reason', 'meta'])]
class AdminActionLog extends Model
{
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
    public function shop(): BelongsTo  { return $this->belongsTo(Shop::class); }
}