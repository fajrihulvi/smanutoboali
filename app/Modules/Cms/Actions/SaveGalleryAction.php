<?php

namespace App\Modules\Cms\Actions;

use App\Models\User;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsiteGalleryPhoto;
use App\Modules\Cms\Services\ContentPublisher;
use App\Modules\Cms\Services\SlugGenerator;
use App\Modules\Cms\Services\WebsiteMedia;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buat/ubah album galeri: data album, foto baru, keterangan & urutan foto, hapus foto.
 */
class SaveGalleryAction
{
    public function __construct(
        private SlugGenerator $slugs,
        private ContentPublisher $publisher,
        private WebsiteMedia $media,
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    /**
     * `photos` = foto lama sesuai urutan baru beserta keterangannya.
     *
     * @param  array{title: string, slug: string|null, description: string|null, date: CarbonImmutable|null, status: ContentStatus, publish_at: CarbonImmutable|null, photos: list<array{id: string, caption: string|null}>, remove_photos: list<string>, uploads: list<UploadedFile>}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, ?WebsiteGallery $gallery = null): WebsiteGallery
    {
        $isNew = $gallery === null;

        $gallery = DB::transaction(function () use ($data, $actor, $gallery) {
            $gallery ??= new WebsiteGallery(['created_by' => $actor->id]);

            $gallery->slug = $this->slugs->unique(WebsiteGallery::class, $data['slug'] ?: $data['title'], $gallery->exists ? $gallery->id : null, 160);
            $gallery->fill([
                'title' => $data['title'],
                'description' => $data['description'],
                'date' => $data['date'],
            ]);
            $this->publisher->apply($gallery, $data['status'], $data['publish_at']);
            $gallery->save();

            /** @var Collection<string, WebsiteGalleryPhoto> $existing */
            $existing = $gallery->photos()->with('file')->get()->keyBy('public_id');

            foreach ($data['remove_photos'] as $publicId) {
                $photo = $existing->pull($publicId);

                if ($photo) {
                    $photo->delete();
                    $this->files->delete($photo->file);
                }
            }

            if ($existing->count() + count($data['uploads']) > WebsiteGallery::MAX_PHOTOS) {
                throw ValidationException::withMessages(['uploads' => 'Maksimal '.WebsiteGallery::MAX_PHOTOS.' foto per album.']);
            }

            $position = 0;

            foreach ($data['photos'] as $item) {
                $photo = $existing->pull($item['id']);
                $photo?->update(['caption' => $item['caption'], 'position' => ++$position]);
            }

            // Foto lama yang tidak dikirim tetap di belakang sesuai urutan semula.
            foreach ($existing as $photo) {
                $photo->update(['position' => ++$position]);
            }

            foreach ($data['uploads'] as $upload) {
                $file = $this->media->store($upload, $actor, $gallery, 'uploads');
                $gallery->photos()->create(['file_id' => $file->id, 'position' => ++$position]);
            }

            return $gallery;
        });

        $this->audit->record('cms', $isNew ? 'created' : 'updated', ($isNew ? 'Membuat' : 'Mengubah')." album \"{$gallery->title}\" ({$gallery->status->label()})", $gallery);

        return $gallery;
    }
}
