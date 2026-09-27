<?php

namespace App\Modules\People\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Http\Resources\TeacherResource;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Services\PeopleDirectoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitoring Guru — daftar read-only untuk kepala sekolah.
 */
class TeacherController extends Controller
{
    public function index(Request $request, PeopleDirectoryService $directory): Response
    {
        $this->authorize('viewAny', Teacher::class);
        $filters = $directory->teacherFilters($request);
        $filters['account'] = null;

        return Inertia::render('People::Principal/Teachers/Index', [
            'teachers' => TeacherResource::collection($directory->teachers($filters)),
            'filters' => array_filter($filters),
            'employment' => array_map(fn (EmploymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], EmploymentStatus::cases()),
        ]);
    }
}
