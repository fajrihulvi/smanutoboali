<?php

namespace App\Modules\School\Http\Requests;

use App\Modules\School\Models\AcademicYear;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah & ubah tahun ajaran.
 */
class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $year = $this->route('academicYear');

        return $year instanceof AcademicYear
            ? ($this->user()?->can('update', $year) ?? false)
            : ($this->user()?->can('create', AcademicYear::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $year = $this->route('academicYear');

        return [
            'name' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique(AcademicYear::class, 'name')->ignore($year instanceof AcademicYear ? $year->getKey() : null),
                function (string $attribute, mixed $value, Closure $fail) {
                    [$first, $second] = array_map('intval', explode('/', (string) $value) + [1 => 0]);

                    if ($second !== $first + 1) {
                        $fail('Tahun kedua harus satu tahun setelah tahun pertama, contoh 2026/2027.');
                    }
                },
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama tahun ajaran',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.regex' => 'Format tahun ajaran harus YYYY/YYYY, contoh 2026/2027.'];
    }

    /**
     * @return array{name: string, start_date: string, end_date: string}
     */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'start_date' => $this->string('start_date')->value(),
            'end_date' => $this->string('end_date')->value(),
        ];
    }
}
