<?php

use App\Modules\Announcement\Http\Controllers\ManageAnnouncementController;
use App\Modules\Announcement\Http\Controllers\ReadAnnouncementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Announcement routes — pengumuman (S23)
|--------------------------------------------------------------------------
*/

foreach ([['operator', 'operator'], ['principal', 'kepala-sekolah']] as [$role, $prefix]) {
    $base = "/{$prefix}/pengumuman";

    Route::middleware(['auth', "active.role:{$role}", 'permission:announcement.manage'])
        ->prefix("{$prefix}/pengumuman")->name("{$role}.announcements.")
        ->group(function () use ($base) {
            Route::get('/', [ManageAnnouncementController::class, 'index'])->defaults('base', $base)->name('index');
            Route::get('tambah', [ManageAnnouncementController::class, 'create'])->defaults('base', $base)->name('create');
            Route::post('/', [ManageAnnouncementController::class, 'store'])->defaults('base', $base)->name('store');
            Route::get('{announcement}', [ReadAnnouncementController::class, 'show'])->defaults('base', $base)->name('show');
            Route::get('{announcement}/ubah', [ManageAnnouncementController::class, 'edit'])->defaults('base', $base)->name('edit');
            Route::put('{announcement}', [ManageAnnouncementController::class, 'update'])->defaults('base', $base)->name('update');
            Route::delete('{announcement}', [ManageAnnouncementController::class, 'destroy'])->defaults('base', $base)->name('destroy');
        });
}

foreach ([['teacher', 'guru'], ['guardian', 'orang-tua']] as [$role, $prefix]) {
    Route::middleware(['auth', "active.role:{$role}"])
        ->prefix("{$prefix}/pengumuman")->name("{$role}.announcements.")
        ->group(function () use ($prefix) {
            Route::get('/', [ReadAnnouncementController::class, 'index'])->name('index');
            Route::get('{announcement}', [ReadAnnouncementController::class, 'show'])->defaults('base', "/{$prefix}/pengumuman")->name('show');
        });
}
