<?php

use App\Models\User;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Notifications\StudentAbsenceNotification;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Notifications\WelcomeNotification;
use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Aktifkan Pusher dengan kunci palsu (tanda tangan auth dihitung lokal, tanpa jaringan)
 * lalu daftarkan ulang channel ke broadcaster tersebut.
 */
function usePusher(): void
{
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => '1',
        'broadcasting.connections.pusher.options.cluster' => 'ap1',
    ]);

    require base_path('routes/channels.php');
}

describe('channel privat user', function () {
    it('hanya pemilik yang boleh subscribe channel notifikasinya (public_id, bukan id)', function () {
        usePusher();
        $user = $this->actingAsRole(Role::Teacher);
        $other = User::factory()->create();

        expect($user->receivesBroadcastNotificationsOn())->toBe("App.Models.User.{$user->public_id}");

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$user->public_id}"])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$other->public_id}"])
            ->assertForbidden();
    });

    it('tamu tidak bisa subscribe', function () {
        usePusher();
        $user = User::factory()->create();

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$user->public_id}"])
            ->assertForbidden();
    });
});

describe('shared prop realtime', function () {
    it('null bila tanpa kunci Pusher (fallback)', function () {
        $this->actingAsRole(Role::Teacher);

        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page->where('realtime', null));
    });

    it('berisi kunci publik & channel user bila Pusher aktif, tanpa secret', function () {
        usePusher();
        $user = $this->actingAsRole(Role::Teacher);

        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('realtime.key', 'test-key')
            ->where('realtime.cluster', 'ap1')
            ->where('realtime.userChannel', "App.Models.User.{$user->public_id}")
            ->missing('realtime.secret'));
    });
});

describe('notifikasi realtime', function () {
    it('notifikasi memakai kanal database + broadcast (segera) dan email lewat antrean', function () {
        $notification = new StudentAbsenceNotification('Ani', '01ABC', AttendanceStatus::Absent, CarbonImmutable::today(), 'Rombel VII-A', null);
        $user = User::factory()->create();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'mail'])
            ->and($notification->viaConnections())->toBe(['database' => 'sync', 'broadcast' => 'sync'])
            ->and((new WelcomeNotification)->via($user))->toBe(['database', 'broadcast']);
    });

    it('payload broadcast berisi judul, isi, dan url', function () {
        Notification::fake();
        $user = User::factory()->create();
        $user->notify(new WelcomeNotification);

        Notification::assertSentTo($user, WelcomeNotification::class, function (WelcomeNotification $n, array $channels) use ($user) {
            return in_array('broadcast', $channels, true)
                && array_keys($n->toArray($user)) === ['title', 'body', 'url'];
        });
    });
});

describe('notification center', function () {
    it('menampilkan notifikasi milik sendiri dengan filter belum dibaca', function () {
        $user = $this->actingAsRole(Role::Teacher);
        $user->notify(new WelcomeNotification);
        $user->notify(new WelcomeNotification);
        $user->notifications()->first()->markAsRead();
        User::factory()->create()->notify(new WelcomeNotification);

        $this->get('/notifikasi')->assertInertia(fn (Assert $page) => $page
            ->component('Core::Notifications/Index')
            ->has('notifications.data', 2)
            ->where('unread', 1));

        $this->get('/notifikasi?status=belum')->assertInertia(fn (Assert $page) => $page->has('notifications.data', 1));

        $this->post('/notifikasi/dibaca-semua')->assertRedirect();
        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('tidak bisa menandai notifikasi orang lain', function () {
        $other = User::factory()->create();
        $other->notify(new WelcomeNotification);
        $this->actingAsRole(Role::Guardian);

        $this->postJson('/notifikasi/'.$other->notifications()->first()->id.'/dibaca')->assertNotFound();
    });

    it('tamu diarahkan ke login', function () {
        $this->get('/notifikasi')->assertRedirect('/login');
    });
});

it('Pusher tanpa kunci otomatis turun ke broadcaster log', function () {
    config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.key' => null]);

    (new AppServiceProvider(app()))->boot();

    expect(config('broadcasting.default'))->toBe('log');
});

it('broadcast notifikasi yang gagal tidak membatalkan notifikasi database', function () {
    Broadcast::extend('rusak', fn () => new class extends Broadcaster
    {
        public function auth($request): mixed
        {
            return null;
        }

        public function validAuthenticationResponse($request, $result): mixed
        {
            return null;
        }

        public function broadcast(array $channels, $event, array $payload = []): void
        {
            throw new RuntimeException('Pusher tidak terjangkau');
        }
    });
    config(['broadcasting.connections.rusak' => ['driver' => 'rusak'], 'broadcasting.default' => 'rusak']);
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification);

    expect($user->notifications()->count())->toBe(1);
});

it('broadcast notifikasi dikirim langsung, tidak menunggu antrean database', function () {
    config(['queue.default' => 'database', 'broadcasting.default' => 'log']);
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification);

    expect(DB::table('jobs')->count())->toBe(0)
        ->and($user->notifications()->count())->toBe(1);
});
