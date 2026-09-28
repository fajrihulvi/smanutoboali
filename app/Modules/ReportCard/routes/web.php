<?php

use App\Modules\ReportCard\Http\Controllers\Guardian\ReportCardController as GuardianReportCardController;
use App\Modules\ReportCard\Http\Controllers\OverviewController;
use App\Modules\ReportCard\Http\Controllers\ReportCardController;
use App\Modules\ReportCard\Http\Controllers\Teacher\ReportCardController as TeacherReportCardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ReportCard routes — raport + workflow + PDF (S16)
|--------------------------------------------------------------------------
| Halaman bersama (rombel, detail, PDF, transisi) memakai ReportCardController dengan
| route default `base` = prefix portal.
*/

Route::middleware(['auth', 'active.role:teacher', 'permission:report_card.review'])
    ->prefix('guru/raport')->name('teacher.report_cards.')
    ->group(function () {
        Route::get('/', [TeacherReportCardController::class, 'index'])->name('index');
        Route::post('konfirmasi/{assignment}', [TeacherReportCardController::class, 'confirm'])->middleware('permission:grade.manage')->name('confirm');
        Route::delete('konfirmasi/{assignment}', [TeacherReportCardController::class, 'revoke'])->middleware('permission:grade.manage')->name('revoke');
        Route::get('rombel/{classroom}', [ReportCardController::class, 'classroom'])->defaults('base', '/guru/raport')->name('classroom');
        Route::post('rombel/{classroom}/generate', [TeacherReportCardController::class, 'generate'])->name('generate');
        Route::post('rombel/{classroom}/status', [ReportCardController::class, 'bulkTransition'])->name('bulk');
        Route::get('{card}', [ReportCardController::class, 'show'])->defaults('base', '/guru/raport')->name('show');
        Route::get('{card}/pdf', [ReportCardController::class, 'pdf'])->name('pdf');
        Route::put('{card}/isi', [TeacherReportCardController::class, 'updateContent'])->name('content');
        Route::post('{card}/status', [ReportCardController::class, 'transition'])->name('transition');
    });

Route::middleware(['auth', 'active.role:operator', 'permission:report_card.validate'])
    ->prefix('operator/raport')->name('operator.report_cards.')
    ->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->defaults('base', '/operator/raport')->name('index');
        Route::get('rombel/{classroom}', [ReportCardController::class, 'classroom'])->defaults('base', '/operator/raport')->name('classroom');
        Route::post('rombel/{classroom}/status', [ReportCardController::class, 'bulkTransition'])->name('bulk');
        Route::post('rombel/{classroom}/cetak', [OverviewController::class, 'export'])->name('export');
        Route::get('{card}', [ReportCardController::class, 'show'])->defaults('base', '/operator/raport')->name('show');
        Route::get('{card}/pdf', [ReportCardController::class, 'pdf'])->name('pdf');
        Route::post('{card}/status', [ReportCardController::class, 'transition'])->name('transition');
    });

Route::middleware(['auth', 'active.role:principal', 'permission:report_card.approve'])
    ->prefix('kepala-sekolah/raport')->name('principal.report_cards.')
    ->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->defaults('base', '/kepala-sekolah/raport')->name('index');
        Route::get('rombel/{classroom}', [ReportCardController::class, 'classroom'])->defaults('base', '/kepala-sekolah/raport')->name('classroom');
        Route::post('rombel/{classroom}/status', [ReportCardController::class, 'bulkTransition'])->name('bulk');
        Route::get('{card}', [ReportCardController::class, 'show'])->defaults('base', '/kepala-sekolah/raport')->name('show');
        Route::get('{card}/pdf', [ReportCardController::class, 'pdf'])->name('pdf');
        Route::post('{card}/status', [ReportCardController::class, 'transition'])->name('transition');
    });

Route::middleware(['auth', 'active.role:guardian'])
    ->prefix('orang-tua/raport')->name('guardian.report_cards.')
    ->group(function () {
        Route::get('/', [GuardianReportCardController::class, 'index'])->name('index');
        Route::get('{card}', [ReportCardController::class, 'show'])->defaults('base', '/orang-tua/raport')->name('show');
        Route::get('{card}/pdf', [ReportCardController::class, 'pdf'])->name('pdf');
    });
