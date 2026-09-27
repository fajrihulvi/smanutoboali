<?php

use App\Modules\Core\Http\Controllers\AuditLogController;
use App\Modules\Core\Http\Controllers\FileController;
use App\Modules\Core\Http\Controllers\NotificationController;
use App\Modules\Core\Http\Controllers\Operator\SettingsController;
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

// File: privat lewat otorisasi, publik lewat fallback bila storage:link tidak ada.
Route::get('files/{file}', [FileController::class, 'show'])
    ->middleware(['auth', 'active.role'])
    ->name('files.show');
Route::get('media/{file}', [FileController::class, 'media'])->name('media.show');

// Notifikasi milik user yang login (semua role).
Route::middleware(['auth', 'active.role'])->prefix('notifikasi')->name('notifications.')->group(function () {
    Route::get('terbaru', [NotificationController::class, 'recent'])->name('recent');
    Route::post('dibaca-semua', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::post('{id}/dibaca', [NotificationController::class, 'markAsRead'])->whereUuid('id')->name('read');
});

// Audit log: operator & kepala sekolah (read-only).
Route::middleware(['auth', 'active.role:operator', 'permission:audit_log.view'])
    ->get('operator/audit-log', AuditLogController::class)
    ->name('operator.audit-log.index');
Route::middleware(['auth', 'active.role:principal', 'permission:audit_log.view'])
    ->get('kepala-sekolah/audit-log', AuditLogController::class)
    ->name('principal.audit-log.index');

// Pengaturan sekolah.
Route::middleware(['auth', 'active.role:operator', 'permission:setting.manage'])
    ->prefix('operator')->name('operator.')
    ->group(function () {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

// Review visual design system — hanya di lingkungan lokal.
if (app()->environment('local')) {
    Route::prefix('style-guide')->name('style-guide.')->group(function () {
        Route::get('/', StyleGuideController::class)->name('index');
        Route::get('/orang-tua', [StyleGuideController::class, 'parent'])->name('parent');
        Route::get('/login', [StyleGuideController::class, 'auth'])->name('auth');
    });
}
