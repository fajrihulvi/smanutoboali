<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role as RoleModel;

describe('akses', function () {
    it('operator dapat membuka daftar pengguna', function () {
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Access::Operator/Users/Index')
                ->has('users.data')
                ->has('users.meta.total')
                ->missing('users.data.0.id'));
    });

    it('role lain tidak dapat membuka user management', function (Role $role) {
        $this->actingAsRole($role);

        $this->get('/operator/users')->assertForbidden();
        $this->post('/operator/users', [])->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);

    it('operator tanpa permission user.manage ditolak', function () {
        RoleModel::findByName('operator')->revokePermissionTo('user.manage');
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/users')->assertForbidden();
    });

    it('tamu diarahkan ke login', function () {
        $this->get('/operator/users')->assertRedirect('/login');
    });
});

describe('daftar pengguna', function () {
    it('memfilter berdasarkan pencarian, role, dan status', function () {
        $this->actingAsRole(Role::Operator);
        User::factory()->withRole(Role::Teacher)->create(['name' => 'Budi Guru']);
        User::factory()->withRole(Role::Guardian)->create(['name' => 'Siti Wali']);
        User::factory()->withRole(Role::Teacher)->inactive()->create(['name' => 'Budi Nonaktif']);

        $this->get('/operator/users?search=budi')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));

        $this->get('/operator/users?role=guardian')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Siti Wali'));

        $this->get('/operator/users?role=teacher&status=inactive')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.is_active', false));
    });

    it('memakai paginasi server-side', function () {
        $this->actingAsRole(Role::Operator);
        User::factory()->count(20)->withRole(Role::Teacher)->create();

        $this->get('/operator/users?page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.meta.current_page', 2)
                ->where('users.meta.per_page', 15)
                ->where('users.meta.total', 21));
    });
});

describe('membuat pengguna', function () {
    it('membuat user dengan multi-role dan mencatat audit log', function () {
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/users', [
            'name' => 'Dewi Lestari',
            'email' => 'Dewi@Sekolah.sch.id',
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
            'roles' => ['teacher', 'guardian'],
            'is_active' => true,
        ])->assertRedirect('/operator/users')->assertSessionHas('success');

        $user = User::query()->where('email', 'dewi@sekolah.sch.id')->firstOrFail();

        expect($user->getRoleNames()->sort()->values()->all())->toBe(['guardian', 'teacher'])
            ->and(Hash::check('rahasia-123', $user->password))->toBeTrue()
            ->and(strlen($user->public_id))->toBe(26);

        expect(Activity::query()->where('log_name', 'access')->where('event', 'created')->exists())->toBeTrue();
    });

    it('memvalidasi input', function () {
        $this->actingAsRole(Role::Operator);
        User::factory()->create(['email' => 'ada@skoolia.test']);

        $this->post('/operator/users', [
            'name' => '',
            'email' => 'ada@skoolia.test',
            'password' => 'a',
            'password_confirmation' => 'b',
            'roles' => ['superadmin'],
        ])->assertSessionHasErrors(['name', 'email', 'password', 'roles.0']);

        $this->post('/operator/users', ['roles' => []])->assertSessionHasErrors('roles');

        expect(session('errors')->first('roles'))->toContain('wajib');
    });
});

describe('mengubah pengguna', function () {
    it('memperbarui nama, email, dan role', function () {
        $this->actingAsRole(Role::Operator);
        $user = User::factory()->withRole(Role::Teacher)->create();

        $this->put("/operator/users/{$user->public_id}", [
            'name' => 'Nama Baru',
            'email' => 'baru@skoolia.test',
            'roles' => ['teacher', 'guardian'],
        ])->assertRedirect("/operator/users/{$user->public_id}/edit");

        $user->refresh();
        expect($user->name)->toBe('Nama Baru')
            ->and($user->hasRole('guardian'))->toBeTrue();
    });

    it('memakai public_id di URL, bukan id numerik', function () {
        $this->actingAsRole(Role::Operator);
        $user = User::factory()->withRole(Role::Teacher)->create();

        $this->get("/operator/users/{$user->id}/edit")->assertNotFound();
        $this->get("/operator/users/{$user->public_id}/edit")->assertOk();
    });

    it('operator tidak dapat mencabut role operator dari dirinya sendiri', function () {
        $operator = $this->actingAsRole(Role::Operator);

        $this->put("/operator/users/{$operator->public_id}", [
            'name' => $operator->name,
            'email' => $operator->email,
            'roles' => ['teacher'],
        ])->assertSessionHasErrors('roles');

        expect($operator->fresh()->hasRole('operator'))->toBeTrue();
    });
});

describe('reset password', function () {
    it('mengganti password dan menghapus sesi user', function () {
        $this->actingAsRole(Role::Operator);
        $user = User::factory()->withRole(Role::Teacher)->create();
        DB::table('sessions')->insert([
            'id' => 'sesi-lama',
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => time(),
        ]);
        config(['session.driver' => 'database']);

        $this->put("/operator/users/{$user->public_id}/password", [
            'password' => 'password-baru-1',
            'password_confirmation' => 'password-baru-1',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        expect(Hash::check('password-baru-1', $user->fresh()->password))->toBeTrue()
            ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();
    });
});

describe('status aktif', function () {
    it('menonaktifkan dan mengaktifkan kembali user', function () {
        $this->actingAsRole(Role::Operator);
        $user = User::factory()->withRole(Role::Teacher)->create();

        $this->patch("/operator/users/{$user->public_id}/status", ['is_active' => false])
            ->assertSessionHas('success');
        expect($user->fresh()->is_active)->toBeFalse();

        $this->patch("/operator/users/{$user->public_id}/status", ['is_active' => true]);
        expect($user->fresh()->is_active)->toBeTrue();

        expect(Activity::query()->where('event', 'deactivated')->exists())->toBeTrue();
    });

    it('operator tidak dapat menonaktifkan dirinya sendiri', function () {
        $operator = $this->actingAsRole(Role::Operator);

        $this->patch("/operator/users/{$operator->public_id}/status", ['is_active' => false])
            ->assertForbidden();

        expect($operator->fresh()->is_active)->toBeTrue();
    });

    it('user yang dinonaktifkan tidak dapat login', function () {
        $this->actingAsRole(Role::Operator);
        $user = User::factory()->withRole(Role::Teacher)->create();
        $this->patch("/operator/users/{$user->public_id}/status", ['is_active' => false]);

        auth()->logout();

        $this->post('/login/guru', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    });
});
