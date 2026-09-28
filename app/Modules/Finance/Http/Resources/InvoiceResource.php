<?php

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceItem;
use App\Modules\Finance\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: student, feeType; detail juga items & payments.{proof,submitter,verifier}.
 * `classroom` diisi controller lewat withClassroom() (rombel aktif).
 *
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    private bool $detail = false;

    private ?string $classroom = null;

    public function detail(): static
    {
        $this->detail = true;

        return $this;
    }

    public function withClassroom(?string $classroom): static
    {
        $this->classroom = $classroom;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'public_id' => $this->public_id,
            'number' => $this->number,
            'title' => "{$this->feeType->name} {$this->period_label}",
            'fee_type' => $this->feeType->name,
            'period' => $this->period,
            'period_label' => $this->period_label,
            'student' => ['public_id' => $this->student->public_id, 'name' => $this->student->name, 'nis' => $this->student->nis],
            'classroom' => $this->classroom,
            'due_date' => $this->due_date->toDateString(),
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'remaining' => $this->remaining(),
            'status' => $this->status->toOption(),
            'overdue' => $this->isOverdue(CarbonImmutable::today()),
            'created_at' => $this->created_at->toIso8601String(),
        ];

        if (! $this->detail) {
            return $data;
        }

        return [
            ...$data,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'items' => $this->items->map(fn (InvoiceItem $item) => [
                'type' => $item->type->value,
                'description' => $item->description,
                'amount' => $item->amount,
            ])->values(),
            'payments' => $this->payments->map(fn (Payment $p) => (new PaymentResource($p))->resolve($request))->values(),
            'can' => [
                'pay' => $request->user()?->can('pay', $this->resource) ?? false,
                'manage' => $request->user()?->can('manage', Invoice::class) ?? false,
            ],
        ];
    }
}
