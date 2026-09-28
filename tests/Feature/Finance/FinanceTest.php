<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\SettingsService;
use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceBatch;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\StudentDiscount;
use App\Modules\Finance\Notifications\InvoiceIssuedNotification;
use App\Modules\Finance\Notifications\InvoiceReminderNotification;
use App\Modules\Finance\Notifications\PaymentProofSubmittedNotification;
use App\Modules\Finance\Notifications\PaymentReviewedNotification;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * VII-A: Ani (punya akun orang tua) & Budi (beasiswa 50%); VIII-B: Citra (orang tua lain). SPP bulanan Rp150.000.
 *
 * @return array<string, mixed>
 */
function financeScenario(): array
{
    $year = activeAcademicYear();
    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'grade' => 7]);
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B', 'grade' => 8]);

    $parent = User::factory()->withRole(Role::Guardian)->create();
    $ani = Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']);
    $budi = Student::factory()->create(['name' => 'Budi']);
    $otherParent = User::factory()->withRole(Role::Guardian)->create();
    $citra = Student::factory()->withGuardian(Guardian::factory()->withAccount($otherParent)->create())->create(['name' => 'Citra']);
    enrollIn($classA, $ani);
    enrollIn($classA, $budi);
    enrollIn($classB, $citra);

    $spp = FeeType::query()->create(['name' => 'SPP', 'default_amount' => 150000, 'frequency' => FeeFrequency::Monthly]);
    StudentDiscount::query()->create(['student_id' => $budi->id, 'fee_type_id' => null, 'kind' => DiscountKind::Percent, 'value' => 50, 'reason' => 'Beasiswa prestasi']);

    return [
        'year' => $year, 'classA' => $classA, 'classB' => $classB, 'parent' => $parent, 'otherParent' => $otherParent,
        'ani' => $ani, 'budi' => $budi, 'citra' => $citra, 'spp' => $spp,
        'operator' => User::factory()->withRole(Role::Operator)->create(),
        'principal' => User::factory()->withRole(Role::Principal)->create(),
    ];
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function generatePayload(FeeType $fee, array $extra = []): array
{
    return ['fee_type' => $fee->public_id, 'month' => '2026-09', 'amount' => 150000, 'due_date' => '2026-10-10', 'scope' => 'SEMUA', ...$extra];
}

function invoiceFor(Student $student): Invoice
{
    return Invoice::query()->where('student_id', $student->id)->where('status', '!=', 'CANCELLED')->sole();
}

describe('generate tagihan massal', function () {
    it('preview menghitung sasaran, potongan, dan total', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);

        $this->postJson('/operator/keuangan/buat/preview', generatePayload($s['spp']))
            ->assertOk()
            ->assertJson(['label' => 'SPP September 2026', 'students' => 3, 'skipped' => 0, 'to_create' => 3, 'discounted' => 1, 'gross' => 450000, 'total' => 375000]);

        $this->postJson('/operator/keuangan/buat/preview', generatePayload($s['spp'], ['scope' => 'ROMBEL', 'classrooms' => [$s['classB']->public_id]]))
            ->assertJson(['students' => 1, 'total' => 150000]);
        $this->postJson('/operator/keuangan/buat/preview', generatePayload($s['spp'], ['scope' => 'TINGKAT', 'grade' => 7]))
            ->assertJson(['students' => 2, 'total' => 225000]);
    });

    it('membuat tagihan dengan nomor berurutan & potongan, memberi tahu orang tua, dan tidak menggandakan periode yang sama', function () {
        $s = financeScenario();
        Notification::fake();
        $this->actingAsRole(Role::Operator, $s['operator']);

        $this->post('/operator/keuangan/buat', generatePayload($s['spp']))->assertSessionHasNoErrors();

        $batch = InvoiceBatch::query()->sole();
        expect($batch->status->value)->toBe('DONE')
            ->and($batch->created_count)->toBe(3)
            ->and($batch->total_amount)->toBe(375000)
            ->and(Invoice::query()->orderBy('id')->pluck('number')->all())->toBe(['INV/2026/09/0001', 'INV/2026/09/0002', 'INV/2026/09/0003']);

        $budiInvoice = invoiceFor($s['budi']);
        expect($budiInvoice->total)->toBe(75000)
            ->and($budiInvoice->items()->pluck('amount')->all())->toBe([150000, -75000])
            ->and($budiInvoice->period_label)->toBe('September 2026');

        Notification::assertSentTo($s['parent'], InvoiceIssuedNotification::class, fn ($n) => $n->total === 150000 && $n->studentName === 'Ani');

        $this->post('/operator/keuangan/buat', generatePayload($s['spp']))->assertSessionHasNoErrors();
        $second = InvoiceBatch::query()->latest('id')->first();
        expect($second->created_count)->toBe(0)->and($second->skipped_count)->toBe(3)->and(Invoice::query()->count())->toBe(3);
    });

    it('tagihan yang dibatalkan boleh dibuat ulang; yang sudah dibayar tidak bisa dibatalkan', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/buat', generatePayload($s['spp']));
        $ani = invoiceFor($s['ani']);
        $citra = invoiceFor($s['citra']);

        $this->post("/operator/keuangan/tagihan/{$ani->public_id}/batal", ['reason' => 'Salah nominal'])->assertSessionHasNoErrors();
        expect($ani->fresh()->status->value)->toBe('CANCELLED')->and($ani->fresh()->dedupe_key)->toBeNull();

        $this->post('/operator/keuangan/buat', generatePayload($s['spp'], ['amount' => 140000]));
        expect(invoiceFor($s['ani'])->total)->toBe(140000);

        $this->post("/operator/keuangan/tagihan/{$citra->public_id}/pembayaran", ['amount' => 1000, 'paid_on' => '2026-09-28']);
        $this->post("/operator/keuangan/tagihan/{$citra->public_id}/batal", ['reason' => 'x'])->assertSessionHasErrors('invoice');
    });
});

describe('pembayaran', function () {
    it('operator mencatat cicilan sampai lunas, dengan kwitansi per pembayaran', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/buat', generatePayload($s['spp']));
        $invoice = invoiceFor($s['ani']);
        $url = "/operator/keuangan/tagihan/{$invoice->public_id}/pembayaran";

        $this->post($url, ['amount' => 50000, 'paid_on' => '2026-09-28'])->assertSessionHasNoErrors();
        expect($invoice->fresh()->status->value)->toBe('UNPAID')->and($invoice->fresh()->paid_amount)->toBe(50000);

        $this->post($url, ['amount' => 200000, 'paid_on' => '2026-09-28'])->assertSessionHasErrors('amount');
        $this->post($url, ['amount' => 100000, 'paid_on' => '2026-10-01'])->assertSessionHasErrors('paid_on');

        $this->post($url, ['amount' => 100000, 'paid_on' => '2026-09-28'])->assertSessionHasNoErrors();
        $fresh = $invoice->fresh();
        expect($fresh->status->value)->toBe('PAID')
            ->and($fresh->paid_at)->not->toBeNull()
            ->and(Payment::query()->pluck('receipt_number')->all())->toBe(['KW/2026/09/0001', 'KW/2026/09/0002']);

        $this->post($url, ['amount' => 1, 'paid_on' => '2026-09-28'])->assertSessionHasErrors('amount');

        $payment = Payment::query()->first();
        $this->get("/operator/keuangan/pembayaran/{$payment->public_id}/kwitansi")->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->get("/orang-tua/keuangan/pembayaran/{$payment->public_id}/kwitansi")->assertOk();
        $this->actingAsRole(Role::Guardian, $s['otherParent']);
        $this->get("/orang-tua/keuangan/pembayaran/{$payment->public_id}/kwitansi")->assertForbidden();
    });

    it('orang tua mengirim bukti → PENDING → ditolak lalu dikirim ulang dan diterima', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/buat', generatePayload($s['spp']));
        $invoice = invoiceFor($s['ani']);
        Notification::fake();

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $proofUrl = "/orang-tua/keuangan/{$invoice->public_id}/bukti";
        $this->post($proofUrl, ['amount' => 150000, 'paid_on' => '2026-09-27'])->assertSessionHasErrors('proof');
        $this->post($proofUrl, ['amount' => 150000, 'paid_on' => '2026-09-27', 'proof' => UploadedFile::fake()->image('transfer.jpg')])->assertSessionHasNoErrors();

        expect($invoice->fresh()->status->value)->toBe('PENDING');
        Notification::assertSentTo($s['operator'], PaymentProofSubmittedNotification::class);
        $this->post($proofUrl, ['amount' => 150000, 'paid_on' => '2026-09-27', 'proof' => UploadedFile::fake()->image('lagi.jpg')])->assertSessionHasErrors('proof');

        $pending = Payment::query()->sole();
        $proof = $pending->proof()->sole();
        $this->get(route('files.show', $proof))->assertOk();
        $this->actingAsRole(Role::Guardian, $s['otherParent']);
        $this->get(route('files.show', $proof))->assertForbidden();

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->get('/operator/keuangan/verifikasi')->assertInertia(fn (Assert $page) => $page
            ->component('Finance::Operator/Verification')
            ->has('payments.data', 1)
            ->where('payments.data.0.invoice.student.name', 'Ani'));
        $this->post("/operator/keuangan/pembayaran/{$pending->public_id}/verifikasi", ['decision' => 'reject'])->assertSessionHasErrors('reason');
        $this->post("/operator/keuangan/pembayaran/{$pending->public_id}/verifikasi", ['decision' => 'reject', 'reason' => 'Nominal tidak terbaca'])->assertSessionHasNoErrors();
        expect($invoice->fresh()->status->value)->toBe('UNPAID');
        Notification::assertSentTo($s['parent'], PaymentReviewedNotification::class, fn ($n) => ! $n->accepted && $n->reason === 'Nominal tidak terbaca');

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->post($proofUrl, ['amount' => 150000, 'paid_on' => '2026-09-27', 'proof' => UploadedFile::fake()->image('ulang.jpg')])->assertSessionHasNoErrors();
        $second = Payment::query()->where('status', 'PENDING')->sole();

        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post("/operator/keuangan/pembayaran/{$second->public_id}/verifikasi", ['decision' => 'approve'])->assertSessionHasNoErrors();
        expect($invoice->fresh()->status->value)->toBe('PAID')->and($second->fresh()->receipt_number)->toBe('KW/2026/09/0001');
        $this->post("/operator/keuangan/pembayaran/{$second->public_id}/verifikasi", ['decision' => 'approve'])->assertForbidden();
    });
});

describe('akses', function () {
    it('orang tua hanya melihat tagihan anaknya', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/buat', generatePayload($s['spp']));
        $ani = invoiceFor($s['ani']);
        $citra = invoiceFor($s['citra']);

        $this->actingAsRole(Role::Guardian, $s['parent']);
        $this->get('/orang-tua/keuangan')->assertInertia(fn (Assert $page) => $page
            ->component('Finance::Guardian/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.student.name', 'Ani')
            ->where('outstanding.amount', 150000)
            ->missing('invoices.data.0.id'));
        app(SettingsService::class)->set(['payment_instructions' => 'BSI 123 a.n. Sekolah']);
        $this->get("/orang-tua/keuangan/{$ani->public_id}")->assertInertia(fn (Assert $page) => $page
            ->where('invoice.can.pay', true)
            ->where('instructions', 'BSI 123 a.n. Sekolah'));
        $this->get("/orang-tua/keuangan/{$citra->public_id}")->assertForbidden();
        $this->post("/orang-tua/keuangan/{$citra->public_id}/bukti", ['amount' => 1000, 'paid_on' => '2026-09-27', 'proof' => UploadedFile::fake()->image('x.jpg')])->assertForbidden();
        $this->get('/operator/keuangan/tagihan')->assertForbidden();
    });

    it('guru tidak punya akses; kepala sekolah hanya rekap', function () {
        $s = financeScenario();

        $this->actingAsRole(Role::Teacher);
        $this->get('/operator/keuangan/tagihan')->assertForbidden();
        $this->get('/kepala-sekolah/keuangan')->assertForbidden();

        $this->actingAsRole(Role::Principal, $s['principal']);
        $this->get('/kepala-sekolah/keuangan')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Finance::Shared/Report'));
        $this->get('/operator/keuangan/tagihan')->assertForbidden();
        $this->post('/operator/keuangan/buat', generatePayload($s['spp']))->assertForbidden();
    });
});

describe('master & rekap', function () {
    it('jenis biaya yang sudah dipakai tidak bisa dihapus; potongan khusus jenis diutamakan', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/potongan', ['students' => [$s['budi']->public_id], 'fee_type' => $s['spp']->public_id, 'kind' => 'NOMINAL', 'value' => 20000, 'reason' => 'Keringanan'])->assertSessionHasNoErrors();
        $this->post('/operator/keuangan/potongan', ['students' => [$s['ani']->public_id], 'kind' => 'PERSEN', 'value' => 150, 'reason' => 'x'])->assertSessionHasErrors('value');

        $this->post('/operator/keuangan/buat', generatePayload($s['spp']));
        expect(invoiceFor($s['budi'])->total)->toBe(130000);

        $this->delete("/operator/keuangan/jenis-biaya/{$s['spp']->public_id}")->assertSessionHasErrors('fee_type');
        $this->post('/operator/keuangan/jenis-biaya', ['name' => 'Uang kegiatan', 'default_amount' => 50000, 'frequency' => 'SEKALI', 'is_active' => true])->assertSessionHasNoErrors();
        $unused = FeeType::query()->where('name', 'Uang kegiatan')->sole();
        $this->delete("/operator/keuangan/jenis-biaya/{$unused->public_id}")->assertSessionHasNoErrors();
        expect(FeeType::query()->count())->toBe(1);
    });

    it('rekap tunggakan per rombel & penerimaan, serta export Excel', function () {
        $s = financeScenario();
        Excel::fake();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/buat', generatePayload($s['spp']));
        $this->post('/operator/keuangan/tagihan/'.invoiceFor($s['ani'])->public_id.'/pembayaran', ['amount' => 150000, 'paid_on' => '2026-09-28']);

        $this->get('/operator/keuangan/rekap')->assertInertia(fn (Assert $page) => $page
            ->component('Finance::Shared/Report')
            ->where('summary.outstanding', 225000)
            ->where('byClassroom.0.classroom', 'VII-A')
            ->where('byClassroom.0.amount', 75000)
            ->where('byClassroom.1.amount', 150000)
            ->where('students.data.0.name', 'Citra')
            ->where('receipts.total', 150000));

        $this->get('/operator/keuangan/rekap/tunggakan.xlsx')->assertOk();
        Excel::assertDownloaded('tunggakan-20260928.xlsx');
        $this->get('/operator/keuangan/rekap/penerimaan.xlsx?dari=2026-09-01&sampai=2026-09-30')->assertOk();
        Excel::assertDownloaded('penerimaan-20260901-20260930.xlsx');
    });

    it('pengingat H-3 dan H+1 hanya untuk tagihan belum lunas', function () {
        $s = financeScenario();
        $this->actingAsRole(Role::Operator, $s['operator']);
        $this->post('/operator/keuangan/buat', generatePayload($s['spp'], ['due_date' => '2026-10-01']));
        Notification::fake();

        $this->artisan('finance:remind')->assertSuccessful();
        Notification::assertSentTo($s['parent'], InvoiceReminderNotification::class, fn ($n) => ! $n->overdue);
        Notification::assertSentTo($s['otherParent'], InvoiceReminderNotification::class);

        $this->post('/operator/keuangan/tagihan/'.invoiceFor($s['citra'])->public_id.'/pembayaran', ['amount' => 150000, 'paid_on' => '2026-09-28']);
        Notification::fake();
        $this->travelTo('2026-10-02 07:00:00');
        $this->artisan('finance:remind')->assertSuccessful();
        Notification::assertSentTo($s['parent'], InvoiceReminderNotification::class, fn ($n) => $n->overdue);
        Notification::assertNotSentTo($s['otherParent'], InvoiceReminderNotification::class);
    });
});
