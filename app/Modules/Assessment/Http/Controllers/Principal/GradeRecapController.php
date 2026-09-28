<?php

namespace App\Modules\Assessment\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Services\AssessmentSettings;
use App\Modules\Assessment\Services\GradeRecap;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap nilai akhir per rombel × mapel + distribusi predikat (read-only).
 */
class GradeRecapController extends Controller
{
    public function index(Request $request, ActivePeriod $period, SemesterPicker $semesters, GradeRecap $recap, AssessmentSettings $settings): Response
    {
        $semester = $semesters->selected($request);

        $classrooms = Classroom::query()
            ->where('academic_year_id', $period->academicYearId() ?? 0)
            ->orderBy('grade')->orderBy('name')
            ->get(['id', 'public_id', 'name']);

        $classroom = $classrooms->firstWhere('public_id', $request->string('rombel')->value()) ?? $classrooms->first();

        $assignments = $classroom
            ? TeachingAssignment::query()->where('classroom_id', $classroom->id)->with(['subject:id,name', 'teacher:id,name'])->get()
                ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)->values()
            : collect();

        $finals = $semester ? $recap->finals($semester, $assignments) : [];

        $rows = $assignments->map(fn (TeachingAssignment $a) => [
            'public_id' => $a->public_id,
            'subject' => $a->subject->name,
            'teacher' => $a->teacher->name,
            'summary' => $recap->summarize($finals[$a->id] ?? []),
        ])->values();

        $all = array_merge(...array_map(fn (array $byStudent) => array_values($byStudent), array_values($finals ?: [[]])));

        return Inertia::render('Assessment::Principal/Index', [
            'semester' => $semester?->public_id,
            'semesters' => $semesters->toOptions(),
            'classrooms' => $classrooms->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
            'classroom' => $classroom?->public_id,
            'rows' => $rows,
            'overall' => $recap->summarize($all),
            'kkm' => $settings->kkm(),
        ]);
    }
}
