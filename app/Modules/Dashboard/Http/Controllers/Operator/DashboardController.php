<?php

namespace App\Modules\Dashboard\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Services\OperatorDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperatorDashboard $dashboard): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard::Operator/Index', $dashboard->for($user, CarbonImmutable::today()));
    }
}
