<?php

namespace App\Modules\Academic\Jobs;

use App\Modules\Academic\Imports\SubjectsImport;
use App\Modules\Academic\Models\SubjectImport;
use App\Modules\Academic\Services\SubjectImportRowProcessor;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Enums\ImportStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Proses import mata pelajaran (setelah respons unggah, dengan antrean database sebagai cadangan).
 */
class ImportSubjectsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tidak diulang otomatis: baris yang sudah masuk tidak boleh diimpor dua kali. */
    public int $tries = 1;

    /** Batas waktu job (worker scheduler berhenti setelah job ini selesai). */
    public int $timeout = 300;

    public function __construct(public SubjectImport $import) {}

    public function handle(SubjectImportRowProcessor $processor, AuditLogger $audit): void
    {
        $import = $this->import->fresh();

        // Dijalankan dua jalur (setelah respons unggah & antrean cadangan): hanya satu yang lolos.
        if (! $import || ! $import->claimForProcessing()) {
            return;
        }

        // Jalur setelah-respons berjalan di proses web: jangan terhenti saat browser ditutup.
        ignore_user_abort(true);

        if (function_exists('set_time_limit')) {
            @set_time_limit($this->timeout);
        }

        $file = $import->file;

        if (! $file) {
            $this->fail($import, 'File import tidak ditemukan.');

            return;
        }

        try {
            Excel::import(new SubjectsImport($import, $processor), $file->path, $file->disk);
        } catch (Throwable $e) {
            report($e);
            $this->fail($import, 'File tidak dapat dibaca. Pastikan memakai template .xlsx atau .csv yang benar.');

            return;
        }

        $import->forceFill(['status' => ImportStatus::Completed, 'finished_at' => now()])->save();

        $audit->record('academic', 'imported', "Import mata pelajaran {$import->original_name}: {$import->success_rows} berhasil, {$import->failed_rows} gagal", $import, [
            'total' => $import->total_rows,
            'success' => $import->success_rows,
            'failed' => $import->failed_rows,
        ], $import->creator);
    }

    private function fail(SubjectImport $import, string $message): void
    {
        $import->forceFill([
            'status' => ImportStatus::Failed,
            'failure_message' => $message,
            'finished_at' => now(),
        ])->save();
    }
}
