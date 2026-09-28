<?php

use App\Modules\Chat\Http\Controllers\ContactController;
use App\Modules\Chat\Http\Controllers\ConversationController;
use App\Modules\Chat\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat routes — API JSON bersama untuk semua role (S20)
|--------------------------------------------------------------------------
| Halaman chat per portal (/guru/chat, /orang-tua/chat, ...) dibuat di S21.
*/

Route::middleware(['auth', 'permission:chat.use'])->prefix('chat')->name('chat.')->group(function () {
    Route::get('kontak', ContactController::class)->name('contacts');
    Route::get('belum-dibaca', [ConversationController::class, 'unread'])->name('unread');

    Route::get('percakapan', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('percakapan', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('percakapan/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('percakapan/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::post('percakapan/{conversation}/baca', [ConversationController::class, 'read'])->name('conversations.read');

    Route::get('percakapan/{conversation}/pesan', [MessageController::class, 'index'])->name('messages.index');
    Route::post('percakapan/{conversation}/pesan', [MessageController::class, 'store'])->middleware('throttle:chat-send')->name('messages.store');
    Route::patch('pesan/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('pesan/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
});
