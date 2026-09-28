<?php

namespace App\Modules\ReportCard\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Events\ReportCardPublished;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardLog;
use App\Modules\ReportCard\Services\ReportCardAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalan mengubah status raport: cek canTransitionTo(), hak pelaku, catatan wajib
 * untuk REVISION; tulis report_card_logs + audit log; picu ReportCardPublished.
 * $actor null = transisi sistem (DRAFT → SUBMITTED setelah semua mapel dikonfirmasi).
 */
class TransitionReportCardAction
{
    public function __construct(
        private ReportCardAccess $access,
        private AuditLogger $audit,
    ) {}

    /**
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function handle(ReportCard $card, ReportCardStatus $target, ?User $actor, ?string $note = null): ReportCard
    {
        $card->loadMissing(['student:id,name', 'classroom']);
        $from = $card->status;
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        if (! $from->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "Raport berstatus \"{$from->label()}\" tidak bisa diubah menjadi \"{$target->label()}\".",
            ]);
        }

        if ($actor !== null && ! $this->access->canPerform($actor, $card, $target)) {
            throw new AuthorizationException('Anda tidak berwenang melakukan tahap ini.');
        }

        if ($target === ReportCardStatus::Revision && $note === null) {
            throw ValidationException::withMessages(['note' => 'Tuliskan alasan pengembalian untuk revisi.']);
        }

        DB::transaction(function () use ($card, $from, $target, $actor, $note) {
            $card->status = $target;
            $card->published_at = match (true) {
                $target === ReportCardStatus::Published => now(),
                $from === ReportCardStatus::Published => null,
                default => $card->published_at,
            };
            $card->save();

            ReportCardLog::query()->create([
                'report_card_id' => $card->id,
                'from_status' => $from,
                'to_status' => $target,
                'actor_id' => $actor?->id,
                'note' => $note,
            ]);

            $this->audit->record(
                'report_card',
                'transitioned',
                "Raport {$card->student->name}: {$from->label()} → {$target->label()}",
                $card,
                ['from' => $from->value, 'to' => $target->value, 'note' => $note],
                $actor,
            );
        });

        if ($target === ReportCardStatus::Published) {
            ReportCardPublished::dispatch($card);
        }

        return $card;
    }
}
