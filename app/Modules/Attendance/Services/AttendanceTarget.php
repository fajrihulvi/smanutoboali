<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Enums\AttendanceMode;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Validasi "apa yang dipresensi": rombel tahun aktif, tanggal (bukan masa depan/Minggu, di dalam semester),
 * dan jadwal (wajib & sesuai hari pada mode per mapel; tidak dipakai pada mode harian).
 */
class AttendanceTarget
{
    public function __construct(
        private ActivePeriod $period,
        private AttendanceSettings $settings,
    ) {}

    /**
     * @throws ValidationException
     */
    public function resolve(Classroom $classroom, CarbonImmutable $date, ?Schedule $schedule): Semester
    {
        if ($classroom->academic_year_id !== $this->period->academicYearId()) {
            throw ValidationException::withMessages(['date' => 'Presensi hanya untuk rombel tahun ajaran aktif.']);
        }

        if ($date->isAfter(today())) {
            throw ValidationException::withMessages(['date' => 'Presensi tidak bisa diisi untuk tanggal yang akan datang.']);
        }

        if ($date->isSunday()) {
            throw ValidationException::withMessages(['date' => 'Hari Minggu bukan hari sekolah.']);
        }

        $semester = Semester::query()
            ->where('academic_year_id', $classroom->academic_year_id)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();

        if ($semester === null) {
            throw ValidationException::withMessages(['date' => 'Tanggal di luar periode semester tahun ajaran aktif.']);
        }

        if ($this->settings->mode() === AttendanceMode::PerSubject) {
            if ($schedule === null) {
                throw ValidationException::withMessages(['schedule' => 'Pilih jadwal pelajaran yang dipresensi.']);
            }

            if ($schedule->teachingAssignment->classroom_id !== $classroom->id || $schedule->day_of_week->value !== $date->dayOfWeekIso) {
                throw ValidationException::withMessages(['schedule' => 'Jadwal tidak sesuai dengan rombel atau hari tersebut.']);
            }
        } elseif ($schedule !== null) {
            throw ValidationException::withMessages(['schedule' => 'Mode presensi harian tidak memakai jadwal.']);
        }

        return $semester;
    }
}
