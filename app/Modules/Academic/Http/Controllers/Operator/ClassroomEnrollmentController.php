<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Actions\EnrollStudentsAction;
use App\Modules\Academic\Actions\RemoveEnrollmentAction;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Http\Requests\EnrollStudentsRequest;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassroomEnrollmentController extends Controller
{
    public const CANDIDATE_LIMIT = 50;

    public function store(EnrollStudentsRequest $request, Classroom $classroom, EnrollStudentsAction $enroll): RedirectResponse
    {
        $count = $enroll->handle($classroom, $request->students(), $request->boolean('move'));

        return back()->with('success', "{$count} siswa dimasukkan ke rombel {$classroom->name}.");
    }

    public function destroy(Classroom $classroom, Student $student, RemoveEnrollmentAction $remove): RedirectResponse
    {
        $this->authorize('update', $classroom);

        $enrollment = ClassEnrollment::query()
            ->where('classroom_id', $classroom->getKey())
            ->where('student_id', $student->getKey())
            ->where('status', EnrollmentStatus::Active)
            ->firstOrFail();

        $remove->handle($enrollment);

        return back()->with('success', "{$student->name} dikeluarkan dari rombel.");
    }

    /**
     * Calon anggota rombel: siswa aktif; ?tanpa_rombel=1 hanya yang belum punya rombel tahun ini.
     */
    public function candidates(Request $request, Classroom $classroom): JsonResponse
    {
        $this->authorize('update', $classroom);

        $yearId = $classroom->academic_year_id;
        $term = $request->string('q')->trim()->value();

        $students = Student::query()
            ->where('status', StudentStatus::Active)
            ->search($term !== '' ? $term : null)
            ->when($request->boolean('tanpa_rombel', true), fn (Builder $q) => $q->whereDoesntHave(
                'enrollments',
                fn (Builder $e) => $e->where('academic_year_id', $yearId)->where('status', EnrollmentStatus::Active),
            ))
            ->with(['enrollments' => fn ($e) => $e->where('academic_year_id', $yearId)
                ->where('status', EnrollmentStatus::Active)
                ->with('classroom:id,name')])
            ->orderBy('name')
            ->limit(self::CANDIDATE_LIMIT)
            ->get(['id', 'public_id', 'nis', 'name', 'gender']);

        return response()->json([
            'data' => $students->map(fn (Student $student) => [
                'public_id' => $student->public_id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender->value,
                'current_classroom' => $student->enrollments->first()?->classroom->name,
            ])->values(),
        ]);
    }
}
