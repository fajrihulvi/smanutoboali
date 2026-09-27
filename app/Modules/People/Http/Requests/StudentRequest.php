<?php

namespace App\Modules\People\Http\Requests;

use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\Religion;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah & ubah siswa oleh operator.
 */
class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->student();

        return $student
            ? ($this->user()?->can('update', $student) ?? false)
            : ($this->user()?->can('create', Student::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ignore = $this->student()?->getKey();

        return [
            'nis' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-\/]+$/', Rule::unique(Student::class)->ignore($ignore)],
            'nisn' => ['nullable', 'digits:10', Rule::unique(Student::class)->ignore($ignore)],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'entry_date' => ['nullable', 'date_format:Y-m-d'],
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'name' => 'nama',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'religion' => 'agama',
            'address' => 'alamat',
            'entry_date' => 'tanggal masuk',
            'photo' => 'foto',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $optional = fn (string $key) => $this->filled($key) ? $this->string($key)->trim()->value() : null;

        return [
            'nis' => $this->string('nis')->trim()->value(),
            'nisn' => $optional('nisn'),
            'name' => $this->string('name')->trim()->value(),
            'gender' => $this->string('gender')->value(),
            'birth_place' => $optional('birth_place'),
            'birth_date' => $optional('birth_date'),
            'religion' => $optional('religion'),
            'address' => $optional('address'),
            'status' => $this->string('status')->value(),
            'entry_date' => $optional('entry_date'),
        ];
    }

    private function student(): ?Student
    {
        $student = $this->route('student');

        return $student instanceof Student ? $student : null;
    }
}
