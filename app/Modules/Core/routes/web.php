<?php

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
