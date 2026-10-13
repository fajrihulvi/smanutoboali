<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/security', [
            'mustChangePassword' => $user->must_change_password,
        ]);
    }

    /**
     * Ganti password sendiri: sesi di perangkat lain dikeluarkan, sesi ini dipertahankan
     * dengan ID baru (mencegah session fixation).
     */
    public function update(PasswordUpdateRequest $request, UserSessionTerminator $sessions, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $forced = $user->must_change_password;
        $user->update(['password' => $request->validated('password')]);

        $request->session()->regenerate();
        $sessions->terminate($user, $request->session()->getId());
        $audit->record('auth', 'password_changed', 'Mengganti password sendiri; sesi di perangkat lain dikeluarkan', $user);

        // Password awal sudah diganti → lanjut ke dashboard role aktif.
        if ($forced) {
            return redirect()->route('dashboard')->with('success', 'Password berhasil diganti. Selamat bekerja!');
        }

        return back()->with('success', 'Password berhasil diganti. Sesi di perangkat lain telah dikeluarkan.');
    }
}
