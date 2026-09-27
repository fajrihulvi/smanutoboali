<?php

namespace App\Modules\School\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Enums\SemesterType;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class CreateAcademicYearAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, start_date: string, end_date: string}  $data
     */
    public function handle(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data) {
            $year = AcademicYear::query()->create([...$data, 'is_active' => false]);

            self::createSemesters($year);

            $this->audit->record('school', 'created', "Membuat tahun ajaran {$year->name}", $year, [
                'attributes' => $data,
            ]);

            return $year->load('semesters');
        });
    }

    /**
     * Dua semester default: GANJIL = mulai s.d. 31 Des, GENAP = 1 Jan s.d. selesai.
     */
    public static function createSemesters(AcademicYear $year): void
    {
        $firstYearEnd = $year->start_date->endOfYear()->startOfDay();

        $ganjilEnd = $firstYearEnd->lessThan($year->end_date) ? $firstYearEnd : $year->end_date;
        $genapStart = $ganjilEnd->addDay()->lessThanOrEqualTo($year->end_date) ? $ganjilEnd->addDay() : $year->end_date;

        $year->semesters()->createMany([
            ['type' => SemesterType::Ganjil, 'start_date' => $year->start_date, 'end_date' => $ganjilEnd, 'is_active' => false],
            ['type' => SemesterType::Genap, 'start_date' => $genapStart, 'end_date' => $year->end_date, 'is_active' => false],
        ]);
    }
}
