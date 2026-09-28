<?php

namespace App\Modules\ReportCard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\ReportCard\Jobs\GenerateReportCardZip;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardExport;
use App\Modules\ReportCard\Services\ReportCardAccess;
use App\Modules\ReportCard\Services\ReportCardPresenter;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ringkasan raport semua rombel (operator & kepala sekolah) + cetak massal (operator).
 */
class OverviewController extends Controller
{
    public function index(Request $request, string $base, ActivePeriod $period, SemesterPicker $semesters, ReportCardPresenter $presenter): Response
    {
        $semester = $semesters->selected($request);
        $classrooms = Classroom::query()
            ->where('academic_year_id', $period->academicYearId() ?? 0)
            ->with('homeroomTeacher:id,name')
            ->orderBy('grade')->orderBy('name')
            ->get();

        $cards = $semester
            ? ReportCard::query()->where('semester_id', $semester->id)->whereIn('classroom_id', $classrooms->modelKeys())->get(['id', 'classroom_id', 'status'])->groupBy('classroom_id')
            : collect();

        return Inertia::render('ReportCard::Shared/Overview', [
            'base' => $base,
            'semester' => $semester?->public_id,
            'semesters' => $semesters->toOptions(),
            'classrooms' => $classrooms->map(fn (Classroom $c) => [
                'public_id' => $c->public_id,
                'name' => $c->name,
                'homeroom' => $c->homeroomTeacher?->name,
                'total' => $cards->get($c->id, collect())->count(),
                'counts' => $presenter->counts($cards->get($c->id, new Collection)),
            ])->values(),
        ]);
    }

    public function export(Request $request, Classroom $classroom, ReportCardAccess $access, SemesterPicker $semesters): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($access->isOperator($user), 403);
        $semester = $semesters->selected($request);
        abort_if($semester === null, 404);

        $export = ReportCardExport::query()->create([
            'classroom_id' => $classroom->id,
            'semester_id' => $semester->id,
            'requested_by' => $user->id,
            'status' => ReportCardExport::PENDING,
        ]);

        GenerateReportCardZip::dispatch($export->id);

        return back()->with('success', 'Cetak massal diantrekan. File ZIP siap dalam beberapa menit; muat ulang halaman untuk memeriksa.');
    }
}
