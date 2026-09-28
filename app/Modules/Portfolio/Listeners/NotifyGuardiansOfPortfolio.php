<?php

namespace App\Modules\Portfolio\Listeners;

use App\Models\User;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Events\PortfolioPublished;
use App\Modules\Portfolio\Notifications\PortfolioPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyGuardiansOfPortfolio implements ShouldQueue
{
    public function handle(PortfolioPublished $event): void
    {
        $portfolio = $event->portfolio->fresh(['category', 'students.guardians.user']);

        if ($portfolio === null || $portfolio->visibility !== PortfolioVisibility::Published) {
            return;
        }

        foreach ($portfolio->students as $student) {
            /** @var Student $student */
            $student->guardians
                ->map(fn (Guardian $guardian) => $guardian->user)
                ->filter(fn (?User $user) => $user !== null && $user->is_active)
                ->each(fn (User $user) => $user->notify(new PortfolioPublishedNotification(
                    $student->name,
                    $student->public_id,
                    $portfolio->title,
                    $portfolio->category->name,
                )));
        }
    }
}
