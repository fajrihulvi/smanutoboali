<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Validation\ValidationException;

/**
 * Hapus (soft delete) data guru/orang tua. Akun login TIDAK ikut dihapus;
 * nonaktifkan lewat User Management bila perlu.
 *
 * Orang tua yang masih tertaut ke siswa ditolak.
 * TODO(S11): tolak juga guru yang masih punya penugasan/wali kelas aktif.
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

        $person->delete();

        $label = $person instanceof Teacher ? 'guru' : 'orang tua';
        $this->audit->record('people', 'deleted', "Menghapus data {$label} {$person->name}", $person);
    }
}
