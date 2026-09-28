<?php

namespace App\Modules\Ppdb\Http\Resources;

use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: academicYear, tracks.requirements.
 *
 * @mixin PpdbPeriod
 */
class PeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'academic_year' => ['public_id' => $this->academicYear->public_id, 'name' => $this->academicYear->name],
            'grade' => $this->grade,
            'opens_at' => $this->opens_at->toIso8601String(),
            'closes_at' => $this->closes_at->toIso8601String(),
            'announces_at' => $this->announces_at?->toIso8601String(),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_open' => $this->isOpen(),
            'applicants_count' => (int) ($this->getAttribute('applicants_count') ?? 0),
            'tracks' => $this->tracks->map(fn (PpdbTrack $t) => [
                'public_id' => $t->public_id,
                'name' => $t->name,
                'quota' => $t->quota,
                'description' => $t->description,
                'requirements' => $t->requirements->map(fn (PpdbDocumentRequirement $r) => [
                    'public_id' => $r->public_id,
                    'name' => $r->name,
                    'description' => $r->description,
                    'is_required' => $r->is_required,
                ])->values(),
            ])->values(),
        ];
    }
}
