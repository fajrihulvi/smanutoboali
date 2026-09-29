<?php

namespace App\Modules\Cms\Models;

use App\Models\User;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\Concerns\Publishable;
use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Berita atau agenda.
 *
 * @property int $id
 * @property string $public_id
 * @property PostType $type
 * @property int|null $category_id
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $content
 * @property int|null $featured_image_id
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property ContentStatus $status
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $published_at
 * @property bool $is_featured
 * @property CarbonImmutable|null $event_starts_at
 * @property CarbonImmutable|null $event_ends_at
 * @property string|null $event_location
 * @property CarbonImmutable $updated_at
 * @property-read WebsiteCategory|null $category
 * @property-read File|null $featuredImage
 * @property-read User|null $author
 */
class WebsitePost extends Model
{
    use HasPublicId, Publishable, SoftDeletes;

    protected $fillable = [
        'type', 'category_id', 'slug', 'title', 'excerpt', 'content', 'featured_image_id', 'seo_title', 'seo_description',
        'status', 'publish_at', 'published_at', 'is_featured', 'event_starts_at', 'event_ends_at', 'event_location',
        'created_by', 'updated_by',
    ];

    protected $hidden = ['id', 'category_id', 'featured_image_id', 'created_by', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => ContentStatus::class,
            'publish_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'is_featured' => 'boolean',
            'event_starts_at' => 'immutable_datetime',
            'event_ends_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<WebsiteCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WebsiteCategory::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'featured_image_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
