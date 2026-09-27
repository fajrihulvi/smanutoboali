<?php

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Orang tua A (dengan anak A) dan orang tua B (dengan anak B).
 *
 * @return array{userA: User, userB: User, childA: Student, childB: Student}
 */
function twoFamilies(): array
{
    $userA = User::factory()->withRole(Role::Guardian)->create();
    $userB = User::factory()->withRole(Role::Guardian)->create();
    $childA = Student::factory()->withGuardian(Guardian::factory()->withAccount($userA)->create())->create();
    $childB = Student::factory()->withGuardian(Guardian::factory()->withAccount($userB)->create())->create();

    return compact('userA', 'userB', 'childA', 'childB');
}

describe('Student::visibleTo', function () {
    it('operator dan kepala sekolah melihat semua siswa', function (Role $role) {
        Student::factory()->count(3)->create();
        $user = User::factory()->withRole($role)->create();

        expect(Student::query()->visibleTo($user, $role)->count())->toBe(3);
    })->with([Role::Operator, Role::Principal]);

    it('orang tua hanya melihat anak yang tertaut', function () {
        ['userA' => $userA, 'childA' => $childA] = twoFamilies();

        expect(Student::query()->visibleTo($userA, Role::Guardian)->pluck('id')->all())->toBe([$childA->id]);
    });

    it('kakak-adik dengan orang tua yang sama terlihat keduanya', function () {
        $user = User::factory()->withRole(Role::Guardian)->create();
        $guardian = Guardian::factory()->withAccount($user)->create();
        Student::factory()->count(2)->withGuardian($guardian)->create();
        Student::factory()->create();

        expect(Student::query()->visibleTo($user, Role::Guardian)->count())->toBe(2);
    });

    it('menolak role yang tidak dimiliki user', function () {
        Student::factory()->create();
        $guardianUser = User::factory()->withRole(Role::Guardian)->create();

        expect(Student::query()->visibleTo($guardianUser, Role::Operator)->count())->toBe(0);
    });

    it('memakai role aktif di sesi bila role tidak diberikan', function () {
        ['userA' => $userA] = twoFamilies();
        $userA->assignRole('operator');
        $this->actingAsRole(Role::Guardian, $userA);

        $this->get('/orang-tua/anak')->assertInertia(fn (Assert $page) => $page->has('students.data', 1));
    });

    it('guru melihat siswa di rombel yang diajar atau diwalikan pada tahun aktif', function () {
        $year = activeAcademicYear();
        $user = User::factory()->withRole(Role::Teacher)->create();
        $teacher = Teacher::factory()->withAccount($user)->create();

        $homeroom = Classroom::factory()->for($year)->create(['homeroom_teacher_id' => $teacher->id]);
        $taught = Classroom::factory()->for($year)->create();
        assignTeaching($taught, $teacher);
        $other = Classroom::factory()->for($year)->create();

        $inHomeroom = Student::factory()->create();
        $inTaught = Student::factory()->create();
        enrollIn($homeroom, $inHomeroom);
        enrollIn($taught, $inTaught);
        enrollIn($taught, Student::factory()->create(), EnrollmentStatus::Moved);
        enrollIn($other, Student::factory()->create());
        Student::factory()->create();

        expect(Student::query()->visibleTo($user, Role::Teacher)->pluck('id')->sort()->values()->all())
            ->toBe([$inHomeroom->id, $inTaught->id]);
    });

    it('guru tidak melihat siswa rombel tahun ajaran nonaktif', function () {
        activeAcademicYear();
        $user = User::factory()->withRole(Role::Teacher)->create();
        $teacher = Teacher::factory()->withAccount($user)->create();
        $old = Classroom::factory()->for(AcademicYear::factory()->startingIn(2020)->create())->create();
        assignTeaching($old, $teacher);
        enrollIn($old, Student::factory()->create());

        expect(Student::query()->visibleTo($user, Role::Teacher)->count())->toBe(0);
    });

    it('guru tanpa penugasan tidak melihat siswa mana pun', function () {
        activeAcademicYear();
        Student::factory()->count(2)->create();
        $teacher = User::factory()->withRole(Role::Teacher)->create();

        expect(Student::query()->visibleTo($teacher, Role::Teacher)->count())->toBe(0);
    });
});

describe('ownership orang tua', function () {
    it('orang tua A tidak bisa membuka detail siswa B (403) lewat manipulasi public_id', function () {
        ['userA' => $userA, 'childA' => $childA, 'childB' => $childB] = twoFamilies();
        $this->actingAsRole(Role::Guardian, $userA);

        $this->get("/orang-tua/anak/{$childA->public_id}")->assertOk();
        $this->get("/orang-tua/anak/{$childB->public_id}")->assertForbidden();
    });

    it('id numerik tidak dikenali di URL', function () {
        ['userA' => $userA, 'childA' => $childA] = twoFamilies();
        $this->actingAsRole(Role::Guardian, $userA);

        $this->get("/orang-tua/anak/{$childA->id}")->assertNotFound();
    });

    it('daftar anak hanya berisi anak sendiri tanpa kontak orang tua', function () {
        ['userA' => $userA, 'childA' => $childA] = twoFamilies();
        $this->actingAsRole(Role::Guardian, $userA);

        $this->get('/orang-tua/anak')->assertInertia(fn (Assert $page) => $page
            ->component('People::Guardian/Children/Index')
            ->has('students.data', 1)
            ->where('students.data.0.public_id', $childA->public_id)
            ->where('students.data.0.guardians.0.phone', null)
            ->missing('students.data.0.id'));
    });

    it('orang tua tanpa anak tertaut melihat daftar kosong', function () {
        twoFamilies();
        $this->actingAsRole(Role::Guardian);

        $this->get('/orang-tua/anak')->assertInertia(fn (Assert $page) => $page->has('students.data', 0));
    });

    it('StudentOwnershipGate memakai visibleTo', function () {
        ['userA' => $userA, 'childA' => $childA, 'childB' => $childB] = twoFamilies();
        $this->actingAsRole(Role::Guardian, $userA);
        $gate = app(StudentOwnershipGate::class);

        expect($gate->canViewStudent($userA, $childA->id))->toBeTrue()
            ->and($gate->canViewStudent($userA, $childB->id))->toBeFalse();
    });

    it('guru dan operator tidak bisa membuka halaman anak di portal orang tua', function (Role $role) {
        ['childA' => $childA] = twoFamilies();
        $this->actingAsRole($role);

        $this->get("/orang-tua/anak/{$childA->public_id}")->assertForbidden();
    })->with([Role::Teacher, Role::Operator]);
});

describe('monitoring siswa kepala sekolah', function () {
    it('kepala sekolah melihat semua siswa (read-only)', function () {
        Student::factory()->count(4)->create();
        $this->actingAsRole(Role::Principal);

        $this->get('/kepala-sekolah/siswa')->assertInertia(fn (Assert $page) => $page
            ->component('People::Principal/Students/Index')
            ->has('students.data', 4));
    });

    it('kepala sekolah tidak dapat mengubah data siswa', function () {
        $student = Student::factory()->create();
        $this->actingAsRole(Role::Principal);

        $this->get("/operator/master-data/siswa/{$student->public_id}/ubah")->assertForbidden();
    });
});
