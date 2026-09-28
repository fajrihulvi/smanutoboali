<?php

namespace App\Modules\Assessment\Listeners;

use App\Models\User;
use App\Modules\Assessment\Events\GradePublished;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Notifications\GradePublishedNotification;
use App\Modules\People\Models\Guardian;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Satu notifikasi per orang tua (akun aktif) per anak yang punya nilai di komponen ini.
 */
class NotifyGuardiansOfGrades implements ShouldQueue
{
    public function handle(GradePublished $event): void
    {
        $component = $event->component->loadMissing('teachingAssignment.subject:id,name');

        if (! $component->isPublished()) {
            return;
        }

        $grades = Grade::query()
            ->where('grade_component_id', $component->id)
            ->with(['student:id,public_id,name', 'student.guardians.user'])
            ->get();

        foreach ($grades as $grade) {
            $users = $grade->student->guardians
                ->map(fn (Guardian $guardian) => $guardian->user)
                ->filter(fn (?User $user) => $user !== null && $user->is_active);

            foreach ($users as $user) {
                $user->notify(new GradePublishedNotification(
                    $grade->student->name,
                    $grade->student->public_id,
                    $component->teachingAssignment->subject->name,
                    $component->name,
                    $grade->value(),
                ));
            }
        }
    }
}
