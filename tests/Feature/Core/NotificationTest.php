<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Notifications\WelcomeNotification;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Inertia\Testing\AssertableInertia as Assert;

it('membagikan jumlah notifikasi belum dibaca ke Inertia', function () {
    $user = $this->actingAsRole(Role::Teacher);
    $user->notify(new WelcomeNotification);
    $user->notify(new WelcomeNotification);

    $this->get('/guru/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('notifications.unread', 2));
});

it('mengembalikan notifikasi terbaru milik sendiri', function () {
    $user = $this->actingAsRole(Role::Guardian);
    $user->notify(new WelcomeNotification);
    User::factory()->create()->notify(new WelcomeNotification);

    $this->getJson('/notifikasi/terbaru')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Selamat datang di '.config('app.name'))
        ->assertJsonPath('data.0.url', '/settings/profile')
        ->assertJsonPath('unread', 1);
});

it('menandai satu notifikasi dibaca', function () {
    $user = $this->actingAsRole(Role::Teacher);
    $user->notify(new WelcomeNotification);
    $id = $user->notifications()->firstOrFail()->id;

    $this->postJson("/notifikasi/{$id}/dibaca")->assertOk()->assertJsonPath('unread', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('tidak dapat menandai notifikasi milik orang lain (404)', function () {
    $other = User::factory()->create();
    $other->notify(new WelcomeNotification);
    $id = $other->notifications()->firstOrFail()->id;

    $this->actingAsRole(Role::Teacher);

    $this->postJson("/notifikasi/{$id}/dibaca")->assertNotFound();
    expect($other->unreadNotifications()->count())->toBe(1);
});

it('menandai semua notifikasi dibaca', function () {
    $user = $this->actingAsRole(Role::Operator);
    $user->notify(new WelcomeNotification);
    $user->notify(new WelcomeNotification);

    $this->postJson('/notifikasi/dibaca-semua')->assertOk();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('tamu tidak dapat mengakses notifikasi', function () {
    $this->getJson('/notifikasi/terbaru')->assertUnauthorized();
});

it('menjadwalkan queue worker, prune, dan pembersihan audit log', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn (Event $event) => (string) $event->command);

    $queue = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'queue:work'));

    expect($queue)->not->toBeNull()
        ->and($queue->command)->toContain('--stop-when-empty')->toContain('--max-time=50')
        ->and($queue->expression)->toBe('* * * * *')
        ->and($queue->withoutOverlapping)->toBeTrue()
        ->and($commands->contains(fn (string $command) => str_contains($command, 'model:prune')))->toBeTrue()
        ->and($commands->contains(fn (string $command) => str_contains($command, 'activitylog:clean')))->toBeTrue();
});
