<?php

namespace App\Modules\Announcement\Models;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Enums\AnnouncementStatus;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $academic_year_id
 * @property string $title
 * @property string $body
 * @property AnnouncementAudience $audience
 * @property list<string>|null $roles
 * @property bool $pinned
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $notified_at
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 * @property-read Collection<int, Classroom> $classrooms
 * @property-read Collection<int, File> $attachments
 * @property-read User|null $creator
 */
class Announcement extends Model
{
    use HasPublicId, SoftDeletes;

    public const MAX_ATTACHMENTS = 5;

    protected $fillable = ['academic_year_id', 'title', 'body', 'audience', 'roles', 'pinned', 'publish_at', 'notified_at', 'created_by'];

    protected $hidden = ['id', 'academic_year_id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'roles' => 'array',
            'pinned' => 'boolean',
            'publish_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
        ];
    }

    public function status(): AnnouncementStatus
    {
        return match (true) {
            $this->publish_at === null => AnnouncementStatus::Draft,
            $this->publish_at->isFuture() => AnnouncementStatus::Scheduled,
            default => AnnouncementStatus::Published,
        };
    }

    public function isPublished(): bool
    {
        return $this->status() === AnnouncementStatus::Published;
    }

    /**
     * Role target (untuk audiens PERAN).
     *
     * @return list<Role>
     */
    public function targetRoles(): array
    {
        return array_values(array_filter(array_map(fn (string $r) => Role::tryFrom($r), $this->roles ?? [])));
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('publish_at')->where('publish_at', '<=', now());
    }

    /**
     * Disematkan dulu, lalu terbaru.
     *
     * @param  Builder<Announcement>  $query
     */
    public function scopeFeedOrder(Builder $query): void
    {
        $query->orderByDesc('pinned')->orderByDesc('publish_at')->orderByDesc('id');
    }

    /**
     * @return BelongsToMany<Classroom, $this>
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'announcement_classroom');
    }

    /**
     * @return MorphMany<File, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
