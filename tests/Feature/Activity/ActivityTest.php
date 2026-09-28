<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Activity\Models\Activity;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-09-28 10:00:00');
    Storage::fake('private');
    Storage::fake('public');
});

/**
 * Guru mengajar di VII-A (dua siswa; siswa pertama punya orang tua); VIII-A berisi siswa lain dengan orang tua lain.
 *
 * @return array{teacher: User, mine: Classroom, other: Classroom, students: list<Student>, parent: User, otherParent: User}
 */
function activityScenario(): array
{
    $year = activeAcademicYear();
    $teacher = User::factory()->withRole(Role::Teacher)->create();
    $mine = Classroom::factory()->for($year)->create(['name' => 'VII-A']);
    $other = Classroom::factory()->for($year)->create(['name' => 'VIII-A']);
    assignTeaching($mine, Teacher::factory()->withAccount($teacher)->create());

    $parent = User::factory()->withRole(Role::Guardian)->create();
    $otherParent = User::factory()->withRole(Role::Guardian)->create();
    $students = [
        Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']),
        Student::factory()->create(['name' => 'Budi']),
    ];
    foreach ($students as $student) {
        enrollIn($mine, $student);
    }
    enrollIn($other, Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create());

    return compact('teacher', 'mine', 'other', 'students', 'parent', 'otherParent');
}

/**
 * @return array<string, mixed>
 */
function activityPayload(array $overrides = []): array
{
    return [
        'title' => 'Kunjungan museum',
        'type' => 'KELAS',
        'date' => '2026-09-25',
        'start_time' => '08:00',
        'end_time' => '12:00',
        'location' => 'Museum Timah',
        'description' => 'Belajar sejarah.',
        'status' => 'TERBIT',
        'classrooms' => [],
        'students' => [],
        ...$overrides,
    ];
}

it('guru membuat kegiatan kelas untuk rombelnya dengan foto', function () {
    ['teacher' => $teacher, 'mine' => $mine] = activityScenario();
    $this->actingAsRole(Role::Teacher, $teacher);

    $this->post('/guru/kegiatan', activityPayload([
        'classrooms' => [$mine->public_id],
        'photos' => [UploadedFile::fake()->image('a.jpg', 2400, 1800), UploadedFile::fake()->image('b.jpg')],
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $activity = Activity::query()->sole();
    expect($activity->participants()->count())->toBe(1)
        ->and($activity->photos()->count())->toBe(2)
        ->and($activity->photos()->first()->width)->toBeLessThanOrEqual(1600);

    $this->get('/guru/kegiatan')->assertInertia(fn (Assert $page) => $page
        ->component('Activity::Manage/Index')
        ->has('activities.data', 1)
        ->where('activities.data.0.classrooms.0.name', 'VII-A')
        ->missing('activities.data.0.id'));
});

it('validasi: rombel yang tidak diajar, jenis SEKOLAH oleh guru, peserta wajib, jam', function () {
    ['teacher' => $teacher, 'mine' => $mine, 'other' => $other] = activityScenario();
    $this->actingAsRole(Role::Teacher, $teacher);

    $this->post('/guru/kegiatan', activityPayload(['classrooms' => [$other->public_id]]))->assertSessionHasErrors('classrooms');
    $this->post('/guru/kegiatan', activityPayload(['type' => 'SEKOLAH']))->assertSessionHasErrors('type');
    $this->post('/guru/kegiatan', activityPayload())->assertSessionHasErrors('classrooms');
    $this->post('/guru/kegiatan', activityPayload(['classrooms' => [$mine->public_id], 'start_time' => '10:00', 'end_time' => '09:00']))->assertSessionHasErrors('end_time');

    expect(Activity::query()->count())->toBe(0);
});

it('operator membuat kegiatan SEKOLAH tanpa peserta', function () {
    activityScenario();
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/kegiatan', activityPayload(['type' => 'SEKOLAH', 'title' => 'Upacara']))->assertSessionHasNoErrors();

    expect(Activity::query()->sole()->participants()->count())->toBe(0);
});

describe('feed & kalender orang tua', function () {
    it('melihat kegiatan sekolah, kelas anak, dan kegiatan yang menyebut anak — bukan kelas lain atau draf', function () {
        ['teacher' => $teacher, 'mine' => $mine, 'students' => $students, 'parent' => $parent, 'otherParent' => $otherParent] = activityScenario();

        $this->actingAsRole(Role::Operator);
        $this->post('/operator/kegiatan', activityPayload(['type' => 'SEKOLAH', 'title' => 'Upacara']));

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->post('/guru/kegiatan', activityPayload(['title' => 'Museum', 'classrooms' => [$mine->public_id], 'photos' => [UploadedFile::fake()->image('a.jpg')]]));
        $this->post('/guru/kegiatan', activityPayload(['title' => 'Lomba Budi', 'type' => 'HARIAN', 'students' => [$students[1]->public_id]]));
        $this->post('/guru/kegiatan', activityPayload(['title' => 'Draf', 'classrooms' => [$mine->public_id], 'status' => 'DRAF']));

        $this->actingAsRole(Role::Guardian, $parent);
        $this->get('/orang-tua/kegiatan')->assertInertia(fn (Assert $page) => $page
            ->component('Activity::Guardian/Feed')
            ->has('activities.data', 2)
            ->where('activities.data', fn ($rows) => collect($rows)->pluck('title')->sort()->values()->all() === ['Museum', 'Upacara']));
        $this->get('/orang-tua/kegiatan/kalender?bulan=2026-09')->assertInertia(fn (Assert $page) => $page
            ->component('Activity::Shared/Calendar')
            ->has('activities', 2));

        $photo = File::query()->firstOrFail();
        $this->get("/files/{$photo->public_id}")->assertOk();

        $this->actingAsRole(Role::Guardian, $otherParent);
        $this->get('/orang-tua/kegiatan')->assertInertia(fn (Assert $page) => $page->has('activities.data', 1));
        $this->get("/files/{$photo->public_id}")->assertForbidden();
    });

    it('nama siswa lain di kegiatan tidak tampil ke orang tua', function () {
        ['teacher' => $teacher, 'students' => $students, 'parent' => $parent] = activityScenario();
        $this->actingAsRole(Role::Teacher, $teacher);
        $this->post('/guru/kegiatan', activityPayload(['type' => 'HARIAN', 'students' => [$students[0]->public_id, $students[1]->public_id]]));

        $this->actingAsRole(Role::Guardian, $parent);
        $this->get('/orang-tua/kegiatan')->assertInertia(fn (Assert $page) => $page
            ->has('activities.data.0.students', 1)
            ->where('activities.data.0.students.0.name', 'Ani'));
    });
});

it('kalender untuk guru, operator, dan kepala sekolah sesuai cakupan', function () {
    ['teacher' => $teacher, 'mine' => $mine, 'other' => $other] = activityScenario();
    $this->actingAsRole(Role::Operator);
    $this->post('/operator/kegiatan', activityPayload(['title' => 'Kelas lain', 'classrooms' => [$other->public_id]]));
    $this->post('/operator/kegiatan', activityPayload(['title' => 'Draf operator', 'type' => 'SEKOLAH', 'status' => 'DRAF']));
    $this->actingAsRole(Role::Teacher, $teacher);
    $this->post('/guru/kegiatan', activityPayload(['title' => 'Kelasku', 'classrooms' => [$mine->public_id]]));

    $this->get('/guru/kegiatan/kalender?bulan=2026-09')->assertInertia(fn (Assert $page) => $page->has('activities', 1)->where('canManage', true));

    $this->actingAsRole(Role::Operator);
    $this->get('/operator/kegiatan/kalender?bulan=2026-09')->assertInertia(fn (Assert $page) => $page->has('activities', 3));

    $this->actingAsRole(Role::Principal);
    $this->get('/kepala-sekolah/kegiatan?bulan=2026-09')->assertInertia(fn (Assert $page) => $page->has('activities', 2)->where('canManage', false));
});

it('guru lain tidak bisa mengubah; pembuat & operator bisa menghapus beserta foto', function () {
    ['teacher' => $teacher, 'mine' => $mine] = activityScenario();
    $this->actingAsRole(Role::Teacher, $teacher);
    $this->post('/guru/kegiatan', activityPayload(['classrooms' => [$mine->public_id], 'photos' => [UploadedFile::fake()->image('a.jpg')]]));
    $activity = Activity::query()->sole();

    $other = User::factory()->withRole(Role::Teacher)->create();
    assignTeaching($mine, Teacher::factory()->withAccount($other)->create());
    $this->actingAsRole(Role::Teacher, $other);
    $this->get("/guru/kegiatan/{$activity->public_id}/ubah")->assertForbidden();
    $this->put("/guru/kegiatan/{$activity->public_id}", activityPayload(['classrooms' => [$mine->public_id]]))->assertForbidden();

    $this->actingAsRole(Role::Teacher, $teacher);
    $this->delete("/guru/kegiatan/{$activity->public_id}")->assertRedirect('/guru/kegiatan');
    expect(Activity::query()->count())->toBe(0)->and(File::query()->count())->toBe(0);
});

it('role lain tidak bisa mengelola kegiatan', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/guru/kegiatan')->assertForbidden();
    $this->post('/operator/kegiatan', [])->assertForbidden();
})->with([Role::Principal, Role::Guardian]);
