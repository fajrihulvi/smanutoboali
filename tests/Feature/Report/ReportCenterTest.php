<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\FileService;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\Report\Enums\ExportStatus;
use App\Modules\Report\Jobs\GenerateReportExport;
use App\Modules\Report\Models\ReportExport;
use App\Modules\Report\Notifications\ReportExportNotification;
use App\Modules\Report\Reports\ReportRegistry;
use App\Modules\Report\Services\ReportRenderer;
use App\Modules\Report\Services\ReportRunner;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * Guru A: wali kelas VII-A + mengajar Matematika VII-A & IPA VIII-B.
 * Guru B: mengajar Bahasa VII-A saja. Dua siswa di VII-A, satu di VIII-B.
 *
 * @return array<string, mixed>
 */
function reportCenterScenario(): array
{
    $year = activeAcademicYear();
    Semester::query()->where('academic_year_id', $year->id)->orderBy('start_date')->firstOrFail()->update(['is_active' => true]);
    app(ActivePeriod::class)->flush();

    $userA = User::factory()->withRole(Role::Teacher)->create();
    $teacherA = Teacher::factory()->withAccount($userA)->create();
    $userB = User::factory()->withRole(Role::Teacher)->create();
    $teacherB = Teacher::factory()->withAccount($userB)->create();
    $userC = User::factory()->withRole(Role::Teacher)->create();
    Teacher::factory()->withAccount($userC)->create(); // tidak mengajar apa pun

    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'grade' => 7, 'homeroom_teacher_id' => $teacherA->id]);
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B', 'grade' => 8]);
    $ani = Student::factory()->create(['name' => 'Ani']);
    $dewi = Student::factory()->create(['name' => 'Dewi']);
    $budi = Student::factory()->create(['name' => 'Budi']);
    enrollIn($classA, $ani);
    enrollIn($classA, $dewi);
    enrollIn($classB, $budi);

    assignTeaching($classA, $teacherA, Subject::factory()->create(['name' => 'Matematika']));
    assignTeaching($classB, $teacherA, Subject::factory()->create(['name' => 'IPA']));
    assignTeaching($classA, $teacherB, Subject::factory()->create(['name' => 'Bahasa']));

    recordAttendance($classA, $ani, 'HADIR', '2026-09-25');
    recordAttendance($classA, $dewi, 'SAKIT', '2026-09-25');
    recordAttendance($classB, $budi, 'ALPA', '2026-09-25');

    return compact('year', 'userA', 'userB', 'userC', 'classA', 'classB', 'ani', 'dewi', 'budi');
}

function reportCenterKeys(): array
{
    $keys = [];
    test()->get(test()->reportBase)->assertOk()->assertInertia(function (Assert $page) use (&$keys) {
        $keys = array_column($page->toArray()['props']['reports'], 'key');
    });

    return $keys;
}

describe('akses', function () {
    it('menampilkan laporan sesuai role dan izin', function () {
        reportCenterScenario();

        $this->actingAsRole(Role::Operator);
        $this->reportBase = '/operator/laporan';
        expect(reportCenterKeys())->toBe(['presensi-siswa', 'presensi-guru', 'nilai', 'raport', 'penerimaan', 'tunggakan', 'ppdb', 'buku-tamu']);

        $this->actingAsRole(Role::Principal);
        $this->reportBase = '/kepala-sekolah/laporan';
        expect(reportCenterKeys())->toBe(['presensi-siswa', 'presensi-guru', 'nilai', 'raport', 'penerimaan', 'tunggakan', 'ppdb']);

        $this->actingAsRole(Role::Teacher);
        $this->reportBase = '/guru/laporan';
        expect(reportCenterKeys())->toBe(['presensi-siswa', 'nilai']);
    });

    it('menolak orang tua dan role lain di portal yang salah', function () {
        reportCenterScenario();

        $this->actingAsRole(Role::Guardian);
        $this->get('/operator/laporan')->assertForbidden();
        $this->get('/guru/laporan')->assertForbidden();

        $this->actingAsRole(Role::Teacher);
        $this->get('/operator/laporan')->assertForbidden();
    });

    it('menyembunyikan laporan keuangan tanpa izin keuangan', function () {
        reportCenterScenario();
        $operator = User::factory()->withRole(Role::Operator)->create();
        $operator->roles->each->revokePermissionTo(['finance.manage', 'finance.view']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAsRole(Role::Operator, $operator->fresh());
        $this->reportBase = '/operator/laporan';
        expect(reportCenterKeys())->not->toContain('penerimaan')->not->toContain('tunggakan');

        $this->postJson('/operator/laporan/ekspor', ['laporan' => 'tunggakan', 'format' => 'pdf'])->assertForbidden();
    });
});

describe('pratinjau', function () {
    it('menyusun rekap presensi siswa per siswa dengan total', function () {
        $s = reportCenterScenario();

        $this->actingAsRole(Role::Operator);
        $this->get('/operator/laporan?laporan=presensi-siswa&from=2026-09-01&to=2026-09-28&classroom='.$s['classA']->public_id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Report::Center')
                ->where('selected', 'presensi-siswa')
                ->where('preview.total', 2)
                ->where('preview.rows.0.2', 'Ani')
                ->where('preview.rows.0.4', '1')   // hadir
                ->where('preview.rows.1.2', 'Dewi')
                ->where('preview.rows.1.6', '1')   // sakit
                ->where('preview.footer.2', 'Total')
                ->where('preview.footer.10', '50,0'));
    });

    it('menampilkan pesan bila filter belum lengkap', function () {
        reportCenterScenario();

        $this->actingAsRole(Role::Operator);
        $this->get('/operator/laporan?laporan=nilai')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('preview', null)
            ->has('previewErrors.classroom'));

        $this->get('/operator/laporan?laporan=presensi-siswa&from=2025-01-01&to=2026-09-28')
            ->assertInertia(fn (Assert $page) => $page->where('previewErrors.to', 'Rentang tanggal maksimal satu tahun.'));
    });
});

describe('laporan keuangan', function () {
    it('penerimaan menjumlahkan pembayaran terkonfirmasi dan tunggakan menghitung sisa tagihan', function () {
        $s = reportCenterScenario();
        $fee = FeeType::query()->create(['name' => 'SPP', 'default_amount' => 150000, 'frequency' => FeeFrequency::Monthly]);
        $invoice = Invoice::query()->create([
            'number' => 'INV-1', 'academic_year_id' => $s['year']->id, 'student_id' => $s['ani']->id, 'fee_type_id' => $fee->id,
            'period' => '2026-09', 'period_label' => 'September 2026', 'due_date' => '2026-09-10',
            'total' => 150000, 'paid_amount' => 100000, 'status' => InvoiceStatus::Unpaid,
        ]);
        Payment::query()->create(['invoice_id' => $invoice->id, 'method' => PaymentMethod::Manual, 'status' => PaymentStatus::Confirmed, 'amount' => 100000, 'paid_on' => '2026-09-20', 'receipt_number' => 'KW/2026/09/0001']);
        Payment::query()->create(['invoice_id' => $invoice->id, 'method' => PaymentMethod::Manual, 'status' => PaymentStatus::Pending, 'amount' => 50000, 'paid_on' => '2026-09-21']);

        $this->actingAsRole(Role::Operator);
        $this->get('/operator/laporan?laporan=penerimaan&from=2026-09-01&to=2026-09-28')->assertInertia(fn (Assert $page) => $page
            ->where('preview.total', 1)
            ->where('preview.rows.0.2', 'KW/2026/09/0001')
            ->where('preview.rows.0.5', 'VII-A')
            ->where('preview.footer.8', 'Rp 100.000'));

        $this->get('/operator/laporan?laporan=tunggakan')->assertInertia(fn (Assert $page) => $page
            ->where('preview.total', 1)
            ->where('preview.rows.0.2', 'Ani')
            ->where('preview.rows.0.5', 'Rp 50.000'));
    });
});

describe('scope guru', function () {
    it('guru hanya bisa memilih rombel yang diajar atau diwalikan', function () {
        $s = reportCenterScenario();

        $this->actingAsRole(Role::Teacher, $s['userB']);
        $this->get('/guru/laporan?laporan=presensi-siswa')->assertInertia(fn (Assert $page) => $page
            ->has('fields.2.options', 1)
            ->where('fields.2.options.0.label', 'VII-A')
            ->where('preview.total', 2));

        $this->get('/guru/laporan?laporan=presensi-siswa&classroom='.$s['classB']->public_id)
            ->assertInertia(fn (Assert $page) => $page->has('previewErrors.classroom'));

        $this->postJson('/guru/laporan/ekspor', [
            'laporan' => 'presensi-siswa', 'format' => 'pdf', 'from' => '2026-09-01', 'to' => '2026-09-28', 'classroom' => $s['classB']->public_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('classroom');
    });

    it('nilai: guru mapel hanya melihat mapelnya, wali kelas melihat semua mapel', function () {
        $s = reportCenterScenario();
        $url = '/guru/laporan?laporan=nilai&classroom='.$s['classA']->public_id;

        $this->actingAsRole(Role::Teacher, $s['userB']);
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('preview.columns', ['No', 'NIS', 'Nama', 'Bahasa', 'Rata-rata']));

        $this->actingAsRole(Role::Teacher, $s['userA']);
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('preview.columns', ['No', 'NIS', 'Nama', 'Bahasa', 'Matematika', 'Rata-rata']));
    });

    it('guru tanpa kelas tidak melihat data siswa', function () {
        $s = reportCenterScenario();

        $this->actingAsRole(Role::Teacher, $s['userC']);
        $this->get('/guru/laporan?laporan=presensi-siswa')->assertInertia(fn (Assert $page) => $page->where('preview.total', 0));
    });
});

describe('ekspor', function () {
    it('laporan kecil langsung diunduh sebagai PDF dan Excel, dan tercatat di audit log', function () {
        reportCenterScenario();
        $this->actingAsRole(Role::Operator);
        $payload = ['laporan' => 'presensi-siswa', 'from' => '2026-09-01', 'to' => '2026-09-28'];

        $pdf = $this->postJson('/operator/laporan/ekspor', [...$payload, 'format' => 'pdf'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        expect($pdf->headers->get('Content-Disposition'))->toContain('rekap-presensi-siswa')
            ->and(substr((string) $pdf->getContent(), 0, 4))->toBe('%PDF');

        $xlsx = $this->postJson('/operator/laporan/ekspor', [...$payload, 'format' => 'xlsx'])->assertOk();
        expect($xlsx->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and(substr((string) $xlsx->getContent(), 0, 2))->toBe('PK');

        expect(Activity::query()->where('log_name', 'report')->where('event', 'exported')->count())->toBe(2)
            ->and(ReportExport::query()->count())->toBe(0);
    });

    it('laporan besar diproses Job, pemilik diberi tahu, dan tautan unduh hanya untuk pemilik selama 24 jam', function () {
        reportCenterScenario();
        config(['skoolia.reports.direct_limit' => 1]);
        Notification::fake();
        $operator = $this->actingAsRole(Role::Operator);

        $this->postJson('/operator/laporan/ekspor', ['laporan' => 'presensi-siswa', 'format' => 'xlsx', 'from' => '2026-09-01', 'to' => '2026-09-28'])
            ->assertStatus(202)
            ->assertJsonPath('queued', true);

        // QUEUE_CONNECTION=sync di test: Job langsung berjalan.
        $export = ReportExport::query()->with('file')->sole();
        expect($export->status)->toBe(ExportStatus::Done)
            ->and($export->rows)->toBe(3)
            ->and($export->file)->not->toBeNull();
        Storage::disk('private')->assertExists($export->file->path);
        Notification::assertSentTo($operator, ReportExportNotification::class, fn ($n) => $n->toArray($operator)['url'] === "/laporan/ekspor/{$export->public_id}");

        $this->get("/laporan/ekspor/{$export->public_id}")->assertOk()->assertDownload();

        $this->actingAsRole(Role::Operator);
        $this->get("/laporan/ekspor/{$export->public_id}")->assertNotFound();
        $this->get("/files/{$export->file->public_id}")->assertForbidden();

        $this->actingAs($operator);
        $this->travel(25)->hours();
        $this->get("/laporan/ekspor/{$export->public_id}")->assertRedirect('/dashboard')->assertSessionHas('error');

        $this->artisan('model:prune', ['--model' => [ReportExport::class]])->assertSuccessful();
        expect(ReportExport::query()->count())->toBe(0);
        Storage::disk('private')->assertMissing($export->file->path);
    });

    it('Job menolak bila pemohon sudah tidak berhak', function () {
        $s = reportCenterScenario();
        config(['skoolia.reports.direct_limit' => 0]);
        Notification::fake();

        $export = ReportExport::query()->create([
            'user_id' => $s['userB']->id, 'role' => Role::Teacher->value, 'report' => 'presensi-guru', 'format' => 'pdf',
            'filters' => ['from' => '2026-09-01', 'to' => '2026-09-28'], 'title' => 'Rekap Presensi Guru',
            'status' => ExportStatus::Pending, 'expires_at' => now()->addDay(),
        ]);
        (new GenerateReportExport($export->id))->handle(
            app(ReportRegistry::class),
            app(ReportRunner::class),
            app(ReportRenderer::class),
            app(FileService::class),
        );

        expect($export->fresh()->status)->toBe(ExportStatus::Failed);
        Notification::assertSentTo($s['userB'], ReportExportNotification::class);
    });
});
