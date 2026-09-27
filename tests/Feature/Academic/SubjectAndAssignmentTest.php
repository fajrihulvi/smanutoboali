<?php

use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use Inertia\Testing\AssertableInertia as Assert;

describe('mata pelajaran', function () {
    it('operator membuat, mengubah, dan mencari mapel', function () {
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/master-data/mapel', [
            'code' => 'MTK',
            'name' => 'Matematika',
            'group' => SubjectGroup::General->value,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $subject = Subject::query()->sole();

        $this->put("/operator/master-data/mapel/{$subject->public_id}", [
            'code' => 'MTK',
            'name' => 'Matematika Lanjut',
            'group' => SubjectGroup::General->value,
            'is_active' => false,
        ])->assertSessionHasNoErrors();

        expect($subject->fresh())->name->toBe('Matematika Lanjut')->is_active->toBeFalse();

        $this->get('/operator/master-data/mapel?search=lanjut')->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Operator/Subjects/Index')
            ->has('subjects.data', 1)
            ->missing('subjects.data.0.id'));
    });

    it('kode mapel unik', function () {
        Subject::factory()->create(['code' => 'MTK']);
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/master-data/mapel', [
            'code' => 'MTK',
            'name' => 'Matematika',
            'group' => SubjectGroup::General->value,
        ])->assertSessionHasErrors('code');
    });

    it('mapel yang dipakai penugasan tidak dapat dihapus', function () {
        $used = Subject::factory()->create();
        $unused = Subject::factory()->create();
        assignTeaching(Classroom::factory()->create(), Teacher::factory()->create(), $used);
        $this->actingAsRole(Role::Operator);

        $this->delete("/operator/master-data/mapel/{$used->public_id}")->assertSessionHasErrors('subject');
        $this->delete("/operator/master-data/mapel/{$unused->public_id}")->assertSessionHasNoErrors();

        expect(Subject::query()->pluck('id')->all())->toBe([$used->id]);
    });

    it('role selain operator ditolak', function (Role $role) {
        $this->actingAsRole($role);

        $this->get('/operator/master-data/mapel')->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);
});

describe('penugasan mengajar', function () {
    it('matriks memuat rombel, mapel aktif, dan penugasan tahun terpilih', function () {
        $year = activeAcademicYear();
        $classroom = Classroom::factory()->for($year)->create();
        $subject = Subject::factory()->create();
        Subject::factory()->create(['is_active' => false]);
        $teacher = Teacher::factory()->create();
        assignTeaching($classroom, $teacher, $subject);
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/akademik/penugasan')->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Operator/Assignments/Index')
            ->has('classrooms', 1)
            ->has('subjects', 1)
            ->where("assignments.{$classroom->public_id}:{$subject->public_id}", $teacher->public_id));
    });

    it('menetapkan, mengganti, dan mengosongkan guru pengampu', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $subject = Subject::factory()->create();
        [$first, $second] = Teacher::factory()->count(2)->create()->all();
        $this->actingAsRole(Role::Operator);

        $payload = ['classroom' => $classroom->public_id, 'subject' => $subject->public_id];

        $this->put('/operator/akademik/penugasan', [...$payload, 'teacher' => $first->public_id])->assertSessionHasNoErrors();
        $this->put('/operator/akademik/penugasan', [...$payload, 'teacher' => $second->public_id])->assertSessionHasNoErrors();

        expect(TeachingAssignment::query()->sole())
            ->teacher_id->toBe($second->id)
            ->academic_year_id->toBe($classroom->academic_year_id);

        $this->put('/operator/akademik/penugasan', [...$payload, 'teacher' => null])->assertSessionHasNoErrors();

        expect(TeachingAssignment::query()->count())->toBe(0);
    });

    it('guru nonaktif dan mapel nonaktif ditolak', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $this->actingAsRole(Role::Operator);

        $this->put('/operator/akademik/penugasan', [
            'classroom' => $classroom->public_id,
            'subject' => Subject::factory()->create(['is_active' => false])->public_id,
            'teacher' => Teacher::factory()->inactive()->create()->public_id,
        ])->assertSessionHasErrors(['subject', 'teacher']);
    });

    it('role selain operator ditolak', function (Role $role) {
        $this->actingAsRole($role);

        $this->get('/operator/akademik/penugasan')->assertForbidden();
        $this->put('/operator/akademik/penugasan', [])->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);
});
