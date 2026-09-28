<?php

use App\Modules\Attendance\Http\Controllers\Guardian\AttendanceController as GuardianAttendanceController;
use App\Modules\Attendance\Http\Controllers\Operator\AttendanceController as OperatorAttendanceController;
use App\Modules\Attendance\Http\Controllers\Principal\AttendanceController as PrincipalAttendanceController;
use App\Modules\Attendance\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance routes — presensi siswa (S13)
|--------------------------------------------------------------------------
*/

foreach ([
    ['teacher', 'guru', TeacherAttendanceController::class],
    ['operator', 'operator', OperatorAttendanceController::class],
] as [$role, $prefix, $controller]) {
    Route::middleware(['auth', "active.role:{$role}", 'permission:attendance.manage'])
        ->prefix("{$prefix}/presensi")->name("{$role}.attendance.")
        ->group(function () use ($controller) {
            Route::get('/', [$controller, 'index'])->name('index');
            Route::get('rombel/{classroom}', [$controller, 'show'])->name('show');
            Route::put('rombel/{classroom}', [$controller, 'update'])->name('update');
            Route::get('rombel/{classroom}/rekap', [$controller, 'recap'])->name('recap');
            Route::get('siswa/{student}', [$controller, 'student'])->name('student');
        });
}

Route::middleware(['auth', 'active.role:principal'])
    ->prefix('kepala-sekolah/presensi')->name('principal.attendance.')
    ->group(function () {
        Route::get('/', [PrincipalAttendanceController::class, 'index'])->name('index');
        Route::get('rombel/{classroom}', [PrincipalAttendanceController::class, 'classroom'])->name('classroom');
        Route::get('rombel/{classroom}/rekap', [PrincipalAttendanceController::class, 'recap'])->name('recap');
        Route::get('siswa/{student}', [PrincipalAttendanceController::class, 'student'])->name('student');
    });

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua')->name('guardian.')
    ->group(function () {
        Route::get('presensi', [GuardianAttendanceController::class, 'index'])->name('attendance.index');
    });
