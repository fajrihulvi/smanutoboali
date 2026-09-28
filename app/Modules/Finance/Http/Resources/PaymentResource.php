<?php

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\Finance\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: proof, submitter, verifier (dan invoice.student/feeType bila withInvoice()).
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    private bool $withInvoice = false;

    public function withInvoice(): static
    {
        $this->withInvoice = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'public_id' => $this->public_id,
            'method' => ['value' => $this->method->value, 'label' => $this->method->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'amount' => $this->amount,
            'paid_on' => $this->paid_on->toDateString(),
            'receipt_number' => $this->receipt_number,
            'note' => $this->note,
            'rejection_reason' => $this->rejection_reason,
            'submitted_by' => $this->submitter?->name,
            'verified_by' => $this->verifier?->name,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'proof' => $this->proof ? [
                'url' => app(FileUrlGenerator::class)->url($this->proof),
                'name' => $this->proof->original_name,
                'is_image' => $this->proof->isImage(),
            ] : null,
        ];

        if ($this->withInvoice) {
            $invoice = $this->invoice;
            $data['invoice'] = [
                'public_id' => $invoice->public_id,
                'number' => $invoice->number,
                'title' => "{$invoice->feeType->name} {$invoice->period_label}",
                'total' => $invoice->total,
                'remaining' => $invoice->remaining(),
                'student' => ['public_id' => $invoice->student->public_id, 'name' => $invoice->student->name, 'nis' => $invoice->student->nis],
            ];
        }

        return $data;
    }
}
