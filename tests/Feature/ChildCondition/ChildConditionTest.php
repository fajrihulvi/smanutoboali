<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Notifications\ChildConditionSubmittedNotification;
use App\Modules\ChildCondition\Notifications\ChildConditionUpdatedNotification;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * VII-A: wali kelas (Bu Rina) + guru mapel (Pak Joko); Ani punya dua orang tua (pelapor & pasangan).
 * Ditambah guru lain, orang tua lain, operator, kepala sekolah.
 *
 * @return array<string, mixed>
 */
function conditionScenario(): array
{
    $year = activeAcademicYear();
    $homeroomUser = User::factory()->withRole(Role::Teacher)->create(['name' => 'Bu Rina']);
    $homeroom = Teacher::factory()->withAccount($homeroomUser)->create(['name' => 'Bu Rina']);
    $classroom = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'homeroom_teacher_id' => $homeroom->id]);

    $subjectUser = User::factory()->withRole(Role::Teacher)->create(['name' => 'Pak Joko']);
    $subjectTeacher = Teacher::factory()->withAccount($subjectUser)->create(['name' => 'Pak Joko']);
    assignTeaching($classroom, $subjectTeacher);

    $otherTeacherUser = User::factory()->withRole(Role::Teacher)->create();
    assignTeaching(Classroom::factory()->for($year)->create(), Teacher::factory()->withAccount($otherTeacherUser)->create());

    $parent = User::factory()->withRole(Role::Guardian)->create(['name' => 'Pak Budi']);
    $spouse = User::factory()->withRole(Role::Guardian)->create();
    $child = Student::factory()
        ->withGuardian(Guardian::factory()->withAccount($parent)->create())
        ->withGuardian(Guardian::factory()->withAccount($spouse)->create())
        ->create(['name' => 'Ani']);
    enrollIn($classroom, $child);

    $otherParent = User::factory()->withRole(Role::Guardian)->create();
    // Nama tetap: nama acak Faker bisa mengandung "ani" dan merusak test pencarian.
    $otherChild = Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create(['name' => 'Bima']);
    enrollIn($classroom, $otherChild);

    return [
        'classroom' => $classroom,
        'homeroomUser' => $homeroomUser,
        'homeroom' => $homeroom,
        'subjectUser' => $subjectUser,
        'subjectTeacher' => $subjectTeacher,
        'otherTeacherUser' => $otherTeacherUser,
        'parent' => $parent,
        'spouse' => $spouse,
        'child' => $child,
        'otherParent' => $otherParent,
        'otherChild' => $otherChild,
        'operator' => User::factory()->withRole(Role::Operator)->create(),
        'principal' => User::factory()->withRole(Role::Principal)->create(),
    ];
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function conditionPayload(Student $child, array $extra = []): array
{
    return [
        'student' => $child->public_id,
        'type' => 'SAKIT',
        'description' => 'Demam sejak semalam, sudah ke dokter.',
        'valid_from' => '2026-09-28',
        'valid_until' => '2026-09-29',
        ...$extra,
    ];
}

function submitCondition(User $parent, Student $child, array $extra = []): ChildCondition
{
    test()->actingAsRole(Role::Guardian, $parent);
    test()->post('/orang-tua/kondisi-anak', conditionPayload($child, $extra))->assertSessionHasNoErrors();

    return ChildCondition::query()->latest('id')->firstOrFail();
}

describe('orang tua melapor', function () {
    it('default ke wali kelas, dengan foto privat, dan memberi tahu guru', function () {
        ['parent' => $parent, 'child' => $child, 'homeroom' => $homeroom, 'homeroomUser' => $homeroomUser] = conditionScenario();
        Notification::fake();
        $this->actingAsRole(Role::Guardian, $parent);

        $response = $this->post('/orang-tua/kondisi-anak', conditionPayload($child, [
            'photos' => [UploadedFile::fake()->image('surat.jpg', 800, 600)],
        ]));

        $condition = ChildCondition::query()->sole();
        $response->assertRedirect("/orang-tua/kondisi-anak/{$condition->public_id}");
        expect($condition->target_teacher_id)->toBe($homeroom->id)
            ->and($condition->status->value)->toBe('SUBMITTED')
            ->and($condition->guardian_user_id)->toBe($parent->id)
            ->and($condition->photos()->sole()->visibility->value)->toBe('private');

        Notification::assertSentTo($homeroomUser, ChildConditionSubmittedNotification::class,
            fn ($n) => ! str_contains(json_encode($n->toArray($homeroomUser)), 'Demam'));
    });

    it('bisa memilih guru mapel anaknya; wali kelas tetap diberi tahu', function () {
        ['parent' => $parent, 'child' => $child, 'subjectTeacher' => $subjectTeacher, 'subjectUser' => $subjectUser, 'homeroomUser' => $homeroomUser] = conditionScenario();
        Notification::fake();

        $condition = submitCondition($parent, $child, ['target_teacher' => $subjectTeacher->public_id]);

        expect($condition->target_teacher_id)->toBe($subjectTeacher->id);
        Notification::assertSentTo([$subjectUser, $homeroomUser], ChildConditionSubmittedNotification::class);
    });

    it('menolak guru yang tidak mengajar anak, anak orang lain, dan tanggal tidak wajar', function () {
        ['parent' => $parent, 'child' => $child, 'otherChild' => $otherChild, 'otherTeacherUser' => $otherTeacherUser] = conditionScenario();
        $this->actingAsRole(Role::Guardian, $parent);
        $otherTeacher = Teacher::query()->where('user_id', $otherTeacherUser->id)->sole();

        $this->post('/orang-tua/kondisi-anak', conditionPayload($child, ['target_teacher' => $otherTeacher->public_id]))->assertSessionHasErrors('target_teacher');
        $this->post('/orang-tua/kondisi-anak', conditionPayload($otherChild))->assertSessionHasErrors('student');
        $this->post('/orang-tua/kondisi-anak', conditionPayload($child, ['valid_until' => '2026-11-30']))->assertSessionHasErrors('valid_until');
        $this->post('/orang-tua/kondisi-anak', conditionPayload($child, ['valid_from' => '2026-09-01', 'valid_until' => null]))->assertSessionHasErrors('valid_from');
        $this->post('/orang-tua/kondisi-anak', conditionPayload($child, ['type' => 'RAHASIA']))->assertSessionHasErrors('type');

        expect(ChildCondition::query()->count())->toBe(0);
    });

    it('riwayat hanya berisi laporan milik sendiri', function () {
        ['parent' => $parent, 'child' => $child, 'spouse' => $spouse] = conditionScenario();
        submitCondition($parent, $child);

        $this->actingAsRole(Role::Guardian, $spouse);
        $this->get('/orang-tua/kondisi-anak')->assertInertia(fn (Assert $page) => $page
            ->component('ChildCondition::Guardian/Index')
            ->has('conditions.data', 0));

        $this->actingAsRole(Role::Guardian, $parent);
        $this->get('/orang-tua/kondisi-anak')->assertInertia(fn (Assert $page) => $page
            ->has('conditions.data', 1)
            ->where('conditions.data.0.status.label', 'Terkirim')
            ->missing('conditions.data.0.id'));
    });

    it('form lapor menawarkan wali kelas lebih dulu', function () {
        ['parent' => $parent] = conditionScenario();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get('/orang-tua/kondisi-anak/lapor')->assertInertia(fn (Assert $page) => $page
            ->component('ChildCondition::Guardian/Create')
            ->where('children.0.name', 'Ani')
            ->where('children.0.teachers.0.name', 'Bu Rina')
            ->where('children.0.teachers.0.homeroom', true)
            ->has('types', 6));
    });

    it('bisa menarik laporan selama belum dibuka guru', function () {
        ['parent' => $parent, 'child' => $child, 'homeroomUser' => $homeroomUser] = conditionScenario();
        $first = submitCondition($parent, $child, ['photos' => [UploadedFile::fake()->image('a.jpg')]]);

        $this->delete("/orang-tua/kondisi-anak/{$first->public_id}")->assertRedirect('/orang-tua/kondisi-anak');
        expect(ChildCondition::query()->count())->toBe(0)->and(File::query()->count())->toBe(0);

        $second = submitCondition($parent, $child);
        $this->actingAsRole(Role::Teacher, $homeroomUser);
        $this->get("/guru/kondisi-anak/{$second->public_id}")->assertOk();

        $this->actingAsRole(Role::Guardian, $parent);
        $this->delete("/orang-tua/kondisi-anak/{$second->public_id}")->assertForbidden();
    });
});

describe('akses data sensitif', function () {
    it('hanya pelapor, guru tujuan, wali kelas, dan kepala sekolah yang bisa melihat', function () {
        $s = conditionScenario();
        $condition = submitCondition($s['parent'], $s['child'], [
            'target_teacher' => $s['subjectTeacher']->public_id,
            'photos' => [UploadedFile::fake()->image('surat.jpg')],
        ]);
        $photo = $condition->photos()->sole();

        $allowed = [
            [Role::Guardian, $s['parent'], '/orang-tua/kondisi-anak'],
            [Role::Teacher, $s['subjectUser'], '/guru/kondisi-anak'],
            [Role::Teacher, $s['homeroomUser'], '/guru/kondisi-anak'],
            [Role::Principal, $s['principal'], '/kepala-sekolah/kondisi-anak'],
        ];

        foreach ($allowed as [$role, $user, $base]) {
            $this->actingAsRole($role, $user);
            $this->get("{$base}/{$condition->public_id}")->assertOk();
            $this->get(route('files.show', $photo))->assertOk();
        }

        $denied = [
            [Role::Guardian, $s['spouse'], '/orang-tua/kondisi-anak'],
            [Role::Guardian, $s['otherParent'], '/orang-tua/kondisi-anak'],
            [Role::Teacher, $s['otherTeacherUser'], '/guru/kondisi-anak'],
        ];

        foreach ($denied as [$role, $user, $base]) {
            $this->actingAsRole($role, $user);
            $this->get("{$base}/{$condition->public_id}")->assertForbidden();
            $this->get(route('files.show', $photo))->assertForbidden();
        }

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->get(route('files.show', $photo))->assertForbidden();
        $this->get("/guru/kondisi-anak/{$condition->public_id}")->assertForbidden();
    });

    it('kepala sekolah hanya-baca dan tidak mengubah status saat membuka', function () {
        $s = conditionScenario();
        $condition = submitCondition($s['parent'], $s['child']);

        $this->actingAsRole(Role::Principal, $s['principal']);
        $this->get("/kepala-sekolah/kondisi-anak/{$condition->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('ChildCondition::Shared/Show')
            ->where('condition.can.respond', false));
        $this->get('/kepala-sekolah/kondisi-anak')->assertInertia(fn (Assert $page) => $page
            ->component('ChildCondition::Staff/Index')
            ->where('readOnly', true)
            ->has('conditions.data', 1));

        expect($condition->fresh()->status->value)->toBe('SUBMITTED');
        $this->post("/guru/kondisi-anak/{$condition->public_id}/tanggapan", ['body' => 'Tes'])->assertForbidden();
    });
});

describe('guru menangani laporan', function () {
    it('dibuka → RECEIVED, ditinjau, ditanggapi (orang tua diberi tahu), lalu ditutup', function () {
        $s = conditionScenario();
        $condition = submitCondition($s['parent'], $s['child']);
        Notification::fake();

        $this->actingAsRole(Role::Teacher, $s['homeroomUser']);
        $this->get("/guru/kondisi-anak/{$condition->public_id}")->assertInertia(fn (Assert $page) => $page
            ->where('condition.status.value', 'RECEIVED')
            ->where('condition.can.respond', true)
            ->where('condition.can.review', true)
            ->where('condition.attendance_suggestion', 'Sakit'));

        $this->post("/guru/kondisi-anak/{$condition->public_id}/tinjau")->assertSessionHasNoErrors();
        expect($condition->fresh()->status->value)->toBe('REVIEWED');

        $this->post("/guru/kondisi-anak/{$condition->public_id}/tanggapan", ['body' => 'Semoga lekas sembuh, Pak.'])->assertSessionHasNoErrors();
        $this->post("/guru/kondisi-anak/{$condition->public_id}/tanggapan", ['body' => 'Tugas bisa menyusul.'])->assertSessionHasNoErrors();
        expect($condition->fresh()->status->value)->toBe('RESPONDED')
            ->and($condition->responses()->count())->toBe(2);
        Notification::assertSentToTimes($s['parent'], ChildConditionUpdatedNotification::class, 2);

        $this->post("/guru/kondisi-anak/{$condition->public_id}/selesai")->assertSessionHasNoErrors();
        $fresh = $condition->fresh();
        expect($fresh->status->value)->toBe('CLOSED')->and($fresh->closed_by)->toBe($s['homeroomUser']->id);
        Notification::assertSentToTimes($s['parent'], ChildConditionUpdatedNotification::class, 3);

        $this->post("/guru/kondisi-anak/{$condition->public_id}/tanggapan", ['body' => 'Lagi'])->assertForbidden();

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->get("/orang-tua/kondisi-anak/{$condition->public_id}")->assertInertia(fn (Assert $page) => $page
            ->has('condition.responses', 2)
            ->where('condition.responses.0.author', 'Bu Rina')
            ->has('condition.timeline', 5));
    });

    it('inbox: hanya laporan yang ditangani, yang baru di atas, dengan tab selesai & cari', function () {
        $s = conditionScenario();
        $old = submitCondition($s['parent'], $s['child'], ['type' => 'EMOSIONAL']);
        $this->actingAsRole(Role::Teacher, $s['homeroomUser']);
        $this->get("/guru/kondisi-anak/{$old->public_id}");
        $this->travel(1)->hours();
        $new = submitCondition($s['otherParent'], $s['otherChild']);

        $this->actingAsRole(Role::Teacher, $s['homeroomUser']);
        $this->get('/guru/kondisi-anak')->assertInertia(fn (Assert $page) => $page
            ->component('ChildCondition::Staff/Index')
            ->has('conditions.data', 2)
            ->where('conditions.data.0.public_id', $new->public_id)
            ->where('conditions.data.0.is_new', true)
            ->where('newCount', 1));
        $this->get('/guru/kondisi-anak?q=Ani')->assertInertia(fn (Assert $page) => $page->has('conditions.data', 1));
        $this->get('/guru/kondisi-anak?tab=selesai')->assertInertia(fn (Assert $page) => $page->has('conditions.data', 0));

        // Guru mapel bukan tujuan & bukan wali kelas → tidak melihat apa pun.
        $this->actingAsRole(Role::Teacher, $s['subjectUser']);
        $this->get('/guru/kondisi-anak')->assertInertia(fn (Assert $page) => $page->has('conditions.data', 0));
    });

    it('widget dashboard: kondisi anak yang berlaku hari ini', function () {
        $s = conditionScenario();
        submitCondition($s['parent'], $s['child']);
        submitCondition($s['otherParent'], $s['otherChild'], ['valid_from' => '2026-10-05', 'valid_until' => null]);

        $this->actingAsRole(Role::Teacher, $s['homeroomUser']);
        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('conditions.today', 1)
            ->where('conditions.today.0.student.name', 'Ani')
            ->where('conditions.newCount', 2));
    });
});

describe('usulan presensi', function () {
    it('laporan SAKIT menjadi usulan status pada tanggal berlaku, tautan hanya untuk yang berhak', function () {
        $s = conditionScenario();
        submitCondition($s['parent'], $s['child']);
        submitCondition($s['otherParent'], $s['otherChild'], ['type' => 'KELUHAN_KESEHATAN']);
        $url = "/guru/presensi/rombel/{$s['classroom']->public_id}";

        $this->actingAsRole(Role::Teacher, $s['homeroomUser']);
        $this->get("{$url}?tanggal=2026-09-29")->assertInertia(function (Assert $page) use ($s) {
            $students = collect($page->toArray()['props']['students'])->keyBy('public_id');
            expect($students[$s['child']->public_id]['suggestion'])->toMatchArray(['status' => 'SAKIT', 'source' => 'Laporan orang tua: Sakit'])
                ->and($students[$s['child']->public_id]['suggestion']['url'])->toStartWith('/guru/kondisi-anak/')
                ->and($students[$s['otherChild']->public_id]['suggestion'])->toBeNull()
                ->and($students[$s['child']->public_id]['status'])->toBeNull();
        });
        $this->get("{$url}?tanggal=2026-09-30")->assertInertia(fn (Assert $page) => $page->where('students.0.suggestion', null)->where('students.1.suggestion', null));
    });
});
