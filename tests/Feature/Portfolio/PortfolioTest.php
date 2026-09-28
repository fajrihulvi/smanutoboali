<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Notifications\PortfolioPublishedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

/**
 * Guru yang mengajar di VII-A berisi dua siswa (siswa pertama punya akun orang tua) + satu siswa di rombel lain.
 *
 * @return array{teacher: User, students: list<Student>, outsider: Student, parent: User}
 */
function portfolioScenario(): array
{
    $year = activeAcademicYear();
    $teacher = User::factory()->withRole(Role::Teacher)->create();
    $classroom = Classroom::factory()->for($year)->create();
    assignTeaching($classroom, Teacher::factory()->withAccount($teacher)->create());
    $parent = User::factory()->withRole(Role::Guardian)->create();
    $students = [
        Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']),
        Student::factory()->create(['name' => 'Budi']),
    ];
    foreach ($students as $student) {
        enrollIn($classroom, $student);
    }
    $outsider = Student::factory()->create(['name' => 'Luar']);
    enrollIn(Classroom::factory()->for($year)->create(), $outsider);

    return compact('teacher', 'students', 'outsider', 'parent');
}

/**
 * @param  list<Student>  $students
 * @return array<string, mixed>
 */
function portfolioPayload(array $students, string $visibility = 'DRAFT', array $extra = []): array
{
    return [
        'category' => 'PROYEK',
        'title' => 'Maket tata surya',
        'description' => 'Proyek kelompok.',
        'date' => now()->toDateString(),
        'visibility' => $visibility,
        'students' => array_map(fn (Student $s) => $s->public_id, $students),
        ...$extra,
    ];
}

it('guru membuat satu portofolio untuk beberapa siswa dengan banyak file', function () {
    ['teacher' => $teacher, 'students' => $students] = portfolioScenario();
    $this->actingAsRole(Role::Teacher, $teacher);

    $this->post('/guru/portofolio', portfolioPayload($students, extra: ['files' => [
        UploadedFile::fake()->image('a.jpg', 1200, 900),
        UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
    ]]))->assertSessionHasNoErrors()->assertRedirect();

    $portfolio = Portfolio::query()->sole();
    expect($portfolio->students()->count())->toBe(2)
        ->and($portfolio->files()->count())->toBe(2)
        ->and($portfolio->files()->first()->visibility->value)->toBe('private');

    $this->get('/guru/portofolio')->assertInertia(fn (Assert $page) => $page
        ->component('Portfolio::Manage/Index')
        ->has('portfolios.data', 1)
        ->where('portfolios.data.0.students_count', 2)
        ->missing('portfolios.data.0.id'));
});

it('guru tidak bisa memilih siswa yang tidak diajarnya', function () {
    ['teacher' => $teacher, 'students' => $students, 'outsider' => $outsider] = portfolioScenario();
    $this->actingAsRole(Role::Teacher, $teacher);

    $this->post('/guru/portofolio', portfolioPayload([$students[0], $outsider]))->assertSessionHasErrors('students');

    $this->getJson('/guru/portofolio/siswa?q=Luar')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/guru/portofolio/siswa')->assertJsonCount(2, 'data');
});

it('menerbitkan mengirim notifikasi sekali (draf → terbit), bukan tiap edit', function () {
    Notification::fake();
    ['teacher' => $teacher, 'students' => $students, 'parent' => $parent] = portfolioScenario();
    $this->actingAsRole(Role::Teacher, $teacher);

    $this->post('/guru/portofolio', portfolioPayload($students));
    Notification::assertNothingSent();

    $portfolio = Portfolio::query()->sole();
    $this->put("/guru/portofolio/{$portfolio->public_id}", portfolioPayload($students, 'PUBLISHED'))->assertSessionHasNoErrors();
    Notification::assertSentToTimes($parent, PortfolioPublishedNotification::class, 1);

    $this->put("/guru/portofolio/{$portfolio->public_id}", portfolioPayload($students, 'PUBLISHED', ['title' => 'Revisi judul']));
    Notification::assertSentToTimes($parent, PortfolioPublishedNotification::class, 1);
});

it('menghapus file tertentu dan membatasi jumlah file', function () {
    ['teacher' => $teacher, 'students' => $students] = portfolioScenario();
    $this->actingAsRole(Role::Teacher, $teacher);
    $this->post('/guru/portofolio', portfolioPayload($students, extra: ['files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]]));
    $portfolio = Portfolio::query()->sole();
    $first = $portfolio->files()->first();

    $this->put("/guru/portofolio/{$portfolio->public_id}", portfolioPayload($students, extra: ['remove_files' => [$first->public_id]]))->assertSessionHasNoErrors();
    expect($portfolio->files()->count())->toBe(1);

    $tooMany = array_map(fn ($i) => UploadedFile::fake()->image("x{$i}.jpg"), range(1, 10));
    $this->put("/guru/portofolio/{$portfolio->public_id}", portfolioPayload($students, extra: ['files' => $tooMany]))->assertSessionHasErrors('files');
});

it('guru lain tidak bisa mengubah/menghapus; operator bisa', function () {
    ['teacher' => $teacher, 'students' => $students] = portfolioScenario();
    $this->actingAsRole(Role::Teacher, $teacher);
    $this->post('/guru/portofolio', portfolioPayload($students));
    $portfolio = Portfolio::query()->sole();

    $other = User::factory()->withRole(Role::Teacher)->create();
    Teacher::factory()->withAccount($other)->create();
    $this->actingAsRole(Role::Teacher, $other);
    $this->get("/guru/portofolio/{$portfolio->public_id}/ubah")->assertForbidden();
    $this->delete("/guru/portofolio/{$portfolio->public_id}")->assertForbidden();

    $this->actingAsRole(Role::Operator);
    $this->delete("/operator/portofolio/{$portfolio->public_id}")->assertRedirect('/operator/portofolio');
    expect(Portfolio::query()->count())->toBe(0);
});

describe('orang tua', function () {
    it('melihat portofolio terbit anaknya (tanpa nama siswa lain) dan bisa membuka filenya', function () {
        ['teacher' => $teacher, 'students' => $students, 'parent' => $parent] = portfolioScenario();
        $this->actingAsRole(Role::Teacher, $teacher);
        $this->post('/guru/portofolio', portfolioPayload($students, 'PUBLISHED', ['files' => [UploadedFile::fake()->image('a.jpg')]]));
        $this->post('/guru/portofolio', portfolioPayload([$students[0]], 'DRAFT', ['title' => 'Draf']));
        $file = File::query()->firstOrFail();

        $this->actingAsRole(Role::Guardian, $parent);
        $this->get('/orang-tua/portofolio')->assertInertia(fn (Assert $page) => $page
            ->component('Portfolio::Guardian/Index')
            ->has('portfolios.data', 1)
            ->has('portfolios.data.0.students', 1)
            ->where('portfolios.data.0.students.0.name', 'Ani')
            ->where('portfolios.data.0.students_count', 2));
        $this->get("/files/{$file->public_id}")->assertOk();
    });

    it('tidak bisa membuka file portofolio draf atau milik anak lain', function () {
        ['teacher' => $teacher, 'students' => $students, 'parent' => $parent] = portfolioScenario();
        $this->actingAsRole(Role::Teacher, $teacher);
        $this->post('/guru/portofolio', portfolioPayload([$students[0]], 'DRAFT', ['files' => [UploadedFile::fake()->image('draf.jpg')]]));
        $this->post('/guru/portofolio', portfolioPayload([$students[1]], 'PUBLISHED', ['files' => [UploadedFile::fake()->image('budi.jpg')]]));
        [$draftFile, $otherFile] = File::query()->orderBy('id')->get()->all();

        $this->actingAsRole(Role::Guardian, $parent);
        $this->get("/files/{$draftFile->public_id}")->assertForbidden();
        $this->get("/files/{$otherFile->public_id}")->assertForbidden();
        $this->get('/orang-tua/portofolio?anak='.$students[1]->public_id)->assertForbidden();
    });
});

it('kepala sekolah melihat galeri terbit; tidak bisa mengelola', function () {
    ['teacher' => $teacher, 'students' => $students] = portfolioScenario();
    $this->actingAsRole(Role::Teacher, $teacher);
    $this->post('/guru/portofolio', portfolioPayload($students, 'PUBLISHED'));
    $this->post('/guru/portofolio', portfolioPayload($students, 'DRAFT', ['title' => 'Draf']));

    $this->actingAsRole(Role::Principal);
    $this->get('/kepala-sekolah/portofolio')->assertInertia(fn (Assert $page) => $page->has('portfolios.data', 1));
    $this->get('/guru/portofolio')->assertForbidden();
    $this->get('/operator/portofolio')->assertForbidden();
});
