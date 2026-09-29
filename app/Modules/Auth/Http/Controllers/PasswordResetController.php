<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response
    {
        return Inertia::render('Auth::ForgotPassword');
    }

    /**
     * Kirim tautan reset. Respons selalu sama agar keberadaan email tidak bocor.
     */
    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink(['email' => $request->string('email')->lower()->value()]);

        return back()->with('success', 'Jika email tersebut terdaftar, tautan untuk mengatur ulang password telah dikirim.');
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Auth::ResetPassword', [
            'token' => $token,
            'email' => $request->string('email')->value(),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Semua sesi lama (mis. perangkat yang mungkin dicuri) dikeluarkan.
                app(UserSessionTerminator::class)->terminate($user);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Password berhasil diatur ulang. Silakan masuk kembali.');
    }
}
