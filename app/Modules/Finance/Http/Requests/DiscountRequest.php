<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah: `students` (satu atau beberapa siswa sekaligus). Ubah: siswa tidak berubah.
 */
class DiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Invoice::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = $this->route('discount') === null;

        return [
            'students' => [$creating ? 'required' : 'nullable', 'array', 'max:100'],
            'students.*' => ['string', 'distinct'],
            'fee_type' => ['nullable', 'string', Rule::exists(FeeType::class, 'public_id')],
            'kind' => ['required', Rule::enum(DiscountKind::class)],
            'value' => ['required', 'integer', 'min:1', $this->input('kind') === DiscountKind::Percent->value ? 'max:100' : 'max:100000000'],
            'reason' => ['required', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['students' => 'siswa', 'fee_type' => 'jenis biaya', 'kind' => 'jenis potongan', 'value' => 'nilai potongan', 'reason' => 'alasan'];
    }

    /**
     * @return array{fee_type_id: int|null, kind: DiscountKind, value: int, reason: string, is_active: bool}
     */
    public function payload(): array
    {
        return [
            'fee_type_id' => $this->filled('fee_type') ? (int) FeeType::query()->where('public_id', $this->string('fee_type')->value())->value('id') : null,
            'kind' => DiscountKind::from($this->string('kind')->value()),
            'value' => $this->integer('value'),
            'reason' => trim($this->string('reason')->value()),
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
