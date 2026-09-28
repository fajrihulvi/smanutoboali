<?php

namespace App\Modules\Attendance\Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\Attendance\Models\TeacherAttendance;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Presensi harian demo 15 hari sekolah terakhir (tidak termasuk hari ini, agar guru bisa mencoba mengisi).
 * Mayoritas HADIR; Aisyah (anak ortu@) punya satu hari SAKIT dan satu ALPA. Juga presensi guru (S14).
 */
class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        app(ActivePeriod::class)->flush();
        $yearId = app(ActivePeriod::class)->academicYearId();
        $recorder = User::query()->where('email', 'guru@skoolia.test')->value('id');

        if ($yearId === null || AttendanceSession::query()->exists()) {
            return;
        }

        $classrooms = Classroom::query()
            ->where('academic_year_id', $yearId)
            ->with(['enrollments' => fn ($q) => $q->where('status', EnrollmentStatus::Active)->with('student:id,name')])
            ->get();

        $dates = [];

        for ($date = CarbonImmutable::yesterday(); count($dates) < 15; $date = $date->subDay()) {
            if (! $date->isSunday()) {
                $dates[] = $date;
            }
        }

        mt_srand(2026);
        $this->seedTeachers($dates, $yearId);

        foreach (array_reverse($dates) as $n => $date) {
            $semester = Semester::query()
                ->where('academic_year_id', $yearId)
                ->whereDate('start_date', '<=', $date->toDateString())
                ->whereDate('end_date', '>=', $date->toDateString())
                ->first();

            if ($semester === null) {
                continue;
            }

            foreach ($classrooms as $classroom) {
                $session = AttendanceSession::query()->create([
                    'academic_year_id' => $yearId,
                    'semester_id' => $semester->id,
                    'classroom_id' => $classroom->id,
                    'date' => $date->toDateString(),
                    'recorded_by' => $recorder,
                ]);

                foreach ($classroom->enrollments as $enrollment) {
                    [$status, $note] = $this->statusFor($enrollment->student->name, $n);

                    AttendanceRecord::query()->create([
                        'attendance_session_id' => $session->id,
                        'student_id' => $enrollment->student_id,
                        'status' => $status,
                        'note' => $note,
                    ]);
                }
            }
        }
    }

    /**
     * @return array{AttendanceStatus, string|null}
     */
    private function statusFor(string $name, int $dayIndex): array
    {
        if ($name === 'Aisyah Putri Hidayat') {
            return match ($dayIndex) {
                10 => [AttendanceStatus::Sick, 'Demam, surat dari orang tua'],
                13 => [AttendanceStatus::Absent, null],
                default => [AttendanceStatus::Present, null],
            };
        }

        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 90 => [AttendanceStatus::Present, null],
            $roll <= 94 => [AttendanceStatus::Late, null],
            $roll <= 96 => [AttendanceStatus::Permit, 'Acara keluarga'],
            $roll <= 98 => [AttendanceStatus::Sick, null],
            default => [AttendanceStatus::Absent, null],
        };
    }

    /**
     * Presensi guru: masuk 06:40–07:20 (lewat 07:15 = terlambat), pulang 13:30–14:30, sesekali izin/sakit/dinas.
     *
     * @param  list<CarbonImmutable>  $dates
     */
    private function seedTeachers(array $dates, int $yearId): void
    {
        $teachers = Teacher::query()->where('is_active', true)->pluck('id');

        foreach ($dates as $date) {
            foreach ($teachers as $teacherId) {
                $roll = mt_rand(1, 100);
                $status = match (true) {
                    $roll <= 3 => TeacherAttendanceStatus::Sick,
                    $roll <= 6 => TeacherAttendanceStatus::Permit,
                    $roll <= 8 => TeacherAttendanceStatus::Duty,
                    default => null,
                };

                if ($status !== null) {
                    TeacherAttendance::query()->create([
                        'academic_year_id' => $yearId, 'teacher_id' => $teacherId, 'date' => $date->toDateString(),
                        'status' => $status, 'source' => TeacherAttendance::SOURCE_OPERATOR,
                        'note' => $status === TeacherAttendanceStatus::Duty ? 'Rapat MGMP' : null,
                    ]);

                    continue;
                }

                $checkIn = $date->setTime(6, 40)->addMinutes(mt_rand(0, 40));

                TeacherAttendance::query()->create([
                    'academic_year_id' => $yearId, 'teacher_id' => $teacherId, 'date' => $date->toDateString(),
                    'check_in_at' => $checkIn,
                    'check_out_at' => $date->setTime(13, 30)->addMinutes(mt_rand(0, 60)),
                    'status' => $checkIn->isAfter($date->setTime(7, 15)) ? TeacherAttendanceStatus::Late : TeacherAttendanceStatus::Present,
                    'source' => TeacherAttendance::SOURCE_SELF,
                ]);
            }
        }
    }
}
