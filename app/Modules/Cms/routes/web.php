<?php

use App\Modules\Cms\Http\Controllers\Operator\BannerController;
use App\Modules\Cms\Http\Controllers\Operator\CategoryController;
use App\Modules\Cms\Http\Controllers\Operator\EditorImageController;
use App\Modules\Cms\Http\Controllers\Operator\GalleryController;
use App\Modules\Cms\Http\Controllers\Operator\PageController;
use App\Modules\Cms\Http\Controllers\Operator\PostController;
use App\Modules\Cms\Http\Controllers\Operator\SettingController;
use App\Modules\Cms\Http\Controllers\Operator\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cms routes — panel pengelolaan website (S28)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:operator', 'permission:cms.manage'])
    ->prefix('operator/cms')->name('operator.cms.')
    ->group(function () {
        Route::prefix('halaman')->name('pages.')->controller(PageController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{page}/ubah', 'edit')->name('edit');
            Route::put('{page}', 'update')->name('update');
            Route::patch('{page}/status', 'status')->name('status');
            Route::get('{page}/pratinjau', 'preview')->name('preview');
            Route::delete('{page}', 'destroy')->name('destroy');
        });

        foreach (['berita', 'agenda'] as $type) {
            Route::prefix($type)->name("{$type}.")->controller(PostController::class)->group(function () use ($type) {
                Route::get('/', 'index')->defaults('type', $type)->name('index');
                Route::get('tambah', 'create')->defaults('type', $type)->name('create');
                Route::post('/', 'store')->defaults('type', $type)->name('store');
                Route::get('{post}/ubah', 'edit')->defaults('type', $type)->name('edit');
                Route::put('{post}', 'update')->defaults('type', $type)->name('update');
                Route::patch('{post}/status', 'status')->defaults('type', $type)->name('status');
                Route::get('{post}/pratinjau', 'preview')->defaults('type', $type)->name('preview');
                Route::delete('{post}', 'destroy')->defaults('type', $type)->name('destroy');
            });
        }

        Route::prefix('kategori')->name('categories.')->controller(CategoryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('{category}', 'update')->name('update');
            Route::delete('{category}', 'destroy')->name('destroy');
        });

        Route::prefix('galeri')->name('galleries.')->controller(GalleryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{gallery}/ubah', 'edit')->name('edit');
            Route::put('{gallery}', 'update')->name('update');
            Route::patch('{gallery}/status', 'status')->name('status');
            Route::get('{gallery}/pratinjau', 'preview')->name('preview');
            Route::delete('{gallery}', 'destroy')->name('destroy');
        });

        Route::prefix('banner')->name('banners.')->controller(BannerController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('{banner}', 'update')->name('update');
            Route::delete('{banner}', 'destroy')->name('destroy');
        });

        Route::get('guru', [TeacherController::class, 'index'])->name('teachers.index');
        Route::put('guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');

        Route::get('pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('pengaturan', [SettingController::class, 'update'])->name('settings.update');

        Route::post('gambar-editor', EditorImageController::class)->middleware('throttle:30,1')->name('editor-image');
    });
