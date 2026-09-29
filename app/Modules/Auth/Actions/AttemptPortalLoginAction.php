<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Auth\Services\AuthLog;
use App\Modules\Core\Enums\Role;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login ke portal tertentu: kredensial benar DAN user memiliki role portal.
 */
class AttemptPortalLoginAction
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(private ActiveRoleService $activeRole) {}

    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     *
     * @throws ValidationException
     */
    public function handle(Request $request, Role $portal, array $credentials): User
    {
        $key = $this->throttleKey($credentials['email'], (string) $request->ip());

        $this->ensureIsNotRateLimited($key);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        $valid = $user !== null
            && Hash::check($credentials['password'], $user->getAuthPassword())
            && $user->hasRoleEnum($portal);

        if (! $valid) {
            RateLimiter::hit($key);
            AuthLog::record('login_failed', ['email' => Str::lower($credentials['email']), 'portal' => $portal->value, 'reason' => 'invalid_credentials'], 'warning');

            // Pesan generik agar keberadaan akun/role tidak bocor.
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (! $user->is_active) {
            RateLimiter::hit($key);
            AuthLog::record('login_failed', ['email' => $user->email, 'user_id' => $user->id, 'portal' => $portal->value, 'reason' => 'inactive'], 'warning');

            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan. Hubungi operator sekolah.',
            ]);
        }

        RateLimiter::clear($key);

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();
        $this->activeRole->set($request->session(), $portal);

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $user;
    }

    public function throttleKey(string $email, string $ip): string
    {
        return 'login:'.Str::transliterate(Str::lower($email)).'|'.$ip;
    }

    private function ensureIsNotRateLimited(string $key): void
    {
        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }
}
