<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchRoleController extends Controller
{
    public function __invoke(Request $request, string $role, ActiveRoleService $activeRole, AuditLogger $audit): RedirectResponse
    {
        $target = Role::tryFrom($role);

        /** @var User $user */
        $user = $request->user();

        abort_unless($target !== null && $user->hasRoleEnum($target), 403);

        $activeRole->set($request->session(), $target);
        $request->session()->regenerateToken();

        $audit->record('auth', 'role_switched', "Beralih ke role {$target->label()}", $user, ['role' => $target->value]);

        return redirect()->route($target->dashboardRoute())
            ->with('success', "Anda sekarang masuk sebagai {$target->label()}.");
    }
}
