<?php

use App\Modules\Ppdb\Http\Controllers\AccountController;
use App\Modules\Ppdb\Http\Controllers\Applicant\ApplicationController;
use App\Modules\Ppdb\Http\Controllers\Applicant\DashboardController as ApplicantDashboardController;
use App\Modules\Ppdb\Http\Controllers\Applicant\ReregistrationController;
use App\Modules\Ppdb\Http\Controllers\DashboardController;
use App\Modules\Ppdb\Http\Controllers\Operator\ApplicantController;
use App\Modules\Ppdb\Http\Controllers\Operator\PeriodController;
use App\Modules\Ppdb\Http\Controllers\PublicResultController;
use App\Modules\Ppdb\Http\Controllers\SelectionController;
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

// Cek hasil seleksi publik (S27).
Route::get('ppdb/hasil', [PublicResultController::class, 'show'])->name('ppdb.result');
Route::post('ppdb/hasil', [PublicResultController::class, 'check'])->middleware('throttle:10,1')->name('ppdb.result.check');

Route::middleware(['auth', 'active.role:applicant'])->prefix('ppdb/akun')->name('applicant.')->group(function () {
    Route::get('dashboard', ApplicantDashboardController::class)->name('dashboard');
    Route::post('pendaftaran', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('pendaftaran/{applicant}', [ApplicationController::class, 'edit'])->name('applications.edit');
    Route::put('pendaftaran/{applicant}', [ApplicationController::class, 'update'])->middleware('throttle:60,1')->name('applications.update');
    Route::delete('pendaftaran/{applicant}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::post('pendaftaran/{applicant}/dokumen/{requirement}', [ApplicationController::class, 'upload'])->name('applications.documents.store');
    Route::delete('pendaftaran/{applicant}/dokumen/{requirement}', [ApplicationController::class, 'removeDocument'])->name('applications.documents.destroy');
    Route::post('pendaftaran/{applicant}/kirim', [ApplicationController::class, 'submit'])->name('applications.submit');
    Route::get('pendaftaran/{applicant}/bukti.pdf', [ApplicationController::class, 'proof'])->name('applications.proof');
    Route::get('pendaftaran/{applicant}/daftar-ulang', [ReregistrationController::class, 'edit'])->name('reregistration.edit');
    Route::post('pendaftaran/{applicant}/daftar-ulang', [ReregistrationController::class, 'store'])->name('reregistration.store');
});

Route::middleware(['auth', 'active.role:operator', 'permission:ppdb.manage'])->prefix('operator/ppdb')->name('operator.ppdb.')->group(function () {
    Route::redirect('/', '/operator/ppdb/dashboard')->name('home');
    Route::get('dashboard', DashboardController::class)->defaults('base', '/operator/ppdb')->name('dashboard');
    Route::get('periode', [PeriodController::class, 'index'])->name('periods.index');
    Route::get('periode/tambah', [PeriodController::class, 'create'])->name('periods.create');
    Route::post('periode', [PeriodController::class, 'store'])->name('periods.store');
    Route::get('periode/{period}/ubah', [PeriodController::class, 'edit'])->name('periods.edit');
    Route::put('periode/{period}', [PeriodController::class, 'update'])->name('periods.update');
    Route::delete('periode/{period}', [PeriodController::class, 'destroy'])->name('periods.destroy');
    Route::post('periode/{period}/umumkan', [SelectionController::class, 'publish'])->name('periods.publish');

    Route::get('pendaftar', [ApplicantController::class, 'index'])->defaults('base', '/operator/ppdb')->name('applicants.index');
    Route::get('pendaftar/{applicant}', [ApplicantController::class, 'show'])->defaults('base', '/operator/ppdb')->name('applicants.show');
    Route::post('pendaftar/{applicant}/verifikasi', [ApplicantController::class, 'verify'])->name('applicants.verify');
    Route::post('pendaftar/{applicant}/perbaikan', [ApplicantController::class, 'revise'])->name('applicants.revise');
    Route::post('pendaftar/{applicant}/daftar-ulang', [ApplicantController::class, 'confirmReregistration'])->name('applicants.reregister');
    Route::post('dokumen/{document}/periksa', [ApplicantController::class, 'reviewDocument'])->name('documents.review');

    Route::get('seleksi', [SelectionController::class, 'index'])->defaults('base', '/operator/ppdb/seleksi')->name('selection.index');
    Route::put('seleksi/pendaftar/{applicant}', [SelectionController::class, 'score'])->name('selection.score');
    Route::post('seleksi/jalur/{track}/usulan', [SelectionController::class, 'propose'])->name('selection.propose');
});

Route::middleware(['auth', 'active.role:principal', 'permission:ppdb.decide'])->prefix('kepala-sekolah/ppdb')->name('principal.ppdb.')->group(function () {
    Route::redirect('/', '/kepala-sekolah/ppdb/dashboard')->name('home');
    Route::get('dashboard', DashboardController::class)->defaults('base', '/kepala-sekolah/ppdb')->name('dashboard');
    Route::get('seleksi', [SelectionController::class, 'index'])->defaults('base', '/kepala-sekolah/ppdb/seleksi')->name('selection.index');
    Route::post('seleksi/pendaftar/{applicant}/putuskan', [SelectionController::class, 'decide'])->name('selection.decide');
    Route::post('seleksi/jalur/{track}/setujui', [SelectionController::class, 'approve'])->name('selection.approve');
    Route::get('pendaftar', [ApplicantController::class, 'index'])->defaults('base', '/kepala-sekolah/ppdb')->name('applicants.index');
    Route::get('pendaftar/{applicant}', [ApplicantController::class, 'show'])->defaults('base', '/kepala-sekolah/ppdb')->name('applicants.show');
});
