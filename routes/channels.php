<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels (Pusher)
|--------------------------------------------------------------------------
| Pola: nama channel memakai public_id; callback mengembalikan true hanya untuk
| pemilik/anggota. Modul lain (mis. Chat) mendaftarkan channel-nya di provider
| modul dengan pola yang sama.
*/

Broadcast::channel('App.Models.User.{publicId}', fn (User $user, string $publicId) => $user->public_id === $publicId);
