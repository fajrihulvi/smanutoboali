<?php

namespace App\Modules\People\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\People\Database\Factories\TeacherFactory;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property string|null $nip
 * @property string|null $nuptk
 * @property string $name
 * @property Gender $gender
 * @property string|null $phone
 * @property int|null $photo_file_id
 * @property EmploymentStatus $employment_status
 * @property bool $is_active
 * @property-read User|null $user
 * @property-read File|null $photo
 */
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'user_id', 'nip', 'nuptk', 'name', 'gender', 'phone',
        'photo_file_id', 'employment_status', 'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'employment_status' => EmploymentStatus::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    /**
     * @param  Builder<Teacher>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (! $term) {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('nip', 'like', "%{$term}%")
            ->orWhere('nuptk', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%"));
    }

    /**
     * Profil guru AKTIF yang tertaut ke akun ini (null bila tidak ada/nonaktif).
     */
    public static function activeForUser(User $user): ?self
    {
        return self::query()->where('user_id', $user->getKey())->where('is_active', true)->first();
    }
}
