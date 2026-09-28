<?php

namespace App\Modules\Activity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Activity\Http\Resources\ActivityResource;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kalender kegiatan bulanan untuk semua role (isi sesuai ActivityVisibility).
 */
class CalendarController extends Controller
{
    public function __invoke(Request $request, string $base, ActivityVisibility $visibility, ActiveRoleService $roles): Response
    {
        /** @var User $user */
        $user = $request->user();
        $role = $roles->current($request->session()) ?? Role::Guardian;
        $value = $request->string('bulan')->value();
        $month = preg_match('/^\d{4}-\d{2}$/', $value) === 1 ? CarbonImmutable::parse($value.'-01') : CarbonImmutable::today()->startOfMonth();

        $activities = $visibility->forUser($user, $role)
            ->whereDate('date', '>=', $month->startOfMonth()->toDateString())
            ->whereDate('date', '<=', $month->endOfMonth()->toDateString())
            ->with(['participants.classroom:id,public_id,name', 'participants.student:id,public_id,name', 'photos', 'creator:id,name'])
            ->orderBy('date')->orderBy('start_time')
            ->get();

        $children = $role === Role::Guardian ? Student::query()->visibleTo($user, Role::Guardian)->pluck('students.id')->all() : null;

        return Inertia::render('Activity::Shared/Calendar', [
            'base' => $base,
            'month' => $month->format('Y-m'),
            'activities' => $activities->map(fn (Activity $a) => $children === null
                ? (new ActivityResource($a))->resolve($request)
                : (new ActivityResource($a))->onlyStudents($children)->resolve($request))->values(),
            'canManage' => $user->can('manageAny', Activity::class) && in_array($role, [Role::Teacher, Role::Operator], true),
        ]);
    }
}
