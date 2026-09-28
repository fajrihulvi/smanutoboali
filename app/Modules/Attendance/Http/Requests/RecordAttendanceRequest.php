<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Enums\AttendanceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Otorisasi (siapa boleh mengisi rombel/jadwal ini) dicek controller lewat AttendanceAccess.
 */
class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'schedule' => ['nullable', 'string', Rule::exists(Schedule::class, 'public_id')->whereNull('deleted_at')],
            'records' => ['required', 'array', 'min:1', 'max:100'],
            'records.*.student' => ['required', 'string', 'distinct'],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => 'tanggal',
            'schedule' => 'jadwal',
            'records' => 'daftar presensi',
            'records.*.status' => 'status',
            'records.*.note' => 'catatan',
        ];
    }

    public function attendanceDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('date')->value())->startOfDay();
    }

    public function schedule(): ?Schedule
    {
        return $this->filled('schedule')
            ? Schedule::query()->where('public_id', $this->string('schedule')->value())->with('teachingAssignment.subject')->firstOrFail()
            : null;
    }

    /**
     * @return array<string, array{status: AttendanceStatus, note: string|null}>
     */
    public function entries(): array
    {
        /** @var list<array{student: string, status: string, note?: string|null}> $records */
        $records = $this->input('records', []);
        $entries = [];

        foreach ($records as $record) {
            $note = trim((string) ($record['note'] ?? ''));
            $entries[$record['student']] = [
                'status' => AttendanceStatus::from($record['status']),
                'note' => $note !== '' ? $note : null,
            ];
        }

        return $entries;
    }
}
