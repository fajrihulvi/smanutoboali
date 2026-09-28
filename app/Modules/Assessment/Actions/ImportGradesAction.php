<?php

namespace App\Modules\Assessment\Actions;

use App\Models\User;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Impor nilai dari Excel berformat GradesExport. Baris dicocokkan via NIS, kolom via nama komponen.
 * Sel kosong dilewati; komponen terkunci (dipublikasikan) dilewati dengan peringatan.
 * Diproses sinkron: satu rombel hanya puluhan baris.
 */
class ImportGradesAction
{
    public function __construct(
        private SaveGradeAction $save,
        private AuditLogger $audit,
    ) {}

    /**
     * @return array{updated: int, warnings: list<string>}
     *
     * @throws ValidationException
     */
    public function handle(Gradebook $book, UploadedFile $file, User $actor): array
    {
        $sheets = Excel::toArray(new class implements ToArray
        {
            /**
             * @param  array<int, array<int, mixed>>  $array
             */
            public function array(array $array): void {}
        }, $file);

        $rows = $sheets[0] ?? [];
        $header = array_map(fn ($cell) => trim((string) $cell), array_shift($rows) ?? []);

        if (($header[0] ?? '') !== 'NIS') {
            throw ValidationException::withMessages(['file' => 'Format tidak dikenali. Gunakan file hasil "Ekspor Excel".']);
        }

        /** @var array<int, GradeComponent> $columns */
        $columns = [];

        foreach ($header as $index => $title) {
            $component = $book->components->firstWhere('name', $title);

            if ($component !== null) {
                $columns[$index] = $component;
            }
        }

        if ($columns === []) {
            throw ValidationException::withMessages(['file' => 'Tidak ada kolom yang cocok dengan nama komponen nilai.']);
        }

        $students = $book->students->keyBy(fn ($s) => (string) $s->nis);
        $warnings = [];
        $updated = 0;

        foreach ($columns as $component) {
            if ($component->isPublished()) {
                $warnings[] = "Komponen {$component->name} dipublikasikan (terkunci), kolomnya dilewati.";
            }
        }

        DB::transaction(function () use ($rows, $columns, $students, $actor, &$warnings, &$updated) {
            foreach ($rows as $n => $row) {
                $nis = trim((string) ($row[0] ?? ''));

                if ($nis === '') {
                    continue;
                }

                $student = $students->get($nis);

                if ($student === null) {
                    $warnings[] = 'Baris '.($n + 2).": NIS {$nis} bukan siswa aktif rombel ini.";

                    continue;
                }

                foreach ($columns as $index => $component) {
                    $cell = $row[$index] ?? null;

                    if ($component->isPublished() || $cell === null || trim((string) $cell) === '') {
                        continue;
                    }

                    $value = is_numeric($cell) ? (float) $cell : (is_numeric(str_replace(',', '.', (string) $cell)) ? (float) str_replace(',', '.', (string) $cell) : null);

                    if ($value === null || $value < 0 || $value > 100) {
                        $warnings[] = 'Baris '.($n + 2).", {$component->name}: \"{$cell}\" bukan nilai 0–100.";

                        continue;
                    }

                    $this->save->handle($component, $student, $value, $actor);
                    $updated++;
                }
            }
        });

        $this->audit->record('assessment', 'grades_imported', "Mengimpor {$updated} nilai dari Excel", properties: ['warnings' => count($warnings)]);

        return ['updated' => $updated, 'warnings' => $warnings];
    }
}
