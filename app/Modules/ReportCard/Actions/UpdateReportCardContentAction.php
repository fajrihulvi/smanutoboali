<?php

namespace App\Modules\ReportCard\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\ReportCard\Models\ReportCard;
use Illuminate\Support\Facades\DB;

/**
 * Isi raport dari wali kelas: catatan, ekstrakurikuler, prestasi (ditulis ulang utuh).
 */
class UpdateReportCardContentAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{homeroom_note?: string|null, extracurriculars?: list<array{name: string, predicate?: string|null, note?: string|null}>, achievements?: list<array{name: string, note?: string|null}>}  $data
     */
    public function handle(ReportCard $card, array $data): void
    {
        DB::transaction(function () use ($card, $data) {
            $note = trim((string) ($data['homeroom_note'] ?? ''));
            $card->update(['homeroom_note' => $note !== '' ? $note : null]);

            $card->extracurriculars()->delete();
            foreach ($data['extracurriculars'] ?? [] as $i => $row) {
                $card->extracurriculars()->create([
                    'name' => trim($row['name']),
                    'predicate' => $row['predicate'] ?? null,
                    'note' => $row['note'] ?? null,
                    'position' => $i + 1,
                ]);
            }

            $card->achievements()->delete();
            foreach ($data['achievements'] ?? [] as $i => $row) {
                $card->achievements()->create(['name' => trim($row['name']), 'note' => $row['note'] ?? null, 'position' => $i + 1]);
            }
        });

        $this->audit->record('report_card', 'content_updated', "Mengubah isi raport {$card->student->name}", $card);
    }
}
