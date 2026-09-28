<?php

namespace App\Modules\Finance\Database\Seeders;

use App\Models\User;
use App\Modules\Finance\Actions\GenerateInvoicesAction;
use App\Modules\Finance\Actions\RecordPaymentAction;
use App\Modules\Finance\Actions\SubmitPaymentProofAction;
use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\StudentDiscount;
use App\Modules\Finance\Services\InvoiceTargets;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Keuangan demo: jenis biaya, satu beasiswa, SPP Agustus & September untuk semua siswa,
 * uang kegiatan untuk tingkat 7, beberapa pembayaran (lunas, cicilan, bukti menunggu verifikasi).
 */
class FinanceDemoSeeder extends Seeder
{
    public function run(GenerateInvoicesAction $generate, RecordPaymentAction $record, SubmitPaymentProofAction $submit): void
    {
        if (app()->isProduction() || FeeType::query()->exists()) {
            return;
        }

        $operator = User::query()->where('email', 'operator@skoolia.test')->first();
        $ortu = User::query()->where('email', 'ortu@skoolia.test')->first();
        $aisyah = Student::query()->where('name', 'Aisyah Putri Hidayat')->first();

        if (! $operator) {
            return;
        }

        $spp = FeeType::query()->create(['name' => 'SPP', 'default_amount' => 150000, 'frequency' => FeeFrequency::Monthly, 'description' => 'Sumbangan pembinaan pendidikan bulanan']);
        $activity = FeeType::query()->create(['name' => 'Uang kegiatan', 'default_amount' => 250000, 'frequency' => FeeFrequency::Once, 'description' => 'Kegiatan & karya wisata satu tahun']);
        FeeType::query()->create(['name' => 'Daftar ulang', 'default_amount' => 500000, 'frequency' => FeeFrequency::Once]);

        $scholar = Student::query()->where('name', '!=', $aisyah?->name)->orderBy('id')->first();
        if ($scholar) {
            StudentDiscount::query()->create(['student_id' => $scholar->id, 'fee_type_id' => $spp->id, 'kind' => DiscountKind::Percent, 'value' => 50, 'reason' => 'Beasiswa prestasi', 'created_by' => $operator->id]);
        }

        $today = CarbonImmutable::today();

        // Seeding tidak menunggu scheduler: Job & email diproses langsung.
        $queue = config('queue.default');
        config(['queue.default' => 'sync']);

        // GenerateInvoicesAction menjalankan Job-nya langsung (queue sync).
        $generate->handle($operator, $spp, $today->subMonth()->format('Y-m'), $today->subMonth()->locale('id')->translatedFormat('F Y'), 150000, $today->subMonth()->day(10), ['type' => InvoiceTargets::ALL]);
        $generate->handle($operator, $spp, $today->format('Y-m'), $today->locale('id')->translatedFormat('F Y'), 150000, $today->day(10)->addMonthNoOverflow(), ['type' => InvoiceTargets::ALL]);
        $generate->handle($operator, $activity, '2026-2027', '2026/2027', 250000, $today->addDays(14), ['type' => InvoiceTargets::GRADE, 'grade' => 7]);

        // Sebagian besar SPP bulan lalu lunas, sisanya menunggak.
        Invoice::query()->where('fee_type_id', $spp->id)->where('period', $today->subMonth()->format('Y-m'))->orderBy('id')->get()
            ->each(function (Invoice $invoice, int $i) use ($record, $operator, $today) {
                if ($i % 4 !== 3) {
                    $record->handle($invoice, $operator, $invoice->total, $today->subMonth()->day(8), 'Tunai di TU');
                }
            });

        if ($aisyah && $ortu) {
            $activityInvoice = Invoice::query()->where('student_id', $aisyah->id)->where('fee_type_id', $activity->id)->first();
            if ($activityInvoice) {
                $record->handle($activityInvoice, $operator, 100000, $today->subDays(3), 'Cicilan pertama');
            }

            $september = Invoice::query()->where('student_id', $aisyah->id)->where('period', $today->format('Y-m'))->first();
            if ($september) {
                $submit->handle($september, $ortu, $september->total, $today->subDay(), UploadedFile::fake()->image('bukti-transfer.jpg', 600, 900), 'Transfer BSI a.n. Ahmad Hidayat');
            }
        }

        config(['queue.default' => $queue]);
    }
}
