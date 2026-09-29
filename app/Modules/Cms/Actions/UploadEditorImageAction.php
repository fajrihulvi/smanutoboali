<?php

namespace App\Modules\Cms\Actions;

use App\Models\User;
use App\Modules\Cms\Services\WebsiteMedia;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Gambar yang disisipkan di editor: disimpan publik, URL-nya masuk ke isi HTML.
 */
class UploadEditorImageAction
{
    public function __construct(private WebsiteMedia $media, private FileUrlGenerator $urls) {}

    /**
     * @return array{url: string, width: int|null, height: int|null}
     *
     * @throws ValidationException
     */
    public function handle(UploadedFile $upload, User $actor): array
    {
        $file = $this->media->store($upload, $actor, null, 'image');

        return ['url' => $this->urls->url($file), 'width' => $file->width, 'height' => $file->height];
    }
}
