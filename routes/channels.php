<?php

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels (Pusher)
|--------------------------------------------------------------------------
| Pola: nama channel memakai public_id; callback mengembalikan true hanya untuk
| pemilik/anggota. Semua channel didefinisikan di sini agar terdaftar ulang
| bersama bila broadcaster diganti.
*/

Broadcast::channel('App.Models.User.{publicId}', fn (User $user, string $publicId) => $user->public_id === $publicId);

// Ruang chat: hanya peserta percakapan (S20).
Broadcast::channel('conversation.{publicId}', function (User $user, string $publicId) {
    $conversation = Conversation::query()->where('public_id', $publicId)->first();

    return $conversation !== null && $user->can('view', $conversation);
});

// Status online chat (presence) — hanya bila diaktifkan di pengaturan.
Broadcast::channel('chat.online', function (User $user) {
    if (! filter_var(app(SettingsService::class)->get(SettingKey::ChatPresenceEnabled), FILTER_VALIDATE_BOOL) || ! $user->can('chat.use')) {
        return false;
    }

    return ['id' => $user->public_id, 'name' => $user->name];
});
