<?php

namespace App\Modules\Ppdb\Models;

use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Jalur pendaftaran (Reguler, Prestasi, Afirmasi, ...) dengan kuota & dokumen wajibnya.
 *
 * @property int $id
 * @property string $public_id
 * @property int $ppdb_period_id
 * @property string $name
 * @property int $quota
 * @property string|null $description
 * @property int $position
 * @property-read PpdbPeriod $period
 * @property-read Collection<int, PpdbDocumentRequirement> $requirements
 */
class PpdbTrack extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['ppdb_period_id', 'name', 'quota', 'description', 'position'];

    protected $hidden = ['id', 'ppdb_period_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['quota' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<PpdbPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'ppdb_period_id');
    }

    /**
     * @return HasMany<PpdbDocumentRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(PpdbDocumentRequirement::class)->orderBy('position')->orderBy('id');
    }
}
