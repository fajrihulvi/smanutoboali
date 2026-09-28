<?php

namespace App\Modules\Assessment\Actions;

use App\Models\User;
use App\Modules\Assessment\Events\GradePublished;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Publikasi komponen ke orang tua (mengunci nilai) atau membatalkannya.
 */
class PublishGradeComponentAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function publish(GradeComponent $component, User $actor): void
    {
        if ($component->isPublished()) {
            return;
        }

        if (! $component->grades()->exists()) {
            throw ValidationException::withMessages(['component' => 'Isi nilai terlebih dahulu sebelum dipublikasikan.']);
        }

        $component->update(['published_at' => now(), 'published_by' => $actor->id]);

        $this->audit->record('assessment', 'component_published', "Mempublikasikan nilai {$component->name}", $component);

        GradePublished::dispatch($component);
    }

    public function unpublish(GradeComponent $component): void
    {
        if (! $component->isPublished()) {
            return;
        }

        $component->update(['published_at' => null, 'published_by' => null]);

        $this->audit->record('assessment', 'component_unpublished', "Membatalkan publikasi nilai {$component->name}", $component);
    }
}
