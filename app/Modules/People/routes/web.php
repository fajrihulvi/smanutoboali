<?php

use App\Modules\People\Http\Controllers\Guardian\ProfileController as GuardianProfileController;
use App\Modules\People\Http\Controllers\Operator\GuardianController;
use App\Modules\People\Http\Controllers\Operator\TeacherController;
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
        Route::get('guru/{teacher}/ubah', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::post('guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('guru/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
        Route::post('guru/{teacher}/akun', [TeacherController::class, 'linkAccount'])->name('teachers.account');

        Route::get('orang-tua', [GuardianController::class, 'index'])->name('guardians.index');
        Route::get('orang-tua/tambah', [GuardianController::class, 'create'])->name('guardians.create');
        Route::post('orang-tua', [GuardianController::class, 'store'])->name('guardians.store');
        Route::get('orang-tua/{guardian}/ubah', [GuardianController::class, 'edit'])->name('guardians.edit');
        Route::put('orang-tua/{guardian}', [GuardianController::class, 'update'])->name('guardians.update');
        Route::delete('orang-tua/{guardian}', [GuardianController::class, 'destroy'])->name('guardians.destroy');
        Route::post('orang-tua/{guardian}/akun', [GuardianController::class, 'linkAccount'])->name('guardians.account');
    });

Route::middleware(['auth', 'active.role:principal'])
    ->prefix('kepala-sekolah')->name('principal.')
    ->group(function () {
        Route::get('guru', [PrincipalTeacherController::class, 'index'])->name('teachers.index');
    });

Route::middleware(['auth', 'active.role:teacher'])
    ->prefix('guru')->name('teacher.')
    ->group(function () {
        Route::get('profil', [TeacherProfileController::class, 'edit'])->name('profile.edit');
        Route::post('profil', [TeacherProfileController::class, 'update'])->name('profile.update');
    });

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua')->name('guardian.')
    ->group(function () {
        Route::get('profil', [GuardianProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [GuardianProfileController::class, 'update'])->name('profile.update');
    });
