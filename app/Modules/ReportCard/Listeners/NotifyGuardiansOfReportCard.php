<?php

namespace App\Modules\ReportCard\Listeners;

use App\Models\User;
use App\Modules\People\Models\Guardian;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Events\ReportCardPublished;
use App\Modules\ReportCard\Notifications\ReportCardPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyGuardiansOfReportCard implements ShouldQueue
{
    public function handle(ReportCardPublished $event): void
    {
        $card = $event->card->fresh(['student.guardians.user', 'semester.academicYear']);

        if ($card === null || $card->status !== ReportCardStatus::Published) {
            return;
        }

        $period = "{$card->semester->label()} TA {$card->semester->academicYear->name}";

        $card->student->guardians
            ->map(fn (Guardian $guardian) => $guardian->user)
            ->filter(fn (?User $user) => $user !== null && $user->is_active)
            ->each(fn (User $user) => $user->notify(new ReportCardPublishedNotification($card->student->name, $card->public_id, $period)));
    }
}
