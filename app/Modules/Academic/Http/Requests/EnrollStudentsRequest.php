<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Models\Classroom;
use App\Modules\People\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $classroom instanceof Classroom && ($this->user()?->can('update', $classroom) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'students' => ['required', 'array', 'min:1', 'max:100'],
            'students.*' => ['string', 'distinct', Rule::exists(Student::class, 'public_id')->whereNull('deleted_at')],
            'move' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['students.required' => 'Pilih minimal satu siswa.'];
    }

    /**
     * @return Collection<int, Student>
     */
    public function students(): Collection
    {
        return Student::query()->whereIn('public_id', $this->array('students'))->get();
    }
}
