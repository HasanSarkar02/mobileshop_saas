<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SetInitialPasswordNotification;
use Illuminate\Support\Facades\Password;

class UserInviter
{
    public function invite(User $user, string $contextLabel): void
    {
        $token = Password::broker()->createToken($user);

        $user->notify(new SetInitialPasswordNotification($token, $contextLabel));
    }
}