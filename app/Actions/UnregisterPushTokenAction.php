<?php

namespace App\Actions;

use App\Models\UserPushToken;

class UnregisterPushTokenAction
{
    public function execute(string $token): void
    {
        UserPushToken::where('token', $token)->update(['is_active' => false]);
    }
}