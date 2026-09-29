<?php

namespace App\Modules\Report\Reports;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pemohon laporan (user + role aktif) beserta scope datanya.
 */
final class ReportContext
{
    private ?Teacher $teacher = null;

    private bool $teacherLoaded = false;

    /** @var Collection<int, Classroom>|null */
    private ?Collection $classrooms = null;

    public function __construct(public readonly User $user, public readonly Role $role) {}

    public function isTeacher(): bool
    {
        return $this->role === Role::Teacher;
    }

    public function teacher(): ?Teacher
    {
        if (! $this->teacherLoaded) {
            $this->teacher = Teacher::activeForUser($this->user);
            $this->teacherLoaded = true;
        }

        return $this->teacher;
    }

    /**
     * Rombel tahun ajaran aktif yang boleh dilaporkan: semua (operator/kepsek) atau
     * yang diajar/diwalikan (guru).
     *
     * @return Collection<int, Classroom>
     */
    public function classrooms(): Collection
    {
        if ($this->classrooms !== null) {
            return $this->classrooms;
        }

        $yearId = app(ActivePeriod::class)->academicYearId();
        $teacher = $this->teacher();

        if ($yearId === null || ($this->isTeacher() && $teacher === null)) {
            return $this->classrooms = new Collection;
        }

        return $this->classrooms = Classroom::query()
            ->where('academic_year_id', $yearId)
            ->when($this->isTeacher(), fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('homeroom_teacher_id', $teacher?->id)
                ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher?->id))))
            ->orderBy('grade')->orderBy('name')
            ->get(['id', 'public_id', 'name', 'grade', 'homeroom_teacher_id']);
    }

    public function classroom(?string $publicId): ?Classroom
    {
        return $publicId ? $this->classrooms()->firstWhere('public_id', $publicId) : null;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function classroomOptions(): array
    {
        return $this->classrooms()->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values()->all();
    }
}
