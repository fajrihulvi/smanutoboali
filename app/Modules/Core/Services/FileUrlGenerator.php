<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use Illuminate\Support\Facades\Storage;

/**
 * URL file: privat selalu lewat /files/{public_id} (dengan otorisasi);
 * publik lewat symlink public/storage bila ada, selain itu route fallback /media.
 */
class FileUrlGenerator
{
    public function url(File $file): string
    {
        if ($file->visibility === FileVisibility::Private) {
            return route('files.show', $file);
        }

        if ($this->storageLinked()) {
            return Storage::disk('public')->url($file->path);
        }

        return route('media.show', $file);
    }

    public function storageLinked(): bool
    {
        return is_link(public_path('storage')) || is_dir(public_path('storage'));
    }
}
