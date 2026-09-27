<?php

namespace App\Modules\People\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Http\Resources\StudentResource;
use App\Modules\People\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Anak Saya — hanya siswa yang tertaut ke akun orang tua ini (Student::visibleTo).
 */
class ChildController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Student::class);

        /** @var User $user */
        $user = $request->user();

        $children = Student::query()
            ->visibleTo($user, Role::Guardian)
            ->with(['photo', 'guardians'])
            ->orderBy('name')
            ->get();

        return Inertia::render('People::Guardian/Children/Index', [
            'students' => StudentResource::collection($children),
        ]);
    }

    /**
     * Manipulasi public_id siswa lain → 403 dari StudentPolicy::view.
     */
    public function show(Request $request, Student $student): Response
    {
        $this->authorize('view', $student);

        $student->load(['photo', 'guardians']);

        return Inertia::render('People::Guardian/Children/Show', [
            'child' => (new StudentResource($student))->resolve($request),
        ]);
    }
}
