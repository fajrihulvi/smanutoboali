<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

const ORTU_BASE = '/operator/master-data/orang-tua';

function guardianPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ahmad Hidayat',
        'nik' => '1971010101800001',
        'phone' => '081234567890',
        'email' => 'ahmad@contoh.id',
        'occupation' => 'Wiraswasta',
        'address' => 'Jl. Melati 5',
    ], $overrides);
}

it('operator membuka daftar orang tua tanpa NIK utuh', function () {
    Guardian::factory()->create(['nik' => '1971010101800001']);
    $this->actingAsRole(Role::Operator);

    $this->get(ORTU_BASE)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('People::Operator/Guardians/Index')
            ->has('guardians.data', 1)
            ->where('guardians.data.0.nik_masked', '••••••••••••0001')
            ->missing('guardians.data.0.nik'));
});

it('role lain tidak dapat mengelola orang tua', function (Role $role) {
    $this->actingAsRole($role);

    $this->get(ORTU_BASE)->assertForbidden();
    $this->post(ORTU_BASE, guardianPayload())->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('menyimpan NIK terenkripsi dan tidak mencatatnya di audit log', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(ORTU_BASE, guardianPayload())->assertSessionHasNoErrors();

    $raw = DB::table('guardians')->value('nik');
    expect($raw)->not->toBe('1971010101800001')
        ->and(Guardian::query()->first()->nik)->toBe('1971010101800001');

    $log = Activity::query()->where('log_name', 'people')->firstOrFail();
    expect(json_encode($log->properties))->not->toContain('1971010101800001');
});

it('mengosongkan NIK saat ubah berarti NIK tidak diubah', function () {
    $guardian = Guardian::factory()->create(['nik' => '1971010101800001']);
    $this->actingAsRole(Role::Operator);

    $this->put(ORTU_BASE."/{$guardian->public_id}", guardianPayload(['nik' => '', 'name' => 'Nama Baru']))
        ->assertSessionHasNoErrors();

    $guardian->refresh();
    expect($guardian->name)->toBe('Nama Baru')->and($guardian->nik)->toBe('1971010101800001');
});

it('membuat akun portal orang tua dengan email data', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(ORTU_BASE, guardianPayload(['create_account' => true]))->assertSessionHas('credentials');

    $guardian = Guardian::query()->with('user')->firstOrFail();
    expect($guardian->user?->email)->toBe('ahmad@contoh.id')
        ->and($guardian->user->hasRole('guardian'))->toBeTrue();
});

it('mewajibkan email bila membuat akun', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(ORTU_BASE, guardianPayload(['email' => '', 'create_account' => true]))
        ->assertSessionHasErrors(['email' => 'Email wajib diisi untuk membuat akun login.']);
});

it('memvalidasi NIK 16 digit', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(ORTU_BASE, guardianPayload(['nik' => '123']))->assertSessionHasErrors('nik');
});

describe('profil orang tua sendiri', function () {
    it('orang tua mengubah kontak sendiri', function () {
        $user = User::factory()->withRole(Role::Guardian)->create();
        $guardian = Guardian::factory()->withAccount($user)->create();
        $this->actingAsRole(Role::Guardian, $user);

        $this->get('/orang-tua/profil')->assertInertia(fn (Assert $page) => $page
            ->component('People::Guardian/Profile')
            ->where('guardian.public_id', $guardian->public_id));

        $this->put('/orang-tua/profil', ['phone' => '0899', 'occupation' => 'Petani', 'address' => 'Desa', 'name' => 'Hack'])
            ->assertSessionHasNoErrors();

        $guardian->refresh();
        expect($guardian->phone)->toBe('0899')
            ->and($guardian->occupation)->toBe('Petani')
            ->and($guardian->name)->not->toBe('Hack');
    });

    it('orang tua lain tidak bisa mengubah profil milik orang tua lain', function () {
        $owner = User::factory()->withRole(Role::Guardian)->create();
        $guardian = Guardian::factory()->withAccount($owner)->create(['phone' => '0811']);
        $this->actingAsRole(Role::Guardian);

        // Endpoint selalu memakai data milik user login; tidak ada parameter id.
        $this->put('/orang-tua/profil', ['phone' => '0899'])->assertNotFound();
        expect($guardian->fresh()->phone)->toBe('0811');
    });
});
