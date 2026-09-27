<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Student;

/**
 * Soft delete siswa (salah input). Siswa yang keluar/pindah sebaiknya diubah statusnya,
 * bukan dihapus, agar riwayat tetap ada.
 */
class DeleteStudentAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Student $student): void
    {
        $student->delete();

        $this->audit->record('people', 'deleted', "Menghapus siswa {$student->name} ({$student->nis})", $student);
    }
}
