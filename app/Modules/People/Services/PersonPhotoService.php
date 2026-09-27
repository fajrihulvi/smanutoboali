<?php

namespace App\Modules\People\Services;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Foto profil guru/siswa: privat, gambar saja, maks 2 MB, dikompres FileService.
 */
class PersonPhotoService
{
    public function __construct(private FileService $files) {}

    /**
     * Mengembalikan id file foto baru (atau null bila dihapus / tidak berubah → $currentId).
     */
    public function replace(
        Model $owner,
        FileDirectory $directory,
        ?int $currentId,
        ?UploadedFile $photo,
        bool $remove,
    ): ?int {
        if (! $photo && ! $remove) {
            return $currentId;
        }

        $newId = null;

        if ($photo) {
            $newId = $this->files->store(
                $photo,
                $directory,
                FileVisibility::Private,
                $owner,
                allowedMimes: FileService::IMAGE_MIMES,
                maxKb: 2048,
                field: 'photo',
            )->id;
        }

        if ($currentId && ($old = File::query()->find($currentId))) {
            $this->files->delete($old);
        }

        return $newId;
    }
}
