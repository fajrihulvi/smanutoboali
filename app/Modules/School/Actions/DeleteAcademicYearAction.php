<?php

namespace App\Modules\School\Actions;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Validation\ValidationException;

class DeleteAcademicYearAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(AcademicYear $year): void
    {
        if ($year->is_active) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun ajaran aktif tidak dapat dihapus. Aktifkan tahun ajaran lain terlebih dahulu.',
            ]);
        }

        if (Classroom::query()->where('academic_year_id', $year->getKey())->exists()) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun ajaran yang sudah memiliki rombel tidak dapat dihapus.',
            ]);
        }

        $name = $year->name;
        $year->delete();

        $this->audit->record('school', 'deleted', "Menghapus tahun ajaran {$name}", properties: ['name' => $name]);
    }
}
