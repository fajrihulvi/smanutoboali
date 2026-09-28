<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Models\GradeDescription;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\ReportCard\Actions\TransitionReportCardAction;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardExport;
use App\Modules\ReportCard\Models\ReportCardLog;
use App\Modules\ReportCard\Notifications\ReportCardPublishedNotification;
use App\Modules\School\Models\Semester;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-09-28 10:00:00');
    Storage::fake('private');
    Storage::fake('public');
});

/**
 * Rombel VII-A: wali kelas (akun), dua mapel dengan guru masing-masing (akun), dua siswa
 * (siswa pertama punya akun orang tua), nilai lengkap 100% untuk kedua mapel.
 *
 * @return array{homeroom: User, teachers: list<User>, assignments: list<TeachingAssignment>, classroom: Classroom, semester: Semester, students: list<Student>, parent: User}
 */
function reportScenario(bool $withDescriptions = true): array
{
    $year = activeAcademicYear();
    $homeroom = User::factory()->withRole(Role::Teacher)->create();
    $classroom = Classroom::factory()->for($year)->create([
        'name' => 'VII-A',
        'homeroom_teacher_id' => Teacher::factory()->withAccount($homeroom)->create()->id,
    ]);
    $semester = Semester::query()->where('academic_year_id', $year->id)->orderBy('start_date')->firstOrFail();
    $parent = User::factory()->withRole(Role::Guardian)->create();
    $students = [
        Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']),
        Student::factory()->create(['name' => 'Budi']),
    ];
    foreach ($students as $student) {
        enrollIn($classroom, $student);
    }

    $teachers = [];
    $assignments = [];
    foreach (['Matematika', 'Bahasa Indonesia'] as $i => $name) {
        $teachers[$i] = User::factory()->withRole(Role::Teacher)->create();
        $assignments[$i] = assignTeaching($classroom, Teacher::factory()->withAccount($teachers[$i])->create(), Subject::factory()->create(['name' => $name]));
        $component = GradeComponent::query()->create([
            'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'teaching_assignment_id' => $assignments[$i]->id,
            'type' => ComponentType::FinalTerm, 'name' => 'PAS', 'weight' => 100,
        ]);
        foreach ($students as $s => $student) {
            Grade::query()->create(['grade_component_id' => $component->id, 'student_id' => $student->id, 'score' => 80 + $s * 10]);
            if ($withDescriptions) {
                GradeDescription::query()->create([
                    'teaching_assignment_id' => $assignments[$i]->id, 'semester_id' => $semester->id,
                    'student_id' => $student->id, 'description' => "{$student->name} baik di {$name}.",
                ]);
            }
        }
    }

    return compact('homeroom', 'teachers', 'assignments', 'classroom', 'semester', 'students', 'parent');
}

function confirmAll(array $s): void
{
    foreach ($s['teachers'] as $i => $teacher) {
        test()->actingAsRole(Role::Teacher, $teacher);
        test()->post("/guru/raport/konfirmasi/{$s['assignments'][$i]->public_id}?semester={$s['semester']->public_id}")->assertSessionHasNoErrors();
    }
}

function cardsOf(array $s): Collection
{
    return ReportCard::query()->where('classroom_id', $s['classroom']->id)->get();
}

describe('status raport', function () {
    it('hanya transisi yang diizinkan', function () {
        expect(ReportCardStatus::Draft->canTransitionTo(ReportCardStatus::Submitted))->toBeTrue()
            ->and(ReportCardStatus::Draft->canTransitionTo(ReportCardStatus::Published))->toBeFalse()
            ->and(ReportCardStatus::Submitted->canTransitionTo(ReportCardStatus::Validated))->toBeFalse()
            ->and(ReportCardStatus::Validated->canTransitionTo(ReportCardStatus::Revision))->toBeTrue()
            ->and(ReportCardStatus::Published->canTransitionTo(ReportCardStatus::Revision))->toBeTrue()
            ->and(ReportCardStatus::Published->canTransitionTo(ReportCardStatus::Approved))->toBeFalse()
            ->and(ReportCardStatus::Revision->canTransitionTo(ReportCardStatus::Submitted))->toBeTrue();
    });

    it('transisi ilegal ditolak dan REVISION wajib catatan', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate?semester={$s['semester']->public_id}");
        $card = cardsOf($s)->first();
        $action = app(TransitionReportCardAction::class);

        expect(fn () => $action->handle($card, ReportCardStatus::Published, null))->toThrow(ValidationException::class);

        $card->update(['status' => ReportCardStatus::Submitted]);
        expect(fn () => $action->handle($card->fresh(), ReportCardStatus::Revision, null))->toThrow(ValidationException::class, 'alasan');
    });
});

describe('alur lengkap', function () {
    it('draf → konfirmasi semua mapel (otomatis diajukan) → review → validasi → setuju → terbit', function () {
        Notification::fake();
        $s = reportScenario();
        $classroom = $s['classroom'];
        $q = "?semester={$s['semester']->public_id}";

        // Wali kelas membuat draf.
        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post("/guru/raport/rombel/{$classroom->public_id}/generate{$q}")->assertSessionHasNoErrors();
        expect(cardsOf($s)->pluck('status')->unique()->all())->toBe([ReportCardStatus::Draft]);

        $card = cardsOf($s)->firstWhere('student_id', $s['students'][0]->id);
        expect($card->details()->count())->toBe(2)
            ->and($card->details()->where('subject_name', 'Matematika')->first()->final_score)->toBe(80)
            ->and($card->attendance)->toBe(['sick' => 0, 'permit' => 0, 'absent' => 0, 'days' => 0]);

        // Konfirmasi guru mapel: yang terakhir memicu SUBMITTED.
        $this->actingAsRole(Role::Teacher, $s['teachers'][0]);
        $this->post("/guru/raport/konfirmasi/{$s['assignments'][0]->public_id}{$q}")->assertSessionHasNoErrors();
        expect(cardsOf($s)->pluck('status')->unique()->all())->toBe([ReportCardStatus::Draft]);

        $this->actingAsRole(Role::Teacher, $s['teachers'][1]);
        $this->post("/guru/raport/konfirmasi/{$s['assignments'][1]->public_id}{$q}")->assertSessionHasNoErrors();
        expect(cardsOf($s)->pluck('status')->unique()->all())->toBe([ReportCardStatus::Submitted])
            ->and(ReportCardLog::query()->where('to_status', 'SUBMITTED')->whereNull('actor_id')->count())->toBe(2);

        $ids = cardsOf($s)->pluck('public_id')->all();
        $bulk = fn (string $base, string $target) => $this->post("{$base}/rombel/{$classroom->public_id}/status", ['target' => $target, 'cards' => $ids]);

        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $bulk('/guru/raport', 'HOMEROOM_REVIEWED')->assertSessionHasNoErrors();

        $this->actingAsRole(Role::Operator);
        $bulk('/operator/raport', 'VALIDATED')->assertSessionHasNoErrors();

        $this->actingAsRole(Role::Principal);
        $bulk('/kepala-sekolah/raport', 'APPROVED')->assertSessionHasNoErrors();

        $this->actingAsRole(Role::Operator);
        $bulk('/operator/raport', 'PUBLISHED')->assertSessionHasNoErrors();

        expect(cardsOf($s)->pluck('status')->unique()->all())->toBe([ReportCardStatus::Published])
            ->and($card->fresh()->published_at)->not->toBeNull();
        Notification::assertSentTo($s['parent'], ReportCardPublishedNotification::class);

        // Orang tua melihat & mengunduh.
        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->get('/orang-tua/raport')->assertInertia(fn (Assert $page) => $page->has('cards', 1));
        $this->get("/orang-tua/raport/{$card->public_id}")->assertOk();
        $this->get("/orang-tua/raport/{$card->public_id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    });

    it('konfirmasi ditolak bila deskripsi/nilai akhir belum lengkap', function () {
        $s = reportScenario(withDescriptions: false);
        $this->actingAsRole(Role::Teacher, $s['teachers'][0]);

        $this->post("/guru/raport/konfirmasi/{$s['assignments'][0]->public_id}?semester={$s['semester']->public_id}")
            ->assertSessionHasErrors('confirmation');
    });

    it('guru lain tidak bisa mengonfirmasi mapel orang lain', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Teacher, $s['teachers'][1]);

        $this->post("/guru/raport/konfirmasi/{$s['assignments'][0]->public_id}")->assertForbidden();
    });
});

describe('revisi & penguncian', function () {
    beforeEach(function () {
        $this->s = reportScenario();
        $this->actingAsRole(Role::Teacher, $this->s['homeroom']);
        $this->post("/guru/raport/rombel/{$this->s['classroom']->public_id}/generate?semester={$this->s['semester']->public_id}");
        confirmAll($this->s);
        $this->card = cardsOf($this->s)->first();
    });

    it('wali kelas mengubah isi selama SUBMITTED, terkunci setelah direview', function () {
        $this->actingAsRole(Role::Teacher, $this->s['homeroom']);
        $url = "/guru/raport/{$this->card->public_id}";
        $content = ['homeroom_note' => 'Rajin.', 'extracurriculars' => [['name' => 'Pramuka', 'predicate' => 'Baik']], 'achievements' => [['name' => 'Juara 1 lomba baca puisi']]];

        $this->put("{$url}/isi", $content)->assertSessionHasNoErrors();
        expect($this->card->fresh()->extracurriculars()->count())->toBe(1);

        $this->post("{$url}/status", ['target' => 'HOMEROOM_REVIEWED'])->assertSessionHasNoErrors();
        $this->put("{$url}/isi", $content)->assertForbidden();
    });

    it('konfirmasi mapel tidak bisa dibatalkan setelah raport diajukan', function () {
        $this->actingAsRole(Role::Teacher, $this->s['teachers'][0]);

        $this->delete("/guru/raport/konfirmasi/{$this->s['assignments'][0]->public_id}?semester={$this->s['semester']->public_id}")
            ->assertSessionHasErrors('confirmation');
    });

    it('operator mengembalikan ke revisi (catatan wajib), wali kelas mengajukan ulang', function () {
        $this->actingAsRole(Role::Teacher, $this->s['homeroom']);
        $this->post("/guru/raport/{$this->card->public_id}/status", ['target' => 'HOMEROOM_REVIEWED']);

        $this->actingAsRole(Role::Operator);
        $url = "/operator/raport/{$this->card->public_id}/status";
        $this->post($url, ['target' => 'REVISION'])->assertSessionHasErrors('note');
        $this->post($url, ['target' => 'REVISION', 'note' => 'Deskripsi IPA kurang lengkap.'])->assertSessionHasNoErrors();
        expect($this->card->fresh()->status)->toBe(ReportCardStatus::Revision);

        $this->actingAsRole(Role::Teacher, $this->s['homeroom']);
        $this->post("/guru/raport/{$this->card->public_id}/status", ['target' => 'SUBMITTED'])->assertSessionHasNoErrors();
        expect($this->card->fresh()->status)->toBe(ReportCardStatus::Submitted)
            ->and($this->card->logs()->first()->note)->toBe('Diajukan ulang setelah revisi.');
    });

    it('operator menarik raport terbit; orang tua tidak lagi bisa membukanya', function () {
        $action = app(TransitionReportCardAction::class);
        foreach ([ReportCardStatus::HomeroomReviewed, ReportCardStatus::Validated, ReportCardStatus::Approved, ReportCardStatus::Published] as $target) {
            $action->handle($this->card->fresh(), $target, null);
        }
        $this->card = $this->card->fresh();

        $this->actingAsRole(Role::Operator);
        $this->post("/operator/raport/{$this->card->public_id}/status", ['target' => 'REVISION', 'note' => 'Salah nama.'])->assertSessionHasNoErrors();
        expect($this->card->fresh()->published_at)->toBeNull();

        $this->actingAsRole(Role::Guardian, $this->s['parent']);
        $this->get("/orang-tua/raport/{$this->card->public_id}")->assertForbidden();
    });
});

describe('akses', function () {
    it('guru non-wali tidak bisa membuka rombel maupun mereview', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate?semester={$s['semester']->public_id}");
        confirmAll($s);
        $card = cardsOf($s)->first();

        $this->actingAsRole(Role::Teacher, $s['teachers'][0]);
        $this->get("/guru/raport/rombel/{$s['classroom']->public_id}")->assertForbidden();
        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate")->assertForbidden();
        $this->post("/guru/raport/{$card->public_id}/status", ['target' => 'HOMEROOM_REVIEWED'])->assertForbidden();
        $this->get("/guru/raport/{$card->public_id}")->assertForbidden();
    });

    it('orang tua tidak bisa membuka raport DRAF atau raport anak lain', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate?semester={$s['semester']->public_id}");
        $own = cardsOf($s)->firstWhere('student_id', $s['students'][0]->id);
        $other = cardsOf($s)->firstWhere('student_id', $s['students'][1]->id);

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->get("/orang-tua/raport/{$own->public_id}")->assertForbidden();
        $this->get("/orang-tua/raport/{$own->public_id}/pdf")->assertForbidden();
        $this->get('/orang-tua/raport')->assertInertia(fn (Assert $page) => $page->has('cards', 0));

        $other->update(['status' => ReportCardStatus::Published]);
        $this->get("/orang-tua/raport/{$other->public_id}")->assertForbidden();
    });

    it('kepala sekolah tidak bisa memvalidasi; operator tidak bisa menyetujui', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate?semester={$s['semester']->public_id}");
        confirmAll($s);
        $card = cardsOf($s)->first();
        $card->update(['status' => ReportCardStatus::HomeroomReviewed]);

        $this->actingAsRole(Role::Principal);
        $this->post("/kepala-sekolah/raport/{$card->public_id}/status", ['target' => 'VALIDATED'])->assertForbidden();

        $card->update(['status' => ReportCardStatus::Validated]);
        $this->actingAsRole(Role::Operator);
        $this->post("/operator/raport/{$card->public_id}/status", ['target' => 'APPROVED'])->assertForbidden();
    });
});

describe('cetak & tanda tangan', function () {
    it('operator mencetak massal satu rombel menjadi ZIP yang bisa diunduh', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate?semester={$s['semester']->public_id}");

        $operator = $this->actingAsRole(Role::Operator);
        $this->post("/operator/raport/rombel/{$s['classroom']->public_id}/cetak?semester={$s['semester']->public_id}")->assertSessionHasNoErrors();

        $export = ReportCardExport::query()->sole();
        expect($export->status)->toBe(ReportCardExport::DONE)->and($export->total)->toBe(2);

        $file = $export->file()->sole();
        expect($file->mime_type)->toBe('application/zip');
        $this->get("/files/{$file->public_id}")->assertOk();

        $this->actingAsRole(Role::Teacher, $s['teachers'][0]);
        $this->get("/files/{$file->public_id}")->assertForbidden();
    });

    it('operator & guru mengunggah tanda tangan; PDF tetap terbentuk', function () {
        $s = reportScenario();
        $this->actingAsRole(Role::Operator);
        $this->post('/operator/settings/tanda-tangan', ['signature' => UploadedFile::fake()->image('ttd.png', 300, 100)])->assertSessionHasNoErrors();

        $this->actingAsRole(Role::Teacher, $s['homeroom']);
        $this->post('/guru/profil/tanda-tangan', ['signature' => UploadedFile::fake()->image('ttd.png', 300, 100)])->assertSessionHasNoErrors();
        expect(File::query()->where('visibility', 'private')->count())->toBe(2);

        $this->post("/guru/raport/rombel/{$s['classroom']->public_id}/generate?semester={$s['semester']->public_id}");
        $card = cardsOf($s)->first();
        $this->get("/guru/raport/{$card->public_id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->delete('/guru/profil/tanda-tangan')->assertSessionHasNoErrors();
        expect(Teacher::activeForUser($s['homeroom'])->signature_file_id)->toBeNull();
    });
});
