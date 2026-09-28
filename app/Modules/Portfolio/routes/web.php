<?php

use App\Modules\Portfolio\Http\Controllers\GalleryController;
use App\Modules\Portfolio\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portfolio routes — portofolio siswa (S17)
|--------------------------------------------------------------------------
*/

foreach ([['teacher', 'guru'], ['operator', 'operator']] as [$role, $prefix]) {
    $base = "/{$prefix}/portofolio";

    Route::middleware(['auth', "active.role:{$role}", 'permission:portfolio.manage'])
        ->prefix("{$prefix}/portofolio")->name("{$role}.portfolios.")
        ->group(function () use ($base) {
            Route::get('/', [PortfolioController::class, 'index'])->defaults('base', $base)->name('index');
            Route::get('tambah', [PortfolioController::class, 'create'])->defaults('base', $base)->name('create');
            Route::post('/', [PortfolioController::class, 'store'])->defaults('base', $base)->name('store');
            Route::get('siswa', [PortfolioController::class, 'students'])->name('students');
            Route::get('{portfolio}/ubah', [PortfolioController::class, 'edit'])->defaults('base', $base)->name('edit');
            Route::put('{portfolio}', [PortfolioController::class, 'update'])->name('update');
            Route::delete('{portfolio}', [PortfolioController::class, 'destroy'])->defaults('base', $base)->name('destroy');
        });
}

Route::middleware(['auth', 'active.role:guardian'])
    ->get('orang-tua/portofolio', [GalleryController::class, 'guardian'])
    ->name('guardian.portfolios.index');

Route::middleware(['auth', 'active.role:principal'])
    ->get('kepala-sekolah/portofolio', [GalleryController::class, 'principal'])
    ->name('principal.portfolios.index');
