<?php

namespace App\Modules\Dashboard\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Services\GuardianOverview;
use App\Modules\People\Services\GuardianChildren;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GuardianChildren $children, GuardianOverview $overview): Response
    {
        /** @var User $user */
        $user = $request->user();
        $list = $children->of($user);
        $child = $children->selected($list, $request->string(GuardianChildren::QUERY_KEY)->value());

        return Inertia::render('Dashboard::Guardian/Index', $overview->for($user, $list, $child, CarbonImmutable::today()));
    }
}
