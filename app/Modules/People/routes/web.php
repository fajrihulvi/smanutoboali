<?php

use App\Modules\People\Http\Controllers\Guardian\ActiveChildController;
use App\Modules\People\Http\Controllers\Guardian\ChildController;
use App\Modules\People\Http\Controllers\Guardian\ProfileController as GuardianProfileController;
use App\Modules\People\Http\Controllers\Operator\GuardianController;
use App\Modules\People\Http\Controllers\Operator\StudentController;
use App\Modules\People\Http\Controllers\Operator\StudentGuardianController;
use App\Modules\People\Http\Controllers\Operator\StudentImportController;
use App\Modules\People\Http\Controllers\Operator\TeacherController;
use App\Modules\People\Http\Controllers\Operator\TeacherImportController;
use App\Modules\People\Http\Controllers\Principal\StudentController as PrincipalStudentController;
use App\Modules\People\Http\Controllers\Principal\TeacherController as PrincipalTeacherController;
use App\Modules\People\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| People routes — guru, orang tua (dan siswa di S10)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:operator', 'permission:master_data.manage'])
    ->prefix('operator/master-data')->name('operator.')
    ->group(function () {
        Route::get('guru', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('guru/tambah', [TeacherController::class, 'create'])->name('teachers.create');
        Route::post('guru', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('guru/impor', [TeacherImportController::class, 'index'])->name('teachers.import');
        Route::post('guru/impor', [TeacherImportController::class, 'store'])->name('teachers.import.store');
        Route::get('guru/impor/template', [TeacherImportController::class, 'template'])->name('teachers.import.template');
        Route::get('guru/{teacher}/ubah', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::post('guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('guru/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
        Route::post('guru/{teacher}/akun', [TeacherController::class, 'linkAccount'])->name('teachers.account');
        Route::delete('guru/{teacher}/akun', [TeacherController::class, 'unlinkAccount'])->name('teachers.account.unlink');

        Route::get('orang-tua', [GuardianController::class, 'index'])->name('guardians.index');
        Route::get('orang-tua/tambah', [GuardianController::class, 'create'])->name('guardians.create');
        Route::post('orang-tua', [GuardianController::class, 'store'])->name('guardians.store');
        Route::get('orang-tua/{guardian}/ubah', [GuardianController::class, 'edit'])->name('guardians.edit');
        Route::put('orang-tua/{guardian}', [GuardianController::class, 'update'])->name('guardians.update');
        Route::delete('orang-tua/{guardian}', [GuardianController::class, 'destroy'])->name('guardians.destroy');
        Route::post('orang-tua/{guardian}/akun', [GuardianController::class, 'linkAccount'])->name('guardians.account');
        Route::delete('orang-tua/{guardian}/akun', [GuardianController::class, 'unlinkAccount'])->name('guardians.account.unlink');
        Route::get('orang-tua-cari', [StudentGuardianController::class, 'search'])->name('guardians.search');

        Route::get('siswa', [StudentController::class, 'index'])->name('students.index');
        Route::get('siswa/tambah', [StudentController::class, 'create'])->name('students.create');
        Route::post('siswa', [StudentController::class, 'store'])->name('students.store');
        Route::get('siswa/impor', [StudentImportController::class, 'index'])->name('students.import');
        Route::post('siswa/impor', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::get('siswa/impor/template', [StudentImportController::class, 'template'])->name('students.import.template');
        Route::get('siswa/{student}/ubah', [StudentController::class, 'edit'])->name('students.edit');
        Route::post('siswa/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('siswa/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('siswa/{student}/orang-tua', [StudentGuardianController::class, 'store'])->name('students.guardians.store');
        Route::post('siswa/{student}/orang-tua/{guardian}/utama', [StudentGuardianController::class, 'makePrimary'])->name('students.guardians.primary');
        Route::delete('siswa/{student}/orang-tua/{guardian}', [StudentGuardianController::class, 'destroy'])->name('students.guardians.destroy');
    });

Route::middleware(['auth', 'active.role:principal'])
    ->prefix('kepala-sekolah')->name('principal.')
    ->group(function () {
        Route::get('guru', [PrincipalTeacherController::class, 'index'])->name('teachers.index');
        Route::get('siswa', [PrincipalStudentController::class, 'index'])
            ->middleware('permission:student.view')
            ->name('students.index');
    });

Route::middleware(['auth', 'active.role:teacher'])
    ->prefix('guru')->name('teacher.')
    ->group(function () {
        Route::get('profil', [TeacherProfileController::class, 'edit'])->name('profile.edit');
        Route::post('profil', [TeacherProfileController::class, 'update'])->name('profile.update');
        Route::post('profil/tanda-tangan', [TeacherProfileController::class, 'uploadSignature'])->name('profile.signature.store');
        Route::delete('profil/tanda-tangan', [TeacherProfileController::class, 'removeSignature'])->name('profile.signature.destroy');
    });

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua')->name('guardian.')
    ->group(function () {
        Route::get('profil', [GuardianProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [GuardianProfileController::class, 'update'])->name('profile.update');
        Route::post('anak-aktif', ActiveChildController::class)->name('active_child');

        Route::middleware('permission:student.view')->group(function () {
            Route::get('anak', [ChildController::class, 'index'])->name('children.index');
            Route::get('anak/{student}', [ChildController::class, 'show'])->name('children.show');
        });
    });
