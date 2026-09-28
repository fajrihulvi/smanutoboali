<?php

use App\Modules\Ppdb\Http\Controllers\AccountController;
use App\Modules\Ppdb\Http\Controllers\Applicant\ApplicationController;
use App\Modules\Ppdb\Http\Controllers\Applicant\DashboardController;
use App\Modules\Ppdb\Http\Controllers\Operator\PeriodController;
use App\Modules\Ppdb\Http\Middleware\EnsureApplicantEmailVerified;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ppdb routes — pendaftaran (S26)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->prefix('ppdb')->group(function () {
    Route::get('daftar', [AccountController::class, 'create'])->name('ppdb.register');
    Route::post('daftar', [AccountController::class, 'store'])->middleware('throttle:5,1')->name('ppdb.register.store');
    Route::get('masuk', [AccountController::class, 'login'])->name('ppdb.login');
    Route::post('masuk', [AccountController::class, 'attempt'])->name('ppdb.login.attempt');
});

// Tautan dari email: bertanda tangan, tidak wajib login.
Route::get('ppdb/verifikasi/{user}/{hash}', [AccountController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('ppdb.verify');

Route::middleware(['auth', 'active.role:applicant'])->prefix('ppdb/akun')->name('applicant.')->group(function () {
    Route::get('verifikasi-email', [AccountController::class, 'notice'])->name('verification.notice');
    Route::post('verifikasi-email', [AccountController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');

    Route::middleware(EnsureApplicantEmailVerified::class)->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::post('pendaftaran', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('pendaftaran/{applicant}', [ApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('pendaftaran/{applicant}', [ApplicationController::class, 'update'])->middleware('throttle:60,1')->name('applications.update');
        Route::delete('pendaftaran/{applicant}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::post('pendaftaran/{applicant}/dokumen/{requirement}', [ApplicationController::class, 'upload'])->name('applications.documents.store');
        Route::delete('pendaftaran/{applicant}/dokumen/{requirement}', [ApplicationController::class, 'removeDocument'])->name('applications.documents.destroy');
        Route::post('pendaftaran/{applicant}/kirim', [ApplicationController::class, 'submit'])->name('applications.submit');
        Route::get('pendaftaran/{applicant}/bukti.pdf', [ApplicationController::class, 'proof'])->name('applications.proof');
    });
});

Route::middleware(['auth', 'active.role:operator', 'permission:ppdb.manage'])->prefix('operator/ppdb')->name('operator.ppdb.')->group(function () {
    Route::redirect('/', '/operator/ppdb/periode')->name('home');
    Route::get('periode', [PeriodController::class, 'index'])->name('periods.index');
    Route::get('periode/tambah', [PeriodController::class, 'create'])->name('periods.create');
    Route::post('periode', [PeriodController::class, 'store'])->name('periods.store');
    Route::get('periode/{period}/ubah', [PeriodController::class, 'edit'])->name('periods.edit');
    Route::put('periode/{period}', [PeriodController::class, 'update'])->name('periods.update');
    Route::delete('periode/{period}', [PeriodController::class, 'destroy'])->name('periods.destroy');
});
