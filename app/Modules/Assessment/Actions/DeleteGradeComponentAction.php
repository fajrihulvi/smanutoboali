<?php

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class DeleteGradeComponentAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(GradeComponent $component): void
    {
        if ($component->isPublished()) {
            throw ValidationException::withMessages(['component' => 'Komponen yang dipublikasikan tidak bisa dihapus. Batalkan publikasi dulu.']);
        }

        $component->delete();

        $this->audit->record('assessment', 'component_deleted', "Menghapus komponen nilai {$component->name}", $component);
    }
}
