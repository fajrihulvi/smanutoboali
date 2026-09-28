<?php

namespace App\Modules\Ppdb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Actions\AttemptPortalLoginAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\Ppdb\Actions\RegisterApplicantAction;
use App\Modules\Ppdb\Http\Requests\RegisterApplicantRequest;
use App\Modules\Ppdb\Notifications\VerifyApplicantEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Akun calon orang tua: daftar, masuk (/ppdb/masuk), verifikasi email.
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

        return redirect()->route('applicant.verification.notice');
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

    public function notice(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return redirect()->route('applicant.dashboard');
        }

        return Inertia::render('Ppdb::Auth/VerifyNotice', ['email' => $user->email]);
    }

    public function resend(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at === null) {
            $user->notify(new VerifyApplicantEmailNotification);
        }

        return back()->with('success', 'Tautan verifikasi dikirim ulang. Periksa kotak masuk/spam email Anda.');
    }

    /**
     * GET /ppdb/verifikasi/{user}/{hash} — signed; tidak wajib login (bisa dibuka dari perangkat lain).
     */
    public function verify(Request $request, User $user, string $hash): RedirectResponse
    {
        abort_unless(hash_equals(sha1($user->email), $hash) && $user->hasRoleEnum(Role::Applicant), 403);

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $request->user()?->is($user)
            ? redirect()->route('applicant.dashboard')->with('success', 'Email terverifikasi. Silakan daftarkan calon siswa.')
            : redirect('/ppdb/masuk')->with('success', 'Email terverifikasi. Silakan masuk.');
    }
}
