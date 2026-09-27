<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

const SISWA_BASE = '/operator/master-data/siswa';

function studentPayload(array $overrides = []): array
{
    return array_merge([
        'nis' => '20260001',
        'nisn' => '0112233445',
        'name' => 'Aisyah Putri',
        'gender' => 'P',
        'birth_place' => 'Pangkalpinang',
        'birth_date' => '2013-05-17',
        'religion' => 'ISLAM',
        'address' => 'Jl. Merdeka 1',
        'status' => 'AKTIF',
        'entry_date' => '2026-07-13',
    ], $overrides);
}

function primaryOf(Student $student): ?string
{
    return $student->guardians()->wherePivot('is_primary', true)->value('guardians.name');
}

describe('akses', function () {
    it('operator membuka daftar siswa', function () {
        Student::factory()->count(3)->create();
        $this->actingAsRole(Role::Operator);

        $this->get(SISWA_BASE)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('People::Operator/Students/Index')
            ->has('students.data', 3)
            ->missing('students.data.0.id'));
    });

    it('role lain tidak dapat mengelola siswa', function (Role $role) {
        $student = Student::factory()->create();
        $this->actingAsRole($role);

        $this->get(SISWA_BASE)->assertForbidden();
        $this->post(SISWA_BASE, studentPayload())->assertForbidden();
        $this->post(SISWA_BASE."/{$student->public_id}", studentPayload())->assertForbidden();
        $this->delete(SISWA_BASE."/{$student->public_id}")->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);
});

describe('CRUD siswa', function () {
    it('menambah siswa lalu diarahkan ke tab orang tua', function () {
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE, studentPayload())->assertSessionHasNoErrors();

        $student = Student::query()->firstOrFail();
        expect($student->nis)->toBe('20260001')
            ->and($student->status)->toBe(StudentStatus::Active)
            ->and($student->birth_date?->toDateString())->toBe('2013-05-17');
        expect(Activity::query()->where('log_name', 'people')->where('event', 'created')->exists())->toBeTrue();
    });

    it('memvalidasi data siswa', function () {
        Student::factory()->create(['nis' => '20260001', 'nisn' => '0112233445']);
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE, studentPayload([
            'name' => '',
            'gender' => 'X',
            'religion' => 'LAINNYA',
            'birth_date' => '2099-01-01',
            'status' => 'ALUMNI',
        ]))->assertSessionHasErrors(['nis', 'nisn', 'name', 'gender', 'religion', 'birth_date', 'status']);
    });

    it('menolak transisi status yang tidak sah', function () {
        $student = Student::factory()->create(['status' => StudentStatus::Graduated]);
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE."/{$student->public_id}", studentPayload(['nis' => $student->nis, 'nisn' => null, 'status' => 'AKTIF']))
            ->assertSessionHasErrors('status');

        expect($student->fresh()->status)->toBe(StudentStatus::Graduated);
    });

    it('mengubah status aktif menjadi pindah', function () {
        $student = Student::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE."/{$student->public_id}", studentPayload(['nis' => $student->nis, 'nisn' => null, 'status' => 'PINDAH']))
            ->assertSessionHasNoErrors();

        expect($student->fresh()->status)->toBe(StudentStatus::Transferred);
    });

    it('menghapus siswa (soft delete)', function () {
        $student = Student::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->delete(SISWA_BASE."/{$student->public_id}")->assertRedirect(SISWA_BASE);

        expect($student->fresh()->trashed())->toBeTrue();
    });
});

describe('relasi orang tua (N:M)', function () {
    it('orang tua pertama otomatis menjadi utama', function () {
        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['name' => 'Budi']);
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE."/{$student->public_id}/orang-tua", [
            'mode' => 'existing', 'guardian' => $guardian->public_id, 'relationship' => 'AYAH',
        ])->assertSessionHasNoErrors();

        expect(primaryOf($student))->toBe('Budi');
    });

    it('hanya ada satu orang tua utama', function () {
        $student = Student::factory()->withGuardian(Guardian::factory()->create(['name' => 'Ibu Lama']))->create();
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE."/{$student->public_id}/orang-tua", [
            'mode' => 'new', 'new_name' => 'Ayah Baru', 'new_phone' => '0811', 'relationship' => 'AYAH', 'is_primary' => true,
        ])->assertSessionHasNoErrors();

        expect($student->guardians()->wherePivot('is_primary', true)->count())->toBe(1)
            ->and(primaryOf($student))->toBe('Ayah Baru');
    });

    it('satu orang tua dapat terhubung ke beberapa anak', function () {
        $guardian = Guardian::factory()->create();
        [$kakak, $adik] = Student::factory()->count(2)->create()->all();
        $this->actingAsRole(Role::Operator);

        foreach ([$kakak, $adik] as $child) {
            $this->post(SISWA_BASE."/{$child->public_id}/orang-tua", [
                'mode' => 'existing', 'guardian' => $guardian->public_id, 'relationship' => 'IBU',
            ])->assertSessionHasNoErrors();
        }

        expect($guardian->students()->count())->toBe(2);
    });

    it('menolak menghubungkan orang tua yang sama dua kali', function () {
        $guardian = Guardian::factory()->create();
        $student = Student::factory()->withGuardian($guardian)->create();
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE."/{$student->public_id}/orang-tua", [
            'mode' => 'existing', 'guardian' => $guardian->public_id, 'relationship' => 'IBU',
        ])->assertSessionHasErrors('guardian');
    });

    it('melepas orang tua utama mempromosikan orang tua lain', function () {
        $ibu = Guardian::factory()->create(['name' => 'Ibu']);
        $ayah = Guardian::factory()->create(['name' => 'Ayah']);
        $student = Student::factory()->withGuardian($ibu)->create();
        $student->guardians()->attach($ayah->id, ['relationship' => 'AYAH', 'is_primary' => false]);
        $this->actingAsRole(Role::Operator);

        $this->delete(SISWA_BASE."/{$student->public_id}/orang-tua/{$ibu->public_id}")->assertSessionHas('success');

        expect(primaryOf($student))->toBe('Ayah')->and($student->guardians()->count())->toBe(1);
    });

    it('menjadikan orang tua lain sebagai utama', function () {
        $ibu = Guardian::factory()->create(['name' => 'Ibu']);
        $ayah = Guardian::factory()->create(['name' => 'Ayah']);
        $student = Student::factory()->withGuardian($ibu)->create();
        $student->guardians()->attach($ayah->id, ['relationship' => 'AYAH', 'is_primary' => false]);
        $this->actingAsRole(Role::Operator);

        $this->post(SISWA_BASE."/{$student->public_id}/orang-tua/{$ayah->public_id}/utama")->assertSessionHas('success');

        expect(primaryOf($student))->toBe('Ayah');
    });

    it('menolak melepas orang tua yang tidak terhubung dengan siswa itu', function () {
        $student = Student::factory()->withGuardian()->create();
        $stranger = Guardian::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->delete(SISWA_BASE."/{$student->public_id}/orang-tua/{$stranger->public_id}")->assertNotFound();
    });

    it('orang tua yang masih terhubung ke siswa tidak bisa dihapus', function () {
        $guardian = Guardian::factory()->create();
        Student::factory()->withGuardian($guardian)->create();
        $this->actingAsRole(Role::Operator);

        $this->delete("/operator/master-data/orang-tua/{$guardian->public_id}")->assertSessionHasErrors('guardian');
        expect($guardian->fresh()->trashed())->toBeFalse();
    });

    it('pencarian orang tua untuk dialog hubungkan', function () {
        Guardian::factory()->create(['name' => 'Siti Aminah']);
        Guardian::factory()->create(['name' => 'Budi']);
        $this->actingAsRole(Role::Operator);

        $this->getJson('/operator/master-data/orang-tua-cari?q=siti')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Siti Aminah')
            ->assertJsonMissingPath('data.0.id');
    });
});

describe('foto siswa (ownership)', function () {
    it('foto siswa hanya bisa dibuka orang tuanya, operator, dan kepala sekolah', function () {
        Storage::fake('private');
        $parentUser = User::factory()->withRole(Role::Guardian)->create();
        $student = Student::factory()->withGuardian(Guardian::factory()->withAccount($parentUser)->create())->create();
        $this->actingAsRole(Role::Operator);
        $this->post(SISWA_BASE."/{$student->public_id}", studentPayload([
            'nis' => $student->nis, 'nisn' => null, 'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]))->assertSessionHasNoErrors();
        $file = File::query()->findOrFail($student->fresh()->photo_file_id);

        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Principal);
        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Guardian, $parentUser);
        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Guardian);
        $this->get("/files/{$file->public_id}")->assertForbidden();

        $this->actingAsRole(Role::Teacher);
        $this->get("/files/{$file->public_id}")->assertForbidden();
    });
});
