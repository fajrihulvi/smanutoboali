<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class DeleteClassroomAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Classroom $classroom): void
    {
        if ($classroom->enrollments()->exists()) {
            throw ValidationException::withMessages([
                'classroom' => 'Rombel yang sudah memiliki (riwayat) siswa tidak dapat dihapus.',
            ]);
        }

        $name = $classroom->name;
        $classroom->teachingAssignments()->delete();
        $classroom->delete();

        $this->audit->record('academic', 'deleted', "Menghapus rombel {$name}", properties: ['name' => $name]);
    }
}
