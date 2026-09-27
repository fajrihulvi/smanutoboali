<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /health — JSON status. Wajib token HEALTH_TOKEN (header X-Health-Token
 * atau ?token=). Tanpa token terkonfigurasi, endpoint tidak ada (404).
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, HealthCheckService $health): JsonResponse
    {
        $expected = (string) config('skoolia.health_token');

        abort_if($expected === '', 404);

        $given = (string) ($request->header('X-Health-Token') ?? $request->query('token', ''));

        abort_unless(hash_equals($expected, $given), 403);

        $report = $health->report();

        return response()
            ->json($report, $report['status'] === 'down' ? 503 : 200)
            ->header('Cache-Control', 'no-store');
    }
}
