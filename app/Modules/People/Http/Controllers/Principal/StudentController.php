<?php

namespace App\Modules\People\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Http\Resources\StudentResource;
use App\Modules\People\Models\Student;
use App\Modules\People\Services\StudentDirectoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitoring Siswa — daftar read-only untuk kepala sekolah (lewat visibleTo).
 */
class StudentController extends Controller
{
    public function index(Request $request, StudentDirectoryService $directory): Response
    {
        $this->authorize('viewAny', Student::class);

        /** @var User $user */
        $user = $request->user();
        $filters = $directory->filters($request);

        return Inertia::render('People::Principal/Students/Index', [
            'students' => StudentResource::collection($directory->paginate($user, $filters)),
            'filters' => array_filter($filters),
            'statuses' => array_map(fn (StudentStatus $s) => ['value' => $s->value, 'label' => $s->label()], StudentStatus::cases()),
            'genders' => array_map(fn (Gender $g) => ['value' => $g->value, 'label' => $g->label()], Gender::cases()),
        ]);
    }
}
