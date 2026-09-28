<?php

use App\Modules\Activity\Http\Controllers\ActivityController;
use App\Modules\Activity\Http\Controllers\CalendarController;
use App\Modules\Activity\Http\Controllers\Guardian\FeedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Activity routes — kegiatan & kalender (S18)
|--------------------------------------------------------------------------
*/

foreach ([['teacher', 'guru'], ['operator', 'operator']] as [$role, $prefix]) {
    $base = "/{$prefix}/kegiatan";

    Route::middleware(['auth', "active.role:{$role}", 'permission:activity.manage'])
        ->prefix("{$prefix}/kegiatan")->name("{$role}.activities.")
        ->group(function () use ($base) {
            Route::get('/', [ActivityController::class, 'index'])->defaults('base', $base)->name('index');
            Route::get('kalender', CalendarController::class)->defaults('base', $base)->name('calendar');
            Route::get('tambah', [ActivityController::class, 'create'])->defaults('base', $base)->name('create');
            Route::post('/', [ActivityController::class, 'store'])->defaults('base', $base)->name('store');
            Route::get('siswa', [ActivityController::class, 'students'])->name('students');
            Route::get('{activity}/ubah', [ActivityController::class, 'edit'])->defaults('base', $base)->name('edit');
            Route::put('{activity}', [ActivityController::class, 'update'])->name('update');
            Route::delete('{activity}', [ActivityController::class, 'destroy'])->defaults('base', $base)->name('destroy');
        });
}

Route::middleware(['auth', 'active.role:principal'])
    ->get('kepala-sekolah/kegiatan', CalendarController::class)
    ->defaults('base', '/kepala-sekolah/kegiatan')
    ->name('principal.activities.calendar');

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua/kegiatan')->name('guardian.activities.')
    ->group(function () {
        Route::get('/', FeedController::class)->name('feed');
        Route::get('kalender', CalendarController::class)->defaults('base', '/orang-tua/kegiatan')->name('calendar');
    });
