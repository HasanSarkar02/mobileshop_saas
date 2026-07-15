<?php

namespace App\Models;

use App\Enums\PushPlatform;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shop_id', 'token', 'platform', 'device_name', 'app_version', 'is_active', 'last_used_at'])]
class UserPushToken extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'platform' => PushPlatform::class,
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}