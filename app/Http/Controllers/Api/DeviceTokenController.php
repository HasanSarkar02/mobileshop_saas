<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function register(Request $request): JsonResponse
{
    $validated = $request->validate([
        'token'       => 'required|string|max:255',
        'platform'    => 'required|string|max:20',
        'device_name' => 'nullable|string|max:255',
        'app_version' => 'nullable|string|max:20',
    ]);

    $user = Auth::user();

    // Ensure token uniqueness globally and transfer ownership
    $tokenRecord = UserPushToken::where('token', $validated['token'])->first();

    if ($tokenRecord) {
        $tokenRecord->update([
            'user_id'      => $user->id,
            'shop_id'      => $user->shop_id,
            'is_active'    => true,
            'last_used_at' => now(),
            'platform'     => $validated['platform'],
            'device_name'  => $validated['device_name'] ?? $tokenRecord->device_name,
            'app_version'  => $validated['app_version'] ?? $tokenRecord->app_version,
        ]);
    } else {
        UserPushToken::create([
            'user_id'      => $user->id,
            'shop_id'      => $user->shop_id,
            'token'        => $validated['token'],
            'platform'     => $validated['platform'],
            'device_name'  => $validated['device_name'],
            'app_version'  => $validated['app_version'],
            'is_active'    => true,
            'last_used_at' => now(),
        ]);
    }

    return response()->json(['message' => 'Token registered successfully.']);
}

public function remove(Request $request): JsonResponse
{
    $validated = $request->validate(['token' => 'required|string']);

    // Deactivate token only for the authenticated user
    $deleted = UserPushToken::where('user_id', Auth::id())
        ->where('token', $validated['token'])
        ->update(['is_active' => false, 'last_used_at' => now()]);

    return response()->json(['message' => 'Token deactivated.'], 200);
}
}
