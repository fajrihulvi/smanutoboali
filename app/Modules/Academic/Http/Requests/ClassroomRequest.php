<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Models\Classroom;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Services\SchoolProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classroom = $this->classroom();

        return $classroom
            ? ($this->user()?->can('update', $classroom) ?? false)
            : ($this->user()?->can('create', Classroom::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $classroom = $this->classroom();
        $yearId = $classroom->academic_year_id ?? $this->academicYear()?->getKey();

        return [
            'academic_year' => [$classroom ? 'nullable' : 'required', 'string', Rule::exists(AcademicYear::class, 'public_id')],
            'name' => [
                'required', 'string', 'max:30',
                Rule::unique(Classroom::class)->where('academic_year_id', $yearId)->ignore($classroom?->getKey()),
            ],
            'grade' => ['required', 'integer', Rule::in(app(SchoolProfile::class)->level()->gradeRange())],
            'homeroom_teacher' => ['nullable', 'string', Rule::exists(Teacher::class, 'public_id')->whereNull('deleted_at')->where('is_active', true)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'academic_year' => 'tahun ajaran',
            'name' => 'nama rombel',
            'grade' => 'tingkat',
            'homeroom_teacher' => 'wali kelas',
            'capacity' => 'kapasitas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama rombel sudah dipakai pada tahun ajaran ini.',
            'grade.in' => 'Tingkat tidak sesuai jenjang sekolah.',
        ];
    }

    /**
     * @return array{academic_year_id: int, name: string, grade: int, homeroom_teacher_id: int|null, capacity: int|null}
     */
    public function payload(): array
    {
        $homeroom = $this->filled('homeroom_teacher')
            ? Teacher::query()->where('public_id', $this->string('homeroom_teacher')->value())->value('id')
            : null;

        return [
            'academic_year_id' => (int) ($this->classroom()->academic_year_id ?? $this->academicYear()?->getKey()),
            'name' => $this->string('name')->trim()->value(),
            'grade' => $this->integer('grade'),
            'homeroom_teacher_id' => $homeroom,
            'capacity' => $this->filled('capacity') ? $this->integer('capacity') : null,
        ];
    }

    private function classroom(): ?Classroom
    {
        $classroom = $this->route('classroom');

        return $classroom instanceof Classroom ? $classroom : null;
    }

    private function academicYear(): ?AcademicYear
    {
        return $this->filled('academic_year')
            ? AcademicYear::query()->where('public_id', $this->string('academic_year')->value())->first()
            : null;
    }
}
