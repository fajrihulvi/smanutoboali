<?php

namespace App\Modules\Activity\Actions;

use App\Models\User;
use App\Modules\Activity\Enums\ActivityStatus;
use App\Modules\Activity\Enums\ActivityType;
use App\Modules\Activity\Models\Activity;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buat/ubah kegiatan: data, peserta (rombel/siswa, ditulis ulang), foto baru & dihapus.
 */
class SaveActivityAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    /**
     * @param  array{title: string, type: ActivityType, date: CarbonImmutable, start_time: string|null, end_time: string|null, location: string|null, description: string|null, status: ActivityStatus, classroom_ids: list<int>, student_ids: list<int>}  $data
     * @param  list<UploadedFile>  $uploads
     * @param  list<string>  $removePhotos
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, array $uploads = [], array $removePhotos = [], ?Activity $activity = null): Activity
    {
        $isNew = $activity === null;

        $activity = DB::transaction(function () use ($data, $actor, $uploads, $removePhotos, $activity) {
            $activity ??= new Activity(['created_by' => $actor->id, 'academic_year_id' => $this->period->academicYearId()]);
            $published = $data['status'] === ActivityStatus::Published;

            $activity->fill([
                'title' => $data['title'],
                'type' => $data['type'],
                'date' => $data['date']->toDateString(),
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'location' => $data['location'],
                'description' => $data['description'],
                'status' => $data['status'],
                'published_at' => $published ? ($activity->published_at ?? now()) : null,
            ])->save();

            $activity->participants()->delete();
            $activity->participants()->createMany([
                ...array_map(fn (int $id) => ['classroom_id' => $id], $data['classroom_ids']),
                ...array_map(fn (int $id) => ['student_id' => $id], $data['student_ids']),
            ]);

            if ($removePhotos !== []) {
                $activity->photos()->whereIn('public_id', $removePhotos)->get()->each(fn ($file) => $this->files->delete($file));
            }

            if ($activity->photos()->count() + count($uploads) > Activity::MAX_PHOTOS) {
                throw ValidationException::withMessages(['photos' => 'Maksimal '.Activity::MAX_PHOTOS.' foto per kegiatan.']);
            }

            foreach ($uploads as $upload) {
                $this->files->store($upload, FileDirectory::Activities, FileVisibility::Private, $activity, FileService::IMAGE_MIMES, uploader: $actor, field: 'photos');
            }

            return $activity;
        });

        $this->audit->record('activity', $isNew ? 'created' : 'updated', ($isNew ? 'Menambah' : 'Mengubah')." kegiatan \"{$activity->title}\"", $activity);

        return $activity;
    }
}
