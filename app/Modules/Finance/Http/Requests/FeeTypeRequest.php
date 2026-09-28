<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeTypeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'frequency' => ['required', Rule::enum(FeeFrequency::class)],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama', 'default_amount' => 'nominal default', 'frequency' => 'periode tagihan', 'description' => 'keterangan'];
    }

    /**
     * @return array{name: string, default_amount: int, frequency: FeeFrequency, is_active: bool, description: string|null}
     */
    public function payload(): array
    {
        return [
            'name' => trim($this->string('name')->value()),
            'default_amount' => $this->integer('default_amount'),
            'frequency' => FeeFrequency::from($this->string('frequency')->value()),
            'is_active' => $this->boolean('is_active', true),
            'description' => $this->filled('description') ? trim($this->string('description')->value()) : null,
        ];
    }
}
