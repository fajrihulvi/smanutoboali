<?php

namespace App\Modules\ChildCondition\Http\Requests;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChildCondition::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student' => ['required', 'string'],
            'type' => ['required', Rule::enum(ConditionType::class)],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
            'valid_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now()->subDays(7)->toDateString(), 'before_or_equal:'.now()->addDays(30)->toDateString()],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'target_teacher' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:'.ChildCondition::MAX_PHOTOS],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('valid_until') && $this->filled('valid_from')
                    && CarbonImmutable::parse($this->string('valid_from')->value())->diffInDays(CarbonImmutable::parse($this->string('valid_until')->value())) > 30) {
                    $validator->errors()->add('valid_until', 'Rentang tanggal maksimal 30 hari.');
                }

                if ($this->filled('student') && $this->child() === null) {
                    $validator->errors()->add('student', 'Pilih anak Anda.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student' => 'anak',
            'type' => 'jenis kondisi',
            'description' => 'deskripsi',
            'valid_from' => 'tanggal mulai',
            'valid_until' => 'tanggal selesai',
            'target_teacher' => 'guru tujuan',
            'photos' => 'foto',
            'photos.*' => 'foto',
        ];
    }

    /**
     * Anak milik orang tua yang login (scope visibleTo), null bila bukan.
     */
    public function child(): ?Student
    {
        /** @var User $user */
        $user = $this->user();

        return Student::query()->visibleTo($user, Role::Guardian)->where('public_id', $this->string('student')->value())->first();
    }

    /**
     * @return array{type: ConditionType, description: string, valid_from: CarbonImmutable, valid_until: CarbonImmutable|null}
     */
    public function payload(): array
    {
        return [
            'type' => ConditionType::from($this->string('type')->value()),
            'description' => trim($this->string('description')->value()),
            'valid_from' => CarbonImmutable::parse($this->string('valid_from')->value()),
            'valid_until' => $this->filled('valid_until') ? CarbonImmutable::parse($this->string('valid_until')->value()) : null,
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function photos(): array
    {
        $files = $this->file('photos');

        return is_array($files) ? array_values($files) : [];
    }
}
