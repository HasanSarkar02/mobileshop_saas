<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Rules\Password;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset(User $user, array $input): void
    {
        // SECURITY: Final defense — Super Admin passwords can NEVER be reset
        // through the shop portal, even with a valid token.
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'This password reset link is invalid.',
            ]);
        }

        Validator::make($input, [
            'password' => ['required', 'string', new Password, 'confirmed'],
        ])->validate();

        $user->forceFill([
            'password'          => $input['password'],
            'password_changed_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}