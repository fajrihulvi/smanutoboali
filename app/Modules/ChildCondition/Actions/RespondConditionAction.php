<?php

namespace App\Modules\ChildCondition\Actions;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Models\ChildConditionResponse;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Guru menanggapi laporan (boleh lebih dari sekali) → RESPONDED; orang tua diberi tahu.
 */
class RespondConditionAction
{
    public function __construct(private TransitionConditionAction $transition, private AuditLogger $audit) {}

    public function handle(ChildCondition $condition, User $actor, string $body): ChildConditionResponse
    {
        $response = DB::transaction(function () use ($condition, $actor, $body) {
            if ($condition->status === ConditionStatus::Submitted) {
                $this->transition->handle($condition, ConditionStatus::Received, $actor);
            }

            $this->transition->handle($condition, ConditionStatus::Responded, $actor);

            return ChildConditionResponse::query()->create([
                'child_condition_id' => $condition->id,
                'user_id' => $actor->id,
                'body' => trim($body),
            ]);
        });

        $condition->loadMissing('student');
        $this->audit->record('child_condition', 'responded', "Menanggapi laporan kondisi {$condition->student->name}", $condition);
        $this->transition->notifyReporter($condition, $actor, closed: false);

        return $response;
    }
}
