<?php

namespace App\Modules\GuestBook\Actions;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\GuestBook\Enums\GuestVisitSource;
use App\Modules\GuestBook\Http\Requests\GuestVisitRequest;
use App\Modules\GuestBook\Models\GuestVisit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Simpan kunjungan tamu (baru atau ubah) beserta foto privatnya.
 */
class SaveGuestVisitAction
{
    public function __construct(private FileService $files, private AuditLogger $audit) {}

    /**
     * @param  array{full_name: string, institution: string, whatsapp: string, category: string, purpose: string, host: string, notes: string|null, visited_at: string}  $data
     */
    public function handle(
        array $data,
        ?UploadedFile $photo = null,
        bool $removePhoto = false,
        ?GuestVisit $visit = null,
        ?User $actor = null,
        GuestVisitSource $source = GuestVisitSource::Operator,
    ): GuestVisit {
        $isNew = $visit === null;

        $visit = DB::transaction(function () use ($data, $photo, $removePhoto, $visit, $actor, $source) {
            $visit ??= new GuestVisit(['source' => $source, 'created_by' => $actor?->getKey()]);
            $visit->fill($data)->save();

            if ($photo || $removePhoto) {
                $old = $visit->photo_file_id ? File::query()->find($visit->photo_file_id) : null;

                $visit->photo_file_id = $photo
                    ? $this->files->store(
                        $photo,
                        FileDirectory::GuestBook,
                        FileVisibility::Private,
                        $visit,
                        allowedMimes: FileService::IMAGE_MIMES,
                        maxKb: GuestVisitRequest::PHOTO_MAX_KB,
                        uploader: $actor,
                        field: 'photo',
                    )->id
                    : null;
                $visit->save();

                if ($old) {
                    $this->files->delete($old);
                }
            }

            return $visit;
        });

        $this->audit->record(
            'guest_book',
            $isNew ? 'created' : 'updated',
            ($isNew ? ($source === GuestVisitSource::Website ? 'Tamu mengisi buku tamu: ' : 'Mencatat tamu ') : 'Mengubah data tamu ').$visit->full_name,
            $visit,
            causer: $actor,
        );

        return $visit;
    }
}
