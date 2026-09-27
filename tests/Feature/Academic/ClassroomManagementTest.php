<?php

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use Inertia\Testing\AssertableInertia as Assert;

describe('akses halaman rombel', function () {
    it('operator membuka daftar rombel tahun aktif', function () {
        $year = activeAcademicYear();
        Classroom::factory()->for($year)->create(['name' => 'VII-A']);
        Classroom::factory()->for(AcademicYear::factory()->startingIn(2025)->create())->create(['name' => 'VII-Z']);
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/master-data/kelas')->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Operator/Classrooms/Index')
            ->where('year.value', $year->public_id)
            ->has('classrooms.data', 1)
            ->where('classrooms.data.0.name', 'VII-A')
            ->where('classrooms.data.0.grade_label', 'VII')
            ->missing('classrooms.data.0.id'));
    });

    it('role selain operator ditolak', function (Role $role) {
        $classroom = Classroom::factory()->create();
        $this->actingAsRole($role);

        $this->get('/operator/master-data/kelas')->assertForbidden();
        $this->get("/operator/master-data/kelas/{$classroom->public_id}")->assertForbidden();
        $this->post('/operator/master-data/kelas', [])->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);

    it('id numerik tidak dikenali di URL', function () {
        $classroom = Classroom::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->get("/operator/master-data/kelas/{$classroom->id}")->assertNotFound();
    });
});

describe('CRUD rombel', function () {
    it('membuat rombel dengan wali kelas', function () {
        $year = activeAcademicYear();
        $teacher = Teacher::factory()->create();
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/master-data/kelas', [
            'academic_year' => $year->public_id,
            'name' => 'VII-A',
            'grade' => 7,
            'homeroom_teacher' => $teacher->public_id,
            'capacity' => 30,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $classroom = Classroom::query()->sole();
        expect($classroom->homeroom_teacher_id)->toBe($teacher->id)
            ->and($classroom->academic_year_id)->toBe($year->id);
    });

    it('nama rombel unik per tahun ajaran dan tingkat harus sesuai jenjang', function () {
        $year = activeAcademicYear();
        Classroom::factory()->for($year)->create(['name' => 'VII-A']);
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/master-data/kelas', [
            'academic_year' => $year->public_id,
            'name' => 'VII-A',
            'grade' => 12,
        ])->assertSessionHasErrors(['name', 'grade']);
    });

    it('nama rombel yang sama boleh di tahun ajaran berbeda', function () {
        Classroom::factory()->for(AcademicYear::factory()->startingIn(2025)->create())->create(['name' => 'VII-A']);
        $year = activeAcademicYear();
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/master-data/kelas', [
            'academic_year' => $year->public_id,
            'name' => 'VII-A',
            'grade' => 7,
        ])->assertSessionHasNoErrors();
    });

    it('satu guru hanya menjadi wali satu rombel per tahun ajaran', function () {
        $year = activeAcademicYear();
        $teacher = Teacher::factory()->create();
        Classroom::factory()->for($year)->create(['homeroom_teacher_id' => $teacher->id]);
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/master-data/kelas', [
            'academic_year' => $year->public_id,
            'name' => 'VIII-A',
            'grade' => 8,
            'homeroom_teacher' => $teacher->public_id,
        ])->assertSessionHasErrors('homeroom_teacher');
    });

    it('kapasitas tidak boleh di bawah jumlah siswa aktif', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create(['capacity' => 30]);
        Student::factory()->count(3)->create()->each(fn (Student $s) => enrollIn($classroom, $s));
        $this->actingAsRole(Role::Operator);

        $this->put("/operator/master-data/kelas/{$classroom->public_id}", [
            'name' => $classroom->name,
            'grade' => 7,
            'capacity' => 2,
        ])->assertSessionHasErrors('capacity');
    });

    it('rombel kosong bisa dihapus, rombel berisi riwayat siswa tidak', function () {
        $year = activeAcademicYear();
        $empty = Classroom::factory()->for($year)->create();
        $filled = Classroom::factory()->for($year)->create();
        enrollIn($filled, Student::factory()->create(), EnrollmentStatus::Moved);
        $this->actingAsRole(Role::Operator);

        $this->delete("/operator/master-data/kelas/{$empty->public_id}")->assertSessionHasNoErrors();
        $this->delete("/operator/master-data/kelas/{$filled->public_id}")->assertSessionHasErrors('classroom');

        expect(Classroom::query()->pluck('id')->all())->toBe([$filled->id]);
    });

    it('detail rombel menampilkan siswa aktif dan penugasan', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $active = Student::factory()->create();
        enrollIn($classroom, $active);
        enrollIn($classroom, Student::factory()->create(), EnrollmentStatus::Moved);
        assignTeaching($classroom, Teacher::factory()->create());
        $this->actingAsRole(Role::Operator);

        $this->get("/operator/master-data/kelas/{$classroom->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Operator/Classrooms/Show')
            ->has('students', 1)
            ->where('students.0.public_id', $active->public_id)
            ->has('assignments', 1)
            ->where('classroom.students_count', 1));
    });
});

describe('anggota rombel', function () {
    it('memasukkan siswa aktif ke rombel', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $students = Student::factory()->count(2)->create();
        $this->actingAsRole(Role::Operator);

        $this->post("/operator/master-data/kelas/{$classroom->public_id}/siswa", [
            'students' => $students->pluck('public_id')->all(),
        ])->assertSessionHasNoErrors();

        expect($classroom->activeEnrollments()->count())->toBe(2);
    });

    it('siswa tidak aktif ditolak', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $student = Student::factory()->create(['status' => StudentStatus::Graduated]);
        $this->actingAsRole(Role::Operator);

        $this->post("/operator/master-data/kelas/{$classroom->public_id}/siswa", [
            'students' => [$student->public_id],
        ])->assertSessionHasErrors('students');

        expect(ClassEnrollment::query()->count())->toBe(0);
    });

    it('siswa yang sudah punya rombel tahun ini hanya bisa dipindah dengan opsi pindahkan', function () {
        $year = activeAcademicYear();
        $from = Classroom::factory()->for($year)->create();
        $to = Classroom::factory()->for($year)->create();
        $student = Student::factory()->create();
        enrollIn($from, $student);
        $this->actingAsRole(Role::Operator);

        $url = "/operator/master-data/kelas/{$to->public_id}/siswa";
        $this->post($url, ['students' => [$student->public_id]])->assertSessionHasErrors('students');
        $this->post($url, ['students' => [$student->public_id], 'move' => true])->assertSessionHasNoErrors();

        expect($from->enrollments()->sole()->status)->toBe(EnrollmentStatus::Moved)
            ->and($to->activeEnrollments()->sole()->student_id)->toBe($student->id);
    });

    it('kapasitas rombel ditegakkan', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create(['capacity' => 1]);
        $students = Student::factory()->count(2)->create();
        $this->actingAsRole(Role::Operator);

        $this->post("/operator/master-data/kelas/{$classroom->public_id}/siswa", [
            'students' => $students->pluck('public_id')->all(),
        ])->assertSessionHasErrors('students');

        expect(ClassEnrollment::query()->count())->toBe(0);
    });

    it('mengeluarkan siswa dari rombel', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $student = Student::factory()->create();
        enrollIn($classroom, $student);
        $this->actingAsRole(Role::Operator);

        $this->delete("/operator/master-data/kelas/{$classroom->public_id}/siswa/{$student->public_id}")
            ->assertSessionHasNoErrors();

        expect($classroom->enrollments()->count())->toBe(0);
    });

    it('calon siswa hanya siswa aktif yang belum punya rombel', function () {
        $year = activeAcademicYear();
        $classroom = Classroom::factory()->for($year)->create();
        $free = Student::factory()->create(['name' => 'Bebas Rombel']);
        enrollIn(Classroom::factory()->for($year)->create(), Student::factory()->create());
        Student::factory()->create(['status' => StudentStatus::Dropped]);
        $this->actingAsRole(Role::Operator);

        $this->getJson("/operator/master-data/kelas/{$classroom->public_id}/calon-siswa")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $free->public_id)
            ->assertJsonMissingPath('data.0.id');

        $this->getJson("/operator/master-data/kelas/{$classroom->public_id}/calon-siswa?tanpa_rombel=0")
            ->assertJsonCount(2, 'data');
    });

    it('riwayat rombel tampil di form siswa', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create(['name' => 'VII-A']);
        $student = Student::factory()->create();
        enrollIn($classroom, $student);
        $this->actingAsRole(Role::Operator);

        $this->get("/operator/master-data/siswa/{$student->public_id}/ubah")->assertInertia(fn (Assert $page) => $page
            ->has('enrollments', 1)
            ->where('enrollments.0.classroom', 'VII-A')
            ->where('enrollments.0.status.value', EnrollmentStatus::Active->value));
    });
});

describe('penjaga penghapusan', function () {
    it('tahun ajaran yang memiliki rombel tidak dapat dihapus', function () {
        activeAcademicYear();
        $old = AcademicYear::factory()->startingIn(2024)->create();
        Classroom::factory()->for($old)->create();
        $this->actingAsRole(Role::Operator);

        $this->delete("/operator/master-data/tahun-ajaran/{$old->public_id}")->assertSessionHasErrors('academic_year');

        expect($old->fresh())->not->toBeNull();
    });

    it('guru yang masih mengajar atau menjadi wali kelas tidak dapat dihapus', function () {
        $year = activeAcademicYear();
        $teaching = Teacher::factory()->create();
        $homeroom = Teacher::factory()->create();
        assignTeaching(Classroom::factory()->for($year)->create(), $teaching);
        Classroom::factory()->for($year)->create(['homeroom_teacher_id' => $homeroom->id]);
        $this->actingAsRole(Role::Operator);

        $this->delete("/operator/master-data/guru/{$teaching->public_id}")->assertSessionHasErrors('teacher');
        $this->delete("/operator/master-data/guru/{$homeroom->public_id}")->assertSessionHasErrors('teacher');

        expect(Teacher::query()->count())->toBe(2);
    });
});
