<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shop_id', 'category', 'channel', 'is_enabled'])]
class NotificationPreference extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'channel' => NotificationChannel::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}