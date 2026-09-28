<?php

namespace App\Modules\Core\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ganti/hapus gambar tanda tangan (kepala sekolah di School, wali kelas di Teacher) yang
 * dipakai PDF raport. Disimpan privat; file lama dibuang.
 */
class ReplaceSignatureAction
{
    public const MAX_KB = 1024;

    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  string  $column  kolom foreign key file, mis. 'signature_file_id'
     * @param  UploadedFile|null  $upload  null = hapus tanda tangan
     *
     * @throws ValidationException
     */
    public function handle(Model $owner, string $column, ?UploadedFile $upload, string $module, string $label): void
    {
        DB::transaction(function () use ($owner, $column, $upload, $module, $label) {
            $oldId = $owner->getAttribute($column);

            $file = $upload
                ? $this->files->store($upload, FileDirectory::Settings, FileVisibility::Private, $owner, FileService::IMAGE_MIMES, self::MAX_KB, field: 'signature')
                : null;

            $owner->forceFill([$column => $file?->id])->save();

            if ($oldId !== null && ($old = File::query()->find($oldId)) !== null) {
                $this->files->delete($old);
            }

            $this->audit->record($module, $upload ? 'signature_updated' : 'signature_removed', ($upload ? 'Mengunggah' : 'Menghapus')." tanda tangan {$label}", $owner);
        });
    }
}
