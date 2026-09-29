<?php

use App\Modules\Report\Http\Controllers\ExportDownloadController;
use App\Modules\Report\Http\Controllers\ReportCenterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report routes — pusat laporan & export (S31)
|--------------------------------------------------------------------------
*/

foreach ([['principal', 'kepala-sekolah'], ['operator', 'operator'], ['teacher', 'guru']] as [$role, $prefix]) {
    Route::middleware(['auth', "active.role:{$role}", 'permission:report.export'])
        ->prefix("{$prefix}/laporan")->name("{$role}.reports.")
        ->group(function () use ($role, $prefix) {
            Route::get('/', [ReportCenterController::class, 'index'])
                ->defaults('base', "/{$prefix}/laporan")->defaults('role', $role)->name('index');
            Route::post('ekspor', [ReportCenterController::class, 'export'])
                ->defaults('base', "/{$prefix}/laporan")->defaults('role', $role)
                ->middleware('throttle:20,1')->name('export');
        });
}

Route::get('laporan/ekspor/{export}', ExportDownloadController::class)
    ->middleware('auth')
    ->name('reports.download');
