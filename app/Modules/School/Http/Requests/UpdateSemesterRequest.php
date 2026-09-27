<?php

namespace App\Modules\School\Http\Requests;

use App\Modules\School\Models\Semester;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $semester = $this->route('semester');

        return $semester instanceof Semester
            && ($this->user()?->can('update', $semester->academicYear) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['start_date' => 'tanggal mulai', 'end_date' => 'tanggal selesai'];
    }

    /**
     * @return array{start_date: string, end_date: string}
     */
    public function payload(): array
    {
        return [
            'start_date' => $this->string('start_date')->value(),
            'end_date' => $this->string('end_date')->value(),
        ];
    }
}
