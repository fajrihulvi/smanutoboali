<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias 'active.role'.
 *  - active.role          : user login, aktif, dan punya role aktif yang sah.
 *  - active.role:teacher  : role aktif di session harus 'teacher'.
 */
class EnsureActiveRole
{
    public function __construct(private ActiveRoleService $activeRole) {}

    public function handle(Request $request, Closure $next, ?string $required = null): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun Anda sedang dinonaktifkan. Hubungi operator sekolah.');
        }

        $session = $request->session();
        $required = $required !== null ? Role::from($required) : null;

        // Sesi tanpa role aktif (mis. dipulihkan dari cookie "ingat saya") boleh
        // langsung memakai role portal yang diminta bila user memilikinya.
        if ($required && $this->activeRole->current($session) === null && $user->hasRoleEnum($required)) {
            $this->activeRole->set($session, $required);
        }

        $active = $this->activeRole->resolve($session, $user);

        abort_if($active === null, 403, 'Akun Anda belum memiliki role.');
        abort_if($required !== null && $active !== $required, 403);

        return $next($request);
    }
}
