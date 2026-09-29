<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Services\GuardianChildren;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Models\PortfolioCategory;
use App\Modules\School\Models\Semester;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * Orang tua dengan dua anak (Ani di VII-A, Budi di VIII-B) + satu anak orang lain.
 *
 * @return array<string, mixed>
 */
function portalScenario(): array
{
    $year = activeAcademicYear();
    $homeroom = Teacher::factory()->withAccount()->create(['name' => 'Bu Rina']);
    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'homeroom_teacher_id' => $homeroom->id]);
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B']);

    $parent = User::factory()->withRole(Role::Guardian)->create();
    $guardian = Guardian::factory()->withAccount($parent)->create();
    $ani = Student::factory()->withGuardian($guardian)->create(['name' => 'Ani']);
    $budi = Student::factory()->withGuardian($guardian)->create(['name' => 'Budi']);
    enrollIn($classA, $ani);
    enrollIn($classB, $budi);

    $otherParent = User::factory()->withRole(Role::Guardian)->create();
    $stranger = Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create();
    enrollIn($classA, $stranger);

    return compact('year', 'classA', 'classB', 'parent', 'ani', 'budi', 'otherParent', 'stranger');
}

describe('anak aktif global (session)', function () {
    it('memilih anak berlaku di semua halaman dan kembali ke halaman asal tanpa ?anak', function () {
        ['parent' => $parent, 'ani' => $ani, 'budi' => $budi] = portalScenario();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get('/orang-tua/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('guardian.activeChild', $ani->public_id)
            ->has('guardian.childOptions', 2)
            ->where('child.name', 'Ani'));

        $this->post('/orang-tua/anak-aktif', ['anak' => $budi->public_id, 'kembali' => "/orang-tua/presensi?bulan=2026-09&anak={$ani->public_id}"])
            ->assertRedirect('/orang-tua/presensi?bulan=2026-09')
            ->assertSessionHas(GuardianChildren::SESSION_KEY, $budi->public_id);

        $this->get('/orang-tua/presensi')->assertInertia(fn (Assert $page) => $page->where('activeChild', $budi->public_id)->where('guardian.activeChild', $budi->public_id));
        $this->get('/orang-tua/dashboard')->assertInertia(fn (Assert $page) => $page->where('child.name', 'Budi'));

        // ?anak= tetap didukung dan memperbarui session.
        $this->get("/orang-tua/nilai?anak={$ani->public_id}")->assertOk();
        $this->get('/orang-tua/dashboard')->assertInertia(fn (Assert $page) => $page->where('child.name', 'Ani'));
    });

    it('menolak anak orang lain dan tujuan kembali di luar portal', function () {
        ['parent' => $parent, 'ani' => $ani, 'stranger' => $stranger] = portalScenario();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->post('/orang-tua/anak-aktif', ['anak' => $stranger->public_id])->assertForbidden();
        $this->post('/orang-tua/anak-aktif', ['anak' => $ani->public_id, 'kembali' => 'https://evil.test/orang-tua/x'])->assertRedirect('/orang-tua/dashboard');
        $this->post('/orang-tua/anak-aktif', ['anak' => $ani->public_id, 'kembali' => '//evil.test/orang-tua/'])->assertRedirect('/orang-tua/dashboard');
        $this->post('/orang-tua/anak-aktif', ['anak' => $ani->public_id, 'kembali' => '/guru/dashboard'])->assertRedirect('/orang-tua/dashboard');
    });

    it('prop guardian hanya untuk portal orang tua', function () {
        $this->actingAsRole(Role::Teacher);

        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page->where('guardian', null));
    });
});

describe('beranda orang tua', function () {
    it('ringkasan anak aktif: rombel, kehadiran hari ini, portofolio, kondisi anak', function () {
        ['parent' => $parent, 'ani' => $ani, 'classA' => $classA] = portalScenario();
        recordAttendance($classA, $ani, 'SAKIT', '2026-09-28');
        Portfolio::query()->create([
            'category_id' => PortfolioCategory::query()->value('id'), 'title' => 'Maket', 'date' => '2026-09-20',
            'visibility' => 'PUBLISHED', 'published_at' => now(),
        ])->students()->attach($ani->id);
        ChildCondition::query()->create([
            'student_id' => $ani->id, 'guardian_user_id' => $parent->id, 'type' => 'SAKIT', 'description' => 'Demam',
            'valid_from' => '2026-09-28', 'status' => ConditionStatus::Received,
        ]);
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get('/orang-tua/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard::Guardian/Index')
            ->where('child.classroom', 'VII-A')
            ->where('child.homeroom', 'Bu Rina')
            ->where('attendanceToday.label', 'Sakit')
            ->where('latestPortfolio.title', 'Maket')
            ->where('latestCondition.status', 'Diterima guru')
            ->has('announcements', 0));
    });
});

describe('timeline anak', function () {
    it('menggabungkan presensi non-hadir, portofolio, dan laporan kondisi milik sendiri per bulan', function () {
        ['parent' => $parent, 'otherParent' => $otherParent, 'ani' => $ani, 'budi' => $budi, 'classA' => $classA] = portalScenario();
        recordAttendance($classA, $ani, 'ALPA', '2026-09-10');
        recordAttendance($classA, $ani, 'HADIR', '2026-09-11');
        recordAttendance($classA, $ani, 'IZIN', '2026-08-20');
        Portfolio::query()->create([
            'category_id' => PortfolioCategory::query()->value('id'), 'title' => 'Poster', 'date' => '2026-09-15',
            'visibility' => 'PUBLISHED', 'published_at' => now(),
        ])->students()->attach($ani->id);
        Portfolio::query()->create([
            'category_id' => PortfolioCategory::query()->value('id'), 'title' => 'Draf', 'date' => '2026-09-16', 'visibility' => 'DRAFT',
        ])->students()->attach($ani->id);
        ChildCondition::query()->create(['student_id' => $ani->id, 'guardian_user_id' => $parent->id, 'type' => 'EMOSIONAL', 'description' => 'x', 'valid_from' => '2026-09-28']);
        // Laporan orang tua lain untuk anak yang sama tidak boleh tampil.
        ChildCondition::query()->create(['student_id' => $ani->id, 'guardian_user_id' => $otherParent->id, 'type' => 'SAKIT', 'description' => 'y', 'valid_from' => '2026-09-28']);
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get("/orang-tua/timeline?anak={$ani->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard::Guardian/Timeline')
            ->where('month', '2026-09')
            ->has('entries', 3)
            ->where('entries.0.kind', 'condition')
            ->where('entries.1.title', 'Poster')
            ->where('entries.2.title', 'Presensi: Alpa'));

        $this->get('/orang-tua/timeline?bulan=2026-08')->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.title', 'Presensi: Izin'));

        $this->get("/orang-tua/timeline?anak={$budi->public_id}")->assertInertia(fn (Assert $page) => $page->has('entries', 0));
    });
});

it('nilai komponen sama yang terbit di hari sama digabung menjadi satu entri', function () {
    ['parent' => $parent, 'ani' => $ani, 'classA' => $classA] = portalScenario();
    $semesterId = Semester::query()->where('academic_year_id', $classA->academic_year_id)->value('id');

    foreach ([['Matematika', 80], ['IPA', 92.5]] as [$subject, $score]) {
        $assignment = assignTeaching($classA, Teacher::factory()->create(), Subject::factory()->create(['name' => $subject]));
        $component = GradeComponent::query()->create([
            'academic_year_id' => $classA->academic_year_id, 'semester_id' => $semesterId, 'teaching_assignment_id' => $assignment->id,
            'type' => 'UH', 'name' => 'UH 1', 'weight' => 100, 'position' => 1, 'published_at' => '2026-09-22 09:00:00',
        ]);
        Grade::query()->create(['grade_component_id' => $component->id, 'student_id' => $ani->id, 'score' => $score]);
    }
    $this->actingAsRole(Role::Guardian, $parent);

    $this->get('/orang-tua/timeline')->assertInertia(fn (Assert $page) => $page
        ->has('entries', 1)
        ->where('entries.0.title', 'Nilai UH 1 terbit (2 mapel)')
        ->where('entries.0.detail', 'IPA 92,5 · Matematika 80'));
});
