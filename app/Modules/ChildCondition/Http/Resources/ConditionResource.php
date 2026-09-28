<?php

namespace App\Modules\ChildCondition\Http\Resources;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Models\ChildConditionResponse;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Laporan kondisi anak — hanya dikirim ke user yang lolos ChildConditionPolicy::view.
 * `summary()` untuk baris daftar (deskripsi dipotong, tanpa foto/tanggapan).
 * Butuh eager load: student, reporter, targetTeacher, (detail) photos, responses.user.
 *
 * @mixin ChildCondition
 */
class ConditionResource extends JsonResource
{
    private bool $summary = false;

    public function summary(): static
    {
        $this->summary = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $viewer */
        $viewer = $request->user();

        $data = [
            'public_id' => $this->public_id,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'status' => $this->status->toOption(),
            'description' => $this->summary ? Str::limit($this->description, 120) : $this->description,
            'valid_from' => $this->valid_from->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'student' => ['public_id' => $this->student->public_id, 'name' => $this->student->name],
            'reporter' => $this->reporter?->name,
            'target_teacher' => $this->targetTeacher?->name,
            'created_at' => $this->created_at->toIso8601String(),
            'is_new' => $this->status === ConditionStatus::Submitted,
        ];

        if ($this->summary) {
            return $data;
        }

        $urls = app(FileUrlGenerator::class);

        return [
            ...$data,
            'photos' => $this->photos->map(fn (File $f) => ['public_id' => $f->public_id, 'name' => $f->original_name, 'url' => $urls->url($f)])->values(),
            'responses' => $this->responses->map(fn (ChildConditionResponse $r) => [
                'author' => $r->user->name ?? 'Guru',
                'body' => $r->body,
                'created_at' => $r->created_at->toIso8601String(),
            ])->values(),
            'timeline' => array_values(array_filter([
                ['label' => 'Dilaporkan', 'at' => $this->created_at->toIso8601String()],
                $this->received_at ? ['label' => 'Diterima guru', 'at' => $this->received_at->toIso8601String()] : null,
                $this->reviewed_at ? ['label' => 'Ditinjau', 'at' => $this->reviewed_at->toIso8601String()] : null,
                $this->responded_at ? ['label' => 'Ditanggapi', 'at' => $this->responded_at->toIso8601String()] : null,
                $this->closed_at ? ['label' => 'Selesai', 'at' => $this->closed_at->toIso8601String()] : null,
            ])),
            'attendance_suggestion' => $this->type->attendanceSuggestion()?->label(),
            'can' => [
                'respond' => $viewer?->can('respond', $this->resource) ?? false,
                'review' => ($viewer?->can('respond', $this->resource) ?? false) && $this->status->canTransitionTo(ConditionStatus::Reviewed),
                'withdraw' => $viewer?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
