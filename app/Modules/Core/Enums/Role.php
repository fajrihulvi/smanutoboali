<?php

namespace App\Modules\Core\Enums;

/**
 * Role inti Skoolia (nama role spatie). Lihat tabel "Konvensi role" di CLAUDE.md.
 */
enum Role: string
{
    case Principal = 'principal';
    case Teacher = 'teacher';
    case Operator = 'operator';
    case Guardian = 'guardian';
    case Applicant = 'applicant';

    public function label(): string
    {
        return match ($this) {
            self::Principal => 'Kepala Sekolah',
            self::Teacher => 'Guru',
            self::Operator => 'Operator',
            self::Guardian => 'Orang Tua',
            self::Applicant => 'Calon Orang Tua',
        };
    }

    /**
     * Prefix URL portal, contoh 'guru' untuk /guru/*.
     */
    public function urlPrefix(): string
    {
        return match ($this) {
            self::Principal => 'kepala-sekolah',
            self::Teacher => 'guru',
            self::Operator => 'operator',
            self::Guardian => 'orang-tua',
            self::Applicant => 'ppdb/akun',
        };
    }

    /**
     * Prefix nama route, contoh 'teacher.' untuk teacher.dashboard.
     */
    public function routePrefix(): string
    {
        return $this->value.'.';
    }

    public function dashboardRoute(): string
    {
        return $this->routePrefix().'dashboard';
    }

    /**
     * Apakah role ini punya halaman login portal di /login/{slug}.
     * Applicant login lewat /ppdb/masuk (S26).
     */
    public function hasStaffPortal(): bool
    {
        return $this !== self::Applicant;
    }

    /**
     * Role dari slug portal login ('kepala-sekolah', 'guru', 'operator', 'orang-tua').
     */
    public static function fromPortalSlug(string $slug): ?self
    {
        foreach (self::cases() as $role) {
            if ($role->hasStaffPortal() && $role->urlPrefix() === $slug) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function portalSlugs(): array
    {
        return array_values(array_map(
            fn (self $role) => $role->urlPrefix(),
            array_filter(self::cases(), fn (self $role) => $role->hasStaffPortal()),
        ));
    }

    /**
     * @return array{code: string, label: string, prefix: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->value,
            'label' => $this->label(),
            'prefix' => $this->urlPrefix(),
        ];
    }
}
