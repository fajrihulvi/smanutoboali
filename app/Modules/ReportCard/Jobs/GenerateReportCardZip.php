<?php

namespace App\Modules\ReportCard\Jobs;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\FileService;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardExport;
use App\Modules\ReportCard\Services\ReportCardPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Cetak massal raport satu rombel → satu ZIP berisi PDF per siswa, disimpan sebagai file privat.
 * Diproses antrean database lewat scheduler (tanpa worker permanen).
 */
class GenerateReportCardZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $exportId) {}

    public function handle(ReportCardPdf $pdf, FileService $files): void
    {
        $export = ReportCardExport::query()->with(['classroom:id,name', 'semester'])->find($this->exportId);

        if ($export === null) {
            return;
        }

        $export->update(['status' => ReportCardExport::PROCESSING]);

        $path = tempnam(sys_get_temp_dir(), 'raport');
        $zip = new ZipArchive;

        try {
            if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Tidak bisa membuat file ZIP.');
            }

            $cards = ReportCard::query()
                ->where('classroom_id', $export->classroom_id)
                ->where('semester_id', $export->semester_id)
                ->with('student:id,nis,name')
                ->get()
                ->sortBy(fn (ReportCard $c) => $c->student->name);

            foreach ($cards as $card) {
                $zip->addFromString($pdf->filename($card), $pdf->render($card));
            }

            $zip->close();

            $name = Str::slug("raport {$export->classroom->name} {$export->semester->label()}").'.zip';
            $files->store(
                new UploadedFile($path, $name, 'application/zip', null, true),
                FileDirectory::ReportCards,
                FileVisibility::Private,
                $export,
                ['application/zip'],
                maxKb: 204_800,
                field: 'export',
            );

            $export->update(['status' => ReportCardExport::DONE, 'total' => $cards->count()]);
        } catch (Throwable $e) {
            $export->update(['status' => ReportCardExport::FAILED, 'error' => Str::limit($e->getMessage(), 500)]);
            report($e);
        } finally {
            if ($path !== false && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
