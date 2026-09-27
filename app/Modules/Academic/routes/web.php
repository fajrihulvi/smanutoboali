<?php

use App\Modules\Academic\Http\Controllers\Operator\ClassroomController;
use App\Modules\Academic\Http\Controllers\Operator\ClassroomEnrollmentController;
use App\Modules\Academic\Http\Controllers\Operator\PromotionController;
use App\Modules\Academic\Http\Controllers\Operator\SubjectController;
use App\Modules\Academic\Http\Controllers\Operator\TeachingAssignmentController;
use App\Modules\Academic\Http\Controllers\Teacher\ClassController;
use App\Modules\Academic\Http\Controllers\Teacher\StudentController as TeacherStudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academic routes — rombel, mapel, penugasan, naik kelas (S11)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:operator', 'permission:master_data.manage'])
    ->prefix('operator')->name('operator.')
    ->group(function () {
        Route::prefix('master-data')->group(function () {
            Route::get('kelas', [ClassroomController::class, 'index'])->name('classrooms.index');
            Route::post('kelas', [ClassroomController::class, 'store'])->name('classrooms.store');
            Route::get('kelas/naik-kelas', [PromotionController::class, 'create'])->name('promotion.create');
            Route::post('kelas/naik-kelas', [PromotionController::class, 'store'])->name('promotion.store');
            Route::get('kelas/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');
            Route::put('kelas/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
            Route::delete('kelas/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
            Route::get('kelas/{classroom}/calon-siswa', [ClassroomEnrollmentController::class, 'candidates'])->name('classrooms.candidates');
            Route::post('kelas/{classroom}/siswa', [ClassroomEnrollmentController::class, 'store'])->name('classrooms.enroll');
            Route::delete('kelas/{classroom}/siswa/{student}', [ClassroomEnrollmentController::class, 'destroy'])->name('classrooms.unenroll');

            Route::get('mapel', [SubjectController::class, 'index'])->name('subjects.index');
            Route::post('mapel', [SubjectController::class, 'store'])->name('subjects.store');
            Route::put('mapel/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
            Route::delete('mapel/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
        });

        Route::get('akademik/penugasan', [TeachingAssignmentController::class, 'index'])->name('assignments.index');
        Route::put('akademik/penugasan', [TeachingAssignmentController::class, 'update'])->name('assignments.update');
    });

Route::middleware(['auth', 'active.role:teacher'])
    ->prefix('guru')->name('teacher.')
    ->group(function () {
        Route::get('kelas', [ClassController::class, 'index'])->name('classes.index');
        Route::get('kelas/{classroom}', [ClassController::class, 'show'])->name('classes.show');
        Route::get('siswa', [TeacherStudentController::class, 'index'])
            ->middleware('permission:student.view')
            ->name('students.index');
    });
