<?php

namespace App\Modules\Portfolio\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu karya/dokumentasi untuk satu atau beberapa siswa, dengan banyak file.
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $academic_year_id
 * @property int $category_id
 * @property string $title
 * @property string|null $description
 * @property CarbonImmutable $date
 * @property PortfolioVisibility $visibility
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property-read PortfolioCategory $category
 */
class Portfolio extends Model
{
    use HasPublicId, SoftDeletes;

    public const MAX_FILES = 10;

    protected $fillable = [
        'academic_year_id', 'category_id', 'title', 'description', 'date',
        'visibility', 'published_at', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'visibility' => PortfolioVisibility::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<PortfolioCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PortfolioCategory::class);
    }

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'portfolio_student');
    }

    /**
     * @return MorphMany<File, $this>
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
