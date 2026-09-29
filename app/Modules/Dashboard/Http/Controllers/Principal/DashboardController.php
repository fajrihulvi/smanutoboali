<?php

namespace App\Modules\Dashboard\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Services\PrincipalDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PrincipalDashboard $dashboard): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard::Principal/Index', $dashboard->for($user, CarbonImmutable::today()));
    }
}
