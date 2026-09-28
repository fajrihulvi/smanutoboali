<?php

namespace App\Modules\Ppdb\Models;

use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int $ppdb_track_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_required
 * @property int $position
 */
class PpdbDocumentRequirement extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['ppdb_track_id', 'name', 'description', 'is_required', 'position'];

    protected $hidden = ['id', 'ppdb_track_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'position' => 'integer'];
    }
}
