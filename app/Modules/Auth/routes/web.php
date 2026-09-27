<?php

use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\LogoutController;
use App\Modules\Auth\Http\Controllers\PasswordResetController;
use App\Modules\Auth\Http\Controllers\SwitchRoleController;
use App\Modules\Core\Enums\Role;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth routes — login per portal, lupa/reset password, logout, role switcher
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'portals'])->name('login');

    Route::get('login/{portal}', [LoginController::class, 'create'])
        ->whereIn('portal', Role::portalSlugs())
        ->name('login.portal');

    Route::post('login/{portal}', [LoginController::class, 'store'])
        ->whereIn('portal', Role::portalSlugs())
        ->name('login.attempt');

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', LogoutController::class)->name('logout');

    Route::post('switch-role/{role}', SwitchRoleController::class)
        ->middleware('active.role')
        ->name('switch-role');
});
