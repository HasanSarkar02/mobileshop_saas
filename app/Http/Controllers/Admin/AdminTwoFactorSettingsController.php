<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Auth;

class AdminTwoFactorSettingsController extends Controller
{
    public function regenerateRecoveryCodes(TwoFactorAuthService $tfa)
    {
        $user = Auth::guard('admin')->user();
        $plainCodes = $tfa->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $tfa->hashRecoveryCodes($plainCodes),
        ])->save();

        session()->flash('new_recovery_codes', $plainCodes);

        return redirect()->route('admin.2fa.recovery-codes.show');
    }
}