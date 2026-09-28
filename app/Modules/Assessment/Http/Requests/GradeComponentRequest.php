<?php

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Enums\ComponentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Otorisasi kepemilikan penugasan dicek controller (GradeAccess).
 */
class GradeComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'semester' => ['sometimes', 'string'],
            'type' => ['required', Rule::enum(ComponentType::class)],
            'name' => ['required', 'string', 'max:50'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['type' => 'jenis', 'name' => 'nama komponen', 'weight' => 'bobot'];
    }

    /**
     * @return array{type: ComponentType, name: string, weight: int}
     */
    public function payload(): array
    {
        return [
            'type' => ComponentType::from($this->string('type')->value()),
            'name' => $this->string('name')->trim()->value(),
            'weight' => $this->integer('weight'),
        ];
    }
}
