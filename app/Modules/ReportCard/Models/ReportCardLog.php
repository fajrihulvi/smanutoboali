<?php

namespace App\Modules\ReportCard\Models;

use App\Models\User;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $report_card_id
 * @property ReportCardStatus|null $from_status
 * @property ReportCardStatus $to_status
 * @property int|null $actor_id
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property-read User|null $actor
 */
class ReportCardLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['report_card_id', 'from_status', 'to_status', 'actor_id', 'note'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['from_status' => ReportCardStatus::class, 'to_status' => ReportCardStatus::class];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
