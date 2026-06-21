<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function __construct(private readonly ImpersonationService $impersonationService) {}

    public function start(Request $request, User $user)
    {
        $superAdmin = Auth::guard('admin')->user();

        $this->impersonationService->start($request, $superAdmin, $user, $request->input('reason'));

        return redirect('/dashboard');
    }

    public function stop(Request $request)
    {
        $this->impersonationService->stop($request);

        return redirect()->route('admin.login');
    }
}