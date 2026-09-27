<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Actions\AttemptPortalLoginAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Core\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Halaman pilihan portal (/login).
     */
    public function portals(): Response
    {
        return Inertia::render('Auth::PortalSelect');
    }

    public function create(string $portal): Response
    {
        $role = $this->portalRole($portal);

        return Inertia::render('Auth::Login', [
            'portal' => $role->value,
            'portalSlug' => $portal,
        ]);
    }

    public function store(LoginRequest $request, string $portal, AttemptPortalLoginAction $login): RedirectResponse
    {
        $role = $this->portalRole($portal);

        $login->handle($request, $role, $request->credentials());

        return redirect()->to($this->intendedUrl($request, $role));
    }

    private function portalRole(string $portal): Role
    {
        $role = Role::fromPortalSlug($portal);

        abort_if($role === null, 404);

        return $role;
    }

    /**
     * Pakai URL tujuan sebelum login hanya bila masih di portal yang sama.
     */
    private function intendedUrl(Request $request, Role $role): string
    {
        $intended = $request->session()->pull('url.intended');
        $portalBase = url($role->urlPrefix()).'/';

        if (is_string($intended) && str_starts_with($intended, $portalBase)) {
            return $intended;
        }

        return route($role->dashboardRoute());
    }
}
