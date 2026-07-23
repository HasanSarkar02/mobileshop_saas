<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\SetInitialPasswordNotification;
use Illuminate\Support\Facades\Password;

class UserInviter
{
    public function invite(User $user, string $contextLabel, ?Shop $shop = null): void
    {
        $shop = $shop ?? $user->shop;
        $token = Password::broker()->createToken($user);

        $user->notify(new SetInitialPasswordNotification($token, $contextLabel, $shop));
    }
}