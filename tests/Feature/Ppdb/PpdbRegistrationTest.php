<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Notifications\ApplicationSubmittedNotification;
use App\Modules\Ppdb\Notifications\VerifyApplicantEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    $this->travelTo('2026-10-01 09:00:00');
});

describe('akun PPDB', function () {
    it('daftar akun → email verifikasi → portal terbuka setelah verifikasi', function () {
        Notification::fake();

        $this->post('/ppdb/daftar', ['name' => 'Budi', 'email' => 'Budi@Contoh.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertRedirect('/ppdb/akun/verifikasi-email');

        $user = User::query()->where('email', 'budi@contoh.test')->sole();
        expect($user->hasRoleEnum(Role::Applicant))->toBeTrue()->and($user->email_verified_at)->toBeNull();
        Notification::assertSentTo($user, VerifyApplicantEmailNotification::class);

        $this->get('/ppdb/akun/dashboard')->assertRedirect('/ppdb/akun/verifikasi-email');

        $url = VerifyApplicantEmailNotification::url($user);
        expect($url)->toContain($user->public_id)->not->toContain('/'.$user->id.'/');
        $this->get($url)->assertRedirect(route('applicant.dashboard'));
        expect($user->fresh()->email_verified_at)->not->toBeNull();
        // Guard di test menyimpan objek user dari request pendaftaran; di aplikasi nyata tiap request memuat ulang.
        $this->actingAsRole(Role::Applicant, $user->fresh());
        $this->get('/ppdb/akun/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Ppdb::Applicant/Dashboard'));
    });

    it('tautan verifikasi palsu atau kedaluwarsa ditolak', function () {
        $user = User::factory()->withRole(Role::Applicant)->create(['email_verified_at' => null]);

        $this->get("/ppdb/verifikasi/{$user->public_id}/".sha1($user->email))->assertForbidden();
        $url = VerifyApplicantEmailNotification::url($user);
        $this->travel(25)->hours();
        $this->get($url)->assertForbidden();
        expect($user->fresh()->email_verified_at)->toBeNull();
    });

    it('login /ppdb/masuk hanya untuk akun PPDB; email sudah dipakai ditolak saat daftar', function () {
        $applicant = User::factory()->withRole(Role::Applicant)->create(['email' => 'a@contoh.test', 'password' => 'rahasia123', 'email_verified_at' => now()]);
        User::factory()->withRole(Role::Guardian)->create(['email' => 'ortu@contoh.test', 'password' => 'rahasia123']);

        $this->post('/ppdb/masuk', ['email' => 'ortu@contoh.test', 'password' => 'rahasia123'])->assertSessionHasErrors('email');
        $this->post('/ppdb/masuk', ['email' => 'a@contoh.test', 'password' => 'rahasia123'])->assertRedirect(route('applicant.dashboard'));
        $this->assertAuthenticatedAs($applicant);

        auth()->logout();
        $this->post('/ppdb/daftar', ['name' => 'X', 'email' => 'a@contoh.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertSessionHasErrors('email');
    });
});

describe('periode (operator)', function () {
    it('operator mengatur periode, jalur & dokumen; yang sudah dipakai tidak bisa dihapus', function () {
        ['period' => $period, 'regular' => $regular, 'operator' => $operator] = ppdbScenario();
        expect($period->tracks()->count())->toBe(2)->and($regular->requirements()->count())->toBe(2);

        verifiedApplicant();
        startApplication($regular);

        $this->actingAsRole(Role::Operator, $operator);
        $this->get('/operator/ppdb/periode')->assertInertia(fn (Assert $page) => $page
            ->component('Ppdb::Operator/Periods/Index')
            ->where('periods.0.is_open', true)
            ->where('periods.0.applicants_count', 1));

        $payload = fn (array $tracks) => ['name' => $period->name, 'academic_year' => $period->academicYear->public_id, 'grade' => 7,
            'opens_at' => '2026-09-01T08:00', 'closes_at' => '2026-10-31T23:59', 'is_active' => true, 'tracks' => $tracks];

        $this->put("/operator/ppdb/periode/{$period->public_id}", $payload([['public_id' => $regular->public_id, 'name' => 'Reguler', 'quota' => 120, 'requirements' => []]]))
            ->assertSessionHasNoErrors();
        expect($regular->fresh()->quota)->toBe(120)->and($regular->requirements()->count())->toBe(0)->and($period->tracks()->count())->toBe(1);

        $this->put("/operator/ppdb/periode/{$period->public_id}", $payload([['name' => 'Baru', 'quota' => 5]]))->assertSessionHasErrors('tracks');
        $this->delete("/operator/ppdb/periode/{$period->public_id}")->assertSessionHasErrors('period');
    });

    it('guru & orang tua tidak bisa mengelola periode', function () {
        $this->actingAsRole(Role::Teacher);
        $this->get('/operator/ppdb/periode')->assertForbidden();
    });
});

describe('formulir pendaftaran', function () {
    it('draf otomatis, validasi per langkah, dokumen, lalu kirim dengan nomor pendaftaran', function () {
        ['regular' => $regular, 'operator' => $operator] = ppdbScenario();
        Notification::fake();
        verifiedApplicant();
        $applicant = startApplication($regular);
        $base = "/ppdb/akun/pendaftaran/{$applicant->public_id}";

        $this->putJson($base, ['student' => ['name' => 'Rafi']])->assertOk();
        expect($applicant->fresh()->name)->toBe('Rafi');
        $this->putJson($base, ['student' => ['nik' => '123']])->assertJsonValidationErrors('student.nik');
        $this->putJson($base, ['student' => ['name' => 'Rafi'], 'complete' => true])->assertJsonValidationErrors(['student.nik', 'student.birth_date']);

        $this->post("{$base}/kirim")->assertSessionHasErrors();
        fillApplication($applicant, $regular);

        $this->get($base.'?langkah=dokumen')->assertInertia(fn (Assert $page) => $page
            ->component('Ppdb::Applicant/Form')
            ->where('step', 'dokumen')
            ->where('applicant.guardians.0.name', 'Budi Pratama')
            ->where('applicant.requirements.0.document.status.value', 'PENDING')
            ->where('applicant.requirements.1.document', null)
            ->missing('applicant.id'));

        $this->post("{$base}/kirim")->assertRedirect('/ppdb/akun/dashboard');
        $fresh = $applicant->fresh();
        expect($fresh->status)->toBe(ApplicantStatus::Submitted)->and($fresh->registration_number)->toBe('PPDB-2027-0001');
        Notification::assertSentTo($operator, ApplicationSubmittedNotification::class);

        // Setelah terkirim formulir terkunci.
        $this->putJson($base, ['student' => ['name' => 'Ubah']])->assertForbidden();
        $this->get($base)->assertInertia(fn (Assert $page) => $page->where('editable', false)->where('step', 'tinjau'));
        $this->get("{$base}/bukti.pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        // Pendaftaran kedua (adik) dengan akun yang sama → nomor berikutnya.
        $second = startApplication($regular);
        fillApplication($second, $regular);
        $this->post("/ppdb/akun/pendaftaran/{$second->public_id}/kirim");
        expect($second->fresh()->registration_number)->toBe('PPDB-2027-0002');
    });

    it('pendaftaran & dokumen orang lain tidak bisa diakses', function () {
        ['regular' => $regular] = ppdbScenario();
        verifiedApplicant('a@contoh.test');
        $applicant = startApplication($regular);
        fillApplication($applicant, $regular);
        $file = File::query()->sole();

        verifiedApplicant('b@contoh.test');
        $this->get("/ppdb/akun/pendaftaran/{$applicant->public_id}")->assertForbidden();
        $this->putJson("/ppdb/akun/pendaftaran/{$applicant->public_id}", ['student' => ['name' => 'X']])->assertForbidden();
        $this->get(route('files.show', $file))->assertForbidden();

        $this->actingAsRole(Role::Guardian);
        $this->get('/ppdb/akun/dashboard')->assertForbidden();
    });

    it('periode tutup: draf terkunci, tapi perbaikan (NEEDS_REVISION) tetap bisa dikirim ulang', function () {
        ['regular' => $regular] = ppdbScenario();
        verifiedApplicant();
        $draft = startApplication($regular);
        $revision = startApplication($regular);
        fillApplication($revision, $regular);
        $this->post("/ppdb/akun/pendaftaran/{$revision->public_id}/kirim");
        $revision->update(['status' => ApplicantStatus::NeedsRevision, 'revision_note' => 'Foto akta buram']);

        $this->travelTo('2026-11-05 09:00:00');
        $this->putJson("/ppdb/akun/pendaftaran/{$draft->public_id}", ['student' => ['name' => 'X']])->assertForbidden();
        $this->post('/ppdb/akun/pendaftaran', ['track' => $regular->public_id])->assertSessionHasErrors('track');

        $this->post("/ppdb/akun/pendaftaran/{$revision->public_id}/kirim")->assertRedirect('/ppdb/akun/dashboard');
        expect($revision->fresh()->status)->toBe(ApplicantStatus::Submitted)->and($revision->fresh()->registration_number)->toBe('PPDB-2027-0001');
    });

    it('draf yang belum dikirim bisa dihapus', function () {
        ['regular' => $regular] = ppdbScenario();
        verifiedApplicant();
        $draft = startApplication($regular);

        $this->delete("/ppdb/akun/pendaftaran/{$draft->public_id}")->assertRedirect('/ppdb/akun/dashboard');
        expect(PpdbApplicant::query()->count())->toBe(0);
    });
});
