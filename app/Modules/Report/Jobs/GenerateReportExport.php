<?php

namespace App\Modules\Report\Jobs;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\FileService;
use App\Modules\Report\Enums\ExportStatus;
use App\Modules\Report\Models\ReportExport;
use App\Modules\Report\Notifications\ReportExportNotification;
use App\Modules\Report\Reports\ReportContext;
use App\Modules\Report\Reports\ReportRegistry;
use App\Modules\Report\Services\ReportRenderer;
use App\Modules\Report\Services\ReportRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Membuat file laporan besar (PDF/Excel) sebagai file privat, lalu memberi tahu pemohon.
 * Izin & filter dicek ulang dengan role yang tercatat saat permintaan dibuat.
 */
class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /** MIME hasil deteksi isi file (xlsx kadang terbaca sebagai zip). */
    private const MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
    ];

    public function __construct(public int $exportId) {}

    public function handle(ReportRegistry $registry, ReportRunner $runner, ReportRenderer $renderer, FileService $files): void
    {
        $export = ReportExport::query()->with('user')->find($this->exportId);

        if ($export === null || $export->status !== ExportStatus::Pending) {
            return;
        }

        $export->update(['status' => ExportStatus::Processing]);
        $path = null;

        try {
            $context = new ReportContext($export->user, Role::from($export->role));
            $report = $registry->find($context, $export->report)
                ?? throw new RuntimeException('Anda tidak lagi memiliki akses ke laporan ini.');

            $data = $report->build($context, $runner->validate($report, $context, $export->filters));
            $filename = $renderer->filename($data, $export->format);

            $path = (string) tempnam(sys_get_temp_dir(), 'lap');
            file_put_contents($path, $renderer->render($data, $export->format, $export->user));

            $files->store(
                new UploadedFile($path, $filename, $export->format->mime(), null, true),
                FileDirectory::Reports,
                FileVisibility::Private,
                $export,
                self::MIMES,
                maxKb: 102_400,
                uploader: $export->user,
                field: 'export',
            );

            $export->update(['status' => ExportStatus::Done, 'rows' => $data->count(), 'completed_at' => now()]);
        } catch (Throwable $e) {
            $export->update(['status' => ExportStatus::Failed, 'error' => Str::limit($e->getMessage(), 490)]);
            report($e);
        } finally {
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        $export->user->notify(new ReportExportNotification($export->fresh() ?? $export));
    }
}
