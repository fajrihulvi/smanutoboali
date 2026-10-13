<?php

use App\Models\User;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Core\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('menampilkan halaman lupa password', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth::ForgotPassword'));
});

it('mengirim email reset berbahasa Indonesia', function () {
    Notification::fake();
    $user = User::factory()->withRole(Role::Teacher)->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Atur Ulang Password Akun '.config('app.name')
            && str_contains((string) $mail->actionUrl, '/reset-password/');
    });
});

it('memberi respons sama untuk email yang tidak terdaftar', function () {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'tidak-ada@skoolia.test'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    Notification::assertNothingSent();
});

it('mengatur ulang password dengan token yang valid', function () {
    Notification::fake();
    $user = User::factory()->withRole(Role::Teacher)->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $this->get("/reset-password/{$notification->token}?email={$user->email}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth::ResetPassword')
                ->where('email', $user->email));

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasNoErrors()->assertRedirect('/login');

        return true;
    });

    expect(Hash::check('password-baru-123', $user->fresh()->password))->toBeTrue();
});

it('menolak token reset yang salah', function () {
    $user = User::factory()->create();

    $this->post('/reset-password', [
        'token' => 'token-palsu',
        'email' => $user->email,
        'password' => 'password-baru-123',
        'password_confirmation' => 'password-baru-123',
    ])->assertSessionHasErrors('email');
});
