<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Actions\DeleteScheduleAction;
use App\Modules\Academic\Actions\SaveScheduleAction;
use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Http\Requests\ScheduleRequest;
use App\Modules\Academic\Http\Resources\ScheduleResource;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\AcademicOptions;
use App\Modules\Academic\Services\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Editor jadwal per rombel (grid mingguan).
 */
class ScheduleController extends Controller
{
    public function index(Request $request, AcademicOptions $options, Timetable $timetable): Response
    {
        $this->authorize('manage', Schedule::class);

        $year = $options->selectedYear($request);

        $classrooms = Classroom::query()
            ->where('academic_year_id', $year?->getKey() ?? 0)
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'public_id', 'name']);

        $classroom = $classrooms->firstWhere('public_id', $request->string('rombel')->value()) ?? $classrooms->first();

        $assignments = $classroom
            ? TeachingAssignment::query()
                ->where('classroom_id', $classroom->id)
                ->with(['subject:id,name', 'teacher:id,name'])
                ->get()
                ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)
                ->map(fn (TeachingAssignment $a) => [
                    'value' => $a->public_id,
                    'label' => "{$a->subject->name} — {$a->teacher->name}",
                ])->values()
            : collect();

        return Inertia::render('Academic::Operator/Schedules/Index', [
            'year' => $year ? ['value' => $year->public_id, 'label' => $year->name, 'is_active' => $year->is_active] : null,
            'years' => $options->years(),
            'classrooms' => $classrooms->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
            'classroom' => $classroom ? ['public_id' => $classroom->public_id, 'name' => $classroom->name] : null,
            'schedules' => $classroom ? ScheduleResource::collection($timetable->forClassroom($classroom->id))->resolve($request) : [],
            'assignments' => $assignments,
            'days' => Weekday::options(),
        ]);
    }

    public function store(ScheduleRequest $request, SaveScheduleAction $save): RedirectResponse
    {
        $save->handle($request->payload());

        return back()->with('success', 'Jadwal ditambahkan.');
    }

    public function update(ScheduleRequest $request, Schedule $schedule, SaveScheduleAction $save): RedirectResponse
    {
        $save->handle($request->payload(), $schedule);

        return back()->with('success', 'Jadwal diperbarui.');
    }

    public function destroy(Schedule $schedule, DeleteScheduleAction $delete): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $delete->handle($schedule);

        return back()->with('success', 'Jadwal dihapus.');
    }
}
