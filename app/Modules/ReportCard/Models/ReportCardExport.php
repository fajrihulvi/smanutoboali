<?php

namespace App\Modules\ReportCard\Models;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Models\File;
use App\Modules\School\Models\Semester;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

/**
 * Cetak massal raport satu rombel → ZIP (diproses Job).
 *
 * @property int $id
 * @property string $public_id
 * @property int $classroom_id
 * @property int $semester_id
 * @property int|null $requested_by
 * @property string $status MENUNGGU | DIPROSES | SELESAI | GAGAL
 * @property int $total
 * @property string|null $error
 * @property CarbonImmutable|null $created_at
 */
class ReportCardExport extends Model
{
    use HasPublicId, Prunable;

    public const KEEP_DAYS = 7;

    public const PENDING = 'MENUNGGU';

    public const PROCESSING = 'DIPROSES';

    public const DONE = 'SELESAI';

    public const FAILED = 'GAGAL';

    protected $fillable = ['classroom_id', 'semester_id', 'requested_by', 'status', 'total', 'error'];

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable');
    }

    /**
     * ZIP cetak massal disimpan 7 hari (model:prune harian).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<=', now()->subDays(self::KEEP_DAYS));
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
