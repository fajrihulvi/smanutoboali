<?php

namespace App\Modules\Announcement\Actions;

use App\Models\User;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;
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
 * Buat/ubah pengumuman: isi, target, jadwal tayang, pin, lampiran. Yang sudah waktunya
 * terbit dan belum diumumkan langsung diantrekan notifikasinya.
 */
class SaveAnnouncementAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
        private ActivePeriod $period,
        private PublishDueAnnouncementsAction $publishDue,
    ) {}

    /**
     * @param  array{title: string, body: string, audience: AnnouncementAudience, roles: list<string>, classroom_ids: list<int>, pinned: bool, publish_at: CarbonImmutable|null}  $data
     * @param  list<UploadedFile>  $uploads
     * @param  list<string>  $removeFiles
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, array $uploads = [], array $removeFiles = [], ?Announcement $announcement = null): Announcement
    {
        $isNew = $announcement === null;

        $announcement = DB::transaction(function () use ($data, $actor, $uploads, $removeFiles, $announcement) {
            $announcement ??= new Announcement(['created_by' => $actor->id, 'academic_year_id' => $this->period->academicYearId()]);

            $announcement->fill([
                'title' => $data['title'],
                'body' => $data['body'],
                'audience' => $data['audience'],
                'roles' => $data['audience'] === AnnouncementAudience::Roles ? $data['roles'] : null,
                'pinned' => $data['pinned'],
                'publish_at' => $data['publish_at'],
            ])->save();

            $announcement->classrooms()->sync($data['audience'] === AnnouncementAudience::Classrooms ? $data['classroom_ids'] : []);

            if ($removeFiles !== []) {
                $announcement->attachments()->whereIn('public_id', $removeFiles)->get()->each(fn ($file) => $this->files->delete($file));
            }

            if ($announcement->attachments()->count() + count($uploads) > Announcement::MAX_ATTACHMENTS) {
                throw ValidationException::withMessages(['files' => 'Maksimal '.Announcement::MAX_ATTACHMENTS.' lampiran per pengumuman.']);
            }

            foreach ($uploads as $upload) {
                $this->files->store($upload, FileDirectory::Announcements, FileVisibility::Private, $announcement, uploader: $actor, field: 'files');
            }

            return $announcement;
        });

        $this->audit->record('announcement', $isNew ? 'created' : 'updated', ($isNew ? 'Membuat' : 'Mengubah')." pengumuman \"{$announcement->title}\" ({$announcement->status()->label()})", $announcement);

        $this->publishDue->handle($announcement);

        return $announcement;
    }
}
