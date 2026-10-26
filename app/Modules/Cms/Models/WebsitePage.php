<?php

namespace App\Modules\Cms\Models;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\Concerns\Publishable;
use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property PageType $type
 * @property string $slug
 * @property string $title
 * @property string|null $content
 * @property string|null $excerpt
 * @property int|null $featured_image_id
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property ContentStatus $status
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $published_at
 * @property bool $show_in_menu
 * @property int $position
 * @property CarbonImmutable $updated_at
 * @property-read File|null $featuredImage
 */
class WebsitePage extends Model
{
    use HasPublicId, Publishable, SoftDeletes;

    /**
     * Slug yang bentrok dengan URL aplikasi/website publik.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'berita', 'agenda', 'galeri', 'guru', 'ppdb', 'login', 'logout', 'register', 'password', 'email', 'settings',
        'dashboard', 'operator', 'kepala-sekolah', 'orang-tua', 'files', 'media', 'webhooks', 'notifikasi',
        'health', 'sitemap', 'sitemap-xml', 'robots-txt', 'style-guide', 'build', 'storage', 'pratinjau', 'cari',
        'halaman', 'pengumuman', 'laporan', 'up', 'buku-tamu',
    ];

    protected $fillable = [
        'type', 'slug', 'title', 'content', 'excerpt', 'featured_image_id', 'seo_title', 'seo_description',
        'status', 'publish_at', 'published_at', 'show_in_menu', 'position', 'created_by', 'updated_by',
    ];

    protected $hidden = ['id', 'featured_image_id', 'created_by', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => ContentStatus::class,
            'publish_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'show_in_menu' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'featured_image_id');
    }
}
