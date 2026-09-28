<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Models\GradeDescription;
use App\Modules\Assessment\Notifications\GradePublishedNotification;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(fn () => $this->travelTo('2026-09-28 10:00:00'));

/**
 * Guru MTK di VII-A (tahun aktif) dengan dua siswa; siswa pertama punya akun orang tua.
 *
 * @return array{user: User, assignment: TeachingAssignment, semester: Semester, students: list<Student>, parent: User}
 */
function gradebookScenario(): array
{
    $year = activeAcademicYear();
    $user = User::factory()->withRole(Role::Teacher)->create();
    $classroom = Classroom::factory()->for($year)->create(['name' => 'VII-A']);
    $assignment = assignTeaching($classroom, Teacher::factory()->withAccount($user)->create(), Subject::factory()->create(['name' => 'Matematika']));
    $parent = User::factory()->withRole(Role::Guardian)->create();
    $students = [
        Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani', 'nis' => '1001']),
        Student::factory()->create(['name' => 'Budi', 'nis' => '1002']),
    ];

    foreach ($students as $student) {
        enrollIn($classroom, $student);
    }

    $semester = Semester::query()->where('academic_year_id', $year->id)->orderBy('start_date')->firstOrFail();

    return compact('user', 'assignment', 'semester', 'students', 'parent');
}

function component(TeachingAssignment $assignment, Semester $semester, string $name, int $weight, bool $published = false): GradeComponent
{
    return GradeComponent::query()->create([
        'academic_year_id' => $assignment->academic_year_id, 'semester_id' => $semester->id,
        'teaching_assignment_id' => $assignment->id, 'type' => ComponentType::DailyTest, 'name' => $name, 'weight' => $weight,
        'published_at' => $published ? now() : null,
    ]);
}

function score(GradeComponent $component, Student $student, float $value): void
{
    Grade::query()->create(['grade_component_id' => $component->id, 'student_id' => $student->id, 'score' => $value]);
}

describe('perhitungan nilai akhir', function () {
    it('Σ nilai × bobot ÷ 100, dibulatkan; kosong atau bobot ≠ 100 → belum dihitung', function () {
        ['assignment' => $a, 'semester' => $sem, 'students' => [$ani, $budi]] = gradebookScenario();
        $tugas = component($a, $sem, 'Tugas', 20);
        $uh = component($a, $sem, 'UH', 30);
        $pts = component($a, $sem, 'PTS', 20);
        $pas = component($a, $sem, 'PAS', 30);

        foreach ([[$tugas, 80], [$uh, 85], [$pts, 90], [$pas, 87.5]] as [$c, $v]) {
            score($c, $ani, $v);
        }
        score($tugas, $budi, 70);

        $book = Gradebook::make($a, $sem);
        // 16 + 25,5 + 18 + 26,25 = 85,75 → 86
        expect($book->finalScore($ani))->toBe(86)
            ->and($book->predicate($ani)?->value)->toBe('B')
            ->and($book->finalScore($budi))->toBeNull();

        $pas->update(['weight' => 20]);
        expect(Gradebook::make($a, $sem)->finalScore($ani))->toBeNull();
    });

    it('predikat mengikuti batas di pengaturan sekolah', function () {
        app(SettingsService::class)->set([SettingKey::GradePredicateA->value => 85]);
        ['assignment' => $a, 'semester' => $sem, 'students' => [$ani]] = gradebookScenario();
        score(component($a, $sem, 'PAS', 100), $ani, 86);

        expect(Gradebook::make($a, $sem)->predicate($ani)?->value)->toBe('A');
    });
});

describe('akses buku nilai', function () {
    it('guru pengampu membuka daftar & buku nilainya', function () {
        ['user' => $user, 'assignment' => $a, 'semester' => $sem] = gradebookScenario();
        $this->actingAsRole(Role::Teacher, $user);

        $this->get('/guru/akademik/nilai')->assertInertia(fn (Assert $page) => $page
            ->component('Assessment::Teacher/Index')
            ->has('assignments', 1));

        $this->get("/guru/akademik/nilai/{$a->public_id}?semester={$sem->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Assessment::Shared/Gradebook')
            ->where('readOnly', false)
            ->has('students', 2)
            ->where('templates.A', fn ($t) => str_contains($t, '{nama}'))
            ->missing('students.0.id'));
    });

    it('guru lain dan penugasan tahun lalu ditolak', function () {
        ['assignment' => $a] = gradebookScenario();
        $other = User::factory()->withRole(Role::Teacher)->create();
        $teacher = Teacher::factory()->withAccount($other)->create();
        $old = assignTeaching(Classroom::factory()->for(AcademicYear::factory()->startingIn(2020)->create())->create(), $teacher);
        $this->actingAsRole(Role::Teacher, $other);

        $this->get("/guru/akademik/nilai/{$a->public_id}")->assertForbidden();
        $this->get("/guru/akademik/nilai/{$old->public_id}")->assertForbidden();
    });

    it('operator (grade.view) & kepala sekolah hanya lihat; tidak bisa menyimpan nilai', function () {
        ['assignment' => $a, 'semester' => $sem, 'students' => [$ani]] = gradebookScenario();
        $c = component($a, $sem, 'UH', 100);

        foreach ([[Role::Operator, '/operator/nilai'], [Role::Principal, '/kepala-sekolah/nilai']] as [$role, $base]) {
            $this->actingAsRole($role);
            $this->get("{$base}/{$a->public_id}")->assertInertia(fn (Assert $page) => $page->where('readOnly', true));
            $this->putJson("/guru/akademik/nilai/komponen/{$c->public_id}/sel", ['student' => $ani->public_id, 'score' => 90])->assertForbidden();
        }
    });
});

describe('komponen', function () {
    it('menambah, validasi nama unik & bobot, mengubah, menghapus', function () {
        ['user' => $user, 'assignment' => $a, 'semester' => $sem] = gradebookScenario();
        $this->actingAsRole(Role::Teacher, $user);
        $url = "/guru/akademik/nilai/{$a->public_id}/komponen?semester={$sem->public_id}";

        $this->post($url, ['type' => 'UH', 'name' => 'UH 1', 'weight' => 30])->assertSessionHasNoErrors();
        $this->post($url, ['type' => 'UH', 'name' => 'UH 1', 'weight' => 30])->assertSessionHasErrors('name');
        $this->post($url, ['type' => 'UH', 'name' => 'UH 2', 'weight' => 0])->assertSessionHasErrors('weight');

        $c = GradeComponent::query()->sole();
        expect($c->semester_id)->toBe($sem->id);

        $this->put("/guru/akademik/nilai/komponen/{$c->public_id}", ['type' => 'UH', 'name' => 'UH Bab 1', 'weight' => 40])->assertSessionHasNoErrors();
        expect($c->fresh()->weight)->toBe(40);

        $this->delete("/guru/akademik/nilai/komponen/{$c->public_id}")->assertSessionHasNoErrors();
        expect(GradeComponent::query()->count())->toBe(0);
    });

    it('publikasi: butuh nilai, mengirim notifikasi, lalu mengunci; batal publikasi membuka kunci', function () {
        Notification::fake();
        ['user' => $user, 'assignment' => $a, 'semester' => $sem, 'students' => [$ani], 'parent' => $parent] = gradebookScenario();
        $c = component($a, $sem, 'UH 1', 100);
        $this->actingAsRole(Role::Teacher, $user);
        $base = "/guru/akademik/nilai/komponen/{$c->public_id}";

        $this->post("{$base}/publikasi")->assertSessionHasErrors('component');

        score($c, $ani, 88);
        $this->post("{$base}/publikasi")->assertSessionHasNoErrors();
        Notification::assertSentTo($parent, GradePublishedNotification::class, fn ($n) => $n->score === 88.0 && $n->component === 'UH 1');

        $this->putJson("{$base}/sel", ['student' => $ani->public_id, 'score' => 90])->assertStatus(422);
        $this->put($base, ['type' => 'UH', 'name' => 'Ubah', 'weight' => 100])->assertSessionHasErrors('name');
        $this->delete($base)->assertSessionHasErrors('component');

        $this->delete("{$base}/publikasi")->assertSessionHasNoErrors();
        $this->putJson("{$base}/sel", ['student' => $ani->public_id, 'score' => 90])->assertOk();
    });
});

describe('simpan sel (JSON)', function () {
    it('menyimpan, mengembalikan nilai akhir terbaru, dan mengosongkan', function () {
        ['user' => $user, 'assignment' => $a, 'semester' => $sem, 'students' => [$ani]] = gradebookScenario();
        $c = component($a, $sem, 'PAS', 100);
        $this->actingAsRole(Role::Teacher, $user);
        $url = "/guru/akademik/nilai/komponen/{$c->public_id}/sel";

        $this->putJson($url, ['student' => $ani->public_id, 'score' => 91.5])
            ->assertOk()
            ->assertJson(['final' => 92, 'predicate' => 'A'])
            ->assertJsonPath('suggested', fn ($text) => str_contains($text, 'Ani') && str_contains($text, 'Matematika'));

        $this->putJson($url, ['student' => $ani->public_id, 'score' => null])->assertOk()->assertJson(['final' => null]);
        expect(Grade::query()->count())->toBe(0);
    });

    it('menolak nilai di luar 0–100 dan siswa di luar rombel', function () {
        ['user' => $user, 'assignment' => $a, 'semester' => $sem, 'students' => [$ani]] = gradebookScenario();
        $c = component($a, $sem, 'PAS', 100);
        $this->actingAsRole(Role::Teacher, $user);
        $url = "/guru/akademik/nilai/komponen/{$c->public_id}/sel";

        $this->putJson($url, ['student' => $ani->public_id, 'score' => 101])->assertStatus(422)->assertJsonValidationErrors('score');
        $this->putJson($url, ['student' => Student::factory()->create()->public_id, 'score' => 80])->assertStatus(422);
    });
});

describe('deskripsi & template', function () {
    it('menyimpan template, mengisi deskripsi kosong dari template tanpa menimpa yang ada', function () {
        ['user' => $user, 'assignment' => $a, 'semester' => $sem, 'students' => [$ani, $budi]] = gradebookScenario();
        $c = component($a, $sem, 'PAS', 100);
        score($c, $ani, 95);
        score($c, $budi, 65);
        $this->actingAsRole(Role::Teacher, $user);
        $url = "/guru/akademik/nilai/{$a->public_id}";

        $this->put("{$url}/template", ['templates' => [
            'A' => '{nama} unggul di {mapel}.', 'B' => 'b', 'C' => 'c', 'D' => '{nama} perlu bimbingan.',
        ]])->assertSessionHasNoErrors();

        $this->putJson("{$url}/deskripsi?semester={$sem->public_id}", ['student' => $budi->public_id, 'description' => 'Ditulis manual.'])->assertOk();
        $this->post("{$url}/deskripsi/template?semester={$sem->public_id}", ['overwrite' => false])->assertSessionHasNoErrors();

        $descriptions = GradeDescription::query()->pluck('description', 'student_id');
        expect($descriptions[$ani->id])->toBe('Ani unggul di Matematika.')
            ->and($descriptions[$budi->id])->toBe('Ditulis manual.');

        $this->putJson("{$url}/deskripsi?semester={$sem->public_id}", ['student' => $budi->public_id, 'description' => ''])->assertOk();
        expect(GradeDescription::query()->where('student_id', $budi->id)->exists())->toBeFalse();
    });
});

describe('impor & ekspor Excel', function () {
    it('mengekspor lalu mengimpor nilai berdasarkan NIS dan nama komponen', function () {
        ['user' => $user, 'assignment' => $a, 'semester' => $sem, 'students' => [$ani, $budi]] = gradebookScenario();
        $tugas = component($a, $sem, 'Tugas 1', 50);
        $locked = component($a, $sem, 'UH 1', 50, published: true);
        $this->actingAsRole(Role::Teacher, $user);
        $url = "/guru/akademik/nilai/{$a->public_id}";

        $this->get("{$url}/ekspor?semester={$sem->public_id}")->assertOk()->assertDownload();

        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['NIS', 'Nama', 'Tugas 1', 'UH 1'],
            ['1001', 'Ani', 88, 99],
            ['1002', 'Budi', '76,5', null],
            ['9999', 'Asing', 70, null],
            ['1001', 'Ani', 'abc', null],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'nilai').'.xlsx';
        (new Xlsx($sheet))->save($path);

        $this->post("{$url}/impor?semester={$sem->public_id}", ['file' => new UploadedFile($path, 'nilai.xlsx', null, null, true)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 nilai diimpor.')
            ->assertSessionHas('error', fn ($warning) => str_contains($warning, '9999') && str_contains($warning, 'UH 1'));

        $book = Gradebook::make($a, $sem);
        expect($book->score($tugas, $ani))->toBe(88.0)
            ->and($book->score($tugas, $budi))->toBe(76.5)
            ->and($book->score($locked, $ani))->toBeNull();
    });
});

describe('orang tua & kepala sekolah', function () {
    it('orang tua hanya melihat komponen yang dipublikasikan; anak lain 403', function () {
        ['assignment' => $a, 'semester' => $sem, 'students' => [$ani], 'parent' => $parent] = gradebookScenario();
        score(component($a, $sem, 'UH 1', 50, published: true), $ani, 80);
        score(component($a, $sem, 'PAS', 50), $ani, 90);
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get('/orang-tua/nilai')->assertInertia(fn (Assert $page) => $page
            ->component('Assessment::Guardian/Index')
            ->has('subjects', 1)
            ->has('subjects.0.components', 1)
            ->where('subjects.0.components.0.score', 80)
            ->where('subjects.0.final', null));

        $this->get('/orang-tua/nilai?anak='.Student::factory()->create()->public_id)->assertForbidden();
    });

    it('kepala sekolah melihat rata-rata, jumlah di bawah KKM, dan distribusi predikat', function () {
        ['assignment' => $a, 'semester' => $sem, 'students' => [$ani, $budi]] = gradebookScenario();
        $c = component($a, $sem, 'PAS', 100);
        score($c, $ani, 92);
        score($c, $budi, 70);
        $this->actingAsRole(Role::Principal);

        $this->get("/kepala-sekolah/nilai?semester={$sem->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Assessment::Principal/Index')
            ->where('rows.0.summary.average', 81)
            ->where('rows.0.summary.below_kkm', 1)
            ->where('rows.0.summary.distribution.A', 1)
            ->where('rows.0.summary.distribution.C', 1));
    });

    it('role lain tidak bisa membuka buku nilai guru', function (Role $role) {
        ['assignment' => $a] = gradebookScenario();
        $this->actingAsRole($role);

        $this->get('/guru/akademik/nilai')->assertForbidden();
        $this->get("/guru/akademik/nilai/{$a->public_id}")->assertForbidden();
    })->with([Role::Operator, Role::Guardian, Role::Principal]);
});
