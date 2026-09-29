<?php

namespace App\Modules\Ppdb\Http\Resources;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbApplicantGuardian;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: period.academicYear, track.requirements; detail juga guardians, documents.file.
 *
 * @mixin PpdbApplicant
 */
class ApplicantResource extends JsonResource
{
    private bool $detail = false;

    public function detail(): static
    {
        $this->detail = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Pemilik hanya melihat keputusan setelah hasil diumumkan.
        $owner = $request->user()?->id === $this->user_id;
        $status = $owner ? $this->visibleStatus() : $this->status;

        $data = [
            'public_id' => $this->public_id,
            'registration_number' => $this->registration_number,
            'status' => $status->toOption(),
            'step' => $status->step(),
            'name' => $this->name,
            'period' => ['public_id' => $this->period->public_id, 'name' => $this->period->name, 'grade' => $this->period->grade, 'announces_at' => $this->period->announces_at?->toIso8601String()],
            'track' => ['public_id' => $this->track->public_id, 'name' => $this->track->name],
            'revision_note' => $this->revision_note,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'reregistration_submitted_at' => $this->reregistration_submitted_at?->toIso8601String(),
            'reregistered_at' => $this->reregistered_at?->toIso8601String(),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'reregister' => $request->user()?->can('reregister', $this->resource) ?? false,
            ],
        ];

        if (! $owner) {
            $data['selection'] = [
                'score' => $this->score !== null ? (float) $this->score : null,
                'note' => $this->selection_note,
                'proposal' => $this->proposal ? ['value' => $this->proposal->value, 'label' => $this->proposal->label()] : null,
                'decided_at' => $this->decided_at?->toIso8601String(),
                'result_notified_at' => $this->result_notified_at?->toIso8601String(),
            ];
            $data['verified_at'] = $this->verified_at?->toIso8601String();
        }

        if (! $this->detail) {
            return $data;
        }

        $urls = app(FileUrlGenerator::class);
        $documents = $this->documents->keyBy('ppdb_document_requirement_id');

        return [
            ...$data,
            'student' => [
                'name' => $this->name,
                'nik' => $this->nik,
                'nisn' => $this->nisn,
                'gender' => $this->gender?->value,
                'birth_place' => $this->birth_place,
                'birth_date' => $this->birth_date?->toDateString(),
                'religion' => $this->religion?->value,
                'address' => $this->address,
                'previous_school' => $this->previous_school,
            ],
            'guardians' => $this->guardians->map(fn (PpdbApplicantGuardian $g) => [
                'relationship' => $g->relationship->value,
                'name' => $g->name,
                'nik' => $g->nik,
                'occupation' => $g->occupation,
                'phone' => $g->phone,
                'email' => $g->email,
                'is_primary' => $g->is_primary,
            ])->values(),
            'requirements' => $this->track->requirements->map(function (PpdbDocumentRequirement $r) use ($documents, $urls) {
                /** @var PpdbDocument|null $doc */
                $doc = $documents->get($r->id);

                return [
                    'public_id' => $r->public_id,
                    'name' => $r->name,
                    'description' => $r->description,
                    'is_required' => $r->is_required,
                    'document' => $doc && $doc->file ? [
                        'public_id' => $doc->public_id,
                        'status' => ['value' => $doc->status->value, 'label' => $doc->status->label()],
                        'note' => $doc->note,
                        'file' => ['name' => $doc->file->original_name, 'url' => $urls->url($doc->file), 'is_image' => $doc->file->isImage(), 'size' => $doc->file->size],
                    ] : null,
                ];
            })->values(),
        ];
    }
}
