<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'app_name', 'logo_path', 'favicon_path', 'support_email', 'support_phone',
    'default_currency', 'default_timezone', 'default_trial_days',
    'terms_url', 'privacy_url', 'footer_text',
])]
class PlatformSetting extends Model
{
    private const CACHE_KEY = 'platform_settings';

    /**
     * There is only ever one row (id=1). Cached indefinitely since this
     * is read on nearly every page load (admin layout, login page, etc.)
     * and only changes when a super admin explicitly saves it.
     */
    public static function current(): self
{
    return static::query()->firstOrCreate(['id' => 1]);
}

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}