<?php

namespace App\Modules\Ppdb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Actions\AttemptPortalLoginAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\Ppdb\Actions\RegisterApplicantAction;
use App\Modules\Ppdb\Http\Requests\RegisterApplicantRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Akun calon orang tua: daftar & masuk (/ppdb/masuk). Tanpa verifikasi email.
 */
class AccountController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Ppdb::Auth/Register');
    }

    public function store(RegisterApplicantRequest $request, RegisterApplicantAction $register, ActiveRoleService $roles): RedirectResponse
    {
        $user = $register->handle($request->payload());

        Auth::login($user);
        $request->session()->regenerate();
        $roles->set($request->session(), Role::Applicant);

        // Langsung ke dashboard dengan dialog "Daftarkan calon siswa" terbuka.
        return redirect()->route('applicant.dashboard', ['mulai' => 1])
            ->with('success', 'Akun berhasil dibuat. Silakan daftarkan calon siswa.');
    }

    public function login(): Response
    {
        return Inertia::render('Auth::Login', [
            'portal' => Role::Applicant->value,
            'portalSlug' => 'ppdb',
            'loginUrl' => '/ppdb/masuk',
            'registerUrl' => '/ppdb/daftar',
        ]);
    }

    public function attempt(LoginRequest $request, AttemptPortalLoginAction $login): RedirectResponse
    {
        $login->handle($request, Role::Applicant, $request->credentials());

        return redirect()->route('applicant.dashboard');
    }
}
