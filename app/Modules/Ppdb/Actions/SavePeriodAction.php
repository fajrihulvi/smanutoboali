<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\School\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Simpan periode beserta jalur & persyaratannya (sinkron berdasarkan public_id).
 * Jalur/persyaratan yang sudah dipakai pendaftar tidak boleh dihapus.
 */
class SavePeriodAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data  tervalidasi PeriodRequest
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, ?PpdbPeriod $period = null): PpdbPeriod
    {
        $isNew = $period === null;

        $period = DB::transaction(function () use ($data, $actor, $period) {
            $period ??= new PpdbPeriod(['created_by' => $actor->id]);
            $period->fill([
                'name' => $data['name'],
                'academic_year_id' => AcademicYear::query()->where('public_id', $data['academic_year'])->value('id'),
                'grade' => (int) $data['grade'],
                'opens_at' => CarbonImmutable::parse($data['opens_at']),
                'closes_at' => CarbonImmutable::parse($data['closes_at']),
                'announces_at' => filled($data['announces_at'] ?? null) ? CarbonImmutable::parse($data['announces_at']) : null,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            $this->syncTracks($period, $data['tracks']);

            return $period;
        });

        $this->audit->record('ppdb', $isNew ? 'period_created' : 'period_updated', ($isNew ? 'Membuat' : 'Mengubah')." periode PPDB {$period->name}", $period);

        return $period;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     *
     * @throws ValidationException
     */
    private function syncTracks(PpdbPeriod $period, array $tracks): void
    {
        $keep = [];

        foreach ($tracks as $i => $row) {
            $track = filled($row['public_id'] ?? null)
                ? $period->tracks()->where('public_id', $row['public_id'])->first()
                : null;
            $track ??= new PpdbTrack(['ppdb_period_id' => $period->id]);
            $track->fill(['name' => $row['name'], 'quota' => (int) $row['quota'], 'description' => $row['description'] ?? null, 'position' => $i])->save();
            $keep[] = $track->id;

            $this->syncRequirements($track, $row['requirements'] ?? []);
        }

        $removed = $period->tracks()->whereNotIn('id', $keep)->get();

        foreach ($removed as $track) {
            if (PpdbApplicant::withTrashed()->where('ppdb_track_id', $track->id)->exists()) {
                throw ValidationException::withMessages(['tracks' => "Jalur {$track->name} sudah dipakai pendaftar dan tidak bisa dihapus."]);
            }

            $track->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $requirements
     *
     * @throws ValidationException
     */
    private function syncRequirements(PpdbTrack $track, array $requirements): void
    {
        $keep = [];

        foreach ($requirements as $i => $row) {
            $requirement = filled($row['public_id'] ?? null)
                ? $track->requirements()->where('public_id', $row['public_id'])->first()
                : null;
            $requirement ??= $track->requirements()->make();
            $requirement->fill([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'is_required' => (bool) ($row['is_required'] ?? true),
                'position' => $i,
            ])->save();
            $keep[] = $requirement->id;
        }

        foreach ($track->requirements()->whereNotIn('id', $keep)->get() as $requirement) {
            if (PpdbDocument::query()->where('ppdb_document_requirement_id', $requirement->id)->exists()) {
                throw ValidationException::withMessages(['tracks' => "Dokumen {$requirement->name} sudah diunggah pendaftar dan tidak bisa dihapus."]);
            }

            $requirement->delete();
        }
    }
}
