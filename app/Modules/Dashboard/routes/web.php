<?php

use App\Modules\Dashboard\Http\Controllers\Guardian\DashboardController as GuardianDashboard;
use App\Modules\Dashboard\Http\Controllers\Guardian\TimelineController as GuardianTimeline;
use App\Modules\Dashboard\Http\Controllers\Operator\DashboardController as OperatorDashboard;
use App\Modules\Dashboard\Http\Controllers\Principal\DashboardController as PrincipalDashboard;
use App\Modules\Dashboard\Http\Controllers\RedirectToRoleDashboardController;
use App\Modules\Dashboard\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard per role (placeholder S04, diisi penuh di S30)
|--------------------------------------------------------------------------
*/

// /dashboard → dashboard role aktif.
Route::get('dashboard', RedirectToRoleDashboardController::class)
    ->middleware(['auth', 'active.role'])
    ->name('dashboard');

Route::middleware(['auth', 'active.role:principal'])
    ->prefix('kepala-sekolah')->name('principal.')
    ->group(function () {
        Route::get('dashboard', PrincipalDashboard::class)->name('dashboard');
    });

Route::middleware(['auth', 'active.role:teacher'])
    ->prefix('guru')->name('teacher.')
    ->group(function () {
        Route::get('dashboard', TeacherDashboard::class)->name('dashboard');
    });

Route::middleware(['auth', 'active.role:operator'])
    ->prefix('operator')->name('operator.')
    ->group(function () {
        Route::get('dashboard', OperatorDashboard::class)->name('dashboard');
    });

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua')->name('guardian.')
    ->group(function () {
        Route::get('dashboard', GuardianDashboard::class)->name('dashboard');
        Route::get('timeline', GuardianTimeline::class)->name('timeline');
    });
