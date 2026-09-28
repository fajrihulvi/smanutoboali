<?php

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\SettingsService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
});

it('halaman chat tersedia untuk semua portal', function (Role $role, string $url) {
    $this->actingAsRole($role);

    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Chat::Index')
        ->where('base', $url)
        ->where('active', null)
        ->where('presenceEnabled', false)
        ->has('conversations', 0));
})->with([
    [Role::Principal, '/kepala-sekolah/chat'],
    [Role::Teacher, '/guru/chat'],
    [Role::Operator, '/operator/chat'],
    [Role::Guardian, '/orang-tua/chat'],
]);

it('role lain tidak bisa membuka portal chat milik role lain', function () {
    $this->actingAsRole(Role::Guardian);

    $this->get('/guru/chat')->assertForbidden();
});

it('membuka percakapan lewat URL: detail aktif + daftar dengan unread', function () {
    ['teacher' => $teacher, 'parent' => $parent, 'child' => $child] = chatScenario();

    $this->actingAsRole(Role::Guardian, $parent);
    $id = startChat($teacher, $child);
    $this->postJson("/chat/percakapan/{$id}/pesan", ['body' => 'Halo Bu'])->assertCreated();

    $this->actingAsRole(Role::Teacher, $teacher);
    $this->get("/guru/chat/{$id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Chat::Index')
        ->where('active.public_id', $id)
        ->where('active.other.name', 'Pak Budi')
        ->where('active.other.role', 'Orang tua Ani')
        ->where('active.can_send', true)
        ->where('conversations.0.unread', 1)
        ->where('conversations.0.last_message.preview', 'Halo Bu')
        ->where('chat.unread', 1)
        ->missing('active.id'));
});

it('non-peserta tidak bisa membuka URL percakapan', function () {
    ['teacher' => $teacher, 'parent' => $parent, 'child' => $child, 'otherTeacher' => $otherTeacher] = chatScenario();
    $this->actingAsRole(Role::Guardian, $parent);
    $id = startChat($teacher, $child);

    $this->actingAsRole(Role::Teacher, $otherTeacher);
    $this->get("/guru/chat/{$id}")->assertForbidden();
    expect(Conversation::query()->count())->toBe(1);
});

it('operator bisa menyalakan status online chat', function () {
    $operator = $this->actingAsRole(Role::Operator);
    app(SettingsService::class)->set(['chat_presence_enabled' => true]);

    $this->get('/operator/chat')->assertInertia(fn (Assert $page) => $page->where('presenceEnabled', true));
    expect($operator)->toBeInstanceOf(User::class);
});
