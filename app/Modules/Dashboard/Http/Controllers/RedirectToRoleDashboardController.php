<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /dashboard → dashboard role aktif.
 */
class RedirectToRoleDashboardController extends Controller
{
    public function __invoke(Request $request, ActiveRoleService $activeRole): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $role = $activeRole->resolve($request->session(), $user);

        abort_if($role === null, 403);

        return redirect()->to($activeRole->homeUrl($role));
    }
}
