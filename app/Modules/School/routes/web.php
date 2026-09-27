<?php

use App\Modules\School\Http\Controllers\Operator\AcademicYearController;
use App\Modules\School\Http\Controllers\Operator\SchoolSettingsController;
use App\Modules\School\Http\Controllers\Operator\SemesterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| School routes — profil sekolah & periode akademik (Operator)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:operator'])
    ->prefix('operator')->name('operator.')
    ->group(function () {
        Route::middleware('permission:setting.manage')->group(function () {
            Route::get('settings', [SchoolSettingsController::class, 'edit'])->name('settings.edit');
            Route::post('settings', [SchoolSettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware('permission:master_data.manage')
            ->prefix('master-data')
            ->group(function () {
                Route::get('tahun-ajaran', [AcademicYearController::class, 'index'])->name('academic-years.index');
                Route::post('tahun-ajaran', [AcademicYearController::class, 'store'])->name('academic-years.store');
                Route::put('tahun-ajaran/{academicYear}', [AcademicYearController::class, 'update'])->name('academic-years.update');
                Route::delete('tahun-ajaran/{academicYear}', [AcademicYearController::class, 'destroy'])->name('academic-years.destroy');
                Route::post('tahun-ajaran/{academicYear}/aktifkan', [AcademicYearController::class, 'activate'])->name('academic-years.activate');

                Route::put('semester/{semester}', [SemesterController::class, 'update'])->name('semesters.update');
                Route::post('semester/{semester}/aktifkan', [SemesterController::class, 'activate'])->name('semesters.activate');
            });
    });
