<?php

namespace App\Support;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Validation\Rules\Password;

/**
 * Aturan kekuatan password per jenis akun.
 *
 * - Akun yang HANYA orang tua / calon pendaftar PPDB: min. 8 karakter; di produksi juga
 *   wajib huruf & angka dan tidak boleh ada di daftar kebocoran publik.
 * - Akun dengan role staf (operator, kepala sekolah, guru) — termasuk guru yang juga
 *   orang tua: aturan bawaan Password::defaults() (produksi: min. 12, huruf besar-kecil,
 *   angka, simbol, tidak bocor).
 */
final class PasswordStrength
{
    /** Role yang boleh memakai aturan password ringan. */
    private const FAMILY_ROLES = [Role::Guardian, Role::Applicant];

    public static function forUser(?User $user): Password
    {
        return $user ? self::forRoles($user->coreRoles()) : Password::default();
    }

    /**
     * @param  iterable<Role|string|null>  $roles  enum atau kode role spatie
     */
    public static function forRoles(iterable $roles): Password
    {
        $resolved = [];

        foreach ($roles as $role) {
            $role = is_string($role) ? Role::tryFrom($role) : $role;

            if ($role instanceof Role) {
                $resolved[] = $role;
            }
        }

        $familyOnly = $resolved !== []
            && array_diff(array_map(fn (Role $r) => $r->value, $resolved), array_map(fn (Role $r) => $r->value, self::FAMILY_ROLES)) === [];

        return $familyOnly ? self::family() : Password::default();
    }

    /**
     * Seperti Password::defaults(): syarat penuh hanya di produksi, lokal/testing cukup min. 8.
     */
    private static function family(): Password
    {
        return app()->isProduction()
            ? Password::min(8)->letters()->numbers()->uncompromised()
            : Password::min(8);
    }
}
