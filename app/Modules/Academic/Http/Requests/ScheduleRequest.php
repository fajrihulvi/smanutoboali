<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\TeachingAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Schedule::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teaching_assignment' => ['required', 'string', Rule::exists(TeachingAssignment::class, 'public_id')],
            'day_of_week' => ['required', Rule::enum(Weekday::class)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'teaching_assignment' => 'mata pelajaran',
            'day_of_week' => 'hari',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'room' => 'ruang',
        ];
    }

    /**
     * @return array{teaching_assignment: TeachingAssignment, day_of_week: Weekday, start_time: string, end_time: string, room: string|null}
     */
    public function payload(): array
    {
        $room = $this->string('room')->trim()->value();

        return [
            'teaching_assignment' => TeachingAssignment::query()->where('public_id', $this->string('teaching_assignment')->value())->firstOrFail(),
            'day_of_week' => Weekday::from($this->integer('day_of_week')),
            // Disimpan & dibandingkan sebagai HH:MM:SS agar selaras dengan kolom TIME.
            'start_time' => $this->string('start_time')->value().':00',
            'end_time' => $this->string('end_time')->value().':00',
            'room' => $room !== '' ? $room : null,
        ];
    }
}
