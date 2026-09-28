<?php

namespace App\Modules\ChildCondition\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $child_condition_id
 * @property int|null $user_id
 * @property string $body
 * @property CarbonImmutable $created_at
 * @property-read User|null $user
 */
class ChildConditionResponse extends Model
{
    protected $fillable = ['child_condition_id', 'user_id', 'body'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
