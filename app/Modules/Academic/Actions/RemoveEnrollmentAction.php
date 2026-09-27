<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Core\Services\AuditLogger;

/**
 * Keluarkan siswa dari rombel (koreksi salah input): enrollment aktif dihapus.
 * Siswa yang pindah rombel memakai "pindahkan" (riwayat tetap), siswa yang
 * keluar sekolah diubah status siswanya.
 */
class RemoveEnrollmentAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(ClassEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['student:id,name,public_id', 'classroom:id,name,public_id']);
        $enrollment->delete();

        $this->audit->record('academic', 'unenrolled', "Mengeluarkan {$enrollment->student->name} dari rombel {$enrollment->classroom->name}", $enrollment->classroom, [
            'student' => $enrollment->student->public_id,
        ]);
    }
}
