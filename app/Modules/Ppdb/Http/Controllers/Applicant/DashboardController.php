<?php

namespace App\Modules\Ppdb\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Ppdb\Http\Resources\ApplicantResource;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard calon orang tua: semua pendaftarannya + periode/jalur yang sedang dibuka.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $applicants = PpdbApplicant::query()
            ->where('user_id', $user->id)
            ->with(['period.academicYear', 'track.requirements'])
            ->latest('id')
            ->get();

        $periods = PpdbPeriod::query()->open()->with(['academicYear', 'tracks'])->orderBy('closes_at')->get();

        return Inertia::render('Ppdb::Applicant/Dashboard', [
            'applicants' => ApplicantResource::collection($applicants)->resolve($request),
            'openPeriods' => $periods->map(fn (PpdbPeriod $p) => [
                'public_id' => $p->public_id,
                'name' => $p->name,
                'academic_year' => $p->academicYear->name,
                'grade' => $p->grade,
                'closes_at' => $p->closes_at->toIso8601String(),
                'description' => $p->description,
                'tracks' => $p->tracks->map(fn (PpdbTrack $t) => ['public_id' => $t->public_id, 'name' => $t->name, 'quota' => $t->quota, 'description' => $t->description])->values(),
            ])->values(),
        ]);
    }
}
