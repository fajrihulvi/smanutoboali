<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

/**
 * Guru (akun + profil) dengan satu penugasan di rombel tahun aktif.
 *
 * @return array{user: User, assignment: TeachingAssignment}
 */
function teacherWithAssignment(): array
{
    $user = User::factory()->withRole(Role::Teacher)->create();
    $classroom = Classroom::factory()->for(AcademicYear::query()->where('is_active', true)->first() ?? activeAcademicYear())->create();
    $assignment = assignTeaching($classroom, Teacher::factory()->withAccount($user)->create());

    return compact('user', 'assignment');
}

function pdfUpload(string $name = 'modul.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
}

it('guru menambah materi file dan tautan, lalu melihatnya di daftar', function () {
    ['user' => $user, 'assignment' => $assignment] = teacherWithAssignment();
    $this->actingAsRole(Role::Teacher, $user);

    $this->post('/guru/akademik/materi', [
        'teaching_assignment' => $assignment->public_id,
        'title' => 'Modul Bilangan',
        'type' => 'FILE',
        'file' => pdfUpload(),
    ])->assertSessionHasNoErrors();

    $this->post('/guru/akademik/materi', [
        'teaching_assignment' => $assignment->public_id,
        'title' => 'Video pecahan',
        'type' => 'LINK',
        'url' => 'https://example.com/video',
    ])->assertSessionHasNoErrors();

    $file = File::query()->sole();
    expect($file->fileable_type)->toBe((new LearningMaterial)->getMorphClass())
        ->and($file->visibility->value)->toBe('private');

    $this->get('/guru/akademik/materi')->assertInertia(fn (Assert $page) => $page
        ->component('Academic::Teacher/Materials/Index')
        ->has('materials.data', 2)
        ->has('assignments', 1)
        ->missing('materials.data.0.id'));
});

it('validasi: file wajib untuk tipe FILE, tautan harus http(s), tipe file dibatasi', function () {
    ['user' => $user, 'assignment' => $assignment] = teacherWithAssignment();
    $this->actingAsRole(Role::Teacher, $user);
    $base = ['teaching_assignment' => $assignment->public_id, 'title' => 'Materi'];

    $this->post('/guru/akademik/materi', [...$base, 'type' => 'FILE'])->assertSessionHasErrors('file');
    $this->post('/guru/akademik/materi', [...$base, 'type' => 'LINK', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
    $this->post('/guru/akademik/materi', [
        ...$base, 'type' => 'FILE', 'file' => UploadedFile::fake()->createWithContent('x.pdf', '<?php echo 1;'),
    ])->assertSessionHasErrors('file');

    expect(LearningMaterial::query()->count())->toBe(0);
});

it('mengganti file ke tautan membuang file lama', function () {
    ['user' => $user, 'assignment' => $assignment] = teacherWithAssignment();
    $this->actingAsRole(Role::Teacher, $user);

    $this->post('/guru/akademik/materi', [
        'teaching_assignment' => $assignment->public_id, 'title' => 'Modul', 'type' => 'FILE', 'file' => pdfUpload(),
    ]);
    $material = LearningMaterial::query()->sole();

    $this->put("/guru/akademik/materi/{$material->public_id}", [
        'teaching_assignment' => $assignment->public_id, 'title' => 'Modul', 'type' => 'LINK', 'url' => 'https://example.com',
    ])->assertSessionHasNoErrors();

    expect(File::query()->count())->toBe(0)
        ->and($material->fresh()->url)->toBe('https://example.com');
});

it('guru tidak bisa memakai penugasan guru lain atau mengubah materi guru lain', function () {
    ['user' => $owner, 'assignment' => $assignment] = teacherWithAssignment();
    ['user' => $other] = teacherWithAssignment();
    $material = LearningMaterial::query()->create([
        'academic_year_id' => $assignment->academic_year_id, 'teaching_assignment_id' => $assignment->id,
        'title' => 'Milik guru A', 'type' => 'LINK', 'url' => 'https://example.com', 'created_by' => $owner->id,
    ]);
    $this->actingAsRole(Role::Teacher, $other);

    $this->post('/guru/akademik/materi', [
        'teaching_assignment' => $assignment->public_id, 'title' => 'Sisip', 'type' => 'LINK', 'url' => 'https://example.com',
    ])->assertSessionHasErrors('teaching_assignment');

    $this->put("/guru/akademik/materi/{$material->public_id}", [
        'teaching_assignment' => $assignment->public_id, 'title' => 'Ubah', 'type' => 'LINK', 'url' => 'https://example.com',
    ])->assertForbidden();
    $this->delete("/guru/akademik/materi/{$material->public_id}")->assertForbidden();

    $this->get('/guru/akademik/materi')->assertInertia(fn (Assert $page) => $page->has('materials.data', 0));
});

it('penugasan tahun ajaran lama ditolak', function () {
    ['user' => $user] = teacherWithAssignment();
    $teacher = Teacher::activeForUser($user);
    $old = assignTeaching(Classroom::factory()->for(AcademicYear::factory()->startingIn(2020)->create())->create(), $teacher);
    $this->actingAsRole(Role::Teacher, $user);

    $this->post('/guru/akademik/materi', [
        'teaching_assignment' => $old->public_id, 'title' => 'Lama', 'type' => 'LINK', 'url' => 'https://example.com',
    ])->assertSessionHasErrors('teaching_assignment');
});

it('guru menghapus materinya beserta file', function () {
    ['user' => $user, 'assignment' => $assignment] = teacherWithAssignment();
    $this->actingAsRole(Role::Teacher, $user);
    $this->post('/guru/akademik/materi', [
        'teaching_assignment' => $assignment->public_id, 'title' => 'Modul', 'type' => 'FILE', 'file' => pdfUpload(),
    ]);
    $material = LearningMaterial::query()->sole();

    $this->delete("/guru/akademik/materi/{$material->public_id}")->assertSessionHasNoErrors();

    expect(LearningMaterial::query()->count())->toBe(0)->and(File::query()->count())->toBe(0);
});

describe('akses file materi', function () {
    it('orang tua siswa di rombel tersebut boleh mengunduh; orang tua lain & guru lain tidak', function () {
        ['user' => $teacherUser, 'assignment' => $assignment] = teacherWithAssignment();
        $this->actingAsRole(Role::Teacher, $teacherUser);
        $this->post('/guru/akademik/materi', [
            'teaching_assignment' => $assignment->public_id, 'title' => 'Modul', 'type' => 'FILE', 'file' => pdfUpload(),
        ]);
        $file = File::query()->sole();

        $parent = User::factory()->withRole(Role::Guardian)->create();
        enrollIn($assignment->classroom, Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create());
        $this->actingAsRole(Role::Guardian, $parent);
        $this->get("/files/{$file->public_id}")->assertOk();

        $otherParent = User::factory()->withRole(Role::Guardian)->create();
        Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create();
        $this->actingAsRole(Role::Guardian, $otherParent);
        $this->get("/files/{$file->public_id}")->assertForbidden();

        ['user' => $otherTeacher] = teacherWithAssignment();
        $this->actingAsRole(Role::Teacher, $otherTeacher);
        $this->get("/files/{$file->public_id}")->assertForbidden();
    });

    it('orang tua melihat materi di halaman akademik anak', function () {
        ['assignment' => $assignment] = teacherWithAssignment();
        LearningMaterial::query()->create([
            'academic_year_id' => $assignment->academic_year_id, 'teaching_assignment_id' => $assignment->id,
            'title' => 'Latihan', 'type' => 'LINK', 'url' => 'https://example.com',
        ]);
        $parent = User::factory()->withRole(Role::Guardian)->create();
        enrollIn($assignment->classroom, Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create());
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get('/orang-tua/akademik')->assertInertia(fn (Assert $page) => $page
            ->has('subjects.0.materials', 1)
            ->where('subjects.0.materials.0.title', 'Latihan'));
    });
});

it('role selain guru tidak dapat mengelola materi', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/guru/akademik/materi')->assertForbidden();
    $this->post('/guru/akademik/materi', [])->assertForbidden();
})->with([Role::Operator, Role::Principal, Role::Guardian]);
