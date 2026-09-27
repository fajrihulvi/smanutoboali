<?php

use App\Modules\Core\Http\Controllers\StyleGuideController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core routes
|--------------------------------------------------------------------------
|
| Dimuat oleh CoreServiceProvider di dalam middleware group 'web'.
|
*/

// Sementara: halaman sambutan. Diganti website publik Blade di S29.
Route::inertia('/', 'Core::Welcome')->name('home');

// Review visual design system — hanya di lingkungan lokal.
if (app()->environment('local')) {
    Route::prefix('style-guide')->name('style-guide.')->group(function () {
        Route::get('/', StyleGuideController::class)->name('index');
        Route::get('/orang-tua', [StyleGuideController::class, 'parent'])->name('parent');
        Route::get('/login', [StyleGuideController::class, 'auth'])->name('auth');
    });
}
