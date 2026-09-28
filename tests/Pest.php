<?php

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\StudentDiscount;
use App\Modules\People\Models\Guardian;
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

/*
|--------------------------------------------------------------------------
| Helper chat (S20)
|--------------------------------------------------------------------------
*/

/**
 * Wali kelas VII-A (guru) + orang tua Ani (siswa VII-A), guru lain yang tidak mengajar Ani,
 * orang tua lain, dan operator.
 *
 * @return array{teacher: User, parent: User, child: Student, otherTeacher: User, otherParent: User, operator: User}
 */
function chatScenario(): array
{
    $year = activeAcademicYear();
    $teacher = User::factory()->withRole(Role::Teacher)->create(['name' => 'Bu Rina']);
    $classroom = Classroom::factory()->for($year)->create([
        'name' => 'VII-A',
        'homeroom_teacher_id' => Teacher::factory()->withAccount($teacher)->create()->id,
    ]);

    $parent = User::factory()->withRole(Role::Guardian)->create(['name' => 'Pak Budi']);
    $child = Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']);
    enrollIn($classroom, $child);

    $otherTeacher = User::factory()->withRole(Role::Teacher)->create(['name' => 'Pak Joko']);
    Teacher::factory()->withAccount($otherTeacher)->create();
    assignTeaching(Classroom::factory()->for($year)->create(['name' => 'VIII-B']), Teacher::query()->where('user_id', $otherTeacher->id)->sole());

    $otherParent = User::factory()->withRole(Role::Guardian)->create(['name' => 'Bu Sari']);
    enrollIn($classroom, Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create());

    $operator = User::factory()->withRole(Role::Operator)->create(['name' => 'Admin TU']);

    return compact('teacher', 'parent', 'child', 'otherTeacher', 'otherParent', 'operator');
}

function startChat(User $with, ?Student $student = null): string
{
    return test()->postJson('/chat/percakapan', ['user' => $with->public_id, 'student' => $student?->public_id])
        ->assertSuccessful()
        ->json('data.public_id');
}

function usePusherForChat(): void
{
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => '1',
        'broadcasting.connections.pusher.options.cluster' => 'ap1',
    ]);

    require base_path('routes/channels.php');
}

/*
|--------------------------------------------------------------------------
| Helper keuangan (S24)
|--------------------------------------------------------------------------
*/

/**
 * VII-A: Ani (punya akun orang tua) & Budi (beasiswa 50%); VIII-B: Citra (orang tua lain). SPP bulanan Rp150.000.
 *
 * @return array<string, mixed>
 */
function financeScenario(): array
{
    $year = activeAcademicYear();
    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'grade' => 7]);
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B', 'grade' => 8]);

    $parent = User::factory()->withRole(Role::Guardian)->create();
    $ani = Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']);
    $budi = Student::factory()->create(['name' => 'Budi']);
    $otherParent = User::factory()->withRole(Role::Guardian)->create();
    $citra = Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create(['name' => 'Citra']);
    enrollIn($classA, $ani);
    enrollIn($classA, $budi);
    enrollIn($classB, $citra);

    $spp = FeeType::query()->create(['name' => 'SPP', 'default_amount' => 150000, 'frequency' => FeeFrequency::Monthly]);
    StudentDiscount::query()->create(['student_id' => $budi->id, 'fee_type_id' => null, 'kind' => DiscountKind::Percent, 'value' => 50, 'reason' => 'Beasiswa prestasi']);

    return [
        'year' => $year, 'classA' => $classA, 'classB' => $classB, 'parent' => $parent, 'otherParent' => $otherParent,
        'ani' => $ani, 'budi' => $budi, 'citra' => $citra, 'spp' => $spp,
        'operator' => User::factory()->withRole(Role::Operator)->create(),
        'principal' => User::factory()->withRole(Role::Principal)->create(),
    ];
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function generatePayload(FeeType $fee, array $extra = []): array
{
    return ['fee_type' => $fee->public_id, 'month' => '2026-09', 'amount' => 150000, 'due_date' => '2026-10-10', 'scope' => 'SEMUA', ...$extra];
}

function invoiceFor(Student $student): Invoice
{
    return Invoice::query()->where('student_id', $student->id)->where('status', '!=', 'CANCELLED')->sole();
}
