<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->travelTo('2026-09-28 10:00:00'); // Senin
});

/**
 * Dua rombel. Guru A mengajar Matematika VII-A (wali kelas VII-A); guru B mengajar IPA VIII-B.
 * Orang tua memiliki Ani (VII-A) dan Budi (VIII-B).
 *
 * @return array<string, mixed>
 */
function dashboardScenario(): array
{
    $year = activeAcademicYear();
    Semester::query()->where('academic_year_id', $year->id)->orderBy('start_date')->firstOrFail()->update(['is_active' => true]);
    app(ActivePeriod::class)->flush();
    $teacherUserA = User::factory()->withRole(Role::Teacher)->create();
    $teacherA = Teacher::factory()->withAccount($teacherUserA)->create(['name' => 'Bu Rina']);
    $teacherUserB = User::factory()->withRole(Role::Teacher)->create();
    $teacherB = Teacher::factory()->withAccount($teacherUserB)->create(['name' => 'Pak Dedi']);

    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'grade' => 7, 'homeroom_teacher_id' => $teacherA->id]);
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B', 'grade' => 8]);

    $parent = User::factory()->withRole(Role::Guardian)->create();
    $guardian = Guardian::factory()->withAccount($parent)->create();
    $ani = Student::factory()->withGuardian($guardian)->create(['name' => 'Ani']);
    $budi = Student::factory()->withGuardian($guardian)->create(['name' => 'Budi']);
    $citra = Student::factory()->create(['name' => 'Citra']); // tanpa orang tua
    enrollIn($classA, $ani);
    enrollIn($classA, $citra);
    enrollIn($classB, $budi);

    $math = assignTeaching($classA, $teacherA, Subject::factory()->create(['name' => 'Matematika']));
    $science = assignTeaching($classB, $teacherB, Subject::factory()->create(['name' => 'IPA']));
    scheduleAt($math, 1, '07:30', '08:10');
    scheduleAt($science, 1, '09:00', '09:40');

    return compact('year', 'teacherUserA', 'teacherUserB', 'teacherA', 'teacherB', 'classA', 'classB', 'parent', 'ani', 'budi', 'citra', 'math', 'science');
}

function dashboardSemester(): Semester
{
    return Semester::query()->where('is_active', true)->firstOrFail();
}

function dashboardInvoice(Student $student, int $total, int $paid = 0, string $due = '2026-10-10'): Invoice
{
    $fee = FeeType::query()->firstOrCreate(['name' => 'SPP'], ['default_amount' => 150000, 'frequency' => FeeFrequency::Monthly]);

    return Invoice::query()->create([
        'number' => 'INV-'.uniqid(),
        'academic_year_id' => app(ActivePeriod::class)->academicYearId(),
        'student_id' => $student->id,
        'fee_type_id' => $fee->id,
        'period' => '2026-09',
        'period_label' => 'September 2026',
        'due_date' => $due,
        'total' => $total,
        'paid_amount' => $paid,
        'status' => $paid >= $total ? InvoiceStatus::Paid : InvoiceStatus::Unpaid,
    ]);
}

function gradeComponent(TeachingAssignment $assignment, int $weight, array $scores = []): GradeComponent
{
    $component = GradeComponent::query()->create([
        'academic_year_id' => $assignment->academic_year_id,
        'semester_id' => dashboardSemester()->id,
        'teaching_assignment_id' => $assignment->id,
        'type' => ComponentType::cases()[0],
        'name' => 'Komponen '.$weight,
        'weight' => $weight,
        'position' => 1,
    ]);

    foreach ($scores as $studentId => $score) {
        Grade::query()->create(['grade_component_id' => $component->id, 'student_id' => $studentId, 'score' => $score]);
    }

    return $component;
}

describe('akses', function () {
    it('setiap dashboard hanya untuk role-nya', function (string $url, Role $allowed) {
        foreach ([Role::Principal, Role::Teacher, Role::Operator, Role::Guardian] as $role) {
            $this->actingAsRole($role);
            $response = $this->get($url);
            $role === $allowed ? $response->assertOk() : $response->assertForbidden();
        }
    })->with([
        ['/kepala-sekolah/dashboard', Role::Principal],
        ['/guru/dashboard', Role::Teacher],
        ['/operator/dashboard', Role::Operator],
        ['/orang-tua/dashboard', Role::Guardian],
    ]);
});

describe('kepala sekolah', function () {
    it('menampilkan KPI, tren kehadiran, distribusi nilai, dan raport menunggu persetujuan', function () {
        $s = dashboardScenario();
        recordAttendance($s['classA'], $s['ani'], 'HADIR', '2026-09-28');
        recordAttendance($s['classA'], $s['citra'], 'SAKIT', '2026-09-28');
        recordAttendance($s['classA'], $s['ani'], 'ALPA', '2026-09-25');
        dashboardInvoice($s['ani'], 200000, 200000);
        dashboardInvoice($s['budi'], 200000, 0);

        // Nilai akhir lengkap (bobot 100): Ani 90 (A), Citra 70 (C).
        gradeComponent($s['math'], 100, [$s['ani']->id => 90, $s['citra']->id => 70]);

        $semester = dashboardSemester();
        foreach ([ReportCardStatus::Validated, ReportCardStatus::Validated, ReportCardStatus::Published] as $i => $status) {
            ReportCard::query()->create([
                'academic_year_id' => $s['year']->id, 'semester_id' => $semester->id, 'classroom_id' => $s['classA']->id,
                'student_id' => [$s['ani'], $s['citra'], $s['budi']][$i]->id, 'status' => $status,
            ]);
        }

        $this->actingAsRole(Role::Principal);
        $this->get('/kepala-sekolah/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard::Principal/Index')
            ->where('kpi.students', 3)
            ->where('kpi.teachers', 2)
            ->where('kpi.attendance.recorded', 2)
            ->where('kpi.attendance.unrecorded', 1)
            ->where('kpi.attendance.percentage', 50)
            ->where('kpi.attendance.counts.SAKIT', 1)
            ->where('kpi.finance.billed', 400000)
            ->where('kpi.finance.paid', 200000)
            ->where('kpi.finance.percentage', 50)
            ->where('kpi.ppdb', null)
            ->has('attendanceTrend', 2)
            ->where('attendanceTrend.0.date', '2026-09-25')
            ->where('attendanceTrend.0.percentage', 0)
            ->where('grades.graded', 2)
            ->where('grades.distribution.A', 1)
            ->where('grades.distribution.C', 1)
            ->where('reportCards.awaiting', 2)
            ->where('reportCards.published', 1)
            ->has('recentActivity'));
    });

    it('meng-cache KPI selama 5 menit', function () {
        dashboardScenario();
        $this->actingAsRole(Role::Principal);
        $this->get('/kepala-sekolah/dashboard')->assertInertia(fn (Assert $page) => $page->where('kpi.students', 3));

        Student::factory()->create();
        $this->get('/kepala-sekolah/dashboard')->assertInertia(fn (Assert $page) => $page->where('kpi.students', 3));

        $this->travel(6)->minutes();
        $this->get('/kepala-sekolah/dashboard')->assertInertia(fn (Assert $page) => $page->where('kpi.students', 4));
    });
});

describe('operator', function () {
    it('menampilkan data belum lengkap dan antrean kerja', function () {
        $s = dashboardScenario();
        Student::factory()->create(['name' => 'Tanpa Rombel']);
        ReportCard::query()->create([
            'academic_year_id' => $s['year']->id, 'semester_id' => dashboardSemester()->id, 'classroom_id' => $s['classA']->id,
            'student_id' => $s['ani']->id, 'status' => ReportCardStatus::HomeroomReviewed,
        ]);

        $this->actingAsRole(Role::Operator);
        $this->get('/operator/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard::Operator/Index')
            ->where('counts.students', 4)
            ->where('counts.teachers', 2)
            ->where('incomplete.students_without_guardian', 2)
            ->where('incomplete.students_without_classroom', 1)
            ->where('queues.report_cards_to_validate', 1)
            ->where('queues.ppdb_to_verify', 0)
            ->where('queues.payments_to_verify', 0)
            ->where('queues.content_to_review.review', 0)
            ->has('finance.outstanding'));
    });

    it('menyembunyikan antrean yang izinnya dicabut', function () {
        dashboardScenario();
        $operator = User::factory()->withRole(Role::Operator)->create();
        $operator->roles->each->revokePermissionTo('finance.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAsRole(Role::Operator, $operator->fresh());
        $this->get('/operator/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('queues.payments_to_verify', null)
            ->where('finance', null));
    });
});

describe('guru', function () {
    it('hanya menampilkan jadwal, presensi, dan nilai miliknya', function () {
        $s = dashboardScenario();
        gradeComponent($s['math'], 60, [$s['ani']->id => 80]); // bobot belum 100, Citra belum dinilai
        gradeComponent($s['science'], 100, []);

        $this->actingAsRole(Role::Teacher, $s['teacherUserA']);
        $this->get('/guru/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard::Teacher/Index')
            ->where('hasTeacherProfile', true)
            ->has('schedule', 1)
            ->where('schedule.0.subject', 'Matematika')
            ->where('schedule.0.recorded', false)
            ->has('attendancePending', 1)
            ->where('attendancePending.0.label', 'VII-A')
            ->where('studentsCount', 2)
            ->has('gradesIncomplete', 1)
            ->where('gradesIncomplete.0.label', 'VII-A · Matematika')
            ->where('gradesIncomplete.0.weight', 60)
            ->where('gradesIncomplete.0.missing', 1)
            ->where('reportTasks.unconfirmed', []));

        recordAttendance($s['classA'], $s['ani'], 'HADIR', '2026-09-28');

        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('attendancePending', 0)
            ->where('schedule.0.recorded', true));
    });

    it('menampilkan tugas raport: konfirmasi mapel & review wali kelas', function () {
        $s = dashboardScenario();
        foreach ([$s['ani'], $s['citra']] as $student) {
            ReportCard::query()->create([
                'academic_year_id' => $s['year']->id, 'semester_id' => dashboardSemester()->id, 'classroom_id' => $s['classA']->id,
                'student_id' => $student->id, 'status' => ReportCardStatus::Submitted,
            ]);
        }

        $this->actingAsRole(Role::Teacher, $s['teacherUserA']);
        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('reportTasks.unconfirmed', 1)
            ->where('reportTasks.to_review', 2));

        $this->actingAsRole(Role::Teacher, $s['teacherUserB']);
        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('reportTasks.unconfirmed', 0)
            ->where('reportTasks.to_review', 0));
    });

    it('tetap tampil untuk akun guru tanpa data guru', function () {
        activeAcademicYear();
        $this->actingAsRole(Role::Teacher);
        $this->get('/guru/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('hasTeacherProfile', false)
            ->has('schedule', 0));
    });
});

describe('orang tua', function () {
    it('menampilkan kartu per anak dan tagihan anak aktif saja', function () {
        $s = dashboardScenario();
        recordAttendance($s['classB'], $s['budi'], 'IZIN', '2026-09-28');
        dashboardInvoice($s['ani'], 150000, 0, '2026-09-20');
        dashboardInvoice($s['budi'], 75000);
        dashboardInvoice($s['citra'], 99000);

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->get('/orang-tua/dashboard?anak='.$s['ani']->public_id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('childCards', 2)
            ->where('childCards.0.name', 'Ani')
            ->where('childCards.0.active', true)
            ->where('childCards.0.outstanding', 150000)
            ->where('childCards.1.name', 'Budi')
            ->where('childCards.1.attendance.status', 'IZIN')
            ->where('childCards.1.outstanding', 75000)
            ->has('invoices', 1)
            ->where('invoices.0.remaining', 150000)
            ->where('invoices.0.overdue', true));
    });
});
