<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

const GURU_BASE = '/operator/master-data/guru';

function teacherPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rina Marlina, S.Pd.',
        'nip' => '198501012010012001',
        'nuptk' => '1234567890123456',
        'gender' => 'P',
        'phone' => '081234567890',
        'employment_status' => 'PNS',
        'is_active' => true,
    ], $overrides);
}

describe('akses', function () {
    it('operator membuka daftar guru', function () {
        Teacher::factory()->count(3)->create();
        $this->actingAsRole(Role::Operator);

        $this->get(GURU_BASE)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('People::Operator/Teachers/Index')
                ->has('teachers.data', 3)
                ->missing('teachers.data.0.id')
                ->missing('teachers.data.0.user_id'));
    });

    it('role lain tidak dapat mengelola guru', function (Role $role) {
        $teacher = Teacher::factory()->create();
        $this->actingAsRole($role);

        $this->get(GURU_BASE)->assertForbidden();
        $this->post(GURU_BASE, teacherPayload())->assertForbidden();
        $this->delete(GURU_BASE."/{$teacher->public_id}")->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);

    it('kepala sekolah melihat daftar guru read-only', function () {
        Teacher::factory()->count(2)->create();
        $this->actingAsRole(Role::Principal);

        $this->get('/kepala-sekolah/guru')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('People::Principal/Teachers/Index')
                ->has('teachers.data', 2));
    });

    it('guru dan orang tua tidak dapat membuka monitoring guru kepala sekolah', function (Role $role) {
        $this->actingAsRole($role);

        $this->get('/kepala-sekolah/guru')->assertForbidden();
    })->with([Role::Teacher, Role::Guardian]);
});

describe('CRUD guru', function () {
    it('menambah guru tanpa akun', function () {
        $this->actingAsRole(Role::Operator);

        $this->post(GURU_BASE, teacherPayload())->assertSessionHasNoErrors()->assertSessionHas('success');

        $teacher = Teacher::query()->firstOrFail();
        expect($teacher->nip)->toBe('198501012010012001')
            ->and($teacher->user_id)->toBeNull();
        expect(Activity::query()->where('log_name', 'people')->where('event', 'created')->exists())->toBeTrue();
    });

    it('menambah guru sekaligus akun login dengan password sementara', function () {
        $this->actingAsRole(Role::Operator);

        $response = $this->post(GURU_BASE, teacherPayload(['create_account' => true, 'account_email' => 'Rina@Sekolah.sch.id']));

        $teacher = Teacher::query()->with('user')->firstOrFail();
        $credentials = session('credentials');

        $response->assertRedirect(GURU_BASE."/{$teacher->public_id}/ubah");
        expect($teacher->user->email)->toBe('rina@sekolah.sch.id')
            ->and($teacher->user->hasRole('teacher'))->toBeTrue()
            ->and($credentials['email'])->toBe('rina@sekolah.sch.id')
            ->and(strlen($credentials['password']))->toBe(12)
            ->and(Hash::check($credentials['password'], $teacher->user->password))->toBeTrue();
    });

    it('menautkan akun yang sudah ada dan menambah role (tanpa akun ganda)', function () {
        $existing = User::factory()->withRole(Role::Guardian)->create(['email' => 'dewi@sekolah.sch.id']);
        $this->actingAsRole(Role::Operator);

        $this->post(GURU_BASE, teacherPayload(['create_account' => true, 'account_email' => 'dewi@sekolah.sch.id']))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('credentials');

        expect(User::query()->where('email', 'dewi@sekolah.sch.id')->count())->toBe(1)
            ->and($existing->fresh()->getRoleNames()->sort()->values()->all())->toBe(['guardian', 'teacher'])
            ->and(Teacher::query()->first()->user_id)->toBe($existing->id);
    });

    it('menolak satu akun untuk dua data guru', function () {
        $user = User::factory()->withRole(Role::Teacher)->create(['email' => 'sama@sekolah.sch.id']);
        Teacher::factory()->withAccount($user)->create();
        $other = Teacher::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->post(GURU_BASE."/{$other->public_id}/akun", ['account_email' => 'sama@sekolah.sch.id'])
            ->assertSessionHasErrors('account_email');

        expect($other->fresh()->user_id)->toBeNull();
    });

    it('membuat akun untuk guru yang sudah ada', function () {
        $teacher = Teacher::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->post(GURU_BASE."/{$teacher->public_id}/akun", ['account_email' => 'baru@sekolah.sch.id'])
            ->assertSessionHas('credentials');

        expect($teacher->fresh()->user?->email)->toBe('baru@sekolah.sch.id');
    });

    it('memvalidasi data guru', function () {
        Teacher::factory()->create(['nip' => '198501012010012001']);
        $this->actingAsRole(Role::Operator);

        $this->post(GURU_BASE, teacherPayload([
            'name' => '',
            'nip' => '198501012010012001',
            'nuptk' => '123',
            'gender' => 'X',
            'employment_status' => 'KONTRAK',
            'phone' => 'abc',
            'create_account' => true,
            'account_email' => '',
        ]))->assertSessionHasErrors(['name', 'nip', 'nuptk', 'gender', 'employment_status', 'phone', 'account_email']);
    });

    it('mengubah guru dan foto, lalu menghapus (soft delete)', function () {
        Storage::fake('private');
        $teacher = Teacher::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->post(GURU_BASE."/{$teacher->public_id}", teacherPayload([
            'name' => 'Nama Baru',
            'photo' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ]))->assertSessionHasNoErrors();

        $teacher->refresh();
        expect($teacher->name)->toBe('Nama Baru')
            ->and($teacher->photo_file_id)->not->toBeNull()
            ->and(File::query()->find($teacher->photo_file_id)?->fileable_type)->toBe(Teacher::class);

        $this->delete(GURU_BASE."/{$teacher->public_id}")->assertRedirect(GURU_BASE);
        expect($teacher->fresh()->trashed())->toBeTrue();
    });

    it('memfilter daftar guru', function () {
        Teacher::factory()->create(['name' => 'Ani Guru', 'employment_status' => 'PNS']);
        Teacher::factory()->create(['name' => 'Budi Guru', 'employment_status' => 'HONORER']);
        Teacher::factory()->withAccount()->create(['name' => 'Cici Guru', 'employment_status' => 'PNS']);
        $this->actingAsRole(Role::Operator);

        $this->get(GURU_BASE.'?employment=PNS')->assertInertia(fn (Assert $page) => $page->has('teachers.data', 2));
        $this->get(GURU_BASE.'?account=yes')->assertInertia(fn (Assert $page) => $page
            ->has('teachers.data', 1)
            ->where('teachers.data.0.name', 'Cici Guru'));
        $this->get(GURU_BASE.'?search=budi')->assertInertia(fn (Assert $page) => $page->has('teachers.data', 1));
    });
});

describe('foto guru (ownership)', function () {
    it('foto guru hanya bisa dibuka operator, kepala sekolah, dan guru itu sendiri', function () {
        Storage::fake('private');
        $owner = User::factory()->withRole(Role::Teacher)->create();
        $teacher = Teacher::factory()->withAccount($owner)->create();
        $this->actingAsRole(Role::Operator);
        $this->post(GURU_BASE."/{$teacher->public_id}", teacherPayload([
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]));
        $file = File::query()->findOrFail($teacher->fresh()->photo_file_id);

        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Principal);
        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Teacher, $owner);
        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Teacher);
        $this->get("/files/{$file->public_id}")->assertForbidden();

        $this->actingAsRole(Role::Guardian);
        $this->get("/files/{$file->public_id}")->assertForbidden();
    });
});

describe('profil guru sendiri', function () {
    it('guru melihat dan mengubah kontak sendiri, bukan data induk', function () {
        $user = User::factory()->withRole(Role::Teacher)->create();
        $teacher = Teacher::factory()->withAccount($user)->create(['nip' => '111111111111111111']);
        $this->actingAsRole(Role::Teacher, $user);

        $this->get('/guru/profil')->assertInertia(fn (Assert $page) => $page
            ->component('People::Teacher/Profile')
            ->where('teacher.public_id', $teacher->public_id));

        $this->post('/guru/profil', ['phone' => '089999', 'nip' => '999999999999999999', 'name' => 'Hack'])
            ->assertSessionHasNoErrors();

        $teacher->refresh();
        expect($teacher->phone)->toBe('089999')
            ->and($teacher->nip)->toBe('111111111111111111')
            ->and($teacher->name)->not->toBe('Hack');
    });

    it('guru tanpa data tertaut melihat pesan kosong', function () {
        $this->actingAsRole(Role::Teacher);

        $this->get('/guru/profil')->assertInertia(fn (Assert $page) => $page->where('teacher', null));
        $this->post('/guru/profil', ['phone' => '0812'])->assertNotFound();
    });
});
