<?php

namespace App\Modules\People\Actions;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Validation\ValidationException;

/**
 * Hapus (soft delete) data guru/orang tua. Akun login TIDAK ikut dihapus;
 * nonaktifkan lewat User Management bila perlu.
 *
 * Orang tua yang masih tertaut ke siswa, dan guru yang masih punya penugasan
 * atau menjadi wali kelas, ditolak.
 */
class DeletePersonAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Teacher|Guardian $person): void
    {
        if ($person instanceof Guardian && $person->students()->exists()) {
            throw ValidationException::withMessages([
                'guardian' => 'Orang tua ini masih terhubung dengan siswa. Lepaskan dari data siswa terlebih dahulu.',
            ]);
        }

        if ($person instanceof Teacher && (
            TeachingAssignment::query()->where('teacher_id', $person->getKey())->exists()
            || Classroom::query()->where('homeroom_teacher_id', $person->getKey())->exists()
        )) {
            throw ValidationException::withMessages([
                'teacher' => 'Guru ini masih memiliki penugasan mengajar atau menjadi wali kelas. Lepaskan penugasannya terlebih dahulu.',
            ]);
        }

        $person->delete();

        $label = $person instanceof Teacher ? 'guru' : 'orang tua';
        $this->audit->record('people', 'deleted', "Menghapus data {$label} {$person->name}", $person);
    }
}
