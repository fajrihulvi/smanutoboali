<?php

namespace App\Modules\Academic\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Http\Resources\TaughtStudentResource;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Siswa yang diajar guru (Student::visibleTo) beserta rombel tahun aktif.
 */
class StudentController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request, ActivePeriod $period): Response
    {
        $this->authorize('viewAny', Student::class);

        /** @var User $user */
        $user = $request->user();
        $yearId = $period->academicYearId();
        $search = $request->string('search')->trim()->value();

        $students = Student::query()
            ->visibleTo($user, Role::Teacher)
            ->search($search !== '' ? $search : null)
            ->with(['enrollments' => fn ($e) => $e
                ->where('academic_year_id', $yearId)
                ->where('status', EnrollmentStatus::Active)
                ->with('classroom:id,public_id,name')])
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Academic::Teacher/Students/Index', [
            'students' => TaughtStudentResource::collection($students),
            'filters' => array_filter(['search' => $search]),
        ]);
    }
}
