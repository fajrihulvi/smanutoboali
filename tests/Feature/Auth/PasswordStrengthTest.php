<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Support\PasswordStrength;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Aturan password produksi: staf min. 12 (huruf besar-kecil, angka, simbol);
 * orang tua & calon pendaftar cukup min. 8 (huruf & angka).
 */
beforeEach(function () {
    // Aturan staf versi produksi (tanpa cek kebocoran agar test tidak butuh internet).
    // Environment tidak diubah ke production: CSRF hanya dilewati saat testing.
    Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());
});

function passwordPasses(Password $rule, string $password): bool
{
    return Validator::make(['password' => $password], ['password' => [$rule]])->passes();
}

it('orang tua & calon pendaftar cukup 8 karakter berisi huruf dan angka', function (array $roles) {
    $rule = PasswordStrength::forRoles($roles);

    expect(passwordPasses($rule, 'rahasia12'))->toBeTrue()
        ->and(passwordPasses($rule, 'abc1234'))->toBeFalse()      // 7 karakter
        ->and(passwordPasses($rule, 'rahasiasaja'))->toBeFalse()  // tanpa angka
        ->and(passwordPasses($rule, '12345678'))->toBeFalse();    // tanpa huruf
})->with([
    'orang tua' => [[Role::Guardian]],
    'calon pendaftar' => [[Role::Applicant]],
    'orang tua + calon pendaftar' => [['guardian', 'applicant']],
]);

it('akun dengan role staf tetap memakai aturan 12 karakter', function (array $roles) {
    $rule = PasswordStrength::forRoles($roles);

    expect(passwordPasses($rule, 'rahasia12'))->toBeFalse()
        ->and(passwordPasses($rule, 'Rahasia#Kuat2026'))->toBeTrue();
})->with([
    'guru' => [[Role::Teacher]],
    'operator' => [[Role::Operator]],
    'kepala sekolah' => [[Role::Principal]],
    'guru yang juga orang tua' => [[Role::Teacher, Role::Guardian]],
    'tanpa role' => [[]],
]);

it('orang tua mengganti password sendiri dengan 8 karakter; guru ditolak', function () {
    $this->actingAsRole(Role::Guardian);

    $this->put('/settings/password', [
        'current_password' => 'password',
        'password' => 'rahasia12',
        'password_confirmation' => 'rahasia12',
    ])->assertSessionHasNoErrors();

    $this->actingAsRole(Role::Teacher);

    $this->put('/settings/password', [
        'current_password' => 'password',
        'password' => 'rahasia12',
        'password_confirmation' => 'rahasia12',
    ])->assertSessionHasErrors('password');
});

it('lupa password: reset akun orang tua cukup 8 karakter', function () {
    $user = User::factory()->withRole(Role::Guardian)->create();

    $this->post('/reset-password', [
        'token' => PasswordBroker::createToken($user),
        'email' => $user->email,
        'password' => 'rahasia12',
        'password_confirmation' => 'rahasia12',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('rahasia12', $user->fresh()->password))->toBeTrue();
});

it('operator membuat & mereset akun orang tua dengan password 8 karakter, akun guru tetap 12', function () {
    $guardian = User::factory()->withRole(Role::Guardian)->create();
    $teacher = User::factory()->withRole(Role::Teacher)->create();
    $this->actingAsRole(Role::Operator);
    $eight = ['password' => 'rahasia12', 'password_confirmation' => 'rahasia12'];

    $this->put("/operator/users/{$guardian->public_id}/password", $eight)->assertSessionHasNoErrors();
    $this->put("/operator/users/{$teacher->public_id}/password", $eight)->assertSessionHasErrors('password');

    $this->post('/operator/users', [...$eight, 'name' => 'Wali Baru', 'email' => 'wali.baru@contoh.id', 'roles' => ['guardian'], 'is_active' => true])
        ->assertSessionHasNoErrors();
    $this->post('/operator/users', [...$eight, 'name' => 'Guru Baru', 'email' => 'guru.baru@contoh.id', 'roles' => ['teacher'], 'is_active' => true])
        ->assertSessionHasErrors('password');

    expect(Hash::check('rahasia12', $guardian->fresh()->password))->toBeTrue()
        ->and(User::query()->where('email', 'wali.baru@contoh.id')->exists())->toBeTrue();
});

it('di produksi password orang tua yang pernah bocor tetap ditolak', function () {
    app()->detectEnvironment(fn () => 'production');
    $hash = strtoupper(sha1('rahasia12'));
    // API range HaveIBeenPwned: kirim 5 karakter awal hash, balasan berisi sisa hash yang bocor.
    Http::fake(['api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).":42\r\n", 200)]);

    $rule = PasswordStrength::forRoles([Role::Guardian]);

    expect(passwordPasses($rule, 'rahasia12'))->toBeFalse()
        ->and(passwordPasses($rule, 'kebunjeruk7'))->toBeTrue();
});
