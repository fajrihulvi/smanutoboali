<?php

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use App\Support\ActivePeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Semua test Feature memakai TestCase Laravel + RefreshDatabase.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helper akademik (S11)
|--------------------------------------------------------------------------
*/

/**
 * Tahun ajaran aktif baru; cache periode aktif dibersihkan agar langsung terbaca.
 */
function activeAcademicYear(int $startYear = 2026): AcademicYear
{
    $year = AcademicYear::factory()->startingIn($startYear)->create(['is_active' => true]);
    app(ActivePeriod::class)->flush();

    return $year;
}

/**
 * Masukkan siswa ke rombel secara langsung (tanpa action) untuk menyiapkan skenario.
 */
function enrollIn(Classroom $classroom, Student $student, EnrollmentStatus $status = EnrollmentStatus::Active): ClassEnrollment
{
    return ClassEnrollment::query()->create([
        'student_id' => $student->getKey(),
        'classroom_id' => $classroom->getKey(),
        'academic_year_id' => $classroom->academic_year_id,
        'status' => $status,
    ]);
}

/**
 * Tugaskan guru mengajar mapel (baru) di rombel.
 */
function assignTeaching(Classroom $classroom, Teacher $teacher, ?Subject $subject = null): TeachingAssignment
{
    return TeachingAssignment::query()->create([
        'classroom_id' => $classroom->getKey(),
        'subject_id' => ($subject ?? Subject::factory()->create())->getKey(),
        'teacher_id' => $teacher->getKey(),
        'academic_year_id' => $classroom->academic_year_id,
    ]);
}

/**
 * Slot jadwal langsung (tanpa action) untuk menyiapkan skenario.
 */
function scheduleAt(TeachingAssignment $assignment, int $day = 1, string $start = '07:30', string $end = '08:10'): Schedule
{
    return Schedule::query()->create([
        'academic_year_id' => $assignment->academic_year_id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => Weekday::from($day),
        'start_time' => $start.':00',
        'end_time' => $end.':00',
    ]);
}
