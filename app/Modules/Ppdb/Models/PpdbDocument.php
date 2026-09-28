<?php

namespace App\Modules\Ppdb\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\Ppdb\Enums\DocumentStatus;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Dokumen untuk satu persyaratan; file-nya di tabel files (fileable = dokumen ini).
 *
 * @property int $id
 * @property string $public_id
 * @property int $ppdb_applicant_id
 * @property int $ppdb_document_requirement_id
 * @property DocumentStatus $status
 * @property string|null $note
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property-read PpdbApplicant $applicant
 * @property-read PpdbDocumentRequirement $requirement
 * @property-read File|null $file
 * @property-read User|null $reviewer
 */
class PpdbDocument extends Model
{
    use HasPublicId;

    protected $fillable = ['ppdb_applicant_id', 'ppdb_document_requirement_id', 'status', 'note', 'reviewed_by', 'reviewed_at'];

    protected $hidden = ['id', 'ppdb_applicant_id', 'ppdb_document_requirement_id', 'reviewed_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => DocumentStatus::class, 'reviewed_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<PpdbApplicant, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(PpdbApplicant::class, 'ppdb_applicant_id');
    }

    /**
     * @return BelongsTo<PpdbDocumentRequirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(PpdbDocumentRequirement::class, 'ppdb_document_requirement_id')->withTrashed();
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable')->latestOfMany();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
