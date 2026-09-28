<?php

namespace App\Modules\Activity\Http\Requests;

use App\Modules\Activity\Enums\ActivityStatus;
use App\Modules\Activity\Enums\ActivityType;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $activity instanceof Activity
            ? ($this->user()?->can('update', $activity) ?? false)
            : ($this->user()?->can('manageAny', Activity::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(ActivityType::class)],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::enum(ActivityStatus::class)],
            'classrooms' => ['array', 'max:50'],
            'classrooms.*' => ['string', 'distinct'],
            'students' => ['array', 'max:100'],
            'students.*' => ['string', 'distinct'],
            'photos' => ['array', 'max:'.Activity::MAX_PHOTOS],
            'photos.*' => ['file', 'max:'.app(SettingsService::class)->uploadMaxKb()],
            'remove_photos' => ['array'],
            'remove_photos.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul', 'type' => 'jenis', 'date' => 'tanggal', 'start_time' => 'jam mulai',
            'end_time' => 'jam selesai', 'location' => 'lokasi', 'description' => 'deskripsi', 'status' => 'status',
            'classrooms' => 'rombel', 'students' => 'siswa', 'photos' => 'foto', 'photos.*' => 'foto',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $type = ActivityType::from($this->string('type')->value());

                if ($type === ActivityType::School && $this->role() !== Role::Operator) {
                    $validator->errors()->add('type', 'Kegiatan tingkat sekolah hanya bisa dibuat operator.');
                }

                if (count($this->classroomIds()) !== count($this->array('classrooms'))) {
                    $validator->errors()->add('classrooms', 'Pilih rombel yang Anda ajar atau walikan.');
                }

                if (count($this->studentIds()) !== count($this->array('students'))) {
                    $validator->errors()->add('students', 'Pilih siswa yang Anda ajar atau walikan.');
                }

                if ($type->needsParticipants() && $this->classroomIds() === [] && $this->studentIds() === []) {
                    $validator->errors()->add('classrooms', 'Pilih minimal satu rombel atau siswa peserta.');
                }
            },
        ];
    }

    public function role(): ?Role
    {
        return app(ActiveRoleService::class)->current($this->session());
    }

    /**
     * @return list<int>
     */
    public function classroomIds(): array
    {
        /** @var list<int> */
        return once(fn () => app(ActivityVisibility::class)
            ->classroomsFor($this->user(), $this->role() ?? Role::Teacher, app(ActivePeriod::class)->academicYearId() ?? 0)
            ->whereIn('public_id', $this->array('classrooms'))
            ->pluck('id')->all());
    }

    /**
     * @return list<int>
     */
    public function studentIds(): array
    {
        /** @var list<int> */
        return once(fn () => Student::query()->visibleTo($this->user())
            ->whereIn('public_id', $this->array('students'))
            ->pluck('students.id')->all());
    }

    /**
     * @return array{title: string, type: ActivityType, date: CarbonImmutable, start_time: string|null, end_time: string|null, location: string|null, description: string|null, status: ActivityStatus, classroom_ids: list<int>, student_ids: list<int>}
     */
    public function payload(): array
    {
        $type = ActivityType::from($this->string('type')->value());
        $optional = fn (string $key) => ($v = $this->string($key)->trim()->value()) !== '' ? $v : null;

        return [
            'title' => $this->string('title')->trim()->value(),
            'type' => $type,
            'date' => CarbonImmutable::parse($this->string('date')->value()),
            'start_time' => $optional('start_time'),
            'end_time' => $optional('end_time'),
            'location' => $optional('location'),
            'description' => $optional('description'),
            'status' => ActivityStatus::from($this->string('status')->value()),
            'classroom_ids' => $type->needsParticipants() ? $this->classroomIds() : [],
            'student_ids' => $type->needsParticipants() ? $this->studentIds() : [],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploads(): array
    {
        return array_values(array_filter((array) $this->file('photos', []), fn ($file) => $file instanceof UploadedFile));
    }

    /**
     * @return list<string>
     */
    public function removedPhotos(): array
    {
        return array_values(array_map('strval', $this->array('remove_photos')));
    }
}
