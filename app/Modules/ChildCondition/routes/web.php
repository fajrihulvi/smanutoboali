<?php

use App\Modules\ChildCondition\Http\Controllers\ConditionController;
use App\Modules\ChildCondition\Http\Controllers\GuardianConditionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ChildCondition routes — laporan kondisi anak (S22)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:guardian', 'permission:child_condition.submit'])
    ->prefix('orang-tua/kondisi-anak')->name('guardian.conditions.')
    ->group(function () {
        Route::get('/', [GuardianConditionController::class, 'index'])->name('index');
        Route::get('lapor', [GuardianConditionController::class, 'create'])->name('create');
        Route::post('/', [GuardianConditionController::class, 'store'])->name('store');
        Route::get('{condition}', [ConditionController::class, 'show'])->defaults('base', '/orang-tua/kondisi-anak')->name('show');
        Route::delete('{condition}', [GuardianConditionController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'active.role:teacher', 'permission:child_condition.respond'])
    ->prefix('guru/kondisi-anak')->name('teacher.conditions.')
    ->group(function () {
        Route::get('/', [ConditionController::class, 'teacherIndex'])->name('index');
        Route::get('{condition}', [ConditionController::class, 'show'])->defaults('base', '/guru/kondisi-anak')->name('show');
        Route::post('{condition}/tanggapan', [ConditionController::class, 'respond'])->name('respond');
        Route::post('{condition}/tinjau', [ConditionController::class, 'review'])->name('review');
        Route::post('{condition}/selesai', [ConditionController::class, 'close'])->name('close');
    });

Route::middleware(['auth', 'active.role:principal'])
    ->prefix('kepala-sekolah/kondisi-anak')->name('principal.conditions.')
    ->group(function () {
        Route::get('/', [ConditionController::class, 'principalIndex'])->name('index');
        Route::get('{condition}', [ConditionController::class, 'show'])->defaults('base', '/kepala-sekolah/kondisi-anak')->name('show');
    });
