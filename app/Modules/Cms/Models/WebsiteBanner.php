<?php

namespace App\Modules\Cms\Models;

use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property string $title
 * @property string|null $subtitle
 * @property int|null $image_file_id
 * @property string|null $link_url
 * @property string|null $link_label
 * @property int $position
 * @property bool $is_active
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property-read File|null $image
 */
class WebsiteBanner extends Model
{
    use HasPublicId;

    protected $fillable = ['title', 'subtitle', 'image_file_id', 'link_url', 'link_label', 'position', 'is_active', 'starts_at', 'ends_at'];

    protected $hidden = ['id', 'image_file_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    /**
     * Aktif dan dalam periode tayang.
     *
     * @param  Builder<WebsiteBanner>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_file_id');
    }
}
