<?php

use App\Modules\GuestBook\Http\Controllers\GuestVisitController;
use App\Modules\GuestBook\Http\Controllers\Public\GuestBookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GuestBook routes — buku tamu
|--------------------------------------------------------------------------
| Form publik /buku-tamu (tanpa cache halaman: berisi token CSRF), kelola oleh
| operator, dan daftar baca-saja untuk kepala sekolah. Rekap PDF lewat pusat laporan.
*/

Route::get('buku-tamu', [GuestBookController::class, 'create'])->name('public.guest-book');
Route::post('buku-tamu', [GuestBookController::class, 'store'])->middleware('throttle:5,1')->name('public.guest-book.store');

Route::middleware(['auth', 'active.role:operator', 'permission:guest_book.manage'])
    ->prefix('operator/buku-tamu')->name('operator.guest-book.')
    ->group(function () {
        Route::get('/', [GuestVisitController::class, 'index'])->defaults('base', '/operator/buku-tamu')->name('index');
        Route::get('tambah', [GuestVisitController::class, 'create'])->name('create');
        Route::post('/', [GuestVisitController::class, 'store'])->name('store');
        Route::get('{visit}/ubah', [GuestVisitController::class, 'edit'])->name('edit');
        // POST (bukan PUT) agar unggahan foto terkirim sebagai multipart.
        Route::post('{visit}', [GuestVisitController::class, 'update'])->name('update');
        Route::delete('{visit}', [GuestVisitController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'active.role:principal', 'permission:guest_book.view'])
    ->prefix('kepala-sekolah/buku-tamu')->name('principal.guest-book.')
    ->group(function () {
        Route::get('/', [GuestVisitController::class, 'index'])->defaults('base', '/kepala-sekolah/buku-tamu')->name('index');
    });
