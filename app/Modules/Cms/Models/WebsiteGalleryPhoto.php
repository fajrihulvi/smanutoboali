<?php

namespace App\Modules\Cms\Models;

use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $website_gallery_id
 * @property int $file_id
 * @property string|null $caption
 * @property int $position
 * @property-read File $file
 */
class WebsiteGalleryPhoto extends Model
{
    use HasPublicId;

    protected $fillable = ['website_gallery_id', 'file_id', 'caption', 'position'];

    protected $hidden = ['id', 'website_gallery_id', 'file_id'];

    /**
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
