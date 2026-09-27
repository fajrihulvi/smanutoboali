<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Resources\AuditLogResource;
use App\Modules\Core\Services\AuditLogQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Audit log read-only untuk operator (/operator/audit-log) dan kepala sekolah
 * (/kepala-sekolah/audit-log). Akses dijaga middleware permission:audit_log.view.
 */
class AuditLogController extends Controller
{
    public function __invoke(Request $request, AuditLogQueryService $query): Response
    {
        $filters = [
            'module' => $request->string('module')->trim()->value() ?: null,
            'user' => $request->string('user')->trim()->value() ?: null,
            'from' => $request->string('from')->value() ?: null,
            'to' => $request->string('to')->value() ?: null,
        ];

        return Inertia::render('Core::AuditLog/Index', [
            'logs' => AuditLogResource::collection($query->paginate($filters)),
            'filters' => array_filter($filters),
            'modules' => $query->moduleOptions(),
            'basePath' => '/'.$request->segment(1).'/audit-log',
        ]);
    }
}
