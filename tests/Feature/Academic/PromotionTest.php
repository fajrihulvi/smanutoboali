<?php

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Services\PromotionPlanner;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Modules\School\Enums\SchoolLevel;
use App\Modules\School\Models\AcademicYear;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * TA 2025/2026 dengan VII-A (2 siswa), VIII-A (1 siswa), IX-A (1 siswa); TA 2026/2027 kosong.
 *
 * @return array{from: AcademicYear, to: AcademicYear, seventh: Classroom, ninth: Classroom, graduating: Student}
 */
function promotionScenario(): array
{
    $from = AcademicYear::factory()->startingIn(2025)->create(['is_active' => true]);
    $to = AcademicYear::factory()->startingIn(2026)->create();

    $seventh = Classroom::factory()->for($from)->create(['name' => 'VII-A', 'grade' => 7]);
    $eighth = Classroom::factory()->for($from)->create(['name' => 'VIII-A', 'grade' => 8]);
    $ninth = Classroom::factory()->for($from)->create(['name' => 'IX-A', 'grade' => 9]);

    Student::factory()->count(2)->create()->each(fn (Student $s) => enrollIn($seventh, $s));
    enrollIn($eighth, Student::factory()->create());
    $graduating = Student::factory()->create();
    enrollIn($ninth, $graduating);

    return compact('from', 'to', 'seventh', 'ninth', 'graduating');
}

it('memetakan nama rombel ke tingkat berikutnya', function (string $name, int $grade, string $expected) {
    expect(PromotionPlanner::nextName($name, $grade, SchoolLevel::Smp))->toBe($expected);
})->with([
    ['VII-A', 7, 'VIII-A'],
    ['VIII B', 8, 'IX B'],
    ['7A', 7, '8A'],
    ['Unggulan', 7, 'VIII-Unggulan'],
]);

it('pratinjau menampilkan rencana tanpa id internal', function () {
    ['from' => $from, 'to' => $to] = promotionScenario();
    $this->actingAsRole(Role::Operator);

    $this->get("/operator/master-data/kelas/naik-kelas?dari={$from->public_id}&ke={$to->public_id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Operator/Promotion/Create')
            ->where('plan.promote', 3)
            ->where('plan.graduate', 1)
            ->has('plan.rows', 3)
            ->where('plan.rows.0.target', 'VIII-A')
            ->missing('plan.rows.0.source_id')
            ->where('plan.new_empty.0.name', 'VII-A'));
});

it('menaikkan siswa, membuat rombel tujuan, dan meluluskan tingkat akhir', function () {
    ['from' => $from, 'to' => $to, 'seventh' => $seventh, 'graduating' => $graduating] = promotionScenario();
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/master-data/kelas/naik-kelas', [
        'from' => $from->public_id,
        'to' => $to->public_id,
        'graduate_final_grade' => true,
    ])->assertRedirect("/operator/master-data/kelas?tahun={$to->public_id}")->assertSessionHasNoErrors();

    $targets = Classroom::query()->where('academic_year_id', $to->id)->orderBy('grade')->orderBy('name')->get();

    expect($targets->pluck('name')->all())->toBe(['VII-A', 'VIII-A', 'IX-A'])
        ->and($targets->firstWhere('name', 'VII-A')->activeEnrollments()->count())->toBe(0)
        ->and($targets->firstWhere('name', 'VIII-A')->activeEnrollments()->count())->toBe(2)
        ->and($targets->firstWhere('name', 'IX-A')->activeEnrollments()->count())->toBe(1)
        ->and($seventh->enrollments()->pluck('status')->unique()->all())->toBe([EnrollmentStatus::Completed])
        ->and($graduating->fresh()->status)->toBe(StudentStatus::Graduated);
});

it('tanpa opsi lulus, status siswa tingkat akhir tidak diubah', function () {
    ['from' => $from, 'to' => $to, 'graduating' => $graduating] = promotionScenario();
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/master-data/kelas/naik-kelas', [
        'from' => $from->public_id,
        'to' => $to->public_id,
        'graduate_final_grade' => false,
    ])->assertSessionHasNoErrors();

    expect($graduating->fresh()->status)->toBe(StudentStatus::Active);
});

it('siswa yang sudah punya rombel di tahun tujuan dilewati', function () {
    ['from' => $from, 'to' => $to, 'seventh' => $seventh] = promotionScenario();
    $already = $seventh->enrollments()->first()->student;
    enrollIn(Classroom::factory()->for($to)->create(['name' => 'VIII-Z', 'grade' => 8]), $already);
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/master-data/kelas/naik-kelas', [
        'from' => $from->public_id,
        'to' => $to->public_id,
    ])->assertSessionHasNoErrors();

    expect($already->enrollments()->where('academic_year_id', $to->id)->count())->toBe(1);
});

it('tahun tujuan harus setelah tahun asal', function () {
    ['from' => $from, 'to' => $to] = promotionScenario();
    $this->actingAsRole(Role::Operator);

    $this->get("/operator/master-data/kelas/naik-kelas?dari={$to->public_id}&ke={$from->public_id}")
        ->assertInertia(fn (Assert $page) => $page->where('plan', null)->whereType('error', 'string'));

    $this->post('/operator/master-data/kelas/naik-kelas', [
        'from' => $to->public_id,
        'to' => $from->public_id,
    ])->assertSessionHasErrors('to');
});

it('role selain operator ditolak', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/operator/master-data/kelas/naik-kelas')->assertForbidden();
    $this->post('/operator/master-data/kelas/naik-kelas', [])->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);
