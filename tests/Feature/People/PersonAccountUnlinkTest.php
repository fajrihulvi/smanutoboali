<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Spatie\Activitylog\Models\Activity;

it('operator melepas akun dari data guru dan orang tua tanpa menghapus akunnya', function () {
    $teacher = Teacher::factory()->withAccount()->create();
    $guardian = Guardian::factory()->withAccount()->create();
    $teacherUserId = $teacher->user_id;
    $this->actingAsRole(Role::Operator);

    $this->delete("/operator/master-data/guru/{$teacher->public_id}/akun")->assertSessionHasNoErrors();
    $this->delete("/operator/master-data/orang-tua/{$guardian->public_id}/akun")->assertSessionHasNoErrors();

    expect($teacher->fresh()->user_id)->toBeNull()
        ->and($guardian->fresh()->user_id)->toBeNull()
        ->and(User::query()->find($teacherUserId))->not->toBeNull()
        ->and(Activity::query()->where('event', 'account_unlinked')->count())->toBe(2);
});

it('role lain tidak dapat melepas akun', function (Role $role) {
    $teacher = Teacher::factory()->withAccount()->create();
    $this->actingAsRole($role);

    $this->delete("/operator/master-data/guru/{$teacher->public_id}/akun")->assertForbidden();

    expect($teacher->fresh()->user_id)->not->toBeNull();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('hapus dari daftar kembali ke halaman & filter yang sama, dari form ke daftar', function () {
    [$a, $b] = Teacher::factory()->count(2)->create()->all();
    $this->actingAsRole(Role::Operator);

    $this->from('/operator/master-data/guru?search=abc&page=2')
        ->delete("/operator/master-data/guru/{$a->public_id}")
        ->assertRedirect('/operator/master-data/guru?search=abc&page=2');

    $this->from("/operator/master-data/guru/{$b->public_id}/ubah")
        ->delete("/operator/master-data/guru/{$b->public_id}")
        ->assertRedirect('/operator/master-data/guru');
});
