<?php

namespace App\Modules\Report\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\Report\Enums\ExportFormat;
use App\Modules\Report\Enums\ExportStatus;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

/**
 * Ekspor laporan yang diproses antrean. File privat, hanya untuk pemilik, kedaluwarsa 24 jam.
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property string $role
 * @property string $report
 * @property ExportFormat $format
 * @property array<string, mixed> $filters
 * @property string $title
 * @property ExportStatus $status
 * @property int|null $rows
 * @property string|null $error
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read File|null $file
 */
class ReportExport extends Model
{
    use HasPublicId, Prunable;

    public const TTL_HOURS = 24;

    protected $fillable = ['user_id', 'role', 'report', 'format', 'filters', 'title', 'status', 'rows', 'error', 'completed_at', 'expires_at'];

    protected $hidden = ['id', 'user_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'format' => ExportFormat::class,
            'filters' => 'array',
            'status' => ExportStatus::class,
            'rows' => 'integer',
            'completed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isDownloadable(): bool
    {
        return $this->status === ExportStatus::Done && ! $this->isExpired();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable');
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<=', now());
    }

    protected function pruning(): void
    {
        $file = $this->file()->withTrashed()->first();

        if ($file !== null) {
            Storage::disk($file->disk)->delete($file->path);
            $file->forceDelete();
        }
    }
}
