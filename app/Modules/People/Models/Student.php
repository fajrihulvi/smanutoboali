<?php

namespace App\Modules\People\Models;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\File;
use App\Modules\People\Database\Factories\StudentFactory;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\Religion;
use App\Modules\People\Enums\StudentStatus;
use App\Support\ActivePeriod;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Siswa — central entity (SKOOLIA_SYSTEM_PLANNING §8.3).
 *
 * @property int $id
 * @property string $public_id
 * @property string $nis
 * @property string|null $nisn
 * @property string $name
 * @property Gender $gender
 * @property string|null $birth_place
 * @property CarbonImmutable|null $birth_date
 * @property Religion|null $religion
 * @property string|null $address
 * @property int|null $photo_file_id
 * @property StudentStatus $status
 * @property CarbonImmutable|null $entry_date
 * @property-read Collection<int, Guardian> $guardians
 * @property-read Collection<int, ClassEnrollment> $enrollments
 * @property-read File|null $photo
 */
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'nis', 'nisn', 'name', 'gender', 'birth_place', 'birth_date',
        'religion', 'address', 'photo_file_id', 'status', 'entry_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'religion' => Religion::class,
            'status' => StudentStatus::class,
            'birth_date' => 'immutable_date',
            'entry_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Riwayat rombel (modul Academic).
     *
     * @return HasMany<ClassEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    /**
     * Cakupan data siswa per role (VIBE_PLANNING §4.3). WAJIB dipakai untuk
     * semua query siswa oleh role non-operator.
     *
     *  - operator, kepala sekolah : semua siswa
     *  - orang tua                : siswa yang tertaut lewat guardian_student
     *  - guru (aktif)              : siswa dengan enrollment AKTIF di rombel tahun ajaran
     *                               aktif tempat ia punya penugasan atau menjadi wali kelas
     *
     * Tanpa $as, role aktif di sesi yang dipakai.
     *
     * @param  Builder<Student>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user, ?Role $as = null): void
    {
        $role = $as ?? self::resolveRole($user);

        if ($role === null || ! $user->hasRoleEnum($role)) {
            $query->whereRaw('1 = 0');

            return;
        }

        match ($role) {
            Role::Operator, Role::Principal => null,
            Role::Guardian => $query->whereHas('guardians', fn (Builder $g) => $g->where('guardians.user_id', $user->getKey())),
            Role::Teacher => self::scopeForTeacher($query, $user),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @param  Builder<Student>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (! $term) {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('nis', 'like', "%{$term}%")
            ->orWhere('nisn', 'like', "%{$term}%"));
    }

    /**
     * @param  Builder<Student>  $query
     */
    private static function scopeForTeacher(Builder $query, User $user): void
    {
        $yearId = app(ActivePeriod::class)->academicYearId();

        if ($yearId === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $isThisTeacher = fn (Builder $teacher) => $teacher
            ->where('teachers.user_id', $user->getKey())
            ->where('teachers.is_active', true);

        $query->whereHas('enrollments', fn (Builder $enrollment) => $enrollment
            ->where('class_enrollments.academic_year_id', $yearId)
            ->where('class_enrollments.status', EnrollmentStatus::Active->value)
            ->whereHas('classroom', fn (Builder $classroom) => $classroom
                ->where(fn (Builder $scope) => $scope
                    ->whereHas('homeroomTeacher', $isThisTeacher)
                    ->orWhereHas('teachingAssignments.teacher', $isThisTeacher))));
    }

    private static function resolveRole(User $user): ?Role
    {
        $request = request();

        if ($request->hasSession()) {
            $active = app(ActiveRoleService::class)->current($request->session());

            if ($active && $user->hasRoleEnum($active)) {
                return $active;
            }
        }

        return $user->coreRoles()[0] ?? null;
    }
}
