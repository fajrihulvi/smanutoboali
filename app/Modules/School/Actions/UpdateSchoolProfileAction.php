<?php

namespace App\Modules\School\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\School\Models\School;
use App\Modules\School\Services\SchoolProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateSchoolProfileAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
        private SchoolProfile $profile,
    ) {}

    /**
     * @param  array{name: string, npsn: string|null, level: string, address: string|null, phone: string|null, email: string|null, principal_name: string|null, principal_nip: string|null}  $data
     */
    public function handle(array $data, ?UploadedFile $logo = null, bool $removeLogo = false): School
    {
        return DB::transaction(function () use ($data, $logo, $removeLogo) {
            $school = School::current();
            $oldLogo = $school->logo;

            $school->fill($data);

            if ($logo) {
                $file = $this->files->store(
                    $logo,
                    FileDirectory::Settings,
                    FileVisibility::Public,
                    $school,
                    allowedMimes: FileService::IMAGE_MIMES,
                    maxKb: 2048,
                    field: 'logo',
                );
                $school->logo_file_id = $file->id;
            } elseif ($removeLogo) {
                $school->logo_file_id = null;
            }

            $dirty = $school->getDirty();
            $original = array_intersect_key($school->getOriginal(), $dirty);
            $school->save();

            if ($oldLogo && $school->logo_file_id !== $oldLogo->id) {
                $this->files->delete($oldLogo);
            }

            if ($dirty !== []) {
                $this->audit->record('school', 'updated', 'Mengubah profil sekolah', $school, [
                    'old' => array_map(fn (mixed $value) => $value instanceof \BackedEnum ? $value->value : $value, $original),
                    'attributes' => array_map(fn (mixed $value) => $value instanceof \BackedEnum ? $value->value : $value, $dirty),
                ]);
            }

            $this->profile->flush();

            return $school->refresh();
        });
    }
}
