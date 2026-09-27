<?php

namespace App\Modules\Dashboard\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard::Principal/Index');
    }
}
