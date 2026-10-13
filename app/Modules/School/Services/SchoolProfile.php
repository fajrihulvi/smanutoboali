<?php

namespace App\Modules\School\Services;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\School\Enums\SchoolLevel;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Identitas sekolah untuk tampilan (shared props Inertia, kop PDF, email), di-cache.
 */
class SchoolProfile
{
    public const CACHE_KEY = 'skoolia.school_profile';

    public function __construct(private FileUrlGenerator $urls) {}

    /**
     * @return array{name: string, level: string, logo_url: string}
     */
    public function toArray(): array
    {
        $profile = $this->cached();

        // Logo bawaan di luar cache agar URL-nya selalu mengikuti APP_URL.
        return [...$profile, 'logo_url' => $profile['logo_url'] ?? SchoolLogo::defaultUrl()];
    }

    /**
     * @return array{name: string, level: string, logo_url: string|null}
     */
    private function cached(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                $school = School::current();

                return [
                    'name' => $school->name,
                    'level' => $school->level->value,
                    'logo_url' => $school->logo ? $this->urls->url($school->logo) : null,
                ];
            });
        } catch (Throwable) {
            // Tabel belum dimigrasi (instalasi awal).
            return ['name' => (string) config('app.name'), 'level' => 'SMP', 'logo_url' => null];
        }
    }

    public function name(): string
    {
        return $this->toArray()['name'];
    }

    /**
     * Jenjang sekolah (dari cache profil; aman dipanggil per baris resource).
     */
    public function level(): SchoolLevel
    {
        return SchoolLevel::tryFrom($this->toArray()['level']) ?? SchoolLevel::Smp;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
