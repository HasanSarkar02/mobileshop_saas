<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserPushToken;

class RegisterPushTokenAction
{
    public function execute(User $user, string $token, string $platform, ?string $deviceName = null, ?string $appVersion = null): UserPushToken
    {
        return UserPushToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'shop_id' => $user->shop_id,
                'platform' => $platform,
                'device_name' => $deviceName,
                'app_version' => $appVersion,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }
}