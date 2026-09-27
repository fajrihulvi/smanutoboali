<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Subject;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class DeleteSubjectAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Subject $subject): void
    {
        if ($subject->teachingAssignments()->exists()) {
            throw ValidationException::withMessages([
                'subject' => 'Mata pelajaran masih dipakai di penugasan mengajar. Nonaktifkan saja bila tidak dipakai lagi.',
            ]);
        }

        $name = $subject->name;
        $subject->delete();

        $this->audit->record('academic', 'deleted', "Menghapus mata pelajaran {$name}", properties: ['name' => $name]);
    }
}
