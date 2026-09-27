<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;

/**
 * Hapus (soft delete) data guru/orang tua. Akun login TIDAK ikut dihapus;
 * nonaktifkan lewat User Management bila perlu.
 *
 * TODO(S10/S11): tolak bila guru masih punya penugasan/wali kelas aktif
 * atau orang tua masih tertaut ke siswa aktif.
 */
class DeletePersonAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Teacher|Guardian $person): void
    {
        $person->delete();

        $label = $person instanceof Teacher ? 'guru' : 'orang tua';
        $this->audit->record('people', 'deleted', "Menghapus data {$label} {$person->name}", $person);
    }
}
