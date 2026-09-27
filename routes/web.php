<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes lintas modul
|--------------------------------------------------------------------------
|
| Route fitur berada di app/Modules/<Module>/routes/web.php.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
