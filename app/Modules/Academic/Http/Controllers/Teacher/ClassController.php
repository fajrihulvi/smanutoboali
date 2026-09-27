<?php

namespace App\Modules\Academic\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Http\Resources\ClassroomResource;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelas Saya: rombel tahun ajaran aktif tempat guru menjadi wali kelas atau mengajar.
 */
class ClassController extends Controller
{
    public function index(Request $request, ActivePeriod $period): Response
    {
        $teacher = $this->teacher($request);
        $yearId = $period->academicYearId();

        $classrooms = $teacher && $yearId
            ? Classroom::query()
                ->where('academic_year_id', $yearId)
                ->where(fn (Builder $q) => $q
                    ->where('homeroom_teacher_id', $teacher->getKey())
                    ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher->getKey())))
                ->with(['homeroomTeacher:id,public_id,name'])
                ->withCount('activeEnrollments')
                ->orderBy('grade')
                ->orderBy('name')
                ->get()
            : new Collection;

        $subjectsByClass = $teacher
            ? TeachingAssignment::query()
                ->where('teacher_id', $teacher->getKey())
                ->whereIn('classroom_id', $classrooms->modelKeys())
                ->with('subject:id,name')
                ->get()
                ->groupBy('classroom_id')
                ->map(fn ($items) => $items->pluck('subject.name')->sort()->values())
            : collect();

        return Inertia::render('Academic::Teacher/Classes/Index', [
            'hasTeacherProfile' => $teacher !== null,
            'classrooms' => $classrooms->map(fn (Classroom $classroom) => [
                ...(new ClassroomResource($classroom))->resolve($request),
                'is_homeroom' => $classroom->homeroom_teacher_id === $teacher?->getKey(),
                'subjects' => $subjectsByClass->get($classroom->getKey(), collect())->all(),
            ])->values(),
        ]);
    }

    public function show(Request $request, Classroom $classroom): Response
    {
        $this->authorize('view', $classroom);

        $classroom->load('homeroomTeacher:id,public_id,name')->loadCount('activeEnrollments');

        $students = ClassEnrollment::query()
            ->with('student:id,public_id,nis,name,gender')
            ->where('classroom_id', $classroom->getKey())
            ->where('status', EnrollmentStatus::Active)
            ->get()
            ->map(fn (ClassEnrollment $e) => [
                'public_id' => $e->student->public_id,
                'nis' => $e->student->nis,
                'name' => $e->student->name,
                'gender' => $e->student->gender->value,
            ])
            ->sortBy('name')
            ->values();

        return Inertia::render('Academic::Teacher/Classes/Show', [
            'classroom' => (new ClassroomResource($classroom))->resolve($request),
            'students' => $students,
        ]);
    }

    private function teacher(Request $request): ?Teacher
    {
        /** @var User $user */
        $user = $request->user();

        return Teacher::query()->where('user_id', $user->getKey())->where('is_active', true)->first();
    }
}
