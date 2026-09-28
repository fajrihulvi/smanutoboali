<?php

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Finance\Models\InvoiceBatch;
use App\Modules\Finance\Services\InvoiceTargets;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: feeType, creator.
 *
 * @mixin InvoiceBatch
 */
class BatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'title' => "{$this->feeType->name} {$this->period_label}",
            'scope' => $this->scopeLabel(),
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'created_count' => $this->created_count,
            'skipped_count' => $this->skipped_count,
            'total_amount' => $this->total_amount,
            'due_date' => $this->due_date->toDateString(),
            'error' => $this->error,
            'creator' => $this->creator?->name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    /**
     * Label cakupan tanpa id internal rombel.
     */
    private function scopeLabel(): string
    {
        return match ($this->scope['type'] ?? null) {
            InvoiceTargets::GRADE => 'Tingkat '.($this->scope['grade'] ?? '-'),
            InvoiceTargets::CLASSROOMS => 'Rombel '.Classroom::query()->whereIn('id', $this->scope['classroom_ids'] ?? [])->orderBy('name')->pluck('name')->implode(', '),
            default => 'Semua siswa',
        };
    }
}
