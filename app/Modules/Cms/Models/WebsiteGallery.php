<?php

namespace App\Modules\Cms\Models;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Models\Concerns\Publishable;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Album foto.
 *
 * @property int $id
 * @property string $public_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property CarbonImmutable|null $date
 * @property ContentStatus $status
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, WebsiteGalleryPhoto> $photos
 * @property-read int|null $photos_count
 */
class WebsiteGallery extends Model
{
    use HasPublicId, Publishable, SoftDeletes;

    public const MAX_PHOTOS = 60;

    protected $fillable = ['slug', 'title', 'description', 'date', 'status', 'publish_at', 'published_at', 'created_by'];

    protected $hidden = ['id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'status' => ContentStatus::class,
            'publish_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<WebsiteGalleryPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(WebsiteGalleryPhoto::class)->orderBy('position')->orderBy('id');
    }
}
