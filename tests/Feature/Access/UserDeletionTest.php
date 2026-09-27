<?php

use App\Models\User;
use App\Modules\Access\Actions\DeleteUserAction;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

it('operator menghapus akun user lain secara permanen dan tercatat di audit log', function () {
    $target = User::factory()->withRole(Role::Principal)->create(['email' => 'hapus@skoolia.test']);
    $this->actingAsRole(Role::Operator);

    $this->from('/operator/users?page=2')
        ->delete("/operator/users/{$target->public_id}")
        ->assertRedirect('/operator/users?page=2')
        ->assertSessionHasNoErrors();

    expect(User::query()->find($target->id))->toBeNull()
        ->and(DB::table('model_has_roles')->where('model_id', $target->id)->count())->toBe(0);

    $log = Activity::query()->where('log_name', 'access')->where('event', 'deleted')->sole();
    expect($log->properties['email'])->toBe('hapus@skoolia.test');
});

it('operator tidak dapat menghapus akunnya sendiri', function () {
    $operator = $this->actingAsRole(Role::Operator);

    $this->delete("/operator/users/{$operator->public_id}")->assertForbidden();

    expect($operator->fresh())->not->toBeNull();
});

it('akun yang tertaut ke data guru atau orang tua ditolak sampai dilepas', function () {
    $teacherUser = User::factory()->withRole(Role::Teacher)->create();
    $teacher = Teacher::factory()->withAccount($teacherUser)->create();
    $guardianUser = User::factory()->withRole(Role::Guardian)->create();
    Guardian::factory()->withAccount($guardianUser)->create();
    $this->actingAsRole(Role::Operator);

    $this->delete("/operator/users/{$teacherUser->public_id}")->assertSessionHasErrors('user');
    $this->delete("/operator/users/{$guardianUser->public_id}")->assertSessionHasErrors('user');

    $this->delete("/operator/master-data/guru/{$teacher->public_id}/akun")->assertSessionHasNoErrors();
    $this->delete("/operator/users/{$teacherUser->public_id}")->assertSessionHasNoErrors();

    expect(User::query()->find($teacherUser->id))->toBeNull()
        ->and(User::query()->find($guardianUser->id))->not->toBeNull();
});

it('data guru yang sudah dihapus (tempat sampah) tidak menghalangi hapus akun', function () {
    $user = User::factory()->withRole(Role::Teacher)->create();
    $teacher = Teacher::factory()->withAccount($user)->create();
    $teacher->delete();
    $this->actingAsRole(Role::Operator);

    $this->delete("/operator/users/{$user->public_id}")->assertSessionHasNoErrors();

    expect(Teacher::withTrashed()->find($teacher->id)->user_id)->toBeNull();
});

it('operator aktif terakhir tidak dapat dihapus', function () {
    $lastOperator = User::factory()->withRole(Role::Operator)->create();
    $actor = User::factory()->withRole(Role::Principal)->create();

    expect(fn () => app(DeleteUserAction::class)->handle($lastOperator, $actor))
        ->toThrow(ValidationException::class, 'Operator aktif terakhir');
});

it('role lain tidak dapat menghapus user', function (Role $role) {
    $target = User::factory()->create();
    $this->actingAsRole($role);

    $this->delete("/operator/users/{$target->public_id}")->assertForbidden();

    expect($target->fresh())->not->toBeNull();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);
