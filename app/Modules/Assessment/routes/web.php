<?php

use App\Modules\Assessment\Http\Controllers\Guardian\GradeController as GuardianGradeController;
use App\Modules\Assessment\Http\Controllers\Operator\GradebookController as OperatorGradebookController;
use App\Modules\Assessment\Http\Controllers\Principal\GradeRecapController;
use App\Modules\Assessment\Http\Controllers\Teacher\GradebookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Assessment routes — penilaian (S15)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:teacher', 'permission:grade.manage'])
    ->prefix('guru/akademik/nilai')->name('teacher.grades.')
    ->group(function () {
        Route::get('/', [GradebookController::class, 'index'])->name('index');
        Route::put('komponen/{component}', [GradebookController::class, 'updateComponent'])->name('components.update');
        Route::delete('komponen/{component}', [GradebookController::class, 'destroyComponent'])->name('components.destroy');
        Route::post('komponen/{component}/publikasi', [GradebookController::class, 'publish'])->name('components.publish');
        Route::delete('komponen/{component}/publikasi', [GradebookController::class, 'unpublish'])->name('components.unpublish');
        Route::put('komponen/{component}/sel', [GradebookController::class, 'saveCell'])->name('cells.update');
        Route::get('{assignment}', [GradebookController::class, 'show'])->name('show');
        Route::post('{assignment}/komponen', [GradebookController::class, 'storeComponent'])->name('components.store');
        Route::put('{assignment}/deskripsi', [GradebookController::class, 'saveDescription'])->name('descriptions.update');
        Route::post('{assignment}/deskripsi/template', [GradebookController::class, 'fillDescriptions'])->name('descriptions.fill');
        Route::put('{assignment}/template', [GradebookController::class, 'saveTemplates'])->name('templates.update');
        Route::get('{assignment}/ekspor', [GradebookController::class, 'export'])->name('export');
        Route::post('{assignment}/impor', [GradebookController::class, 'import'])->name('import');
    });

Route::middleware(['auth', 'active.role:operator', 'permission:grade.view'])
    ->prefix('operator/nilai')->name('operator.grades.')
    ->group(function () {
        Route::get('/', [OperatorGradebookController::class, 'index'])->name('index');
        Route::get('{assignment}', [OperatorGradebookController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active.role:principal'])
    ->prefix('kepala-sekolah/nilai')->name('principal.grades.')
    ->group(function () {
        Route::get('/', [GradeRecapController::class, 'index'])->name('index');
        Route::get('{assignment}', [OperatorGradebookController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua')->name('guardian.')
    ->group(function () {
        Route::get('nilai', [GuardianGradeController::class, 'index'])->name('grades.index');
    });
