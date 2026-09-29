<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/security');
    }

    /**
     * Ganti password sendiri: sesi di perangkat lain dikeluarkan, sesi ini dipertahankan
     * dengan ID baru (mencegah session fixation).
     */
    public function update(PasswordUpdateRequest $request, UserSessionTerminator $sessions, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        $request->session()->regenerate();
        $sessions->terminate($user, $request->session()->getId());
        $audit->record('auth', 'password_changed', 'Mengganti password sendiri; sesi di perangkat lain dikeluarkan', $user);

        return back()->with('success', 'Password berhasil diganti. Sesi di perangkat lain telah dikeluarkan.');
    }
}
