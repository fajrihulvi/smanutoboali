<?php

namespace App\Support;

use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Periode akademik aktif (tahun ajaran + semester). Id disimpan di cache
 * dan dibersihkan oleh Action aktivasi (flush()). Model dimuat sekali per request.
 */
class ActivePeriod
{
    public const CACHE_KEY = 'skoolia.active_period';

    /** @var array{year: int|null, semester: int|null}|null */
    private ?array $ids = null;

    private ?AcademicYear $year = null;

    private ?Semester $semester = null;

    private bool $loaded = false;

    public function academicYear(): ?AcademicYear
    {
        $this->load();

        return $this->year;
    }

    public function semester(): ?Semester
    {
        $this->load();

        return $this->semester;
    }

    public function academicYearId(): ?int
    {
        return $this->ids()['year'];
    }

    public function semesterId(): ?int
    {
        return $this->ids()['semester'];
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->ids = null;
        $this->year = null;
        $this->semester = null;
        $this->loaded = false;
    }

    /**
     * @return array{academic_year: array{public_id: string, name: string}, semester: array{public_id: string, type: string, label: string}|null}|null
     */
    public function toArray(): ?array
    {
        $year = $this->academicYear();

        if (! $year) {
            return null;
        }

        $semester = $this->semester();

        return [
            'academic_year' => ['public_id' => $year->public_id, 'name' => $year->name],
            'semester' => $semester ? [
                'public_id' => $semester->public_id,
                'type' => $semester->type->value,
                'label' => $semester->type->label(),
            ] : null,
        ];
    }

    /**
     * @return array{year: int|null, semester: int|null}
     */
    private function ids(): array
    {
        if ($this->ids !== null) {
            return $this->ids;
        }

        try {
            /** @var array{year: int|null, semester: int|null} $ids */
            $ids = Cache::rememberForever(self::CACHE_KEY, fn () => [
                'year' => AcademicYear::query()->where('is_active', true)->value('id'),
                'semester' => Semester::query()->where('is_active', true)->value('id'),
            ]);
        } catch (Throwable) {
            // Tabel belum dimigrasi: anggap belum ada periode aktif.
            return ['year' => null, 'semester' => null];
        }

        return $this->ids = $ids;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $ids = $this->ids();
        $this->year = $ids['year'] ? AcademicYear::query()->find($ids['year']) : null;
        $this->semester = $ids['semester'] ? Semester::query()->find($ids['semester']) : null;
        $this->loaded = true;
    }
}
