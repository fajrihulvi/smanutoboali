<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\Ppdb\Notifications\ApplicantUpdateNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    $this->travelTo('2026-10-01 09:00:00');
});

/**
 * Pendaftaran terkirim oleh akun baru.
 *
 * @return array{applicant: PpdbApplicant, user: User}
 */
function submittedApplicant(PpdbTrack $track, string $email): array
{
    $user = verifiedApplicant($email);
    $applicant = startApplication($track);
    fillApplication($applicant, $track);
    test()->post("/ppdb/akun/pendaftaran/{$applicant->public_id}/kirim")->assertSessionHasNoErrors();

    return ['applicant' => $applicant->fresh() ?? $applicant, 'user' => $user];
}

function validateAllDocuments(PpdbApplicant $applicant): void
{
    foreach (PpdbDocument::query()->where('ppdb_applicant_id', $applicant->id)->get() as $document) {
        test()->post("/operator/ppdb/dokumen/{$document->public_id}/periksa", ['status' => 'VALID'])->assertSessionHasNoErrors();
    }
}

/**
 * Tiga pendaftar terverifikasi di jalur Reguler (kuota 2) dengan skor 70/90/80.
 *
 * @return array<string, mixed>
 */
function selectionScenario(): array
{
    $s = ppdbScenario();
    $s['regular']->update(['quota' => 2]);
    $applicants = [];

    foreach ([['a@contoh.test', 70], ['b@contoh.test', 90], ['c@contoh.test', 80]] as [$email, $score]) {
        ['applicant' => $applicant, 'user' => $user] = submittedApplicant($s['regular'], $email);
        test()->actingAsRole(Role::Operator, $s['operator']);
        validateAllDocuments($applicant);
        test()->post("/operator/ppdb/pendaftar/{$applicant->public_id}/verifikasi")->assertSessionHasNoErrors();
        test()->put("/operator/ppdb/seleksi/pendaftar/{$applicant->public_id}", ['score' => $score])->assertSessionHasNoErrors();
        $applicants[$email] = ['applicant' => $applicant, 'user' => $user];
    }

    return [...$s, 'applicants' => $applicants, 'principal' => User::factory()->withRole(Role::Principal)->create()];
}

describe('verifikasi', function () {
    it('dokumen tidak valid → kembalikan untuk perbaikan → kirim ulang → valid → terverifikasi', function () {
        $s = ppdbScenario();
        ['applicant' => $applicant, 'user' => $user] = submittedApplicant($s['regular'], 'a@contoh.test');
        $document = PpdbDocument::query()->where('ppdb_applicant_id', $applicant->id)->sole();
        Notification::fake();

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->get("/operator/ppdb/pendaftar/{$applicant->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Ppdb::Operator/Applicants/Show')
            ->where('canManage', true)
            ->where('applicant.status.value', 'SUBMITTED'));

        $this->post("/operator/ppdb/dokumen/{$document->public_id}/periksa", ['status' => 'INVALID'])->assertSessionHasErrors('note');
        $this->post("/operator/ppdb/dokumen/{$document->public_id}/periksa", ['status' => 'INVALID', 'note' => 'Foto buram'])->assertSessionHasNoErrors();
        $this->post("/operator/ppdb/pendaftar/{$applicant->public_id}/verifikasi")->assertSessionHasErrors('status');
        $this->post("/operator/ppdb/pendaftar/{$applicant->public_id}/perbaikan", ['note' => 'Akta: foto buram'])->assertSessionHasNoErrors();

        expect($applicant->fresh()->status)->toBe(ApplicantStatus::NeedsRevision);
        Notification::assertSentTo($user, ApplicantUpdateNotification::class, fn ($n) => $n->kind === ApplicantUpdateNotification::REVISION && $n->note === 'Akta: foto buram');

        // Pendaftar mengunggah ulang lalu mengirim lagi.
        $this->actingAsRole(Role::Applicant, $user);
        $requirement = $s['regular']->requirements()->where('name', 'Akta kelahiran')->sole();
        $this->post("/ppdb/akun/pendaftaran/{$applicant->public_id}/dokumen/{$requirement->public_id}", ['file' => UploadedFile::fake()->image('akta-baru.jpg')])->assertSessionHasNoErrors();
        $this->post("/ppdb/akun/pendaftaran/{$applicant->public_id}/kirim")->assertSessionHasNoErrors();

        $this->actingAsRole(Role::Operator, $s['operator']);
        validateAllDocuments($applicant);
        $this->post("/operator/ppdb/pendaftar/{$applicant->public_id}/verifikasi")->assertSessionHasNoErrors();
        expect($applicant->fresh()->status)->toBe(ApplicantStatus::Verified);
        Notification::assertSentTo($user, ApplicantUpdateNotification::class, fn ($n) => $n->kind === ApplicantUpdateNotification::VERIFIED);
    });

    it('kepala sekolah hanya melihat; guru & pendaftar tidak punya akses internal', function () {
        $s = ppdbScenario();
        ['applicant' => $applicant] = submittedApplicant($s['regular'], 'a@contoh.test');

        $this->actingAsRole(Role::Principal);
        $this->get("/kepala-sekolah/ppdb/pendaftar/{$applicant->public_id}")->assertInertia(fn (Assert $page) => $page->where('canManage', false));
        $this->post("/operator/ppdb/pendaftar/{$applicant->public_id}/verifikasi")->assertForbidden();

        $this->actingAsRole(Role::Teacher);
        $this->get('/operator/ppdb/pendaftar')->assertForbidden();
        $this->get('/kepala-sekolah/ppdb/dashboard')->assertForbidden();
    });
});

describe('seleksi & pengumuman', function () {
    it('usulan sesuai kuota, keputusan kepsek, status tersembunyi sampai diumumkan', function () {
        $s = selectionScenario();
        $track = $s['regular'];
        ['applicant' => $a, 'user' => $userA] = $s['applicants']['a@contoh.test'];
        ['applicant' => $b] = $s['applicants']['b@contoh.test'];
        ['applicant' => $c] = $s['applicants']['c@contoh.test'];

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/ppdb/seleksi/jalur/{$track->public_id}/usulan")->assertSessionHasNoErrors();
        expect($b->fresh()->proposal->value)->toBe('DITERIMA')
            ->and($c->fresh()->proposal->value)->toBe('DITERIMA')
            ->and($a->fresh()->proposal->value)->toBe('TIDAK')
            ->and($a->fresh()->status)->toBe(ApplicantStatus::InSelection);

        $this->get("/operator/ppdb/seleksi?jalur={$track->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Ppdb::Shared/Selection')
            ->where('applicants.0.name', 'Rafi Pratama')
            ->where('applicants.0.selection.score', 90)
            ->where('can.decide', false));

        // Operator tidak boleh memutuskan.
        $this->post("/kepala-sekolah/ppdb/seleksi/jalur/{$track->public_id}/setujui")->assertForbidden();

        $this->actingAsRole(Role::Principal, $s['principal']);
        $this->post("/kepala-sekolah/ppdb/seleksi/jalur/{$track->public_id}/setujui")->assertSessionHasNoErrors();
        expect($b->fresh()->status)->toBe(ApplicantStatus::Accepted)
            ->and($a->fresh()->status)->toBe(ApplicantStatus::Rejected)
            ->and($a->fresh()->decided_by)->toBe($s['principal']->id);

        // Belum diumumkan: pendaftar masih melihat "Seleksi"; cek hasil publik tertutup.
        $this->actingAsRole(Role::Applicant, $userA);
        $this->get('/ppdb/akun/dashboard')->assertInertia(fn (Assert $page) => $page->where('applicants.0.status.value', 'IN_SELECTION'));
        auth()->logout();
        $this->post('/ppdb/hasil', ['number' => $a->registration_number, 'birth_date' => '2014-05-10'])->assertSessionHasErrors('number');

        Notification::fake();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/ppdb/periode/{$s['period']->public_id}/umumkan")->assertSessionHasNoErrors();
        Notification::assertSentTo($userA, ApplicantUpdateNotification::class, fn ($n) => $n->kind === ApplicantUpdateNotification::REJECTED);
        $this->post("/operator/ppdb/periode/{$s['period']->public_id}/umumkan")->assertSessionHasErrors('period');

        $this->actingAsRole(Role::Applicant, $userA);
        $this->get('/ppdb/akun/dashboard')->assertInertia(fn (Assert $page) => $page->where('applicants.0.status.value', 'REJECTED'));

        auth()->logout();
        $this->post('/ppdb/hasil', ['number' => mb_strtolower((string) $b->registration_number), 'birth_date' => '2014-05-10'])
            ->assertSessionHas('ppdb_result', fn ($r) => $r['accepted'] === true);
        $this->post('/ppdb/hasil', ['number' => $b->registration_number, 'birth_date' => '2014-01-01'])->assertSessionHasErrors('number');
    });

    it('dashboard PPDB menghitung per status & jalur', function () {
        $s = selectionScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);

        $this->get('/operator/ppdb/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Ppdb::Shared/Dashboard')
            ->where('stats.tracks.0.registered', 3)
            ->where('stats.tracks.0.verified', 3)
            ->where('stats.statuses.3.value', 'VERIFIED')
            ->where('stats.statuses.3.total', 3)
            ->where('stats.daily.29.total', 3));
    });
});

describe('daftar ulang', function () {
    it('pendaftar diterima mengirim daftar ulang → operator konfirmasi → siswa, orang tua, akun & tagihan', function () {
        $s = selectionScenario();
        ['applicant' => $b, 'user' => $userB] = $s['applicants']['b@contoh.test'];
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/ppdb/seleksi/jalur/{$s['regular']->public_id}/usulan");
        $this->actingAsRole(Role::Principal, $s['principal']);
        $this->post("/kepala-sekolah/ppdb/seleksi/pendaftar/{$b->public_id}/putuskan", ['decision' => 'ACCEPTED'])->assertSessionHasNoErrors();

        // Sebelum diumumkan: daftar ulang belum bisa.
        $this->actingAsRole(Role::Applicant, $userB);
        $this->get("/ppdb/akun/pendaftaran/{$b->public_id}/daftar-ulang")->assertForbidden();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/ppdb/pendaftar/{$b->public_id}/daftar-ulang", ['nis' => '20270001', 'entry_date' => '2027-07-01'])->assertSessionHasErrors('status');
        $this->post("/operator/ppdb/periode/{$s['period']->public_id}/umumkan");

        $this->actingAsRole(Role::Applicant, $userB);
        $this->post("/ppdb/akun/pendaftaran/{$b->public_id}/daftar-ulang", [
            'address' => 'Jl. Baru 5',
            'guardians' => [['relationship' => 'AYAH', 'name' => 'Budi Pratama', 'phone' => '081234567890', 'is_primary' => true]],
        ])->assertSessionHasErrors('agreement');
        $this->post("/ppdb/akun/pendaftaran/{$b->public_id}/daftar-ulang", [
            'address' => 'Jl. Baru 5',
            'guardians' => [
                ['relationship' => 'AYAH', 'name' => 'Budi Pratama', 'phone' => '081234567890', 'is_primary' => true],
                ['relationship' => 'IBU', 'name' => 'Sari', 'phone' => '081200000000', 'is_primary' => false],
            ],
            'agreement' => true,
        ])->assertRedirect('/ppdb/akun/dashboard');
        expect($b->fresh()->reregistration_submitted_at)->not->toBeNull();

        $fee = FeeType::query()->create(['name' => 'Daftar ulang', 'default_amount' => 500000, 'frequency' => FeeFrequency::Once]);
        Student::factory()->create(['nis' => '20270001']);
        Notification::fake();

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->get("/operator/ppdb/pendaftar/{$b->public_id}")->assertInertia(fn (Assert $page) => $page->where('reregistration.suggested_nis', '20270002'));
        $this->post("/operator/ppdb/pendaftar/{$b->public_id}/daftar-ulang", ['nis' => '20270001', 'entry_date' => '2027-07-01'])->assertSessionHasErrors('nis');
        $this->post("/operator/ppdb/pendaftar/{$b->public_id}/daftar-ulang", [
            'nis' => '20270002', 'entry_date' => '2027-07-01',
            'fee_type' => $fee->public_id, 'fee_amount' => 450000, 'fee_due_date' => '2027-07-15',
        ])->assertSessionHasNoErrors();

        $student = Student::query()->where('nis', '20270002')->sole();
        $primary = Guardian::query()->where('user_id', $userB->id)->sole();
        expect($student->name)->toBe('Rafi Pratama')
            ->and($student->address)->toBe('Jl. Baru 5')
            ->and($student->guardians()->count())->toBe(2)
            ->and($student->guardians()->wherePivot('is_primary', true)->sole()->id)->toBe($primary->id)
            ->and($b->fresh()->status)->toBe(ApplicantStatus::ReRegistered)
            ->and($b->fresh()->student_id)->toBe($student->id)
            ->and(Invoice::query()->where('student_id', $student->id)->sole()->total)->toBe(450000);

        $userB->refresh();
        expect($userB->hasRoleEnum(Role::Guardian))->toBeTrue()->and($userB->hasRoleEnum(Role::Applicant))->toBeFalse();
        Notification::assertSentTo($userB, ApplicantUpdateNotification::class, fn ($n) => $n->kind === ApplicantUpdateNotification::REREGISTERED);
    });

    it('akun dengan pendaftaran lain yang masih berjalan tetap punya akses PPDB', function () {
        $s = selectionScenario();
        ['applicant' => $b, 'user' => $userB] = $s['applicants']['b@contoh.test'];
        $this->actingAsRole(Role::Applicant, $userB);
        startApplication($s['regular']);

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/ppdb/seleksi/jalur/{$s['regular']->public_id}/usulan");
        $this->actingAsRole(Role::Principal, $s['principal']);
        $this->post("/kepala-sekolah/ppdb/seleksi/pendaftar/{$b->public_id}/putuskan", ['decision' => 'ACCEPTED']);
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/ppdb/periode/{$s['period']->public_id}/umumkan");
        $this->post("/operator/ppdb/pendaftar/{$b->public_id}/daftar-ulang", ['nis' => '99990001', 'entry_date' => '2027-07-01'])->assertSessionHasNoErrors();

        $userB->refresh();
        expect($userB->hasRoleEnum(Role::Guardian))->toBeTrue()->and($userB->hasRoleEnum(Role::Applicant))->toBeTrue();
    });
});
