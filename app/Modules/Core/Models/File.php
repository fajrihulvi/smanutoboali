<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $public_id
 * @property string $disk
 * @property FileVisibility $visibility
 * @property FileDirectory $directory
 * @property string $original_name
 * @property string $stored_name
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $fileable_type
 * @property int|null $fileable_id
 * @property int|null $uploaded_by
 * @property CarbonImmutable $created_at
 */
class File extends Model
{
    use HasPublicId, Prunable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['id', 'path', 'disk', 'fileable_id', 'fileable_type', 'uploaded_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => FileVisibility::class,
            'directory' => FileDirectory::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * File di tempat sampah > 30 hari dihapus permanen (model:prune).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30));
    }

    protected function pruning(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
