<?php

namespace App\Modules\Academic\Imports;

use App\Modules\Academic\Models\SubjectImport;
use App\Modules\Academic\Services\SubjectImportRowProcessor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Membaca file import per chunk (hemat memori di shared hosting) dan
 * mencatat hasil tiap baris ke SubjectImport.
 */
class SubjectsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public const CHUNK_SIZE = 200;

    /** Baris judul = baris 1, data mulai baris 2. */
    private int $rowNumber = 1;

    public function __construct(
        private SubjectImport $import,
        private SubjectImportRowProcessor $processor,
    ) {}

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        $errors = $this->import->errors ?? [];
        $success = 0;
        $failed = 0;
        $total = 0;

        foreach ($rows as $row) {
            $this->rowNumber++;

            // Lewati baris yang benar-benar kosong.
            if ($row->filter(fn (mixed $value) => $value !== null && trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $total++;
            $messages = $this->processor->process($row->all());

            if ($messages === []) {
                $success++;

                continue;
            }

            $failed++;

            if (count($errors) < SubjectImport::MAX_STORED_ERRORS) {
                $errors[] = ['row' => $this->rowNumber, 'messages' => $messages];
            }
        }

        $this->import->forceFill([
            'total_rows' => $this->import->total_rows + $total,
            'success_rows' => $this->import->success_rows + $success,
            'failed_rows' => $this->import->failed_rows + $failed,
            'errors' => $errors,
        ])->save();
    }

    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }
}
