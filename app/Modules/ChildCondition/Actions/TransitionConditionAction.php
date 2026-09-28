<?php

namespace App\Modules\ChildCondition\Actions;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Notifications\ChildConditionUpdatedNotification;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Pindah status laporan sesuai ConditionStatus::canTransitionTo() dan catat waktunya.
 * CLOSED memberi tahu orang tua pelapor.
 */
class TransitionConditionAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(ChildCondition $condition, ConditionStatus $next, User $actor): ChildCondition
    {
        if (! $condition->status->canTransitionTo($next)) {
            throw ValidationException::withMessages(['status' => "Laporan berstatus {$condition->status->label()} tidak bisa diubah menjadi {$next->label()}."]);
        }

        $stamp = match ($next) {
            ConditionStatus::Received => 'received_at',
            ConditionStatus::Reviewed => 'reviewed_at',
            ConditionStatus::Responded => 'responded_at',
            ConditionStatus::Closed => 'closed_at',
            ConditionStatus::Submitted => null,
        };

        $condition->status = $next;

        if ($stamp !== null) {
            $condition->setAttribute($stamp, now());
        }

        if ($next === ConditionStatus::Closed) {
            $condition->closed_by = $actor->id;
        }

        $condition->save();

        if (in_array($next, [ConditionStatus::Reviewed, ConditionStatus::Closed], true)) {
            $condition->loadMissing('student');
            $this->audit->record('child_condition', strtolower($next->value), "Laporan kondisi {$condition->student->name}: {$next->label()}", $condition);
        }

        if ($next === ConditionStatus::Closed) {
            $this->notifyReporter($condition, $actor, closed: true);
        }

        return $condition;
    }

    public function notifyReporter(ChildCondition $condition, User $actor, bool $closed): void
    {
        $condition->loadMissing(['reporter', 'student']);

        if ($condition->reporter?->is_active) {
            $condition->reporter->notify(new ChildConditionUpdatedNotification($condition->public_id, $condition->student->name, $actor->name, $closed));
        }
    }
}
