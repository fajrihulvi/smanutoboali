<?php

namespace App\Modules\Cms\Services;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Gambar website disimpan publik (folder `website`) dan dikompres FileService.
 */
class WebsiteMedia
{
    public const MAX_KB = 5120;

    public function __construct(private FileService $files) {}

    /**
     * @throws ValidationException
     */
    public function store(UploadedFile $upload, User $actor, ?Model $owner = null, string $field = 'image'): File
    {
        return $this->files->store(
            $upload,
            FileDirectory::Website,
            FileVisibility::Public,
            $owner,
            FileService::IMAGE_MIMES,
            self::MAX_KB,
            $actor,
            $field,
        );
    }

    /**
     * Ganti/hapus gambar yang disimpan di kolom `$column` (FK ke files).
     *
     * @throws ValidationException
     */
    public function replace(Model $model, string $column, ?UploadedFile $upload, bool $remove, User $actor, string $field): void
    {
        $oldId = $model->getAttribute($column);

        if ($upload === null && ! $remove) {
            return;
        }

        $model->setAttribute($column, $upload ? $this->store($upload, $actor, $model, $field)->id : null);
        $model->save();

        if ($oldId !== null) {
            $old = File::query()->find($oldId);

            if ($old) {
                $this->files->delete($old);
            }
        }
    }
}
