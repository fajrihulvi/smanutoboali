<?php

namespace App\Modules\Chat\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Daftar kontak yang boleh dihubungi (per role aktif) dan label peran lawan bicara,
 * mis. "Wali Kelas VII-A · Guru Matematika" atau "Ibu dari Ani".
 *
 * @phpstan-type Contact array{user: string, name: string, role: string, context: string|null, student: array{public_id: string, name: string}|null}
 * @phpstan-type ContactGroup array{key: string, title: string, contacts: list<Contact>, hint?: string}
 */
class ChatDirectory
{
    /** Hasil pencarian orang tua untuk operator/kepala sekolah. */
    private const GUARDIAN_SEARCH_LIMIT = 30;

    public function __construct(private ActivePeriod $period) {}

    /**
     * @return list<ContactGroup>
     */
    public function contacts(User $user, Role $as, ?string $search = null): array
    {
        $search = trim((string) $search);

        $groups = match ($as) {
            Role::Guardian => [...$this->guardianGroups($user), $this->schoolGroup($user)],
            Role::Teacher => [...$this->teacherGroups($user), $this->staffGroup($user, 'Staf sekolah')],
            Role::Operator, Role::Principal => [$this->staffGroup($user, 'Staf sekolah'), $this->guardianSearchGroup($search)],
            default => [],
        };

        if ($search !== '') {
            $groups = array_map(fn (array $group) => [
                ...$group,
                'contacts' => array_values(array_filter(
                    $group['contacts'],
                    fn (array $c) => Str::contains($c['name'].' '.($c['context'] ?? '').' '.($c['student']['name'] ?? ''), $search, ignoreCase: true),
                )),
            ], $groups);
        }

        return array_values(array_filter($groups, fn (array $g) => $g['contacts'] !== [] || isset($g['hint'])));
    }

    /**
     * Label peran lawan bicara dari sudut pandang percakapan (dengan konteks anak bila ada).
     *
     * @param  array<int, array{classroom: string, teachers: array<int, list<string>>}>  $teachersByStudent  dari teachersOfStudents()
     */
    public function describe(User $other, ?Student $student, array $teachersByStudent = []): string
    {
        if ($student !== null) {
            $labels = $teachersByStudent[$student->id]['teachers'][$other->id] ?? null;

            if ($labels !== null) {
                return implode(' · ', $labels);
            }

            if ($other->hasRoleEnum(Role::Guardian)) {
                return 'Orang tua '.$student->name;
            }
        }

        foreach ([Role::Principal, Role::Operator, Role::Teacher, Role::Guardian] as $role) {
            if ($other->hasRoleEnum($role)) {
                return $role->label();
            }
        }

        return 'Pengguna';
    }

    /**
     * Guru (user_id) per siswa di rombel aktif tahun ini, beserta labelnya.
     *
     * @param  iterable<Student>  $students
     * @return array<int, array{classroom: string, teachers: array<int, list<string>>}>
     */
    public function teachersOfStudents(iterable $students): array
    {
        $yearId = $this->period->academicYearId();
        $ids = collect($students)->map(fn (Student $s) => $s->id)->unique()->values()->all();

        if ($yearId === null || $ids === []) {
            return [];
        }

        $enrollments = ClassEnrollment::query()
            ->where('academic_year_id', $yearId)
            ->where('status', EnrollmentStatus::Active)
            ->whereIn('student_id', $ids)
            ->with([
                'classroom.homeroomTeacher',
                'classroom.teachingAssignments' => fn (Relation $q) => $q->where('academic_year_id', $yearId)->orderBy('id'),
                'classroom.teachingAssignments.teacher',
                'classroom.teachingAssignments.subject',
            ])
            ->get();

        $map = [];

        foreach ($enrollments as $enrollment) {
            $classroom = $enrollment->classroom;
            $teachers = [];

            $homeroom = $classroom->getRelation('homeroomTeacher');

            if ($homeroom instanceof Teacher && $homeroom->is_active && $homeroom->user_id !== null) {
                $teachers[$homeroom->user_id][] = 'Wali Kelas '.$classroom->name;
            }

            $subjects = [];
            foreach ($classroom->teachingAssignments as $assignment) {
                /** @var TeachingAssignment $assignment */
                $teacher = $assignment->getRelation('teacher');

                if ($teacher instanceof Teacher && $teacher->is_active && $teacher->user_id !== null) {
                    $subjects[$teacher->user_id][] = $assignment->subject->name;
                }
            }

            foreach ($subjects as $userId => $names) {
                $teachers[$userId][] = 'Guru '.implode(', ', array_unique($names));
            }

            $map[$enrollment->student_id] = ['classroom' => $classroom->name, 'teachers' => $teachers];
        }

        return $map;
    }

    /**
     * Orang tua: satu grup per anak berisi wali kelas & guru mapelnya.
     *
     * @return list<ContactGroup>
     */
    private function guardianGroups(User $user): array
    {
        $children = Student::query()->visibleTo($user, Role::Guardian)->orderBy('name')->get();
        $map = $this->teachersOfStudents($children);
        $users = $this->activeUsers(collect($map)->flatMap(fn (array $row) => array_keys($row['teachers']))->all(), except: $user);

        return $children->map(function (Student $child) use ($map, $users) {
            $contacts = [];

            foreach ($map[$child->id]['teachers'] ?? [] as $userId => $labels) {
                if ($users->has($userId)) {
                    $contacts[] = $this->contact($users[$userId], 'Guru', implode(' · ', $labels), $child);
                }
            }

            return [
                'key' => 'student:'.$child->public_id,
                'title' => $child->name.(isset($map[$child->id]) ? ' · '.$map[$child->id]['classroom'] : ''),
                'contacts' => $contacts,
            ];
        })->values()->all();
    }

    /**
     * Guru: satu grup per rombel (wali kelas/mengajar) berisi orang tua siswa aktifnya.
     *
     * @return list<ContactGroup>
     */
    private function teacherGroups(User $user): array
    {
        $teacher = Teacher::activeForUser($user);
        $yearId = $this->period->academicYearId();

        if ($teacher === null || $yearId === null) {
            return [];
        }

        $classrooms = Classroom::query()
            ->where('academic_year_id', $yearId)
            ->where(fn (Builder $q) => $q
                ->where('homeroom_teacher_id', $teacher->id)
                ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher->id)))
            ->with([
                'activeEnrollments.student.guardians' => fn ($q) => $q->whereNotNull('guardians.user_id'),
                'activeEnrollments.student.guardians.user',
            ])
            ->orderBy('grade')->orderBy('name')
            ->get();

        return $classrooms->map(function (Classroom $classroom) use ($user) {
            $contacts = [];
            $students = $classroom->activeEnrollments->map(fn (ClassEnrollment $e) => $e->student)->sortBy('name');

            foreach ($students as $student) {
                foreach ($student->guardians as $guardian) {
                    /** @var Guardian $guardian */
                    $account = $guardian->user;

                    if ($account === null || ! $account->is_active || $account->is($user)) {
                        continue;
                    }

                    $relationship = GuardianRelationship::tryFrom((string) $guardian->getRelationValue('pivot')?->getAttribute('relationship'));
                    $contacts[] = $this->contact($account, 'Orang Tua', ($relationship?->label() ?? 'Orang tua').' dari '.$student->name, $student);
                }
            }

            return ['key' => 'classroom:'.$classroom->public_id, 'title' => 'Rombel '.$classroom->name, 'contacts' => $contacts];
        })->values()->all();
    }

    /**
     * Kepala sekolah & operator (untuk orang tua).
     *
     * @return ContactGroup
     */
    private function schoolGroup(User $user): array
    {
        $users = $this->usersWithRoles([Role::Principal, Role::Operator], $user);

        return [
            'key' => 'school',
            'title' => 'Sekolah',
            'contacts' => $users->map(fn (User $u) => $this->contact($u, $this->primaryRoleLabel($u), null))->values()->all(),
        ];
    }

    /**
     * Semua staf aktif: kepala sekolah, operator, guru (profil aktif).
     *
     * @return ContactGroup
     */
    private function staffGroup(User $user, string $title): array
    {
        $admins = $this->usersWithRoles([Role::Principal, Role::Operator], $user);
        $teachers = User::query()
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->whereIn('id', Teacher::query()->where('is_active', true)->whereNotNull('user_id')->select('user_id'))
            ->whereHas('roles', fn (Builder $r) => $r->where('name', Role::Teacher->value))
            ->with('roles')
            ->orderBy('name')
            ->get();

        $all = $admins->concat($teachers)->unique('id');

        return [
            'key' => 'staff',
            'title' => $title,
            'contacts' => $all->map(fn (User $u) => $this->contact($u, $this->primaryRoleLabel($u), null))->values()->all(),
        ];
    }

    /**
     * Orang tua untuk operator/kepala sekolah — hanya lewat pencarian (jumlahnya bisa ribuan).
     *
     * @return ContactGroup
     */
    private function guardianSearchGroup(string $search): array
    {
        $group = ['key' => 'guardians', 'title' => 'Orang tua', 'contacts' => []];

        if (mb_strlen($search) < 2) {
            return [...$group, 'hint' => 'Ketik minimal 2 huruf untuk mencari orang tua.'];
        }

        $users = User::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$search}%")
            ->whereHas('roles', fn (Builder $r) => $r->where('name', Role::Guardian->value))
            ->with('roles')
            ->orderBy('name')
            ->limit(self::GUARDIAN_SEARCH_LIMIT)
            ->get();

        $children = Guardian::query()
            ->whereIn('user_id', $users->modelKeys())
            ->with(['students' => fn ($q) => $q->orderBy('name')])
            ->get()
            ->groupBy('user_id');

        $group['contacts'] = $users->map(function (User $u) use ($children) {
            $names = ($children[$u->id] ?? collect())->flatMap(fn (Guardian $g) => $g->students->pluck('name'))->unique()->values();

            return $this->contact($u, 'Orang Tua', $names->isEmpty() ? null : 'Orang tua '.$names->implode(', '));
        })->values()->all();

        return $group;
    }

    /**
     * @param  list<Role>  $roles
     * @return Collection<int, User>
     */
    private function usersWithRoles(array $roles, User $except): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($except->getKey())
            ->whereHas('roles', fn (Builder $r) => $r->whereIn('name', array_map(fn (Role $role) => $role->value, $roles)))
            ->with('roles')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, User>
     */
    private function activeUsers(array $ids, User $except): Collection
    {
        return User::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereKeyNot($except->getKey())
            ->get()
            ->keyBy('id');
    }

    private function primaryRoleLabel(User $user): string
    {
        return $this->describe($user, null);
    }

    /**
     * @return Contact
     */
    private function contact(User $user, string $role, ?string $context, ?Student $student = null): array
    {
        return [
            'user' => $user->public_id,
            'name' => $user->name,
            'role' => $role,
            'context' => $context,
            'student' => $student ? ['public_id' => $student->public_id, 'name' => $student->name] : null,
        ];
    }
}
