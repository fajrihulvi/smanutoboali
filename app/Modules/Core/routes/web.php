<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Core\Http\Controllers\AuditLogController;
use App\Modules\Core\Http\Controllers\FileController;
use App\Modules\Core\Http\Controllers\HealthController;
use App\Modules\Core\Http\Controllers\NotificationController;
use App\Modules\Core\Http\Controllers\StyleGuideController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| Core routes
|--------------------------------------------------------------------------
|
| Dimuat oleh CoreServiceProvider di dalam middleware group 'web'.
|
*/

// Beranda `/` adalah website publik (modul Cms, S29).

// Status operasional berbasis token — tanpa sesi agar ping monitor tidak membuat baris sessions.
Route::get('health', HealthController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
        HandleInertiaRequests::class,
    ])
    ->middleware('throttle:30,1')
    ->name('health');

// File: privat lewat otorisasi, publik lewat fallback bila storage:link tidak ada.
Route::get('files/{file}', [FileController::class, 'show'])
    ->middleware(['auth', 'active.role'])
    ->name('files.show');
Route::get('media/{file}', [FileController::class, 'media'])->name('media.show');

// Notifikasi milik user yang login (semua role).
Route::middleware(['auth', 'active.role'])->prefix('notifikasi')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
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

// Review visual design system — hanya di lingkungan lokal.
if (app()->environment('local')) {
    Route::prefix('style-guide')->name('style-guide.')->group(function () {
        Route::get('/', StyleGuideController::class)->name('index');
        Route::get('/orang-tua', [StyleGuideController::class, 'parent'])->name('parent');
        Route::get('/login', [StyleGuideController::class, 'auth'])->name('auth');
    });
}
