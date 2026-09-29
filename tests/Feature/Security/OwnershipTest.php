<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileService;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\Report\Enums\ExportFormat;
use App\Modules\Report\Enums\ExportStatus;
use App\Modules\Report\Models\ReportExport;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Ownership (S32) — setiap route yang menerima public_id data milik orang lain
|--------------------------------------------------------------------------
|
| Skenario: keluarga A (orang tua A, anak A di VII-A, wali kelas guru A) dan
| keluarga B (anak B di VIII-B, guru B). Setiap baris dataset:
|   [metode, fn(skenario) => url, aktor sah (atau null), aktor yang wajib ditolak]
| Aktor sah harus lolos (membuktikan URL benar); aktor lain wajib 403/404.
| Tambahkan baris baru di dataset untuk route baru.
|
*/

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * @return array<string, mixed>
 */
function ownershipScenario(): array
{
    $year = activeAcademicYear();
    $semester = Semester::query()->where('academic_year_id', $year->id)->orderBy('start_date')->firstOrFail();
    $semester->update(['is_active' => true]);
    app(ActivePeriod::class)->flush();

    $actors = [];
    foreach (['operator' => Role::Operator, 'operator2' => Role::Operator, 'principal' => Role::Principal, 'teacherA' => Role::Teacher, 'teacherB' => Role::Teacher, 'parentA' => Role::Guardian, 'parentB' => Role::Guardian, 'applicantA' => Role::Applicant, 'applicantB' => Role::Applicant] as $key => $role) {
        $actors[$key] = User::factory()->withRole($role)->create(['email_verified_at' => now()]);
    }

    $teacherA = Teacher::factory()->withAccount($actors['teacherA'])->create();
    $teacherB = Teacher::factory()->withAccount($actors['teacherB'])->create();
    $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'homeroom_teacher_id' => $teacherA->id]);
    $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-B', 'homeroom_teacher_id' => $teacherB->id]);
    $childA = Student::factory()->withGuardian(Guardian::factory()->withAccount($actors['parentA'])->create())->create(['name' => 'Anak A']);
    $childB = Student::factory()->withGuardian(Guardian::factory()->withAccount($actors['parentB'])->create())->create(['name' => 'Anak B']);
    enrollIn($classA, $childA);
    enrollIn($classB, $childB);
    assignTeaching($classA, $teacherA, Subject::factory()->create());
    assignTeaching($classB, $teacherB, Subject::factory()->create());

    $fee = FeeType::query()->create(['name' => 'SPP', 'default_amount' => 150000, 'frequency' => FeeFrequency::Monthly]);
    $invoice = Invoice::query()->create([
        'number' => 'INV-OWN-1', 'academic_year_id' => $year->id, 'student_id' => $childA->id, 'fee_type_id' => $fee->id,
        'period' => '2026-09', 'period_label' => 'September 2026', 'due_date' => '2026-10-10',
        'total' => 150000, 'paid_amount' => 50000, 'status' => InvoiceStatus::Unpaid,
    ]);
    $payment = Payment::query()->create([
        'invoice_id' => $invoice->id, 'method' => PaymentMethod::Manual, 'status' => PaymentStatus::Confirmed,
        'amount' => 50000, 'paid_on' => '2026-09-20', 'receipt_number' => 'KW/2026/09/9999',
    ]);
    $files = app(FileService::class);
    $proof = $files->store(UploadedFile::fake()->image('bukti.jpg'), FileDirectory::Payments, FileVisibility::Private, $payment, uploader: $actors['parentA']);

    $card = ReportCard::query()->create([
        'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $classA->id,
        'student_id' => $childA->id, 'status' => ReportCardStatus::Published, 'published_at' => now(),
    ]);

    $condition = ChildCondition::query()->create([
        'academic_year_id' => $year->id, 'student_id' => $childA->id, 'guardian_user_id' => $actors['parentA']->id,
        'type' => ConditionType::Sick, 'description' => 'Demam', 'valid_from' => '2026-09-28', 'valid_until' => '2026-09-29',
        'status' => ConditionStatus::Submitted,
    ]);
    $conditionFile = $files->store(UploadedFile::fake()->image('surat.jpg'), FileDirectory::ChildConditions, FileVisibility::Private, $condition, uploader: $actors['parentA']);

    test()->actingAsRole(Role::Guardian, $actors['parentA']);
    $conversation = startChat($actors['teacherA'], $childA);

    $target = AcademicYear::factory()->startingIn(2027)->create();
    $period = PpdbPeriod::query()->create(['name' => 'PPDB 2027', 'academic_year_id' => $target->id, 'grade' => 7, 'opens_at' => '2026-09-01', 'closes_at' => '2026-10-31', 'is_active' => true]);
    $track = PpdbTrack::query()->create(['ppdb_period_id' => $period->id, 'name' => 'Reguler', 'quota' => 10, 'position' => 1]);
    $application = PpdbApplicant::query()->create(['ppdb_period_id' => $period->id, 'ppdb_track_id' => $track->id, 'user_id' => $actors['applicantA']->id, 'status' => ApplicantStatus::Draft]);

    $export = ReportExport::query()->create([
        'user_id' => $actors['operator']->id, 'role' => 'operator', 'report' => 'tunggakan', 'format' => ExportFormat::Pdf,
        'filters' => [], 'title' => 'Laporan Tunggakan', 'status' => ExportStatus::Done, 'expires_at' => now()->addDay(),
    ]);
    $exportFile = UploadedFile::fake()->create('lap.pdf', 5, 'application/pdf');
    File::query()->create([
        'disk' => 'private', 'visibility' => FileVisibility::Private, 'directory' => FileDirectory::Reports, 'original_name' => 'lap.pdf',
        'stored_name' => 'lap.pdf', 'path' => 'reports/lap.pdf', 'mime_type' => 'application/pdf', 'size' => 5,
        'fileable_type' => $export->getMorphClass(), 'fileable_id' => $export->id,
    ]);
    Storage::disk('private')->put('reports/lap.pdf', $exportFile->getContent());

    auth()->logout();

    return [
        ...$actors,
        'childA' => $childA, 'childB' => $childB, 'invoice' => $invoice, 'payment' => $payment, 'proof' => $proof,
        'card' => $card, 'condition' => $condition, 'conditionFile' => $conditionFile, 'conversation' => $conversation,
        'application' => $application, 'export' => $export,
    ];
}

function actorRole(string $key): Role
{
    return match (true) {
        str_starts_with($key, 'operator') => Role::Operator,
        $key === 'principal' => Role::Principal,
        str_starts_with($key, 'teacher') => Role::Teacher,
        str_starts_with($key, 'parent') => Role::Guardian,
        str_starts_with($key, 'applicant') => Role::Applicant,
    };
}

it('menolak akses silang ke data milik orang lain', function (string $method, Closure $url, ?string $allowed, array $denied) {
    $s = ownershipScenario();
    $target = $url($s);

    if ($allowed !== null) {
        $this->actingAsRole(actorRole($allowed), $s[$allowed]);
        $status = $this->call($method, $target)->getStatusCode();
        expect($status)->toBeLessThan(400, "aktor sah {$allowed} ditolak ({$status}) di {$method} {$target}");
    }

    foreach ($denied as $actor) {
        $this->actingAsRole(actorRole($actor), $s[$actor]);
        $status = $this->call($method, $target)->getStatusCode();
        expect($status)->toBeIn([403, 404], "{$actor} mendapat {$status} di {$method} {$target}");
    }
})->with([
    // Siswa
    'profil anak (orang tua)' => ['GET', fn ($s) => "/orang-tua/anak/{$s['childA']->public_id}", 'parentA', ['parentB', 'teacherA']],
    'kalender presensi siswa (guru)' => ['GET', fn ($s) => "/guru/presensi/siswa/{$s['childA']->public_id}", 'teacherA', ['teacherB', 'parentA']],
    'ubah data siswa (operator)' => ['GET', fn ($s) => "/operator/master-data/siswa/{$s['childA']->public_id}/ubah", 'operator', ['principal', 'teacherA', 'parentA']],
    // Tagihan & pembayaran
    'detail tagihan' => ['GET', fn ($s) => "/orang-tua/keuangan/{$s['invoice']->public_id}", 'parentA', ['parentB', 'teacherA']],
    'unggah bukti ke tagihan orang lain' => ['POST', fn ($s) => "/orang-tua/keuangan/{$s['invoice']->public_id}/bukti", null, ['parentB']],
    'bayar online tagihan orang lain' => ['POST', fn ($s) => "/orang-tua/keuangan/{$s['invoice']->public_id}/bayar", null, ['parentB']],
    'kwitansi (orang tua)' => ['GET', fn ($s) => "/orang-tua/keuangan/pembayaran/{$s['payment']->public_id}/kwitansi", 'parentA', ['parentB']],
    'tagihan (operator)' => ['GET', fn ($s) => "/operator/keuangan/tagihan/{$s['invoice']->public_id}", 'operator', ['principal', 'teacherA', 'parentA']],
    // Raport
    'raport (orang tua)' => ['GET', fn ($s) => "/orang-tua/raport/{$s['card']->public_id}", 'parentA', ['parentB']],
    'PDF raport (orang tua)' => ['GET', fn ($s) => "/orang-tua/raport/{$s['card']->public_id}/pdf", 'parentA', ['parentB']],
    'raport (guru)' => ['GET', fn ($s) => "/guru/raport/{$s['card']->public_id}", 'teacherA', ['teacherB']],
    'raport (kepala sekolah)' => ['GET', fn ($s) => "/kepala-sekolah/raport/{$s['card']->public_id}", 'principal', ['parentA', 'teacherA']],
    // Percakapan
    'halaman chat (orang tua)' => ['GET', fn ($s) => "/orang-tua/chat/{$s['conversation']}", 'parentA', ['parentB']],
    'halaman chat (guru)' => ['GET', fn ($s) => "/guru/chat/{$s['conversation']}", 'teacherA', ['teacherB']],
    'daftar pesan (API)' => ['GET', fn ($s) => "/chat/percakapan/{$s['conversation']}/pesan", 'parentA', ['parentB', 'teacherB', 'principal']],
    'kirim pesan ke percakapan orang lain' => ['POST', fn ($s) => "/chat/percakapan/{$s['conversation']}/pesan", null, ['parentB', 'teacherB']],
    'arsipkan percakapan orang lain' => ['PATCH', fn ($s) => "/chat/percakapan/{$s['conversation']}", null, ['parentB', 'teacherB']],
    // Kondisi anak
    'kondisi anak (orang tua)' => ['GET', fn ($s) => "/orang-tua/kondisi-anak/{$s['condition']->public_id}", 'parentA', ['parentB']],
    'hapus kondisi anak orang lain' => ['DELETE', fn ($s) => "/orang-tua/kondisi-anak/{$s['condition']->public_id}", null, ['parentB']],
    'kondisi anak (guru)' => ['GET', fn ($s) => "/guru/kondisi-anak/{$s['condition']->public_id}", 'teacherA', ['teacherB']],
    'tanggapi kondisi anak (guru lain)' => ['POST', fn ($s) => "/guru/kondisi-anak/{$s['condition']->public_id}/tanggapan", null, ['teacherB']],
    // File privat
    'bukti transfer' => ['GET', fn ($s) => "/files/{$s['proof']->public_id}", 'parentA', ['parentB', 'teacherB']],
    'lampiran kondisi anak' => ['GET', fn ($s) => "/files/{$s['conditionFile']->public_id}", 'teacherA', ['parentB', 'teacherB']],
    'file privat lewat /media' => ['GET', fn ($s) => "/media/{$s['proof']->public_id}", null, ['parentA', 'parentB']],
    // PPDB & ekspor laporan
    'formulir PPDB' => ['GET', fn ($s) => "/ppdb/akun/pendaftaran/{$s['application']->public_id}", 'applicantA', ['applicantB']],
    'hapus pendaftaran orang lain' => ['DELETE', fn ($s) => "/ppdb/akun/pendaftaran/{$s['application']->public_id}", null, ['applicantB']],
    'unduhan ekspor laporan' => ['GET', fn ($s) => "/laporan/ekspor/{$s['export']->public_id}", 'operator', ['operator2', 'principal']],
]);
