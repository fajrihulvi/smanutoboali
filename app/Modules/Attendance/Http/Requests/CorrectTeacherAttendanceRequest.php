<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Koreksi presensi guru oleh operator (route sudah dibatasi active.role:operator + attendance.manage).
 */
class CorrectTeacherAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::enum(TeacherAttendanceStatus::class)],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['date' => 'tanggal', 'check_in' => 'jam masuk', 'check_out' => 'jam pulang', 'note' => 'catatan'];
    }

    public function attendanceDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('date')->value())->startOfDay();
    }

    /**
     * @return array{status: TeacherAttendanceStatus, check_in: string|null, check_out: string|null, note: string|null}
     */
    public function payload(): array
    {
        $note = $this->string('note')->trim()->value();

        return [
            'status' => TeacherAttendanceStatus::from($this->string('status')->value()),
            'check_in' => $this->filled('check_in') ? $this->string('check_in')->value() : null,
            'check_out' => $this->filled('check_out') ? $this->string('check_out')->value() : null,
            'note' => $note !== '' ? $note : null,
        ];
    }
}
