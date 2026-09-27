<?php

use App\Modules\Access\Http\Controllers\Operator\RoleController;
use App\Modules\Access\Http\Controllers\Operator\UserController;
use App\Modules\Access\Http\Controllers\Operator\UserPasswordController;
use App\Modules\Access\Http\Controllers\Operator\UserStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Access routes — user management & role/permission (Operator)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:operator'])
    ->prefix('operator')->name('operator.')
    ->group(function () {
        Route::middleware('permission:user.manage')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::put('users/{user}/password', [UserPasswordController::class, 'update'])->name('users.password.update');
            Route::patch('users/{user}/status', [UserStatusController::class, 'update'])->name('users.status.update');
        });

        Route::middleware('permission:role.manage')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('roles/{role:name}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('roles/{role:name}', [RoleController::class, 'update'])->name('roles.update');
        });
    });
