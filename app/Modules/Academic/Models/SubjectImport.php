<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\People\Enums\ImportStatus;
use App\Modules\People\Models\Concerns\ProcessesImport;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat import Excel mata pelajaran (diproses Job).
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $file_id
 * @property string $original_name
 * @property ImportStatus $status
 * @property int $total_rows
 * @property int $success_rows
 * @property int $failed_rows
 * @property list<array{row: int, messages: list<string>}>|null $errors
 * @property string|null $failure_message
 * @property int|null $created_by
 * @property CarbonImmutable|null $finished_at
 * @property CarbonImmutable $created_at
 * @property-read File|null $file
 * @property-read User|null $creator
 */
class SubjectImport extends Model
{
    use HasPublicId, ProcessesImport;

    /** Batas error yang disimpan (mencegah kolom JSON membengkak). */
    public const MAX_STORED_ERRORS = 500;

    protected $fillable = [
        'file_id', 'original_name', 'status', 'total_rows', 'success_rows',
        'failed_rows', 'errors', 'failure_message', 'created_by', 'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'errors' => 'array',
            'finished_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
