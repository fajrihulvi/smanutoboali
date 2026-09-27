<?php

namespace App\Modules\People\Models;

use App\Models\User;
use App\Modules\People\Database\Factories\GuardianFactory;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $nik
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $occupation
 * @property string|null $address
 * @property-read User|null $user
 * @property-read Collection<int, Student> $students
 */
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'nik', 'phone', 'email', 'occupation', 'address'];

    protected $hidden = ['nik'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['nik' => 'encrypted'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * @param  Builder<Guardian>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (! $term) {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }
}
