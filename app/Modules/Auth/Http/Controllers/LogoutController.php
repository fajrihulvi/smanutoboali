<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\ActiveRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Keluar lalu kembali ke halaman login portal role terakhir.
     */
    public function __invoke(Request $request, ActiveRoleService $activeRole): RedirectResponse
    {
        $role = $activeRole->current($request->session());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($role?->hasStaffPortal()) {
            return redirect()->route('login.portal', $role->urlPrefix());
        }

        return redirect()->route('login');
    }
}
