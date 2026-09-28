<?php

namespace App\Modules\ChildCondition\Actions;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Notifications\ChildConditionSubmittedNotification;
use App\Modules\ChildCondition\Services\ConditionTeachers;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Orang tua melaporkan kondisi anak ke guru tujuan (default wali kelas).
 * Guru tujuan & wali kelas diberi tahu realtime (tanpa isi deskripsi).
 */
class SubmitConditionAction
{
    public function __construct(
        private ConditionTeachers $teachers,
        private FileService $files,
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    /**
     * @param  array{type: ConditionType, description: string, valid_from: CarbonImmutable, valid_until: CarbonImmutable|null}  $data
     * @param  list<UploadedFile>  $photos
     *
     * @throws ValidationException
     */
    public function handle(User $guardian, Student $student, array $data, ?string $targetTeacherPublicId = null, array $photos = []): ChildCondition
    {
        $options = $this->teachers->forStudent($student);

        if ($options === []) {
            throw ValidationException::withMessages(['target_teacher' => "{$student->name} belum terdaftar di rombel tahun ajaran aktif, jadi belum ada guru yang bisa dituju."]);
        }

        $target = $targetTeacherPublicId === null
            ? $options[0]['teacher']
            : collect($options)->first(fn (array $o) => $o['teacher']->public_id === $targetTeacherPublicId)['teacher'] ?? null;

        if (! $target instanceof Teacher) {
            throw ValidationException::withMessages(['target_teacher' => 'Pilih wali kelas atau guru yang mengajar anak Anda.']);
        }

        if (count($photos) > ChildCondition::MAX_PHOTOS) {
            throw ValidationException::withMessages(['photos' => 'Maksimal '.ChildCondition::MAX_PHOTOS.' foto.']);
        }

        $condition = DB::transaction(function () use ($guardian, $student, $data, $target, $photos) {
            $condition = ChildCondition::query()->create([
                'academic_year_id' => $this->period->academicYearId(),
                'student_id' => $student->id,
                'guardian_user_id' => $guardian->id,
                'type' => $data['type'],
                'description' => $data['description'],
                'valid_from' => $data['valid_from']->toDateString(),
                'valid_until' => $data['valid_until']?->toDateString(),
                'target_teacher_id' => $target->id,
                'status' => ConditionStatus::Submitted,
            ]);

            foreach ($photos as $photo) {
                $this->files->store($photo, FileDirectory::ChildConditions, FileVisibility::Private, $condition,
                    allowedMimes: FileService::IMAGE_MIMES, uploader: $guardian, field: 'photos');
            }

            return $condition;
        });

        $this->audit->record('child_condition', 'created', "Melaporkan kondisi anak ({$data['type']->label()}) untuk {$student->name}", $condition);

        $homeroom = collect($options)->firstWhere('homeroom', true)['teacher'] ?? null;
        $recipients = collect([$target, $homeroom])
            ->filter(fn (?Teacher $t) => $t !== null)
            ->unique('id')
            ->map(fn (Teacher $t) => $t->user()->where('is_active', true)->first())
            ->filter();

        Notification::send($recipients, new ChildConditionSubmittedNotification($condition->public_id, $student->name, $data['type']->label()));

        return $condition;
    }
}
