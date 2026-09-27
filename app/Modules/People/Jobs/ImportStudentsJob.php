<?php

namespace App\Modules\People\Jobs;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Enums\ImportStatus;
use App\Modules\People\Imports\StudentsImport;
use App\Modules\People\Models\StudentImport;
use App\Modules\People\Services\StudentImportRowProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Proses import siswa di queue (driver database, dijalankan scheduler tiap menit).
 */
class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tidak diulang otomatis: baris yang sudah masuk tidak boleh diimpor dua kali. */
    public int $tries = 1;

    /** Batas waktu job (worker scheduler berhenti setelah job ini selesai). */
    public int $timeout = 300;

    public function __construct(public StudentImport $import) {}

    public function handle(StudentImportRowProcessor $processor, AuditLogger $audit): void
    {
        $import = $this->import->fresh();

        if (! $import || ! $import->status->canTransitionTo(ImportStatus::Processing)) {
            return;
        }

        $import->forceFill(['status' => ImportStatus::Processing])->save();

        $file = $import->file;

        if (! $file) {
            $this->fail($import, 'File import tidak ditemukan.');

            return;
        }

        try {
            Excel::import(new StudentsImport($import, $processor), $file->path, $file->disk);
        } catch (Throwable $e) {
            report($e);
            $this->fail($import, 'File tidak dapat dibaca. Pastikan memakai template .xlsx atau .csv yang benar.');

            return;
        }

        $import->forceFill(['status' => ImportStatus::Completed, 'finished_at' => now()])->save();

        $audit->record('people', 'imported', "Import siswa {$import->original_name}: {$import->success_rows} berhasil, {$import->failed_rows} gagal", $import, [
            'total' => $import->total_rows,
            'success' => $import->success_rows,
            'failed' => $import->failed_rows,
        ], $import->creator);
    }

    private function fail(StudentImport $import, string $message): void
    {
        $import->forceFill([
            'status' => ImportStatus::Failed,
            'failure_message' => $message,
            'finished_at' => now(),
        ])->save();
    }
}
