<?php

namespace App\Services;

use App\Enums\ShopFeature;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ShopFeatureService
{
    /**
     * Check if a feature is enabled for the current authenticated shop.
     * null enabled_features = all features enabled (legacy / admin bypass).
     */
    public function enabled(ShopFeature|string $feature): bool
    {
        $user = Auth::user();

        if (! $user || $user->isSuperAdmin()) {
            return true;
        }

        $featureValue = $feature instanceof ShopFeature
            ? $feature->value
            : $feature;

        $enabled = $this->enabledFeatures($user->shop_id);

        // null = unrestricted (all enabled)
        if ($enabled === null) {
            return true;
        }

        return in_array($featureValue, $enabled, true);
    }

    public function enabledFeatures(int $shopId): array
{
    return Cache::remember(
        "shop_features_{$shopId}",
        now()->addMinutes(10),
        function () use ($shopId) {
            return Shop::withoutGlobalScopes()
                ->find($shopId)
                ?->enabled_features ?? [];
        }
    );
}

    public function clearCache(int $shopId): void
    {
        Cache::forget("shop_features_{$shopId}");
    }

    /**
     * Enable/disable a set of features for a shop.
     * Passing null removes restriction (all features enabled).
     */
    public function setFeatures(int $shopId, ?array $features): void
    {
        Shop::withoutGlobalScopes()->where('id', $shopId)->update([
            'enabled_features' => $features,
        ]);
        $this->clearCache($shopId);
    }

    public function abort(ShopFeature|string $feature): void
    {
        if (! $this->enabled($feature)) {
            abort(403, 'This feature is not enabled for your shop.');
        }
    }
}