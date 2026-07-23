<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ImpersonationController extends Controller
{
    public function __construct(private readonly ImpersonationService $impersonationService) {}

    public function start(Request $request, User $user)
    {
        $superAdmin = Auth::guard('admin')->user();

        abort_unless($superAdmin instanceof User && $superAdmin->isSuperAdmin(), 403);

        if (! $user->is_active) {
            return redirect()->back()->withErrors([
                'impersonation' => "Cannot impersonate {$user->name} — this account is deactivated.",
            ]);
        }

        try {
            $this->impersonationService->start($request, $superAdmin, $user, $request->input('reason'));
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors(['impersonation' => $e->getMessage()]);
        }

        return redirect('/dashboard');
    }

    public function stop(Request $request)
    {
        $this->impersonationService->stop($request);

        return redirect()->route('admin.login');
    }
}